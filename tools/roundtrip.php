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
// Converts stored content, renders it back, and compares the result against
// what was there before. Read only, it writes nothing to the database.
//
// Run from the emoncms root:
//   php Modules/dashboard/tools/roundtrip.php
//   php Modules/dashboard/tools/roundtrip.php --id=1861
//   php Modules/dashboard/tools/roundtrip.php --show=option_changed
//
// This is the measure of whether a dashboard can be migrated. The converter
// report says nothing an author wrote was dropped. This says the dashboard
// draws from the new column with the same widgets, in the same places, with
// the same options and the same text.
//
// The comparison reads both sides with the same deliberately simple extraction
// that knows nothing about the converter, so a fault in the converter and a
// fault in the renderer both show up here.
//
// A difference is expected when it is something the migration set out to drop:
// generated markup, designer artefacts, browser extension attributes, empty
// options, options no widget declares and values no widget accepts. Anything
// else is a fault and is counted as one.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

$root = dirname(__FILE__) . "/../../..";
require_once dirname(__FILE__) . "/../dashboard_render.php";

$opts = array();
foreach (array_slice($argv, 1) as $a) {
    if (substr($a, 0, 2) !== '--') continue;
    $a = substr($a, 2);
    $eq = strpos($a, '=');
    if ($eq === false) $opts[$a] = true;
    else $opts[substr($a, 0, $eq)] = substr($a, $eq + 1);
}

if (isset($opts['help'])) {
    echo "usage: php roundtrip.php [options]\n";
    echo "  --in=FILE     read an export rather than the database\n";
    echo "  --id=N        check one dashboard and print every difference\n";
    echo "  --show=KIND   print examples of one kind of difference\n";
    echo "  --samples=N   how many examples to print, default 5\n";
    echo "  --limit=N     stop after N dashboards\n";
    exit(0);
}

$missing = widget_registry_missing();
if (count($missing)) {
    fwrite(STDERR, "Warning: these widgets have no declaration, so their options cannot\n"
        . "be checked and every difference on them will be counted as a fault:\n");
    foreach ($missing as $script => $declaration) fwrite(STDERR, "  $script\n");
    fwrite(STDERR, "Generate them with: node Modules/dashboard/tools/extract_registry.js\n\n");
}

$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$samples = isset($opts['samples']) ? (int) $opts['samples'] : 5;
$show = isset($opts['show']) ? $opts['show'] : null;
$only = isset($opts['id']) ? (int) $opts['id'] : 0;

$rows = isset($opts['in']) ? rows_from_export($opts['in']) : rows_from_database($root);

$total = 0;
$empty = 0;
$identical = 0;
$expected_only = 0;
$faulty = 0;
$kinds = array();
$examples = array();
$faulty_ids = array();

foreach ($rows as $row) {
    if ($only && (int) $row['id'] !== $only) continue;
    $total++;
    if ($limit > 0 && $total > $limit) { $total--; break; }

    if (trim($row['content']) === '') {
        $empty++;
        continue;
    }

    $converted = dashboard_convert($row['content']);
    $rendered = dashboard_render($converted['document']);

    $differences = compare(
        extract_shape($row['content']),
        extract_shape($rendered['html'])
    );

    foreach ($rendered['errors'] as $error) {
        // The renderer rejecting what the converter produced is always a fault.
        $differences[] = array('kind' => 'renderer_' . $error['code'],
            'expected' => false, 'detail' => $error['detail']);
    }

    if ($only) {
        echo "dashboard " . $row['id'] . "\n\n";
        if (!count($differences)) echo "  identical\n";
        foreach ($differences as $difference) {
            printf("  %s %-28s %s\n", $difference['expected'] ? ' ' : '!',
                $difference['kind'], $difference['detail']);
        }
        exit(count(array_filter($differences, function ($d) { return !$d['expected']; })) ? 1 : 0);
    }

    $faults = 0;
    $seen = array();
    foreach ($differences as $difference) {
        $kind = $difference['kind'];
        if (!isset($seen[$kind])) {
            $seen[$kind] = true;
            if (!isset($kinds[$kind])) {
                $kinds[$kind] = array('dashboards' => 0, 'total' => 0,
                    'expected' => $difference['expected']);
            }
            $kinds[$kind]['dashboards']++;
        }
        $kinds[$kind]['total']++;
        if (!$difference['expected']) $faults++;

        if ($show === $kind && count($examples) < $samples) {
            $examples[] = "dashboard " . $row['id']
                . ($row['userid'] === null ? '' : " user " . $row['userid'])
                . ": " . $difference['detail'];
        }
    }

    if ($faults) {
        $faulty++;
        $faulty_ids[$row['id']] = $faults;
    } else if (count($differences)) {
        $expected_only++;
    } else {
        $identical++;
    }
}

