<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Converts stored dashboard content and writes the JSON document to the
// content_json column. The content column is not touched.
//
// The switch over converts a dashboard the first time it is loaded, so this is
// not needed to migrate an install. It is here so that a large install can be
// converted and checked before the switch over is deployed, which means no one
// sees a converted dashboard until the numbers have been looked at.
//
// Run from the emoncms root. It reports and changes nothing without --write:
//
//   php Modules/dashboard/tools/migrate.php
//   php Modules/dashboard/tools/migrate.php --write
//
// To leave out the dashboards the round trip check is unhappy with:
//
//   php Modules/dashboard/tools/roundtrip.php --faulty-ids > /var/tmp/faulty.txt
//   php Modules/dashboard/tools/migrate.php --write --skip-ids=/var/tmp/faulty.txt

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
    echo "usage: php migrate.php [options]\n";
    echo "  --write           write content_json, without this nothing changes\n";
    echo "  --id=N            one dashboard only\n";
    echo "  --skip-ids=FILE   leave out the ids in this file, one per line\n";
    echo "  --force           convert again even where content_json is already set\n";
    echo "  --limit=N         stop after N dashboards\n";
    exit(0);
}

$missing = widget_registry_missing();
if (count($missing)) {
    fwrite(STDERR, "Refusing to run. These widgets have no declaration, so their types\n"
        . "would be converted as unknown and their options would not be checked:\n");
    foreach ($missing as $script => $declaration) fwrite(STDERR, "  $script\n");
    fwrite(STDERR, "Generate them with: node Modules/dashboard/tools/extract_registry.js\n");
    exit(1);
}

$write = isset($opts['write']);
$force = isset($opts['force']);
$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$only = isset($opts['id']) ? (int) $opts['id'] : 0;

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

$sql = "SELECT id, content, content_json FROM dashboard ORDER BY id";
if ($only) $sql = "SELECT id, content, content_json FROM dashboard WHERE id=" . $only;

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
$already = 0;
$skipped = 0;
$converted = 0;
$nothing = 0;
$failed = 0;
$written = 0;
$html_bytes = 0;
$json_bytes = 0;
$nothing_ids = array();

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $total++;
    if ($limit > 0 && $total > $limit) { $total--; break; }

    if (isset($skip[$id])) {
        $skipped++;
        continue;
    }

    $content = $row['content'] === null ? '' : $row['content'];
    $existing = $row['content_json'] === null ? '' : $row['content_json'];

    if (trim($existing) !== '' && !$force) {
        $already++;
        continue;
    }
    if (trim($content) === '') {
        $empty++;
        continue;
    }

    $result = dashboard_convert($content);
    $document = $result['document'];

    if ($document === null) {
        echo "  dashboard $id could not be read\n";
        $failed++;
        continue;
    }
    if (!count($document['widgets'])) {
        // Content with no widget in it. Left alone, so the html stays the only
        // copy and can be looked at.
        $nothing++;
        $nothing_ids[] = $id;
        continue;
    }

    $json = dashboard_convert_encode($document);
    if ($json === false) {
        echo "  dashboard $id could not be encoded: " . json_last_error_msg() . "\n";
        $failed++;
        continue;
    }

    $converted++;
    $html_bytes += strlen($content);
    $json_bytes += strlen($json);

    if ($write) {
        $update->bind_param("si", $json, $id);
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

echo "Dashboards:        $total\n";
echo "  empty:           $empty\n";
if ($already) echo "  already done:    $already\n";
if ($skipped) echo "  left out:        $skipped\n";
echo "  no widgets:      $nothing" . ($nothing ? " (" . implode(' ', array_slice($nothing_ids, 0, 10))
    . (count($nothing_ids) > 10 ? ' ...' : '') . ")" : "") . "\n";
echo "  converted:       $converted\n";
if ($failed) echo "  failed:          $failed\n";

if ($html_bytes) {
    printf("  size:            %s of html to %s of json (%d%%)\n",
        bytes($html_bytes), bytes($json_bytes), round(100 * $json_bytes / $html_bytes));
}

if ($write) {
    echo "\nWritten to content_json: $written\n";
    echo "The content column was not changed.\n";
} else {
    echo "\nNothing was changed. Add --write to store the documents.\n";
}

exit($failed ? 1 : 0);

function bytes($n)
{
    if ($n < 1024 * 1024) return round($n / 1024) . 'KB';
    return round($n / 1024 / 1024, 1) . 'MB';
}
