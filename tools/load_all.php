<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Loads every dashboard through dashboard_model::content_html, the same path a
// page view takes, and reports what the load changed. This is the migration
// test: it runs the html conversion, the widget migration and the renderer
// exactly as a visitor would, and writes what a visitor's load would write.
// See notes/RELEASE.md.
//
// Run from the emoncms root, on a copy of the table or after
// tools/snapshot.php has saved it:
//
//   php Modules/dashboard/tools/load_all.php
//   php Modules/dashboard/tools/load_all.php --id=N
//
// Exits 1 when a dashboard still holds a widget nothing can draw, when a
// document could not be read, or when the renderer reported an error.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') {
    die("cli only\n");
}

require_once dirname(__FILE__) . "/cli.php";

$opts = cli_options($argv);

if (isset($opts['help'])) {
    echo "usage: php load_all.php [--id=N] [--quiet]\n";
    echo "  --id=N    one dashboard only\n";
    echo "  --quiet   totals only, no line per dashboard\n";
    exit(0);
}

// The model requires its files relative to the emoncms root.
chdir(cli_root());
$mysqli = cli_connect();
require "Lib/EmonLogger.php";
require "Modules/dashboard/dashboard_model.php";
require_once "Modules/dashboard/dashboard_migrate.php";
require_once "Modules/dashboard/dashboard_render.php";

$only = isset($opts['id']) ? (int) $opts['id'] : 0;
$quiet = isset($opts['quiet']);

$has_column = cli_has_column($mysqli, 'dashboard', 'content_json');
if (!$has_column) {
    echo "The dashboard table has no content_json column. Nothing a load writes will be kept.\n\n";
}

$dashboard = new Dashboard($mysqli);

$sql = "SELECT * FROM dashboard ORDER BY id";
if ($only) {
    $sql = "SELECT * FROM dashboard WHERE id=$only";
}
$result = $mysqli->query($sql);
if (!$result) {
    die("Query failed: " . $mysqli->error . "\n");
}
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$result->free();

// Widget types of a stored document, counted. Null when there is no document.
function document_types($json)
{
    if ($json === null || trim($json) === '') {
        return null;
    }
    $document = json_decode($json, true);
    if (!is_array($document) || !isset($document['widgets']) || !is_array($document['widgets'])) {
        return false;
    }
    $types = [];
    foreach ($document['widgets'] as $widget) {
        $type = isset($widget['type']) ? (string) $widget['type'] : '';
        if (!isset($types[$type])) {
            $types[$type] = 0;
        }
        $types[$type]++;
    }
    return $types;
}

// Version of a stored document, or null when there is none to read.
function document_version($json)
{
    if ($json === null || trim($json) === '') {
        return null;
    }
    $document = json_decode($json, true);
    if (!is_array($document)) {
        return null;
    }
    return isset($document['version']) ? (int) $document['version'] : 0;
}

function stored_json($mysqli, $id)
{
    $result = $mysqli->query("SELECT content_json FROM dashboard WHERE id=$id");
    $row = $result ? $result->fetch_assoc() : null;
    return $row ? $row['content_json'] : null;
}

$old_types = array_keys(dashboard_migrate_old_types());
$chart_types = dashboard_convert_chart_types();

$total = 0;
$empty = 0;
$from_html = 0;
$unreadable = 0;
$blank = [];
$migrated = [];
$still_old = [];
$still_chart = [];
$render_errors = [];
$unknown = [];
$upgraded = 0;
$faulty = [];

