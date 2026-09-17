<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Stage 4 of the move to JSON dashboard content.
// Converts the old paragraph, heading and heading-center widgets into the text
// and image widgets and reports the result. Nothing is written to the
// database.
//
// Run from the emoncms root:
//   php Modules/dashboard/tools/convert_text.php
//   php Modules/dashboard/tools/convert_text.php --in=/var/tmp/census.jsonl
//   php Modules/dashboard/tools/convert_text.php --render
//   php Modules/dashboard/tools/convert_text.php --show=style_not_carried
//   php Modules/dashboard/tools/convert_text.php --id=1861
//
// Widgets are sorted into the same tiers as census.php, so the conversion
// rate can be compared with the census prediction. A large difference means
// the converter is wrong.
//
// --render adds the roundtrip check: each converted widget is drawn both ways
// in a browser and the two are compared. See text_render_check.php.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

$root = dirname(__FILE__) . "/../../..";
require_once dirname(__FILE__) . "/../dashboard_convert.php";
require_once dirname(__FILE__) . "/text_render_check.php";

$opts = array();
foreach (array_slice($argv, 1) as $a) {
    if (substr($a, 0, 2) !== '--') continue;
    $a = substr($a, 2);
    $eq = strpos($a, '=');
    if ($eq === false) $opts[$a] = true;
    else $opts[substr($a, 0, $eq)] = substr($a, $eq + 1);
}

if (isset($opts['help'])) {
    echo "usage: php convert_text.php [options]\n";
    echo "  --in=FILE     read an export rather than the database\n";
    echo "  --id=N        convert one dashboard and print its text widgets\n";
    echo "  --render      draw each conversion both ways and compare\n";
    echo "  --faulty-ids  with --render, print only the ids of the dashboards where a\n";
    echo "                conversion drew differently, for migrate_widgets.php --skip-ids\n";
    echo "  --show=CODE   print examples of one reason for refusing\n";
    echo "  --samples=N   how many examples to print, default 5\n";
    echo "  --limit=N     stop after N dashboards\n";
    exit(0);
}

$missing = widget_registry_missing();
if (count($missing)) {
    fwrite(STDERR, "Warning: these widgets have no declaration, so their types will\n"
        . "convert as unknown and their options will not be checked:\n");
    foreach ($missing as $script => $declaration) fwrite(STDERR, "  $script\n");
    fwrite(STDERR, "Generate them with: node Modules/dashboard/tools/extract_registry.js\n\n");
}

if (widget_registry_option('text', 'text') === false
    || widget_registry_option('image', 'src') === false) {
    die("The text and image widgets have no declaration. Generate it with:\n"
        . "  node Modules/dashboard/tools/extract_registry.js\n");
}

$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$samples = isset($opts['samples']) ? (int) $opts['samples'] : 5;
$show = isset($opts['show']) ? $opts['show'] : null;
$only = isset($opts['id']) ? (int) $opts['id'] : 0;
$render = isset($opts['render']);
$ids_only = isset($opts['faulty-ids']);
if ($ids_only && !$render) die("--faulty-ids needs --render\n");

// Pairs sent to the browser per run. Each pair is two boxes on one page.
$batch = 200;

$old_types = array_keys(dashboard_convert_text_widget_defaults());