if ($only) {
    fwrite(STDERR, "No dashboard with id $only\n");
    exit(1);
}

if ($show !== null) {
    if (!count($examples)) {
        echo "No dashboard showed $show\n";
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

echo "Round trip\n";
printf("  identical:     %d%s\n", $identical, share($identical, $withcontent));
printf("  as intended:   %d%s\n", $expected_only, share($expected_only, $withcontent));
printf("  faults:        %d%s\n", $faulty, share($faulty, $withcontent));
echo "\n  identical means nothing changed at all. As intended means the only\n";
echo "  changes were ones the migration set out to make. Both are safe to\n";
echo "  migrate, which is " . ($identical + $expected_only) . " of $withcontent"
    . share($identical + $expected_only, $withcontent) . "\n";

if (count($kinds)) {
    echo "\nDifferences, by how many dashboards showed each\n";
    uasort($kinds, function ($a, $b) { return $b['dashboards'] - $a['dashboards']; });
    foreach ($kinds as $kind => $count) {
        printf("%s %-32s %5d dashboards %8d in total\n", $count['expected'] ? '  ' : ' !',
            $kind, $count['dashboards'], $count['total']);
    }
    echo "\n  ! a fault. Use --show=KIND for examples, or --id=N for one dashboard.\n";
}

if (count($faulty_ids)) {
    arsort($faulty_ids);
    echo "\nDashboards with the most faults\n";
    $shown = 0;
    foreach ($faulty_ids as $id => $count) {
        printf("  dashboard %-8s %d\n", $id, $count);
        if (++$shown >= $samples) break;
    }
}

exit($faulty ? 1 : 0);

// ---------------------------------------------------------------------------
// Shape
// ---------------------------------------------------------------------------

// Reads the top level widget boxes out of a piece of dashboard html. This
// knows nothing about the converter on purpose, so it can be used to read both
// sides of the comparison.
function extract_shape($html)
{
    $shape = array();
    if (trim($html) === '') return $shape;

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    libxml_clear_errors();
    $ok = $doc->loadHTML('<div>' . dashboard_convert_to_entities($html) . '</div>',
                         LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    if (!$ok || !$doc->documentElement) return $shape;

    foreach ($doc->documentElement->childNodes as $node) {
        if ($node->nodeType !== XML_ELEMENT_NODE) continue;

        $class = $node->hasAttribute('class') ? trim($node->getAttribute('class')) : '';
        if ($class === '') continue;

        $style = dashboard_convert_parse_style(
            $node->hasAttribute('style') ? $node->getAttribute('style') : '');

        $attributes = array();
        foreach ($node->attributes as $attribute) {
            $name = strtolower($attribute->nodeName);
            if ($name === 'id' || $name === 'class' || $name === 'style') continue;
            $attributes[$name] = (string) $attribute->nodeValue;
        }

        $shape[] = array(
            'type' => $class,
            'geometry' => array(
                'left' => number($style, 'left'),
                'top' => number($style, 'top'),
                'width' => number($style, 'width'),
                'height' => number($style, 'height'),
                'wunit' => unit($style, 'width'),
                'hunit' => unit($style, 'height')
            ),
            'attributes' => $attributes,
            'text' => text_of($node),
            'tags' => tags_of($node),
            'nested' => nested_of($node)
        );
    }
    return $shape;
}

function number($style, $property)
{
    if (!isset($style[$property])) return null;
    if (!preg_match('/-?\d+(\.\d+)?/', $style[$property], $match)) return null;
    return (int) round((float) $match[0]);
}

function unit($style, $property)
{
    if (!isset($style[$property])) return null;
    return strpos($style[$property], '%') === false ? 'px' : 'pc';
}

// A widget nested inside another one is dropped on purpose, so it is counted
// on its own and left out of the text and the tags. Otherwise its div would
// read as an allowed tag that went missing.
function is_nested_widget($node)
{
    if (!$node->hasAttribute('class')) return false;
    $registry = widget_registry();
    return isset($registry[trim($node->getAttribute('class'))]);
}

function nested_of($node)
{
    $count = 0;
    foreach ($node->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) continue;
        if (is_nested_widget($child)) {
            $count++;
            continue;
        }
        $count += nested_of($child);
    }
    return $count;
}

// The visible text of a box, with whitespace collapsed. Generated content is
// left out, so the last reading a render script drew is not compared.
function text_of($node)
{
    $text = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            $text .= $child->textContent;
        } else if ($child->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($child->nodeName);
            if ($tag === 'canvas' || $tag === 'iframe' || $tag === 'script') continue;
            if ($child->hasAttribute('id')
                && preg_match('/^can-.*-tooltip-\d+$/', $child->getAttribute('id'))) continue;
            if (is_nested_widget($child)) continue;
            $text .= text_of($child);
        }
    }
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function tags_of($node)
{
    $tags = array();
    foreach ($node->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) continue;
        if (is_nested_widget($child)) continue;
        $tag = strtolower($child->nodeName);
        if (!isset($tags[$tag])) $tags[$tag] = 0;
        $tags[$tag]++;
        foreach (tags_of($child) as $name => $count) {
            if (!isset($tags[$name])) $tags[$name] = 0;
            $tags[$name] += $count;
        }
    }
    return $tags;
}