foreach ($rows as $dash) {
    $id = (int) $dash['id'];
    $total++;

    $before = document_types($has_column ? $dash['content_json'] : null);
    $version_before = document_version($has_column ? $dash['content_json'] : null);
    $had_html = trim((string) $dash['content']) !== '';
    if ($before === null && !$had_html) {
        $empty++;
        continue;
    }

    $html = $dashboard->content_html($dash);

    $json = $has_column ? stored_json($mysqli, $id) : null;
    $after = document_types($json);

    $line = "dashboard $id";
    if ($before === null) {
        $from_html++;
        $line .= " from html";
    }
    $version_after = document_version($json);
    if ($version_before !== null && $version_after !== null && $version_after !== $version_before) {
        $upgraded++;
        $line .= " upgraded $version_before to $version_after";
    }
    if ($after === false) {
        $unreadable++;
        $faulty[$id] = 'document cannot be read';
        $line .= " document cannot be read";
    } elseif ($after === null) {
        if ($has_column) {
            $blank[] = $id;
            $line .= " converts to no widgets";
        }
    } else {
        $changes = [];
        if (is_array($before)) {
            foreach ($before as $type => $count) {
                $now = isset($after[$type]) ? $after[$type] : 0;
                if ($now < $count && in_array($type, $old_types)) {
                    $changes[] = "$type " . ($count - $now);
                    if (!isset($migrated[$type])) {
                        $migrated[$type] = 0;
                    }
                    $migrated[$type] += $count - $now;
                }
            }
        }
        if (count($changes)) {
            $line .= " migrated " . implode(', ', $changes);
        }
        if ($before === null) {
            $holds = [];
            foreach ($after as $type => $count) {
                $holds[] = "$type $count";
            }
            $line .= " to " . implode(', ', $holds);
        }
        foreach ($after as $type => $count) {
            if (!in_array($type, $old_types)) {
                continue;
            }
            if (!isset($still_old[$type])) {
                $still_old[$type] = 0;
            }
            $still_old[$type] += $count;
            if (in_array($type, $chart_types)) {
                $still_chart[$id] = $type;
                $faulty[$id] = "still holds $type";
            }
        }

        $rendered = dashboard_render($json);
        foreach ($rendered['errors'] as $error) {
            $code = $error['code'];
            // A type that was never declared draws as a placeholder, as it
            // did before. A chart type is caught above, so this is a made up
            // class such as Container-red.
            if ($code === 'widget_type_unknown') {
                $detail = $error['detail'];
                if (!isset($unknown[$detail])) {
                    $unknown[$detail] = 0;
                }
                $unknown[$detail]++;
                continue;
            }
            if (!isset($render_errors[$code])) {
                $render_errors[$code] = 0;
            }
            $render_errors[$code]++;
            $faulty[$id] = "render $code";
            $line .= " render $code " . $error['detail'];
        }
    }

    if (!$quiet) {
        echo $line . "\n";
    }
}

$mysqli->close();

if (!$quiet) {
    echo "\n";
}
echo "Dashboards:                $total\n";
echo "  empty:                   $empty\n";
echo "  converted from html:     $from_html\n";
echo "  document upgraded:       $upgraded\n";
if ($unreadable) {
    echo "  unreadable:              $unreadable\n";
}
if (count($blank)) {
    echo "  converted to no widgets: " . count($blank) . "\n";
}

echo "\nWidgets migrated on load:\n";
if (!count($migrated)) {
    echo "  none\n";
}
foreach ($migrated as $type => $count) {
    echo "  " . str_pad($type, 20) . $count . "\n";
}

echo "\nOld widgets still stored:\n";
if (!count($still_old)) {
    echo "  none\n";
}
foreach ($still_old as $type => $count) {
    echo "  " . str_pad($type, 20) . $count . (in_array($type, $chart_types) ? "  nothing draws this" : "  draws as before") . "\n";
}

echo "\nUnknown types, drawn as placeholders:\n";
if (!count($unknown)) {
    echo "  none\n";
}
foreach ($unknown as $type => $count) {
    echo "  " . str_pad($type, 20) . $count . "\n";
}

echo "\nRender errors:\n";
if (!count($render_errors)) {
    echo "  none\n";
}
foreach ($render_errors as $code => $count) {
    echo "  " . str_pad($code, 36) . $count . "\n";
}

if (count($blank)) {
    // Mostly a stray line of text, see migrate.php. Nothing is written for
    // these, so the html stays the only copy and is worth a look.
    echo "\nHeld html and converted to no widgets, check with show.php:\n";
    echo "  " . implode(', ', $blank) . "\n";
}

if (count($faulty)) {
    echo "\nDashboards to look at:\n";
    foreach ($faulty as $id => $why) {
        echo "  $id  $why\n";
    }
    exit(1);
}
echo "\nEvery dashboard loads.\n";
exit(0);