$dashboards = 0;
$found = 0;
$converted = 0;
$by_tier = array();
$by_type = array();
$reasons = array();
$examples = array();
$verdicts = array('identical' => 0, 'differs' => 0, 'not_compared' => 0);
$differences = array();
$render_error = '';
$pending = array();
$faulty_ids = array();
$heights = array();
$height_only = 0;

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
        $tier = text_tier(isset($widget['html']) ? $widget['html'] : '');
        bump($by_tier, $tier . "\t" . 'found');
        bump($by_type, $widget['type'] . "\t" . 'found');

        $reason = '';
        $new = dashboard_convert_text_widget($widget, $reason);

        if ($widget['type'] !== 'paragraph') {
            heading_height($widget, $new, $reason, $heights, $height_only);
        }

        if ($only) {
            echo str_pad($widget['type'], 16) . str_pad($tier, 28)
                . ($new === null ? "refused $reason"
                                 : dashboard_convert_encode($new)) . "\n";
            continue;
        }

        if ($new === null) {
            $code = explode(':', $reason)[0];
            bump($reasons, $code);
            bump($by_tier, $tier . "\t" . 'refused');
            bump($by_type, $widget['type'] . "\t" . 'refused');
            if ($show === $code && count($examples) < $samples) {
                $examples[] = "dashboard " . $row['id'] . ": " . $reason . "\n      "
                    . dashboard_convert_snippet(isset($widget['html']) ? $widget['html'] : '');
            }
            continue;
        }

        $converted++;
        bump($by_tier, $tier . "\t" . 'converted');
        bump($by_type, $widget['type'] . "\t" . 'converted');

        if (!$render) continue;

        $pending[] = array('old' => $widget, 'new' => $new, 'tier' => $tier, 'id' => (int) $row['id']);
        if (count($pending) >= $batch) {
            run_batch($pending, $verdicts, $differences, $by_tier, $render_error, $faulty_ids);
            $pending = array();
        }
    }
}

if ($only) {
    if (!$found) fwrite(STDERR, "No text widget in dashboard $only\n");
    exit($found ? 0 : 1);
}

if ($render && count($pending)) {
    run_batch($pending, $verdicts, $differences, $by_tier, $render_error, $faulty_ids);
}

