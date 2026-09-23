<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

/*
 * Create a new user dashboard
 *
 */
class Dashboard
{
    private $mysqli;
    private $log;

    public function __construct($mysqli)
    {
        $this->mysqli = $mysqli;
        $this->log = new EmonLogger(__FILE__);
    }

    /**
     * The html for the page div of a dashboard.
     *
     * Content is stored as JSON and rendered from it, see
     * notes/SCHEMA.md. A dashboard still holding html is
     * converted the first time it is loaded, and the html is left in place
     * untouched so the conversion can be looked at again later.
     *
     * @param array $dash a row from get
     * @return string
     */
    public function content_html($dash)
    {
        require_once "Modules/dashboard/dashboard_render.php";

        $json = isset($dash['content_json']) ? (string) $dash['content_json'] : '';

        if (trim($json) === '') {
            $content = isset($dash['content']) ? (string) $dash['content'] : '';
            if (trim($content) === '') {
                return '';
            }
            $missing = $this->missing_extensions();
            if (count($missing)) {
                $this->log->error("dashboard " . (int) $dash['id'] . " not converted, "
                    . "php extension missing: " . implode(", ", $missing));
                return '<div class="alert alert-error">This dashboard cannot be shown '
                    . 'because the PHP extension <b>' . implode('</b>, <b>', $missing)
                    . '</b> is not installed. On Debian and Ubuntu run '
                    . '<code>sudo apt install php' . PHP_MAJOR_VERSION . '.'
                    . PHP_MINOR_VERSION . '-xml php' . PHP_MAJOR_VERSION . '.'
                    . PHP_MINOR_VERSION . '-mbstring</code> '
                    . 'and restart the web server.</div>';
            }
            $json = $this->convert_content((int) $dash['id'], $content);
        }

        if (trim($json) === '') {
            return '';
        }

        // Brought to the current version, with old widgets replaced by the
        // widgets that succeed them, before the page is built. A document
        // that changed is stored again.
        $userid = isset($dash['userid']) ? (int) $dash['userid'] : 0;
        $json = $this->migrate_widgets((int) $dash['id'], $userid, $json);

        $rendered = dashboard_render($json);
        foreach ($rendered['errors'] as $error) {
            $this->log->warn("dashboard " . (int) $dash['id'] . " render "
                . $error['code'] . " " . $error['detail']);
        }
        return $rendered['html'];
    }

    /**
     * Converts a dashboard still holding html and stores the result.
     *
     * The html column is not touched. Until it is dropped in a later release
     * it holds what was there before the conversion, so a dashboard that
     * converted badly can be looked at and converted again.
     *
     * @param int $id
     * @param string $content the html column
     * @return string the document, or an empty string if there was nothing to keep
     */
    public function convert_content($id, $content)
    {
        require_once "Modules/dashboard/dashboard_convert.php";

        $converted = dashboard_convert($content);
        if ($converted['document'] === null || !count($converted['document']['widgets'])) {
            $this->log->warn("dashboard $id holds content that converts to no widgets");
            return '';
        }

        $json = dashboard_convert_encode($converted['document']);
        if ($json === false) {
            $this->log->error("dashboard $id could not be encoded: " . json_last_error_msg());
            return '';
        }

        $this->store_document($id, $json, 'converted');

        $warnings = count($converted['warnings']);
        $this->log->info("dashboard $id converted to json, "
            . count($converted['document']['widgets']) . " widgets, $warnings warnings");

        return $json;
    }