// ---------------------------------------------------------------------------
// Comparison
// ---------------------------------------------------------------------------

function compare($before, $after)
{
    $differences = array();
    $registry = widget_registry();

    if (count($before) !== count($after)) {
        $differences[] = difference('widget_count_changed', false,
            count($before) . ' became ' . count($after));
    }

    $shared = min(count($before), count($after));
    for ($i = 0; $i < $shared; $i++) {
        compare_widget($before[$i], $after[$i], $i, $registry, $differences);
    }

    return $differences;
}

function compare_widget($before, $after, $i, $registry, &$differences)
{
    $where = "widget $i " . $before['type'];

    if ($before['type'] !== $after['type']) {
        $differences[] = difference('widget_type_changed', false,
            "$where became " . $after['type']);
        return;
    }

    foreach ($before['geometry'] as $property => $value) {
        if ($value === $after['geometry'][$property]) continue;
        // A widget whose style was destroyed has no geometry to preserve, and
        // the renderer has to put it somewhere.
        $expected = $value === null;
        $differences[] = difference(
            $expected ? 'geometry_supplied' : 'geometry_changed', $expected,
            "$where $property " . describe($value) . ' became '
            . describe($after['geometry'][$property]));
    }

    $known = isset($registry[$before['type']]);

    foreach ($before['attributes'] as $name => $value) {
        if (isset($after['attributes'][$name])) {
            if ($after['attributes'][$name] !== $value) {
                $differences[] = difference('option_changed', false,
                    "$where $name " . describe($value) . ' became '
                    . describe($after['attributes'][$name]));
            }
            continue;
        }
        $reason = why_dropped($name, $value, $before['type'], $known, $before['attributes']);
        $differences[] = difference($reason['kind'], $reason['expected'],
            "$where $name=" . dashboard_convert_snippet($value));
    }

    foreach ($after['attributes'] as $name => $value) {
        if (isset($before['attributes'][$name])) continue;
        $differences[] = difference('option_added', false, "$where $name=" . describe($value));
    }

    if ($before['nested'] > $after['nested']) {
        $differences[] = difference('nested_widget_dropped', true,
            "$where " . $before['nested'] . ' nested, ' . $after['nested'] . ' kept');
    } else if ($after['nested'] > $before['nested']) {
        $differences[] = difference('nested_widget_added', false, $where);
    }

    // Text is only compared where it is author written. On a data widget it is
    // the last reading the render script drew, which is dropped on purpose.
    if (dashboard_convert_holds_html($before['type'], $known, $registry)) {
        if ($before['text'] !== $after['text']) {
            $differences[] = difference('text_changed', false,
                "$where " . dashboard_convert_snippet($before['text']) . ' became '
                . dashboard_convert_snippet($after['text']));
        }
        compare_tags($before, $after, $where, $differences);
    } else if ($after['text'] !== '') {
        $differences[] = difference('text_added', false,
            "$where " . dashboard_convert_snippet($after['text']));
    }
}