if ($ids_only) {
    if ($render_error !== '') {
        fwrite(STDERR, $render_error . "\n");
        exit(1);
    }
    $faulty_ids = array_unique($faulty_ids);
    sort($faulty_ids);
    foreach ($faulty_ids as $id) echo $id . "\n";
    exit(0);
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
echo "Old text widgets: $found\n\n";

echo "Converted\n";
printf("  converted:     %d%s\n", $converted, share($converted, $found));
printf("  refused:       %d%s\n", $found - $converted, share($found - $converted, $found));

echo "\nBy what the widget holds\n";
column_table($by_tier, array('found', 'converted', 'refused'), $found);

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

if (count($heights)) {
    echo "\nHeading heights\n";
    echo "  A heading converts only at " . DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT . "px, where its top padding\n";
    echo "  and the centred text widget draw the same. At another height the\n";
    echo "  text would move by half the difference.\n";
    echo "  kept only for their height:  $height_only\n\n";
    printf("  %-12s %8s %8s   %s\n", 'height', 'headings', 'kept', 'text would move by');
    uksort($heights, 'height_order');
    $shown = 0;
    foreach ($heights as $height => $counts) {
        if ($shown++ >= 25) {
            echo "  ...\n";
            break;
        }
        $kept = $counts['kept'];
        $move = '';
        if (substr($height, -2) === 'px') {
            $delta = ((int) $height - DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT) / 2;
            if ($delta != 0) $move = sprintf('%+dpx', $delta);
        }
        printf("  %-12s %8d %8d   %s\n", $height, $counts['found'], $kept, $move);
    }
}

if ($render) {
    echo "\nDrawn both ways and compared\n";
    printf("  identical:     %d%s\n", $verdicts['identical'], share($verdicts['identical'], $converted));
    printf("  differs:       %d%s\n", $verdicts['differs'], share($verdicts['differs'], $converted));
    printf("  not compared:  %d%s\n", $verdicts['not_compared'], share($verdicts['not_compared'], $converted));

    if (count($differences)) {
        echo "\n  What differed\n";
        arsort($differences);
        foreach ($differences as $detail => $count) {
            printf("    %-30s %6d\n", $detail, $count);
        }
    }
    if ($render_error !== '') echo "\n  " . $render_error . "\n";
} else {
    echo "\nRun again with --render for the roundtrip check.\n";
}

// ---------------------------------------------------------------------------
// Reporting
// ---------------------------------------------------------------------------

function run_batch(&$pending, &$verdicts, &$differences, &$by_tier, &$render_error, &$faulty_ids)
{
    $error = '';
    $results = text_render_check($pending, $error);
    if ($error !== '' && $render_error === '') $render_error = $error;

    foreach ($results as $i => $result) {
        $verdicts[$result['verdict']]++;
        bump($by_tier, $pending[$i]['tier'] . "\t" . $result['verdict']);
        if ($result['detail'] !== '') bump($differences, $result['verdict'] . ' ' . $result['detail']);
        if ($result['verdict'] === 'differs') $faulty_ids[] = $pending[$i]['id'];
    }
}

// Counts a heading by its box height, and whether it was kept for that alone.
function heading_height($widget, $new, $reason, &$heights, &$height_only)
{
    $unit = isset($widget['hunit']) && $widget['hunit'] === 'pc' ? '%' : 'px';
    $height = (isset($widget['h']) ? $widget['h'] : '?') . $unit;
    if (!isset($heights[$height])) $heights[$height] = array('found' => 0, 'kept' => 0);
    $heights[$height]['found']++;

    if ($new !== null || strpos($reason, 'heading_height') !== 0) return;
    $heights[$height]['kept']++;

    // Would it have converted at the default height
    $retry = $widget;
    $retry['h'] = DASHBOARD_CONVERT_TEXT_HEADING_HEIGHT;
    unset($retry['hunit']);
    $again = '';
    if (dashboard_convert_text_widget($retry, $again) !== null) $height_only++;
}

// Heights in order of size, px before per cent.
function height_order($a, $b)
{
    $pa = substr($a, -2) === 'px';
    $pb = substr($b, -2) === 'px';
    if ($pa !== $pb) return $pa ? -1 : 1;
    return (int) $a - (int) $b;
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
// Tiers
// ---------------------------------------------------------------------------

// The same tiers as census.php, so the numbers can be compared with the
// census table. Kept as a copy because census.php is a script, not a library.
function text_tier($html)
{
    $inline = array('b', 'strong', 'i', 'em', 'u', 'sub', 'sup', 'font', 'span', 'small');
    $block = array('p', 'div', 'center', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    $table = array('table', 'thead', 'tbody', 'tr', 'th', 'td', 'colgroup', 'col', 'ul', 'ol', 'li');

    $root = dashboard_convert_parse($html);
    if ($root === null) return 'unparsable';

    $tags = array();
    $has_text = false;
    $stack = array($root);
    while (count($stack)) {
        $current = array_pop($stack);
        foreach ($current->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->nodeValue) !== '') $has_text = true;
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            bump($tags, strtolower($child->nodeName));
            $stack[] = $child;
        }
    }

    $names = array_keys($tags);
    $expected = array_merge($inline, $block, $table, array('a', 'img', 'br'));

    if (count(array_diff($names, $expected))) return 'embed or nested widget';
    if (count(array_intersect($names, $table))) return 'table or list';
    if (!count($tags)) return $has_text ? 'plain text' : 'empty';
    if (!$has_text && in_array('img', $names)) return 'image, no text';
    if (text_tier_one_style($root)) return 'one style throughout';
    if (count(array_intersect($names, $block))) return 'mixed, across blocks';
    if (in_array('a', $names)) return 'mixed, inline with a link';
    return 'mixed, inline';
}

// True when every element wraps all of the text, so one set of options can
// describe the whole box. A br is ignored.
function text_tier_one_style($node)
{
    while (true) {
        $elements = array();
        $text = false;
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->nodeValue) !== '') $text = true;
            } else if ($child->nodeType === XML_ELEMENT_NODE) {
                if (strtolower($child->nodeName) !== 'br') $elements[] = $child;
            }
        }
        if (!count($elements)) return true;
        if (count($elements) > 1) return false;
        if ($text) return false;
        $node = $elements[0];
    }
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
