<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Stage 5 of the move to JSON dashboard content.
// Converts the old Container-* widgets into panel widgets and reports the
// result. Nothing is written to the database.
//
// Run from the emoncms root:
//   php Modules/dashboard/tools/convert_panel.php
//   php Modules/dashboard/tools/convert_panel.php --in=/var/tmp/census.jsonl
//   php Modules/dashboard/tools/convert_panel.php --show=style_not_carried
//   php Modules/dashboard/tools/convert_panel.php --id=1861
//
// The drawing is not compared in a browser. A panel is drawn from the same
// values the class gave the old box, and there is no text to measure.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

$root = dirname(__FILE__) . "/../../..";
require_once dirname(__FILE__) . "/../dashboard_convert.php";

$opts = array();
foreach (array_slice($argv, 1) as $a) {
    if (substr($a, 0, 2) !== '--') continue;
    $a = substr($a, 2);
    $eq = strpos($a, '=');
    if ($eq === false) $opts[$a] = true;
    else $opts[substr($a, 0, $eq)] = substr($a, $eq + 1);
}

if (isset($opts['help'])) {
    echo "usage: php convert_panel.php [options]\n";
    echo "  --in=FILE     read an export rather than the database\n";
    echo "  --id=N        convert one dashboard and print its containers\n";
    echo "  --show=CODE   print examples of one reason for refusing\n";
    echo "  --samples=N   how many examples to print, default 5\n";
    echo "  --limit=N     stop after N dashboards\n";
    exit(0);
}

if (widget_registry_option('panel', 'colour') === false) {
    die("The panel widget has no declaration. Generate it with:\n"
        . "  node Modules/dashboard/tools/extract_registry.js\n");
}

$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$samples = isset($opts['samples']) ? (int) $opts['samples'] : 5;
$show = isset($opts['show']) ? $opts['show'] : null;
$only = isset($opts['id']) ? (int) $opts['id'] : 0;

$old_types = array_keys(dashboard_convert_panel_defaults());

$dashboards = 0;
$found = 0;
$converted = 0;
$by_type = array();
$by_what = array();
$reasons = array();
$examples = array();

$rows = isset($opts['in']) ? rows_from_export($opts['in']) : rows_from_database($root);

foreach ($rows as $row) {
    if ($only && (int) $row['id'] !== $only) continue;
    if (trim($row['content']) === '' && trim($row['content_json']) === '') continue;

    $dashboards++;
    if ($limit > 0 && $dashboards > $limit) { $dashboards--; break; }

    $result = row_document($row);
    if ($result['document'] === null) continue;

    foreach ($result['document']['widgets'] as $widget) {
        if (!isset($widget['type']) || !in_array($widget['type'], $old_types)) continue;

        $found++;
        $what = panel_holds($widget);
        bump($by_type, $widget['type'] . "\t" . 'found');
        bump($by_what, $what . "\t" . 'found');

        $reason = '';
        $new = dashboard_convert_panel_widget($widget, $reason);

        if ($only) {
            echo str_pad($widget['type'], 20) . str_pad($what, 24)
                . ($new === null ? "refused $reason"
                                 : dashboard_convert_encode($new)) . "\n";
            continue;
        }

        if ($new === null) {
            $code = explode(':', $reason)[0];
            bump($reasons, $code);
            bump($by_type, $widget['type'] . "\t" . 'refused');
            bump($by_what, $what . "\t" . 'refused');
            if ($show === $code && count($examples) < $samples) {
                $examples[] = "dashboard " . $row['id'] . ": " . $reason . "\n      "
                    . dashboard_convert_snippet(panel_description($widget));
            }
            continue;
        }

        $converted++;
        bump($by_type, $widget['type'] . "\t" . 'converted');
        bump($by_what, $what . "\t" . 'converted');
    }
}

if ($only) {
    if (!$found) fwrite(STDERR, "No container in dashboard $only\n");
    exit($found ? 0 : 1);
}

if ($show !== null) {
    if (!count($examples)) echo "No widget was refused for $show\n\n";
    else {
        echo "Examples of $show\n";
        foreach ($examples as $example) echo "    $example\n";
        echo "\n";
    }
}

echo "Dashboards:      $dashboards\n";
echo "Old containers:  $found\n\n";

echo "Converted\n";
printf("  converted:     %d%s\n", $converted, share($converted, $found));
printf("  refused:       %d%s\n", $found - $converted, share($found - $converted, $found));

echo "\nBy what the container holds\n";
column_table($by_what, array('found', 'converted', 'refused'), $found);

echo "\nBy widget type\n";
column_table($by_type, array('found', 'converted', 'refused'), $found);

