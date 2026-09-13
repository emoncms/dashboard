<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Stage 3 of the move to JSON dashboard content.
// Converts stored dashboard content and reports how it went. Read only, it
// writes nothing to the database.
//
// Run from the emoncms root:
//   php Modules/dashboard/tools/convert.php
//   php Modules/dashboard/tools/convert.php --out=/var/tmp/converted.jsonl
//   php Modules/dashboard/tools/convert.php --show=iframe_dropped
//   php Modules/dashboard/tools/convert.php --id=1861
//
// Dashboards are sorted into three groups:
//
//   clean     converted with nothing dropped that an author wrote
//   warned    converted, and something an author wrote was dropped
//   failed    could not be read, or held no widget it could keep
//
// The clean group is the one that is safe to migrate without being looked at.

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
    echo "usage: php convert.php [options]\n";
    echo "  --in=FILE     read an export rather than the database\n";
    echo "  --out=FILE    write the converted documents to a JSONL file\n";
    echo "  --id=N        convert one dashboard and print it\n";
    echo "  --show=CODE   print examples of one warning code\n";
    echo "  --samples=N   how many examples to print, default 5\n";
    echo "  --limit=N     stop after N dashboards\n";
    exit(0);
}

// Warnings that mean something an author wrote did not survive. The rest are
// generated markup, artefacts and debris, which is the point of the exercise.
$authored = array(
    'unparsable', 'widget_without_type', 'widget_type_not_one_token',
    'nested_widget_dropped', 'iframe_dropped', 'tag_dropped', 'tag_unwrapped',
    'url_dropped', 'attribute_dropped', 'option_unknown_dropped',
    'option_value_dropped', 'text_outside_widget', 'position_fixed_dropped',
    'style_property_dropped', 'style_value_dropped', 'geometry_missing'
);

$missing = widget_registry_missing();
if (count($missing)) {
    fwrite(STDERR, "Warning: these widgets have no declaration, so their types will\n"
        . "convert as unknown and their options will not be checked:\n");
    foreach ($missing as $script => $declaration) fwrite(STDERR, "  $script\n");
    fwrite(STDERR, "Generate them with: node Modules/dashboard/tools/extract_registry.js\n\n");
}

$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$samples = isset($opts['samples']) ? (int) $opts['samples'] : 5;
$show = isset($opts['show']) ? $opts['show'] : null;
$only = isset($opts['id']) ? (int) $opts['id'] : 0;

$out = null;
if (isset($opts['out'])) {
    $out = fopen($opts['out'], 'w');
    if (!$out) die("Cannot write to " . $opts['out'] . "\n");
}

$rows = isset($opts['in']) ? rows_from_export($opts['in']) : rows_from_database($root);

$total = 0;
$empty = 0;
$clean = 0;
$warned = 0;
$failed = 0;
$widgets = 0;
$html_bytes = 0;
$json_bytes = 0;
$codes = array();
$examples = array();
$worst = array();

foreach ($rows as $row) {
    if ($only && (int) $row['id'] !== $only) continue;
    $total++;
    if ($limit > 0 && $total > $limit) { $total--; break; }

    if (trim($row['content']) === '') {
        $empty++;
        continue;
    }

    $result = dashboard_convert($row['content']);
    $document = $result['document'];

    if ($only) {
        echo "dashboard " . $row['id'] . "\n";
        echo dashboard_convert_encode($document, JSON_PRETTY_PRINT) . "\n";
        exit(0);
    }

    // Counted once per dashboard, so one dashboard repeating the same problem
    // does not look like many dashboards having it.
    $seen = array();
    $authored_here = 0;
    foreach ($result['warnings'] as $warning) {
        $code = $warning['code'];
        if (!isset($seen[$code])) {
            $seen[$code] = 0;
            if (!isset($codes[$code])) $codes[$code] = array('dashboards' => 0, 'total' => 0);
            $codes[$code]['dashboards']++;
        }
        $seen[$code]++;
        $codes[$code]['total']++;
        if (in_array($code, $authored)) $authored_here++;

        if ($show === $code && count($examples) < $samples) {
            $examples[] = "dashboard " . $row['id']
                . ($row['userid'] === null ? '' : " user " . $row['userid'])
                . ": " . $warning['detail'];
        }
    }

    if ($document === null || !count($document['widgets'])) {
        $failed++;
    } else if ($authored_here) {
        $warned++;
        $worst[$row['id']] = $authored_here;
    } else {
        $clean++;
    }

    if ($document !== null) {
        $widgets += count($document['widgets']);
        $html_bytes += strlen($row['content']);
        $json = dashboard_convert_encode($document);
        $json_bytes += strlen($json);
        if ($out) {
            fwrite($out, '{"id":' . (int) $row['id'] . ',"document":'
                . dashboard_convert_encode($document) . "}\n");
        }
    }
}

if ($out) fclose($out);

if ($only) {
    fwrite(STDERR, "No dashboard with id $only\n");
    exit(1);
}

if ($show !== null) {
    if (!count($examples)) {
        echo "No dashboard raised $show\n";
    } else {
        echo "Examples of $show\n";
        foreach ($examples as $example) echo "  $example\n";
    }
    echo "\n";
}

$withcontent = $total - $empty;

echo "Dashboards:      $total\n";
echo "  empty:         $empty\n";
echo "  with content:  $withcontent\n\n";

echo "Converted\n";
printf("  clean:         %d%s\n", $clean, share($clean, $withcontent));
printf("  warned:        %d%s\n", $warned, share($warned, $withcontent));
printf("  failed:        %d%s\n", $failed, share($failed, $withcontent));
echo "  widgets:       $widgets\n";

if ($html_bytes) {
    printf("  size:          %s of html to %s of json (%d%%)\n",
        bytes($html_bytes), bytes($json_bytes), round(100 * $json_bytes / $html_bytes));
}

if (count($codes)) {
    echo "\nWarnings, by how many dashboards raised each\n";
    uasort($codes, function ($a, $b) { return $b['dashboards'] - $a['dashboards']; });
    foreach ($codes as $code => $count) {
        $mark = in_array($code, $authored) ? ' *' : '  ';
        printf("%s %-32s %5d dashboards %8d in total\n", $mark, $code,
            $count['dashboards'], $count['total']);
    }
    echo "\n  * something an author wrote was dropped. Use --show=CODE for examples.\n";
}

if (count($worst)) {
    arsort($worst);
    echo "\nDashboards with the most dropped\n";
    $shown = 0;
    foreach ($worst as $id => $count) {
        printf("  dashboard %-8s %d\n", $id, $count);
        if (++$shown >= $samples) break;
    }
}

function share($count, $total)
{
    if (!$total) return '';
    return sprintf("  %.1f%%", 100 * $count / $total);
}

function bytes($n)
{
    if ($n < 1024 * 1024) return round($n / 1024) . 'KB';
    return round($n / 1024 / 1024, 1) . 'MB';
}

// ---------------------------------------------------------------------------
// Sources
// ---------------------------------------------------------------------------

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

    // Unbuffered so a large table does not have to fit in memory
    $result = $mysqli->query("SELECT id, userid, content FROM dashboard ORDER BY id",
        MYSQLI_USE_RESULT);
    if (!$result) die("Query failed: " . $mysqli->error . "\n");

    while ($row = $result->fetch_assoc()) {
        yield array(
            'id' => $row['id'],
            'userid' => $row['userid'],
            'content' => $row['content'] === null ? '' : $row['content']
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
            'userid' => null,   // the export hashes it
            'content' => $record['content']
        );
    }
    fclose($fh);
}