    /**
     * Upgrades a stored document and replaces its old widgets.
     *
     * A version 1 document gains widget ids, see dashboard_upgrade_document.
     * Text and container widgets become text, image and panel widgets where
     * the converters accept them, and the chart widgets of the retired vis
     * module become graph, zoom and realtime widgets, see
     * dashboard_migrate.php. A document that held any is stored again, so
     * this runs once per dashboard.
     *
     * A text or container widget the converters refuse keeps drawing as it
     * was and is not logged. A chart widget they refuse has nothing to draw
     * it, so it is.
     *
     * A graph widget still pointing at a saved graph is given the chart of
     * the saved graph, see dashboard_migrate_graph_pointers.
     *
     * @param int $id
     * @param int $userid the dashboard owner, who the saved graphs are read as
     * @param string $json the stored document
     * @return string the document, rewritten if anything changed
     */
    public function migrate_widgets($id, $userid, $json)
    {
        require_once "Modules/dashboard/dashboard_migrate.php";

        $document = json_decode($json, true);
        if (!is_array($document)) {
            return $json;
        }

        $upgraded = dashboard_upgrade_document($document);

        $context = $this->migrate_context($userid);
        $kept = [];
        $migrated = dashboard_migrate_widgets($document, $kept, $context);
        $chart_types = dashboard_convert_chart_types();
        foreach ($kept as $widget) {
            if (!in_array($widget['type'], $chart_types)) {
                continue;
            }
            $this->log->warn("dashboard $id widget " . $widget['index'] . " "
                . $widget['type'] . " not converted: " . $widget['reason']);
        }
        $folded = dashboard_migrate_graph_pointers($document, $context);
        if (!count($migrated) && !count($folded) && !$upgraded) {
            return $json;
        }

        $encoded = dashboard_convert_encode($document);
        if ($encoded === false) {
            $this->log->error("dashboard $id migrated but could not be encoded: "
                . json_last_error_msg());
            return $json;
        }

        $this->store_document($id, $encoded, 'migrated');

        if ($upgraded) {
            $this->log->info("dashboard $id document upgraded to version " . DASHBOARD_DOCUMENT_VERSION);
        }
        if (count($migrated)) {
            $this->log->info("dashboard $id migrated " . dashboard_migrate_summary($migrated));
        }
        $summary = dashboard_migrate_graph_summary($folded);
        if ($summary !== '') {
            $this->log->info("dashboard $id " . $summary);
        }
        return $encoded;
    }

