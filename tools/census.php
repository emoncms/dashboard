<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Stage 1 of the move to JSON dashboard content.
// Reads the export produced by export_content.php and reports what the stored
// dashboard html actually contains. It measures, it does not convert or repair.
//
//   php Modules/dashboard/tools/census.php dashboards.jsonl --out=census
//
// Writes census.json with the full counts and census_dashboards.jsonl with one
// record per dashboard, so stage 3 can pick examples to work from.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

$argv_files = array();
$opts = array('out' => 'census', 'samples' => 10, 'root' => dirname(__FILE__) . "/../../..");
for ($i = 1; $i < $argc; $i++) {
    if (substr($argv[$i], 0, 2) === '--') {
        $parts = explode('=', substr($argv[$i], 2), 2);
        $opts[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
    } else {
        $argv_files[] = $argv[$i];
    }
}

if (isset($opts['help']) || !count($argv_files)) {
    echo "usage: php census.php EXPORT.jsonl [--out=PREFIX] [--samples=N] [--root=PATH]\n";
    echo "  --out      output prefix, default census\n";
    echo "  --samples  dashboard ids to record per finding, default 10\n";
    echo "  --root     emoncms root, used to harvest the widget registry\n";
    exit(0);
}

$infile = $argv_files[0];
if (!is_readable($infile)) die("Cannot read $infile\n");
$max_samples = (int) $opts['samples'];

// ---------------------------------------------------------------------------
// Widget registry
//
// Every widget definition in widgetlist.js and in each module's *_render.js
// opens with an "offsetx" key, so the widget names can be harvested by looking
// for quoted keys that sit directly in front of one.
// ---------------------------------------------------------------------------

function harvest_registry($root)
{
    $names = array();
    $files = array($root . "/Modules/dashboard/Views/js/widgetlist.js");
    foreach (glob($root . "/Modules/*/widget", GLOB_ONLYDIR) as $dir) {
        foreach (array_merge(glob("$dir/*_render.js"), glob("$dir/*/*_render.js")) as $f) {
            $files[] = $f;
        }
    }
    foreach ($files as $f) {
        if (!is_readable($f)) continue;
        $js = file_get_contents($f);
        // Keys may be quoted or bare, see kwhperiod_render.js for the bare form
        $re = '/(?:"([^"\r\n]+)"|\'([^\'\r\n]+)\'|([A-Za-z0-9_$-]+))'
            . '\s*:\s*\{\s*(?:"offsetx"|\'offsetx\'|offsetx)\s*:/s';
        if (preg_match_all($re, $js, $m, PREG_SET_ORDER)) {
            foreach ($m as $set) {
                $name = $set[1] !== '' ? $set[1] : (isset($set[2]) && $set[2] !== '' ? $set[2] : (isset($set[3]) ? $set[3] : ''));
                if ($name !== '') $names[$name] = true;
            }
        }
    }
    return $names;
}

$registry = harvest_registry(rtrim($opts['root'], '/'));
echo "Widget registry: " . count($registry) . " types harvested\n";
if (!count($registry)) {
    echo "  warning: none found, check --root. Unknown class counts will be meaningless.\n";
}

// ---------------------------------------------------------------------------
// Counters
// ---------------------------------------------------------------------------

$c = array(
    'dashboards' => 0,
    'empty' => 0,
    'parse_errors' => 0,
    'toplevel_class' => array(),      // widget type => count
    'toplevel_class_dashboards' => array(),
    'toplevel_tag' => array(),        // tag name of top level nodes
    'toplevel_text' => 0,             // bare text sitting outside any widget
    'multi_token_class' => 0,         // class="dial something-else"
    'no_class' => 0,
    'unknown_class' => array(),       // class not in the widget registry
    'tags' => array(),                // every element, at any depth
    'attrs_by_class' => array(),      // widget type => attribute => count
    'nested_tags_by_class' => array(),// widget type => inner tag => count
    'nested_attrs' => array(),        // attribute names below the top level
    'style_props' => array(),         // inline style property => count
    'style_values' => array(),        // property => distinct sample values
    'depth' => array(),               // nesting depth histogram
    'widgets_per_dashboard' => array(),
    'flags' => array(),               // finding => count
    'parse_error_shapes' => array(),  // libxml complaint => count
    'blank_option_attrs' => array(),  // option present but left empty, normal
    'malformed_attrs' => array(),     // fragments of an unquoted value, not normal
    'names_with_value' => array(),    // every attribute name seen holding a value
);

$samples = array();  // finding => list of dashboard ids

function sample($key, $dashid)
{
    global $samples, $max_samples;
    if (!isset($samples[$key])) $samples[$key] = array();
    if (count($samples[$key]) < $max_samples && !in_array($dashid, $samples[$key])) {
        $samples[$key][] = $dashid;
    }
}

function flag($name, $dashid)
{
    global $c;
    if (!isset($c['flags'][$name])) $c['flags'][$name] = 0;
    $c['flags'][$name]++;
    sample("flag:$name", $dashid);
}

function bump(&$arr, $key, $n = 1)
{
    if (!isset($arr[$key])) $arr[$key] = 0;
    $arr[$key] += $n;
}

// Tags that have no business in dashboard content
$tag_flags = array('script', 'iframe', 'object', 'embed', 'link', 'meta', 'base',
                   'form', 'input', 'button', 'svg', 'math', 'style', 'frame', 'frameset');

// Attributes that carry a url
$url_attrs = array('href', 'src', 'action', 'formaction', 'data', 'poster', 'xlink:href', 'background');

// ---------------------------------------------------------------------------
// Parsing
// ---------------------------------------------------------------------------

function to_entities($html)
{
    return mb_encode_numericentity($html, array(0x80, 0x10FFFF, 0, 0x1FFFFF), 'UTF-8');
}

function parse_fragment($html, &$nerrors, &$messages)
{
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    libxml_clear_errors();
    $ok = $doc->loadHTML('<div>' . to_entities($html) . '</div>',
                         LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $errs = libxml_get_errors();
    $nerrors = count($errs);
    $messages = array();
    foreach ($errs as $e) $messages[] = trim($e->message);
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    if (!$ok || !$doc->documentElement) return null;
    return $doc->documentElement;
}

// Collapse a libxml message down to its shape so the same complaint about
// different tag names counts as one kind of problem.
function error_shape($msg)
{
    $rules = array(
        '/mismatch:\s*\S+ and \S+/i' => 'mismatch: X and Y',
        '/\bTag [A-Za-z0-9_:-]+ invalid/i' => 'Tag X invalid',
        '/end tag\s*:\s*[A-Za-z0-9_:-]+/i' => 'end tag : X',
        '/Attribute [A-Za-z0-9_:.-]+ redefined/i' => 'Attribute X redefined',
        '/[A-Za-z0-9_:-]+ line \d+/' => 'X line N',
        '/\d+/' => 'N',
    );
    $m = $msg;
    foreach ($rules as $re => $to) $m = preg_replace($re, $to, $m);
    return $m;
}

function parse_style($style)
{
    $out = array();
    foreach (explode(';', $style) as $decl) {
        if (strpos($decl, ':') === false) continue;
        list($prop, $val) = explode(':', $decl, 2);
        $prop = strtolower(trim($prop));
        if ($prop === '') continue;
        $out[$prop] = trim($val);
    }
    return $out;
}

// Walk one element. $widget is the top level widget class this sits inside.
function walk($node, $depth, $widget, $dashid, &$per)
{
    global $c, $tag_flags, $url_attrs, $max_samples;

    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            if ($depth === 0 && trim($child->nodeValue) !== '') {
                $c['toplevel_text']++;
                sample('toplevel_text', $dashid);
            }
            continue;
        }
        if ($child->nodeType === XML_COMMENT_NODE) continue;
        if ($child->nodeType !== XML_ELEMENT_NODE) continue;

        $tag = strtolower($child->nodeName);
        bump($c['tags'], $tag);
        if ($depth + 1 > $per['max_depth']) $per['max_depth'] = $depth + 1;

        if (in_array($tag, $tag_flags)) flag("tag:$tag", $dashid);

        $this_widget = $widget;

        if ($depth === 0) {
            // Top level: this is a widget box
            bump($c['toplevel_tag'], $tag);
            $class = $child->getAttribute('class');
            $tokens = preg_split('/\s+/', trim($class), -1, PREG_SPLIT_NO_EMPTY);

            if (!count($tokens)) {
                $c['no_class']++;
                sample('no_class', $dashid);
                $this_widget = '(no class)';
            } else {
                if (count($tokens) > 1) {
                    $c['multi_token_class']++;
                    sample('multi_token_class', $dashid);
                }
                // designer.js reads the whole class attribute as the widget type
                $this_widget = $class;
                bump($c['toplevel_class'], $this_widget);
                $per['classes'][$this_widget] = true;

                global $registry;
                if (count($registry) && !isset($registry[$this_widget])) {
                    bump($c['unknown_class'], $this_widget);
                    sample('unknown_class:' . $this_widget, $dashid);
                }
            }
            $per['widgets']++;
        } else {
            $wkey = $widget === null ? '(none)' : $widget;
            if (!isset($c['nested_tags_by_class'][$wkey])) $c['nested_tags_by_class'][$wkey] = array();
            bump($c['nested_tags_by_class'][$wkey], $tag);
        }

        // Attributes
        foreach ($child->attributes as $attr) {
            $name = strtolower($attr->nodeName);
            $value = $attr->nodeValue;

            if ($depth === 0) {
                if (!isset($c['attrs_by_class'][$this_widget])) $c['attrs_by_class'][$this_widget] = array();
                bump($c['attrs_by_class'][$this_widget], $name);
                // An unquoted attribute value that contains a space is parsed as
                // the first word plus one valueless attribute per remaining word,
                // so align=text-align: center arrives as align="text-align:" and
                // center="". A name carrying css punctuation or a leading digit
                // can only have come from a value, never from an option name. An
                // option that is simply blank is a different thing and normal.
                if ($value !== '') $c['names_with_value'][$name] = true;
                if (preg_match('/[:()\/;]|^[0-9]/', $name)) {
                    bump($c['malformed_attrs'], $name);
                    sample('malformed_attr', $dashid);
                } else if ($value === '') {
                    bump($c['blank_option_attrs'], $name);
                    sample('blank_or_malformed:' . $name, $dashid);
                }
            } else {
                bump($c['nested_attrs'], $name);
            }

            if (substr($name, 0, 2) === 'on') flag("event_handler:$name", $dashid);

            if (in_array($name, $url_attrs)) {
                $v = strtolower(ltrim($value));
                $v = preg_replace('/[\x00-\x20]/', '', $v);
                if (strpos($v, 'javascript:') === 0) flag('url:javascript', $dashid);
                else if (strpos($v, 'vbscript:') === 0) flag('url:vbscript', $dashid);
                else if (strpos($v, 'data:') === 0) flag('url:data', $dashid);
            }

            if ($name === 'style') {
                foreach (parse_style($value) as $prop => $val) {
                    bump($c['style_props'], $prop);
                    if (!isset($c['style_values'][$prop])) $c['style_values'][$prop] = array();
                    if (count($c['style_values'][$prop]) < 25 && !in_array($val, $c['style_values'][$prop])) {
                        $c['style_values'][$prop][] = $val;
                    }
                }
                $lv = strtolower($value);
                if (strpos($lv, 'expression(') !== false) flag('style:expression', $dashid);
                if (strpos($lv, 'url(') !== false) flag('style:url', $dashid);
                if (strpos($lv, 'position:fixed') !== false || strpos($lv, 'position: fixed') !== false) {
                    flag('style:position_fixed', $dashid);
                }
            }
        }

        walk($child, $depth + 1, $this_widget, $dashid, $per);
    }
}