function compare_tags($before, $after, $where, &$differences)
{
    $allowed = dashboard_convert_allowed_elements();
    $strip = dashboard_convert_stripped_elements();

    foreach ($before['tags'] as $tag => $count) {
        $now = isset($after['tags'][$tag]) ? $after['tags'][$tag] : 0;
        if ($now >= $count) continue;

        $expected = in_array($tag, $strip) || !in_array($tag, $allowed);
        $differences[] = difference($expected ? 'tag_dropped' : 'tag_lost', $expected,
            "$where <$tag> " . $count . ' became ' . $now);
    }
}

// Says why an attribute that was there is not there any more, and whether that
// was the intention. The rules follow the converter, but are worked out from
// the stored attribute rather than read back out of the converter, so the two
// agreeing means something.
function why_dropped($name, $value, $type, $known, $attributes)
{
    if (in_array($name, dashboard_convert_extension_attributes())) {
        return array('kind' => 'browser_extension_attribute', 'expected' => true);
    }
    if (preg_match('/[:;()\/]|^[0-9]/', $name)) {
        return array('kind' => 'broken_style_attribute', 'expected' => true);
    }
    if ($value === '') {
        // Either a fragment of a broken style or an option left empty. Both
        // are meant to go.
        foreach ($attributes as $other => $ignored) {
            if (preg_match('/[:;()\/]|^[0-9]/', $other)) {
                return array('kind' => 'broken_style_attribute', 'expected' => true);
            }
        }
        return array('kind' => 'empty_option_omitted', 'expected' => true);
    }
    if (!$known) {
        // Nothing on an unknown widget should have been dropped.
        return array('kind' => 'option_lost', 'expected' => false);
    }
    if (substr($name, -9) === '_dropdown'
        && widget_registry_option($type, substr($name, 0, -9))) {
        return array('kind' => 'designer_artefact_attribute', 'expected' => true);
    }

    $option = widget_registry_option($type, $name);
    if ($option === false) {
        return array('kind' => 'option_undeclared_dropped', 'expected' => true);
    }
    if (!dashboard_convert_option_valid($option, $value)) {
        return array('kind' => 'option_value_rejected', 'expected' => true);
    }
    return array('kind' => 'option_lost', 'expected' => false);
}

function difference($kind, $expected, $detail)
{
    return array('kind' => $kind, 'expected' => $expected, 'detail' => $detail);
}

function describe($value)
{
    if ($value === null) return 'unset';
    return '"' . dashboard_convert_snippet($value) . '"';
}

function share($count, $total)
{
    if (!$total) return '';
    return sprintf("  %.1f%%", 100 * $count / $total);
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