    /**
     * Writes a document to content_json.
     *
     * A failure is most likely the column missing because the install has not
     * run its database update yet. The dashboard still draws, it is just
     * converted again on the next load, so this is logged and not fatal.
     *
     * @param int $id
     * @param string $json
     * @param string $what for the log line: converted or migrated
     */
    private function store_document($id, $json, $what)
    {
        try {
            $stmt = $this->mysqli->prepare("UPDATE dashboard SET content_json=? WHERE id=?");
            $stmt->bind_param("si", $json, $id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            $this->log->warn("dashboard $id $what but not stored: " . $e->getMessage());
        }
    }

    /**
     * The document the editor holds, see notes/EDITOR.md.
     *
     * Read fresh, so it is current after content_html has converted and
     * migrated it, and cleaned, so the editor holds what the page was drawn
     * from. A dashboard with no document yet gets an empty one.
     *
     * @param int $id
     * @return string json
     */
    public function document($id)
    {
        require_once "Modules/dashboard/dashboard_render.php";

        $id = (int) $id;
        $empty = ['version' => DASHBOARD_DOCUMENT_VERSION, 'next_id' => 1, 'widgets' => []];
        try {
            $result = $this->mysqli->query("SELECT content_json FROM dashboard WHERE id='$id'");
        } catch (Exception $e) {
            return dashboard_convert_encode($empty);
        }
        $row = $result ? $result->fetch_object() : false;
        $errors = [];
        $document = $row ? dashboard_render_clean((string) $row->content_json, $errors) : null;
        return dashboard_convert_encode($document === null ? $empty : $document);
    }

    /**
     * What the widget converters need from the database.
     *
     * The chart converter reads a multigraph by id. The multigraph table
     * outlives the vis module by a release, and a widget naming a row that is
     * gone converts to an empty chart. A read that fails for any other reason
     * returns false, and the widget is left as it is for the next load.
     *
     * The graph pointer fold reads a saved graph by id, as the dashboard
     * owner, see dashboard_migrate_graph_loader.
     *
     * @param int $userid the dashboard owner
     * @return array
     */
    private function migrate_context($userid)
    {
        global $redis, $settings;

        $mysqli = $this->mysqli;
        $log = $this->log;
        $context = [];
        $graph = dashboard_migrate_graph_loader($mysqli, $userid, $redis, $settings, $log);
        if ($graph !== null) {
            $context['graph'] = $graph;
        }
        $context['multigraph'] = function ($mid) use ($mysqli, $log) {
            if (!ctype_digit((string) $mid)) {
                return null;
            }
            $mid = (int) $mid;
            try {
                $stmt = $mysqli->prepare("SELECT name, feedlist FROM multigraph WHERE id=?");
                if (!$stmt) {
                    return $mysqli->errno === 1146 ? null : false;
                }
                $stmt->bind_param("i", $mid);
                $stmt->execute();
                $stmt->bind_result($name, $feedlist);
                $found = $stmt->fetch();
                $stmt->close();
            } catch (Exception $e) {
                // 1146 is a table that does not exist.
                if ($e->getCode() === 1146) {
                    return null;
                }
                $log->warn("multigraph $mid not read: " . $e->getMessage());
                return false;
            }
            if (!$found) {
                return null;
            }
            return dashboard_convert_multigraph_row((string) $name, (string) $feedlist);
        };
        return $context;
    }

    // PHP extensions the converter needs that are not loaded. dom comes from
    // php-xml on Debian and Ubuntu.
    public function missing_extensions()
    {
        $missing = [];
        foreach (['dom', 'mbstring'] as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        return $missing;
    }

    public function create($userid)
    {
        $userid = (int) $userid;
        $this->mysqli->query("INSERT INTO dashboard (`userid`,`alias`) VALUES ('$userid','')");
        return $this->mysqli->insert_id;
    }

    public function delete($userid, $id)
    {
        $userid = (int) $userid;
        $id = (int) $id;
        // Scoped to the session user so that a dashboard can only be deleted by its owner
        $stmt = $this->mysqli->prepare("DELETE FROM dashboard WHERE userid = ? AND id = ?");
        $stmt->bind_param("ii", $userid, $id);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $affected_rows > 0;
    }

    public function dashclone($userid, $id)
    {
        $userid = (int) $userid;
        $id = (int) $id;

        // Get content, name and description from origin dashboard
        $result = $this->mysqli->query("SELECT content,content_json,name,description,height FROM dashboard WHERE userid = '$userid' AND id='$id'");
        $row = $result->fetch_array();
        if (!$row) {
            return false;
        }

        // Name for cloned dashboard
        $name = sprintf('%s %s', $row['name'], tr('clone'));
        $content = $row['content'];
        // Both columns are copied. A dashboard that has not been converted yet
        // is converted the first time the copy is loaded.
        $content_json = $row['content_json'];
        $description = $row['description'];
        $height = (int) $row['height'];

        $stmt = $this->mysqli->prepare("INSERT INTO dashboard (`userid`,`content`,`content_json`,`name`,`description`,`height`) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("issssi", $userid, $content, $content_json, $name, $description, $height);
        $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();

        return $insert_id;
    }

    public function get_list($userid, $public, $published)
    {
        $userid = (int) $userid;

        $qB = "";
        $qC = "";
        if ($public == true) {
            $qB = " and public=1";
        }
        if ($published == true) {
            $qC = " and published=1";
        }
        if (!$result = $this->mysqli->query("SELECT id, name, alias, description, main, published, public, showdescription, fullscreen FROM dashboard WHERE userid='$userid'" . $qB . $qC)) {
            return [];
        }

        $list = [];
        while ($row = $result->fetch_object()) {
            $list[] =  [
                'id' => (int) $row->id,
                'name' => $row->name,
                'alias' => $row->alias,
                'showdescription' => (bool) $row->showdescription,
                'description' => $row->description,
                'main' => (bool) $row->main,
                'published' => (bool) $row->published,
                'public' => (bool) $row->public
            ];
        }
        return $list;
    }

    /**
     * Saves the document the designer holds.
     *
     * The designer posts the document, see notes/EDITOR.md. It is
     * checked here with the rules the renderer draws by, so the server rather
     * than the browser decides what a dashboard may contain. Anything outside
     * the widget registry and the html allowlist does not survive the check,
     * so it cannot be stored and cannot come back out, see SCHEMA.md.
     */
    public function set_content($userid, $id, $json, $height)
    {
        require_once "Modules/dashboard/dashboard_render.php";

        $userid = (int) $userid;
        $id = (int) $id;
        $height = (int) $height;

        // An editor page opened before the endpoint took the document posts
        // page html instead.
        if ($json === null || $json === false) {
            return ['success' => false,
                'message' => 'Error: This editor page is out of date. Reload it and make the change again.'
            ];
        }

        try {
            $result = $this->mysqli->query(
                "SELECT content_json FROM dashboard WHERE userid = '$userid' AND id='$id'"
            );
        } catch (Exception $e) {
            $this->log->error("dashboard $id cannot be saved: " . $e->getMessage());
            return ['success' => false,
                'message' => 'Error: The dashboard table has no content_json column. '
                    . 'Run the database update.'
            ];
        }
        $row = $result ? $result->fetch_object() : false;
        if (!$row) {
            return ['success' => false, 'message' => 'Dashboard not updated'];
        }

        $posted = json_decode((string) $json, true);
        if (!is_array($posted) || !isset($posted['widgets']) || !is_array($posted['widgets'])) {
            return ['success' => false,
                'message' => 'Error: Dashboard content could not be read, content not saved'
            ];
        }

        $errors = [];
        $document = dashboard_render_clean($posted, $errors);
        if ($document === null) {
            return ['success' => false,
                'message' => 'Error: Dashboard content could not be read, content not saved'
            ];
        }

        // An empty page is a dashboard someone has cleared, which is allowed.
        // A document that arrived with widgets and kept none is damaged, and
        // saving it would wipe the dashboard.
        if (count($posted['widgets']) && !count($document['widgets'])) {
            return ['success' => false,
                'message' => 'Error: No dashboard widgets found in content, content not saved'
            ];
        }

        // An id the dashboard has used is never used again, whatever counter
        // the browser sent.
        $previous = json_decode((string) $row->content_json, true);
        if (
            is_array($previous) && isset($previous['next_id'])
            && (int) $previous['next_id'] > $document['next_id']
        ) {
            $document['next_id'] = (int) $previous['next_id'];
        }

        // Old text and container widgets the converters accept are replaced
        // with text, image and panel widgets, and the chart widgets of the
        // retired vis module with graph widgets. A refused one stays as it is.
        // A graph widget still pointing at a saved graph takes its chart.
        $context = $this->migrate_context($userid);
        $kept = [];
        $migrated = dashboard_migrate_widgets($document, $kept, $context);
        if (count($migrated)) {
            $this->log->info("dashboard $id migrated " . dashboard_migrate_summary($migrated));
        }
        $summary = dashboard_migrate_graph_summary(dashboard_migrate_graph_pointers($document, $context));
        if ($summary !== '') {
            $this->log->info("dashboard $id " . $summary);
        }

        $content_json = dashboard_convert_encode($document);
        if ($content_json === false) {
            return ['success' => false,
                'message' => 'Error: Dashboard content could not be encoded, content not saved'
            ];
        }

        if (
            is_array($previous) && isset($previous['widgets'])
            && $previous['widgets'] == $document['widgets']
        ) {
            return ['success' => false, 'message' => 'Dashboard content not updated, no changes made'];
        }

        try {
            $stmt = $this->mysqli->prepare(
                "UPDATE dashboard SET content_json=?, height=? WHERE userid=? AND id=?"
            );
            $stmt->bind_param("siii", $content_json, $height, $userid, $id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
        } catch (Exception $e) {
            $this->log->error("dashboard $id cannot be saved: " . $e->getMessage());
            return ['success' => false,
                'message' => 'Error: Dashboard content could not be saved, see the log'
            ];
        }

        if ($affected_rows > 0) {
            // Anything the allowlist would not keep has gone. Saying so beats
            // letting it disappear without comment, which is what the author
            // would otherwise see.
            $response = ['success' => true, 'message' => 'Dashboard updated'];
            if (count($migrated)) {
                $response['migrated'] = count($migrated);
            }
            $dropped = $this->authored_drops($errors);
            if (count($dropped)) {
                $this->log->info("dashboard $id saved, dropped " . implode(', ', $dropped));
                $response['dropped'] = array_values($dropped);
            }
            return $response;
        }
        return ['success' => false, 'message' => 'Dashboard not updated'];
    }

    /**
     * Summarises the warnings that mean something an author wrote did not
     * survive. The rest are generated markup, designer artefacts and browser
     * extension debris, which nobody needs telling about.
     *
     * @param array $warnings from dashboard_render_clean
     * @return array code => "code (count)"
     */
    private function authored_drops($warnings)
    {
        $authored = [
            'nested_widget_dropped', 'iframe_dropped', 'tag_dropped', 'tag_unwrapped',
            'url_dropped', 'attribute_dropped', 'style_property_dropped',
            'style_value_dropped', 'option_value_dropped', 'text_outside_widget',
            'position_fixed_dropped', 'widget_without_type',
            'unknown_widget_option_dropped', 'opacity_raised',
            'html_not_allowed_on_type', 'text_not_allowed_on_type',
            'config_block_unknown', 'config_entry_unknown', 'config_value_dropped'
        ];

        $counts = [];
        foreach ($warnings as $warning) {
            if (!in_array($warning['code'], $authored)) {
                continue;
            }
            if (!isset($counts[$warning['code']])) {
                $counts[$warning['code']] = 0;
            }
            $counts[$warning['code']]++;
        }

        $summary = [];
        foreach ($counts as $code => $count) {
            $summary[$code] = "$code ($count)";
        }
        return $summary;
    }

    public function set($userid, $id, $fields)
    {
        $userid = (int) $userid;
        $id = (int) $id;
        $fields = json_decode($fields);
        if (!empty($fields->alias)) {
            $fields->alias = $this->make_slug($fields->alias); // make url friendly
            $fields->alias = substr($fields->alias, 0, 20); // limit to 20 chars to match the db
        }
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' and `id` = '$id'");
        if ($row = $result->fetch_object()) {
            if (isset($fields->height)) {
                $row->height = (int) $fields->height;
            }
            if (isset($fields->name)) {
                $row->name = preg_replace('/[^\p{L}_\p{N}\s\-]/u', '', $fields->name);
            }
            if (isset($fields->alias)) {
                $row->alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u', '', $fields->alias);
            }
            if (isset($fields->description)) {
                $row->description = preg_replace('/[^\p{L}_\p{N}\s\-]/u', '', $fields->description);
            }
            if (isset($fields->backgroundcolor)) {
                $row->backgroundcolor = preg_replace('/[^0-9a-f]/', '', strtolower($fields->backgroundcolor));
            }
            if (isset($fields->gridsize)) {
                $row->gridsize = preg_replace('/[^0-9]/', '', $fields->gridsize);
            }
            if (isset($fields->feedmode)) {
                $row->feedmode = preg_replace('/[^\p{L}_\p{N}\s\-]/u', '', $fields->feedmode);
            }

            if (isset($fields->main)) {
                $main = (bool)$fields->main;
                if ($main) {
                    $this->mysqli->query("UPDATE dashboard SET main = FALSE WHERE userid='$userid' and id<>'$id'");
                }
                $row->main = $main;
            }

            if (isset($fields->public)) {
                $row->public = (bool) $fields->public;
            }
            if (isset($fields->fullscreen)) {
                $row->fullscreen = (bool) $fields->fullscreen;
            }
            if (isset($fields->published)) {
                $row->published = (bool) $fields->published;
            }
            if (isset($fields->showdescription)) {
                $row->showdescription = (bool) $fields->showdescription;
            }

            if (!$stmt = $this->mysqli->prepare("UPDATE dashboard SET height=?,name=?,alias=?,description=?,backgroundcolor=?,gridsize=?,feedmode=?,main=?,public=?,published=?,showdescription=?,fullscreen=? WHERE userid=? AND id=?")) {
                return ['success' => false, 'message' => 'Dashboard schema error, please run emoncms database update'];
            }
            $stmt->bind_param("issssisiiiiiii", $row->height, $row->name, $row->alias, $row->description, $row->backgroundcolor, $row->gridsize, $row->feedmode, $row->main, $row->public, $row->published, $row->showdescription, $row->fullscreen, $userid, $id);

            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $error = $stmt->error;
            $stmt->close();

            if ($affected_rows > 0) {
                return ['success' => true, 'message' => 'Field updated', 'id' => $id, 'alias' => $row->alias];
            } else {
                return ['success' => false, 'message' => 'Nothing changed', 'id' => $id, 'alias' => $row->alias];
            }
        }
        return ['success' => false, 'message' => 'Field could not be updated'];
    }

    // Return the main dashboard from $userid
    public function get_main($userid)
    {
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' and main=TRUE");
        return $result->fetch_array();
    }

    // Any dashboard by id, public or not. The caller has to decide whether the
    // requester may see it, see the access control in dashboard_controller.
    // Use get_owned where the answer is only ever the requester's own.
    public function get($id)
    {
        $id = (int) $id;
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE id='$id'");
        return $result->fetch_array();
    }

    // The requester's own dashboard, or false. The editor loads through this,
    // so an id belonging to somebody else opens nothing rather than showing
    // its content.
    public function get_owned($userid, $id)
    {
        $userid = (int) $userid;
        $id = (int) $id;
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' AND id='$id'");
        return $result->fetch_array();
    }

    // Returns the $id dashboard from $userid
    public function get_from_alias($userid, $alias)
    {
        $userid = (int) $userid;
        $alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u', '', $alias);

        if (!empty($alias)) {
            $stmt = $this->mysqli->prepare("SELECT * FROM dashboard WHERE userid=? and alias=?");
            $stmt->bind_param("is", $userid, $alias);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->free_result();
            $stmt->close();
            return $result->fetch_array();
        }
        return false;
    }

    /**
     * Get the public dashboard from $alias
     * return array of fields for found database
     * @param string $alias
     */
    public function get_from_public_alias($alias)
    {
        $alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u', '', $alias);
        // access to public dashboards
        // Only public rows are matched, and the oldest wins. Matching any row
        // let a private dashboard take an alias already in use and stop the
        // public one it collided with from being reachable.
        if (!empty($alias)) {
            $stmt = $this->mysqli->prepare("SELECT * FROM dashboard WHERE alias=? AND public=1 ORDER BY id ASC LIMIT 1");
            $stmt->bind_param("s", $alias);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->free_result();
            $stmt->close();
            return $result->fetch_array();
        }
    }

    public function build_menu_array($location)
    {
        global $session;

        $dashpath = 'dashboard/' . $location;

        if ($session['public_userid']) {
            $userid = $session['public_userid'];
            $public = 1;
            $published = 1;
        } else {
            $userid = (int) $session['userid'];
            $public = 0;
            $published = 0;
        }

        $dashboards = $this->get_list($userid, $public, $published);
        $menu = [];
        foreach ($dashboards as $dashboard) {
            // Check show description
            $desc = '';
            if ($dashboard['showdescription']) {
                $desc = $dashboard['description'];
            }

            // Set URL using alias or id
            if ($dashboard['alias']) {
                $aliasurl = "/" . $dashboard['alias'];
            } else {
                $aliasurl = '&id=' . $dashboard['id'];
            }

            // Build the menu item
            $menu[] = [
                'id' => $dashboard['id'],
                'name' => $dashboard['name'],
                'desc' => $desc,
                'published' => $dashboard['published'],
                'path' => $dashpath . $aliasurl,
                'main' => $dashboard['main']
            ];
        }
        usort($menu, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        for ($i = 0; $i < count($menu); $i++) {
            $menu[$i]['order'] = $i;
        }

        return $menu;
    }
    public function make_slug($string, $separator = '-')
    {
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = [ '&' => 'and', "'" => ''];
        $string = mb_strtolower(trim($string), 'UTF-8');
        $string = str_replace(array_keys($special_cases), array_values($special_cases), $string);
        $string = preg_replace($accents_regex, '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
        $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);
        return $string;
    }
}