// ---------------------------------------------------------------------------
// Main loop
// ---------------------------------------------------------------------------

$fh = fopen($infile, 'r');
$per_fh = fopen($opts['out'] . "_dashboards.jsonl", 'w');

while (($line = fgets($fh)) !== false) {
    $line = trim($line);
    if ($line === '') continue;
    $row = json_decode($line, true);
    if (!is_array($row) || !isset($row['content'])) continue;

    $c['dashboards']++;
    $dashid = $row['id'];
    $content = $row['content'];

    if (trim($content) === '') {
        $c['empty']++;
        continue;
    }

    $nerrors = 0;
    $messages = array();
    $root = parse_fragment($content, $nerrors, $messages);
    if ($nerrors > 0) {
        $c['parse_errors']++;
        sample('parse_errors', $dashid);
        $seen = array();
        foreach ($messages as $msg) {
            $shape = error_shape($msg);
            bump($c['parse_error_shapes'], $shape);
            if (!isset($seen[$shape])) { sample('parse_error:' . $shape, $dashid); $seen[$shape] = true; }
        }
    }
    if ($root === null) {
        flag('parse_failed', $dashid);
        continue;
    }

    $per = array('max_depth' => 0, 'widgets' => 0, 'classes' => array());
    walk($root, 0, null, $dashid, $per);

    bump($c['depth'], $per['max_depth']);
    bump($c['widgets_per_dashboard'], $per['widgets']);
    foreach (array_keys($per['classes']) as $cl) bump($c['toplevel_class_dashboards'], $cl);

    fwrite($per_fh, json_encode(array(
        'id' => $dashid,
        'user' => isset($row['user']) ? $row['user'] : null,
        'bytes' => strlen($content),
        'widgets' => $per['widgets'],
        'max_depth' => $per['max_depth'],
        'parse_errors' => $nerrors,
        'classes' => array_keys($per['classes'])
    )) . "\n");
}

