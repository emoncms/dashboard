<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Replaces the old paragraph, heading, heading-center and Container-* widgets
// in every stored document with text, image and panel widgets, where the
// converters accept them. The same function runs on every save, so this is
// not needed to migrate an install. It is here so a large install can be
// moved in one go and what is left can be counted.
//
// Only content_json is read and written. A dashboard still holding html and
// no document is counted and passed over, run migrate.php first.
//
// Run from the emoncms root. It reports and changes nothing without --write:
//
//   php Modules/dashboard/tools/migrate_widgets.php
//   php Modules/dashboard/tools/migrate_widgets.php --write
//
// To leave out the dashboards the render check found a difference on:
//
//   php Modules/dashboard/tools/convert_text.php --render --faulty-ids > /var/tmp/faulty.txt
//   php Modules/dashboard/tools/migrate_widgets.php --write --skip-ids=/var/tmp/faulty.txt
//
// Run again afterwards with --list for the census of what is left: each
// dashboard still holding an old widget, its owner and why.

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
    echo "usage: php migrate_widgets.php [options]\n";
    echo "  --write           write the changed documents, without this nothing changes\n";
    echo "  --id=N            one dashboard only\n";
    echo "  --skip-ids=FILE   leave out the ids in this file, one per line\n";
    echo "  --show=CODE       print examples of one reason for keeping a widget\n";
    echo "  --samples=N       how many examples to print, default 5\n";
    echo "  --list            list each dashboard still holding an old widget\n";
    echo "  --limit=N         stop after N dashboards\n";
    exit(0);
}

$missing = widget_registry_missing();
if (count($missing)) {
    fwrite(STDERR, "Refusing to run. These widgets have no declaration:\n");
    foreach ($missing as $script => $declaration) fwrite(STDERR, "  $script\n");
    fwrite(STDERR, "Generate them with: node Modules/dashboard/tools/extract_registry.js\n");
    exit(1);
}
if (widget_registry_option('text', 'text') === false
    || widget_registry_option('image', 'src') === false
    || widget_registry_option('panel', 'colour') === false) {
    die("The text, image and panel widgets have no declaration. Generate it with:\n"
        . "  node Modules/dashboard/tools/extract_registry.js\n");
}

$write = isset($opts['write']);
$list = isset($opts['list']);
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$only = isset($opts['id']) ? (int) $opts['id'] : 0;
$show = isset($opts['show']) ? $opts['show'] : null;
$samples = isset($opts['samples']) ? (int) $opts['samples'] : 5;

$skip = array();
if (isset($opts['skip-ids'])) {
    $lines = @file($opts['skip-ids'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) die("Cannot read " . $opts['skip-ids'] . "\n");
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '' && ctype_digit($line)) $skip[(int) $line] = true;
    }
    echo "Leaving out " . count($skip) . " dashboards listed in " . $opts['skip-ids'] . "\n\n";
}

chdir($root);
require "process_settings.php";

$mysqli = @new mysqli(
    $settings["sql"]["server"],
    $settings["sql"]["username"],
    $settings["sql"]["password"],
    $settings["sql"]["database"],
    $settings["sql"]["port"]
);
if ($mysqli->connect_error) die("Cannot connect to database: " . $mysqli->connect_error . "\n");
$mysqli->set_charset("utf8mb4");

$check = $mysqli->query("SHOW COLUMNS FROM dashboard LIKE 'content_json'");
if (!$check || !$check->num_rows) {
    die("The dashboard table has no content_json column. Run the database update first.\n");
}

$sql = "SELECT id, userid, content, content_json FROM dashboard ORDER BY id";
if ($only) $sql = "SELECT id, userid, content, content_json FROM dashboard WHERE id=" . $only;

// Read into memory rather than streaming, because the same connection is used
// to write as we go.
$result = $mysqli->query($sql);
if (!$result) die("Query failed: " . $mysqli->error . "\n");

$rows = array();
while ($row = $result->fetch_assoc()) $rows[] = $row;
$result->free();

$update = $mysqli->prepare("UPDATE dashboard SET content_json=? WHERE id=?");
if (!$update) die("Cannot prepare update: " . $mysqli->error . "\n");

$total = 0;
$empty = 0;
$no_document = 0;
$skipped = 0;
$unreadable = 0;
$untouched = 0;
$changed = 0;
$written = 0;
$failed = 0;

