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
     * Modules/dashboard/tools/SCHEMA.md. A dashboard still holding html is
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
            if (trim($content) === '') return '';
            $json = $this->convert_content((int) $dash['id'], $content);
        }

        if (trim($json) === '') return '';

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

        try {
            $stmt = $this->mysqli->prepare("UPDATE dashboard SET content_json=? WHERE id=?");
            $stmt->bind_param("si", $json, $id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Most likely the column is missing because the install has not run
            // its database update yet. The dashboard still draws, it is just
            // converted again on the next load.
            $this->log->warn("dashboard $id converted but not stored: " . $e->getMessage());
        }

        $warnings = count($converted['warnings']);
        $this->log->info("dashboard $id converted to json, "
            . count($converted['document']['widgets']) . " widgets, $warnings warnings");

        return $json;
    }

    public function create($userid)
    {
        $userid = (int) $userid;
        $this->mysqli->query("INSERT INTO dashboard (`userid`,`alias`) VALUES ('$userid','')");
        return $this->mysqli->insert_id;
    }

    public function delete($userid,$id)
    {
        $userid = (int) $userid;
        $id = (int) $id;
        // Scoped to the session user so that a dashboard can only be deleted by its owner
        $stmt = $this->mysqli->prepare("DELETE FROM dashboard WHERE userid = ? AND id = ?");
        $stmt->bind_param("ii",$userid,$id);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $affected_rows>0;
    }

    public function dashclone($userid, $id)
    {
        $userid = (int) $userid;
        $id = (int) $id;

        // Get content, name and description from origin dashboard
        $result = $this->mysqli->query("SELECT content,content_json,name,description,height FROM dashboard WHERE userid = '$userid' AND id='$id'");
        $row = $result->fetch_array();
        if (!$row) return false;

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

        $qB = ""; $qC = "";
        if ($public==true) $qB = " and public=1";
        if ($published==true) $qC = " and published=1";
        if (!$result = $this->mysqli->query("SELECT id, name, alias, description, main, published, public, showdescription, fullscreen FROM dashboard WHERE userid='$userid'".$qB.$qC)) {
          return array();
        }
        
        $list = array();
        while ($row = $result->fetch_object())
        {
        $list[] = array (
            'id' => (int) $row->id,
            'name' => $row->name,
            'alias' => $row->alias,
            'showdescription' => (bool) $row->showdescription,
            'description' => $row->description,
            'main' => (bool) $row->main,
            'published'=> (bool) $row->published,
            'public'=> (bool) $row->public
        );
        }
        return $list;
    }

    /**
     * Saves the page the designer built.
     *
     * The designer posts the page html. It is converted to the stored document
     * here, which is what makes the server rather than the browser decide what
     * a dashboard may contain. Anything outside the widget registry and the
     * html allowlist does not survive the conversion, so it cannot be stored
     * and cannot come back out, see Modules/dashboard/tools/SCHEMA.md.
     *
     * This replaces a filter that looked for markup known to be dangerous and
     * refused the save when it found any. Listing what is allowed does not
     * depend on having thought of every way of writing an attack.
     */
    public function set_content($userid, $id, $_content, $height)
    {
        require_once "Modules/dashboard/dashboard_convert.php";

        $userid = (int) $userid;
        $id = (int) $id;
        $height = (int) $height;

        try {
            $result = $this->mysqli->query(
                "SELECT content_json FROM dashboard WHERE userid = '$userid' AND id='$id'");
        } catch (Exception $e) {
            $this->log->error("dashboard $id cannot be saved: " . $e->getMessage());
            return array('success'=>false,
                'message'=>'Error: The dashboard table has no content_json column. '
                    . 'Run the database update.');
        }
        $row = $result ? $result->fetch_object() : false;
        if (!$row) return array('success'=>false, 'message'=>'Dashboard not updated');

        $converted = dashboard_convert($_content);
        $document = $converted['document'];

        if ($document === null) {
            return array('success'=>false,
                'message'=>'Error: Dashboard content could not be read, content not saved');
        }

        // An empty page is a dashboard someone has cleared, which is allowed.
        // A page that arrived with something in it and produced no widgets is
        // damaged, and saving it would wipe the dashboard.
        if (trim($_content) !== '' && !count($document['widgets'])) {
            return array('success'=>false,
                'message'=>'Error: No dashboard widgets found in content, content not saved');
        }

        // The document the editor writes carries no meta block. It records how
        // a conversion went, which belongs to the migration, and its warnings
        // would otherwise store fragments of whatever was posted.
        unset($document['meta']);

        $content_json = dashboard_convert_encode($document);
        if ($content_json === false) {
            return array('success'=>false,
                'message'=>'Error: Dashboard content could not be encoded, content not saved');
        }

        // The widgets are compared rather than the whole document, because the
        // document carries the time it was converted and would never match.
        $previous = json_decode((string) $row->content_json, true);
        if (is_array($previous) && isset($previous['widgets'])
            && $previous['widgets'] == $document['widgets']) {
            return array('success'=>false, 'message'=>'Dashboard content not updated, no changes made');
        }

        try {
            $stmt = $this->mysqli->prepare(
                "UPDATE dashboard SET content_json=?, height=? WHERE userid=? AND id=?");
            $stmt->bind_param("siii", $content_json, $height, $userid, $id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
        } catch (Exception $e) {
            $this->log->error("dashboard $id cannot be saved: " . $e->getMessage());
            return array('success'=>false,
                'message'=>'Error: Dashboard content could not be saved, see the log');
        }

        if ($affected_rows>0){
            // Anything the allowlist would not keep has gone. Saying so beats
            // letting it disappear without comment, which is what the author
            // would otherwise see.
            $dropped = $this->authored_drops($converted['warnings']);
            if (count($dropped)) {
                $this->log->info("dashboard $id saved, dropped " . implode(', ', $dropped));
                return array('success'=>true, 'message'=>'Dashboard updated',
                    'dropped'=>array_values($dropped));
            }
            return array('success'=>true, 'message'=>'Dashboard updated');
        }
        return array('success'=>false, 'message'=>'Dashboard not updated');
    }

    /**
     * Summarises the warnings that mean something an author wrote did not
     * survive. The rest are generated markup, designer artefacts and browser
     * extension debris, which nobody needs telling about.
     *
     * @param array $warnings from dashboard_convert
     * @return array code => "code (count)"
     */
    private function authored_drops($warnings)
    {
        $authored = array(
            'nested_widget_dropped', 'iframe_dropped', 'tag_dropped', 'tag_unwrapped',
            'url_dropped', 'attribute_dropped', 'style_property_dropped',
            'style_value_dropped', 'option_value_dropped', 'text_outside_widget',
            'position_fixed_dropped', 'widget_without_type',
            'unknown_widget_option_dropped', 'opacity_raised'
        );

        $counts = array();
        foreach ($warnings as $warning) {
            if (!in_array($warning['code'], $authored)) continue;
            if (!isset($counts[$warning['code']])) $counts[$warning['code']] = 0;
            $counts[$warning['code']]++;
        }

        $summary = array();
        foreach ($counts as $code => $count) $summary[$code] = "$code ($count)";
        return $summary;
    }

    public function set($userid,$id,$fields)
    {
        $userid = (int) $userid;
        $id = (int) $id;
        $fields = json_decode($fields);
        if(!empty($fields->alias)){
            $fields->alias = $this->make_slug($fields->alias); // make url friendly
            $fields->alias = substr($fields->alias,0,20); // limit to 20 chars to match the db
        }
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' and `id` = '$id'");
        if ($row = $result->fetch_object()) 
        {
            if (isset($fields->height)) $row->height = (int) $fields->height;
            if (isset($fields->name)) $row->name = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->name);
            if (isset($fields->alias)) $row->alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->alias);
            if (isset($fields->description)) $row->description = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->description);
            if (isset($fields->backgroundcolor)) $row->backgroundcolor = preg_replace('/[^0-9a-f]/','', strtolower($fields->backgroundcolor));
            if (isset($fields->gridsize)) $row->gridsize = preg_replace('/[^0-9]/','', $fields->gridsize);
            if (isset($fields->feedmode)) $row->feedmode = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$fields->feedmode);

            if (isset($fields->main))
            {
                $main = (bool)$fields->main;
                if ($main) $this->mysqli->query("UPDATE dashboard SET main = FALSE WHERE userid='$userid' and id<>'$id'");
                $row->main = $main;
            }

            if (isset($fields->public)) $row->public = (bool) $fields->public;
            if (isset($fields->fullscreen)) $row->fullscreen = (bool) $fields->fullscreen;
            if (isset($fields->published)) $row->published = (bool) $fields->published;
            if (isset($fields->showdescription)) $row->showdescription = (bool) $fields->showdescription;
            
            if (!$stmt = $this->mysqli->prepare("UPDATE dashboard SET height=?,name=?,alias=?,description=?,backgroundcolor=?,gridsize=?,feedmode=?,main=?,public=?,published=?,showdescription=?,fullscreen=? WHERE userid=? AND id=?")) {
                return array('success'=>false, 'message'=>'Dashboard schema error, please run emoncms database update');
            }
            $stmt->bind_param("issssisiiiiiii",$row->height,$row->name,$row->alias,$row->description,$row->backgroundcolor,$row->gridsize,$row->feedmode,$row->main,$row->public,$row->published,$row->showdescription,$row->fullscreen,$userid,$id);

            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $error = $stmt->error;
            $stmt->close();
            
            if ($affected_rows>0){
                return array('success'=>true, 'message'=>'Field updated', 'id'=>$id, 'alias'=>$row->alias);
            } else {
                return array('success'=>false, 'message'=>'Nothing changed', 'id'=>$id, 'alias'=>$row->alias);
            }
        }
        return array('success'=>false, 'message'=>'Field could not be updated'. " $error");
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
    
    public function get_content($userid,$id)
    {
        $id = (int) $id;
        $userid = (int) $userid;
        $result = $this->mysqli->query("SELECT * FROM dashboard WHERE userid='$userid' AND id='$id'");
        $row = $result->fetch_object();
        if (!$row) return $row;

        // The content column is frozen at what was there before the dashboard
        // was converted, so returning it would hand back something the
        // dashboard no longer holds. The html the page is built from is
        // returned instead, alongside the document it came from.
        $row->content = $this->content_html((array) $row);
        return $row;
    }

    // Returns the $id dashboard from $userid
    public function get_from_alias($userid, $alias)
    {
        $userid = (int) $userid;
        $alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$alias);
        
        if(!empty($alias)) {
            $stmt = $this->mysqli->prepare("SELECT * FROM dashboard WHERE userid=? and alias=?");
            $stmt->bind_param("is",$userid,$alias);
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
        $alias = preg_replace('/[^\p{L}_\p{N}\s\-]/u','',$alias);
        // access to public dashboards
        // Only public rows are matched, and the oldest wins. Matching any row
        // let a private dashboard take an alias already in use and stop the
        // public one it collided with from being reachable.
        if(!empty($alias)) {
            $stmt = $this->mysqli->prepare("SELECT * FROM dashboard WHERE alias=? AND public=1 ORDER BY id ASC LIMIT 1");
            $stmt->bind_param("s",$alias);
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

        $dashpath = 'dashboard/'.$location;
            
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
        $menu = array();
        foreach ($dashboards as $dashboard)
        {
            // Check show description
            $desc = '';
            if ($dashboard['showdescription']) {
                $desc = $dashboard['description'];
            }

            // Set URL using alias or id
            if ($dashboard['alias']) {
                $aliasurl = "/".$dashboard['alias'];
            } else {
                $aliasurl = '&id='.$dashboard['id'];
            }

            // Build the menu item
            $menu[] = array(
                'id' => $dashboard['id'],
                'name' => $dashboard['name'],
                'desc'=> $desc,
                'published'=> $dashboard['published'],
                'path' => $dashpath.$aliasurl,
                'main' => $dashboard['main']
            );
        }
        usort($menu, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        for ($i=0; $i<count($menu); $i++) {
            $menu[$i]['order'] = $i;
        }
        
        return $menu;
    }
    public function make_slug( $string, $separator = '-' ) {
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = array( '&' => 'and', "'" => '');
        $string = mb_strtolower( trim( $string ), 'UTF-8' );
        $string = str_replace( array_keys($special_cases), array_values( $special_cases), $string );
        $string = preg_replace( $accents_regex, '$1', htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
        $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);
        return $string;
    }
}