fclose($fh);
fclose($per_fh);

// ---------------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------------

// A name that never once carried a value anywhere in the corpus is not an
// option somebody left blank, it is a word split off an unquoted value.
foreach ($c['blank_option_attrs'] as $name => $count) {
    if (!isset($c['names_with_value'][$name])) {
        bump($c['malformed_attrs'], $name, $count);
        unset($c['blank_option_attrs'][$name]);
        if (isset($samples['blank_or_malformed:' . $name])) {
            $samples['malformed:' . $name] = $samples['blank_or_malformed:' . $name];
        }
    }
}
foreach (array_keys($samples) as $k) {
    if (strpos($k, 'blank_or_malformed:') === 0) unset($samples[$k]);
}
$c['names_with_value'] = count($c['names_with_value']);

foreach (array('toplevel_class', 'toplevel_class_dashboards', 'unknown_class', 'tags',
               'nested_attrs', 'style_props', 'flags', 'parse_error_shapes',
               'malformed_attrs', 'blank_option_attrs') as $k) {
    if (isset($c[$k]) && is_array($c[$k])) arsort($c[$k]);
}
ksort($c['depth']);

$c['registry'] = array_keys($registry);
$c['samples'] = $samples;

file_put_contents($opts['out'] . ".json", json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

function table($title, $arr, $limit = 0)
{
    echo "\n$title\n";
    if (!count($arr)) { echo "  none\n"; return; }
    $n = 0;
    foreach ($arr as $k => $v) {
        printf("  %-40s %s\n", substr((string) $k, 0, 40), $v);
        if ($limit && ++$n >= $limit) {
            echo "  ... " . (count($arr) - $limit) . " more, see " . $GLOBALS['opts']['out'] . ".json\n";
            break;
        }
    }
}

echo "\n=== Dashboard content census ===\n";
echo "dashboards        " . $c['dashboards'] . "\n";
echo "empty content     " . $c['empty'] . "\n";
echo "html parse errors " . $c['parse_errors'] . "\n";
echo "bare text at top  " . $c['toplevel_text'] . "\n";
echo "no class on box   " . $c['no_class'] . "\n";
echo "multi token class " . $c['multi_token_class'] . "\n";

table("Widget types by element count", $c['toplevel_class'], 40);
table("Classes not in the widget registry", $c['unknown_class'], 40);
table("All element tags, any depth", $c['tags'], 40);
table("Attribute names below the top level", $c['nested_attrs'], 40);
table("Inline style properties", $c['style_props'], 40);
// Widget boxes hold two different kinds of child. Authored html, which the
// converter has to preserve, and output injected by the render scripts before
// the dashboard was saved, which it should discard.
echo "\nChild tags inside each widget type\n";
if (!count($c['nested_tags_by_class'])) {
    echo "  none\n";
} else {
    $nb = $c['nested_tags_by_class'];
    uasort($nb, function ($a, $b) { return array_sum($b) - array_sum($a); });
    $n = 0;
    foreach ($nb as $cls => $tags) {
        arsort($tags);
        $parts = array();
        foreach ($tags as $t => $v) $parts[] = "$t($v)";
        printf("  %-30s %s\n", substr((string) $cls, 0, 30), implode(' ', array_slice($parts, 0, 12)));
        if (++$n >= 30) { echo "  ... see " . $GLOBALS['opts']['out'] . ".json\n"; break; }
    }
}

table("Why the html parser complained", $c['parse_error_shapes'], 25);
table("Attribute names that can only be fragments of an unquoted value", $c['malformed_attrs'], 30);
table("Options present but left blank, this is normal", $c['blank_option_attrs'], 15);

table("Findings", $c['flags']);
table("Max nesting depth", $c['depth']);

echo "\nWritten " . $opts['out'] . ".json and " . $opts['out'] . "_dashboards.jsonl\n";