$found = 0;
$by_type = array();
$reasons = array();
$examples = array();
$remaining = array();

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $total++;
    if ($limit > 0 && $total > $limit) { $total--; break; }

    if (isset($skip[$id])) {
        $skipped++;
        continue;
    }

    $json = $row['content_json'] === null ? '' : $row['content_json'];
    if (trim($json) === '') {
        if (trim((string) $row['content']) === '') $empty++;
        else $no_document++;
        continue;
    }

    $document = json_decode($json, true);
    if (!is_array($document) || !isset($document['widgets']) || !is_array($document['widgets'])) {
        echo "  dashboard $id holds a document that cannot be read\n";
        $unreadable++;
        continue;
    }

    $kept = array();
    $swapped = dashboard_migrate_widgets($document, $kept);
    $found += count($swapped) + count($kept);

    foreach ($swapped as $swap) {
        bump($by_type, $swap['from'] . "\t" . 'found');
        bump($by_type, $swap['from'] . "\t" . 'converted');
    }
    foreach ($kept as $keep) {
        bump($by_type, $keep['type'] . "\t" . 'found');
        bump($by_type, $keep['type'] . "\t" . 'kept');
        $code = explode(':', $keep['reason'])[0];
        bump($reasons, $code);
        if ($show === $code && count($examples) < $samples) {
            $widget = $document['widgets'][$keep['index']];
            $examples[] = "dashboard $id: " . $keep['reason'] . "\n      "
                . describe($widget);
        }
    }

    if (count($kept)) {
        $codes = array();
        foreach ($kept as $keep) bump($codes, $keep['type'] . ' ' . explode(':', $keep['reason'])[0]);
        $remaining[] = array('id' => $id, 'userid' => (int) $row['userid'], 'codes' => $codes);
    }

    if ($only) {
        foreach ($swapped as $swap) {
            echo str_pad($swap['from'], 20) . "to " . $swap['to'] . "  "
                . dashboard_convert_encode($document['widgets'][$swap['index']]) . "\n";
        }
        foreach ($kept as $keep) {
            echo str_pad($keep['type'], 20) . "kept, " . $keep['reason'] . "\n";
        }
    }

    if (!count($swapped)) {
        $untouched++;
        continue;
    }
    $changed++;

    if ($write) {
        $encoded = dashboard_convert_encode($document);
        if ($encoded === false) {
            echo "  dashboard $id could not be encoded: " . json_last_error_msg() . "\n";
            $failed++;
            continue;
        }
        $update->bind_param("si", $encoded, $id);
        if (!$update->execute()) {
            echo "  dashboard $id could not be written: " . $update->error . "\n";
            $failed++;
            continue;
        }
        $written++;
    }
}

$update->close();
$mysqli->close();

if ($show !== null) {
    if (!count($examples)) echo "No widget was kept for $show\n\n";
    else {
        echo "Examples of $show\n";
        foreach ($examples as $example) echo "    $example\n";
        echo "\n";
    }
}

echo "Dashboards:          $total\n";
echo "  empty:             $empty\n";
if ($no_document) echo "  no document yet:   $no_document (run migrate.php first)\n";
if ($skipped) echo "  left out:          $skipped\n";
if ($unreadable) echo "  unreadable:        $unreadable\n";
echo "  nothing to change: $untouched\n";
echo "  changed:           $changed\n";
if ($failed) echo "  failed:            $failed\n";

$converted = 0;
$kept_total = 0;
foreach ($by_type as $key => $count) {
    if (substr($key, -10) === "\tconverted") $converted += $count;
    if (substr($key, -5) === "\tkept") $kept_total += $count;
}

echo "\nOld widgets:         $found\n";
printf("  converted:         %d%s\n", $converted, share($converted, $found));
printf("  kept:              %d%s\n", $kept_total, share($kept_total, $found));

if (count($by_type)) {
    echo "\nBy widget type\n";
    column_table($by_type, array('found', 'converted', 'kept'), $found);
}

if (count($reasons)) {
    echo "\nWhy a widget was kept\n";
    arsort($reasons);
    foreach ($reasons as $code => $count) {
        printf("  %-32s %6d%s\n", $code, $count, share($count, $found));
    }
    echo "\n  Use --show=CODE for examples.\n";
}

echo "\nDashboards still holding an old widget: " . count($remaining) . "\n";
if ($list && count($remaining)) {
    echo "\n  id      userid  widgets kept\n";
    foreach ($remaining as $entry) {
        $parts = array();
        foreach ($entry['codes'] as $code => $count) $parts[] = "$code($count)";
        printf("  %-7d %-7d %s\n", $entry['id'], $entry['userid'], implode(' ', $parts));
    }
} elseif (count($remaining)) {
    echo "  Add --list to see them.\n";
}

if ($write) {
    echo "\nWritten to content_json: $written\n";
} else {
    echo "\nNothing was changed. Add --write to store the documents.\n";
}

exit($failed ? 1 : 0);

// ---------------------------------------------------------------------------
// Reporting
// ---------------------------------------------------------------------------

// One line saying what an old widget holds, for an example.
function describe($widget)
{
    $parts = array();
    foreach (array('w', 'h') as $key) {
        if (isset($widget[$key])) {
            $unit = isset($widget[$key . 'unit']) && $widget[$key . 'unit'] === 'pc' ? '%' : 'px';
            $parts[] = $key . '=' . $widget[$key] . $unit;
        }
    }
    if (!empty($widget['style'])) $parts[] = 'style=' . json_encode($widget['style']);
    if (isset($widget['html']) && trim((string) $widget['html']) !== '') {
        $parts[] = 'html=' . dashboard_convert_snippet($widget['html']);
    }
    return implode(' ', $parts);
}

function bump(&$arr, $key, $n = 1)
{
    if (!isset($arr[$key])) $arr[$key] = 0;
    $arr[$key] += $n;
}

function share($n, $total)
{
    if (!$total) return '';
    return sprintf(' (%.1f%%)', 100 * $n / $total);
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

    printf("  %-20s", '');
    foreach ($columns as $column) printf(" %10s", $column);
    echo "\n";
    foreach ($rows as $name => $cells) {
        printf("  %-20s", $name);
        foreach ($columns as $column) {
            printf(" %10d", isset($cells[$column]) ? $cells[$column] : 0);
        }
        if ($total && isset($cells['found'])) printf("%s", share($cells['found'], $total));
        echo "\n";
    }
}