if (count($reasons)) {
    echo "\nWhy a widget was refused\n";
    arsort($reasons);
    foreach ($reasons as $code => $count) {
        printf("  %-32s %6d%s\n", $code, $count, share($count, $found));
    }
    echo "\n  Use --show=CODE for examples.\n";
}

// ---------------------------------------------------------------------------
// Reporting
// ---------------------------------------------------------------------------

// The most demanding thing a container holds: html, an author's box style,
// or nothing.
function panel_holds($widget)
{
    $html = isset($widget['html']) && trim((string) $widget['html']) !== '';
    $style = !empty($widget['style']);
    if ($html && $style) return 'html and box style';
    if ($html) return 'html';
    if ($style) return 'box style';
    return 'empty';
}

function panel_description($widget)
{
    $parts = array();
    if (!empty($widget['style'])) $parts[] = dashboard_convert_write_style($widget['style']);
    if (isset($widget['html'])) $parts[] = $widget['html'];
    return implode(' | ', $parts);
}

function bump(&$arr, $key, $n = 1)
{
    if (!isset($arr[$key])) $arr[$key] = 0;
    $arr[$key] += $n;
}

// Counts keyed "row\tcolumn" printed as a table, rows in order of how many
// widgets each holds.
function column_table($counts, $columns, $total)
{
    $rows = array();
    foreach ($counts as $key => $count) {
        list($row, $column) = explode("\t", $key, 2);
        if (!isset($rows[$row])) $rows[$row] = array();
        $rows[$row][$column] = $count;
    }
    uasort($rows, function ($a, $b) {
        return (isset($b['found']) ? $b['found'] : 0) - (isset($a['found']) ? $a['found'] : 0);
    });

    printf("  %-28s", '');
    foreach ($columns as $column) printf("%12s", $column);
    echo "\n";
    foreach ($rows as $name => $row) {
        printf("  %-28s", substr($name, 0, 28));
        foreach ($columns as $column) printf("%12d", isset($row[$column]) ? $row[$column] : 0);
        $found = isset($row['found']) ? $row['found'] : 0;
        printf("  %s\n", share($found, $total));
    }
}

function share($count, $total)
{
    if (!$total) return '';
    return sprintf("  %.1f%%", 100 * $count / $total);
}

// ---------------------------------------------------------------------------
// Sources
// ---------------------------------------------------------------------------

// The stored document of a row, or the html converted where there is none.
function row_document($row)
{
    if (trim($row['content_json']) !== '') {
        $document = json_decode($row['content_json'], true);
        if (is_array($document) && isset($document['widgets'])) {
            return array('document' => $document, 'warnings' => array());
        }
    }
    return dashboard_convert($row['content']);
}

function rows_from_database($root)
{
    $cwd = getcwd();
    chdir($root);
    require "process_settings.php";
    chdir($cwd);

    $mysqli = @new mysqli(
        $settings["sql"]["server"],
        $settings["sql"]["username"],
        $settings["sql"]["password"],
        $settings["sql"]["database"],
        $settings["sql"]["port"]
    );
    if ($mysqli->connect_error) die("Cannot connect to database: " . $mysqli->connect_error . "\n");
    $mysqli->set_charset("utf8mb4");

    // content_json is read where the table has it. After the switch over it
    // is the copy that is drawn and edited, and content is frozen.
    $check = $mysqli->query("SHOW COLUMNS FROM dashboard LIKE 'content_json'");
    $json = $check && $check->num_rows ? ", content_json" : ", NULL AS content_json";

    $result = $mysqli->query("SELECT id, userid, content$json FROM dashboard ORDER BY id",
        MYSQLI_USE_RESULT);
    if (!$result) die("Query failed: " . $mysqli->error . "\n");

    while ($row = $result->fetch_assoc()) {
        yield array(
            'id' => $row['id'],
            'userid' => $row['userid'],
            'content' => $row['content'] === null ? '' : $row['content'],
            'content_json' => $row['content_json'] === null ? '' : $row['content_json']
        );
    }
    $result->free();
    $mysqli->close();
}

function rows_from_export($file)
{
    $fh = @fopen($file, 'r');
    if (!$fh) die("Cannot read $file\n");

    while (($line = fgets($fh)) !== false) {
        $record = json_decode($line, true);
        if (!is_array($record) || !isset($record['content'])) continue;
        yield array(
            'id' => $record['id'],
            'userid' => null,
            'content' => $record['content'],
            'content_json' => isset($record['content_json']) ? (string) $record['content_json'] : ''
        );
    }
    fclose($fh);
}
