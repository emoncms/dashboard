<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Reads the export produced by export_content.php and reports what the stored
// dashboard html actually contains. It measures, it does not convert or repair.
//
//   php Modules/dashboard/tools/census.php dashboards.jsonl --out=census
//
// Writes census.json with the full counts and census_dashboards.jsonl with one
// record per dashboard, so stage 3 can pick examples to work from.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') {
    die("cli only\n");
}

require_once dirname(__FILE__) . "/cli.php";

$argv_files = [];
$opts = ['out' => 'census', 'samples' => 10, 'root' => dirname(__FILE__) . "/../../.."];
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
if (!is_readable($infile)) {
    die("Cannot read $infile\n");
}
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
    $names = [];
    $files = [$root . "/Modules/dashboard/Views/js/widgetlist.js"];
    foreach (glob($root . "/Modules/*/widget", GLOB_ONLYDIR) as $dir) {
        foreach (array_merge(glob("$dir/*_render.js"), glob("$dir/*/*_render.js")) as $f) {
            $files[] = $f;
        }
    }
    foreach ($files as $f) {
        if (!is_readable($f)) {
            continue;
        }
        $js = file_get_contents($f);
        // Keys may be quoted or bare, see kwhperiod_render.js for the bare form
        $re = '/(?:"([^"\r\n]+)"|\'([^\'\r\n]+)\'|([A-Za-z0-9_$-]+))'
            . '\s*:\s*\{\s*(?:"offsetx"|\'offsetx\'|offsetx)\s*:/s';
        if (preg_match_all($re, $js, $m, PREG_SET_ORDER)) {
            foreach ($m as $set) {
                $name = $set[1] !== '' ? $set[1] : (isset($set[2]) && $set[2] !== '' ? $set[2] : (isset($set[3]) ? $set[3] : ''));
                if ($name !== '') {
                    $names[$name] = true;
                }
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

$c = [
    'dashboards' => 0,
    'empty' => 0,
    'parse_errors' => 0,
    'toplevel_class' => [],      // widget type => count
    'toplevel_class_dashboards' => [],
    'toplevel_tag' => [],        // tag name of top level nodes
    'toplevel_text' => 0,             // bare text sitting outside any widget
    'multi_token_class' => 0,         // class="dial something-else"
    'no_class' => 0,
    'unknown_class' => [],       // class not in the widget registry
    'tags' => [],                // every element, at any depth
    'attrs_by_class' => [],      // widget type => attribute => count
    'nested_tags_by_class' => [],// widget type => inner tag => count
    'nested_attrs' => [],        // attribute names below the top level
    'style_props' => [],         // inline style property => count
    'style_values' => [],        // property => distinct sample values
    'box_style_props_by_class' => [],   // widget type => property on the box => count
    'inner_style_props_by_class' => [], // widget type => property below the box => count
    'text_widget' => [],         // what the text widgets hold, see below
    'depth' => [],               // nesting depth histogram
    'widgets_per_dashboard' => [],
    'flags' => [],               // finding => count
    'parse_error_shapes' => [],  // libxml complaint => count
    'blank_option_attrs' => [],  // option present but left empty, normal
    'malformed_attrs' => [],     // fragments of an unquoted value, not normal
    'names_with_value' => [],    // every attribute name seen holding a value
];

// The text widget sub report. Declared up here so the report prints the same
// shape whether or not the corpus holds a text widget.
$c['text_widget'] = [
    'widgets' => 0,
    'tiers' => [],           // what a widget would need to be expressible
    'tiers_by_class' => [],
    'tier_tags' => [],       // the tags that put widgets in each tier
    'box_style_values' => [],// authored property => value => count
    'inner_style_values' => [],
    'tier_dashboards' => [],  // tier => dashboards holding one
    'hardest_tier' => [],     // dashboards by the hardest tier they hold
    'font_tag' => [],        // attribute on a <font> => count
    'font_tag_values' => [],
    'img_src' => [],         // kind of url => count
    'link_href' => [],
    'holds_br' => 0,
];

$samples = [];  // finding => list of dashboard ids

function sample($key, $dashid)
{
    global $samples, $max_samples;
    if (!isset($samples[$key])) {
        $samples[$key] = [];
    }
    if (count($samples[$key]) < $max_samples && !in_array($dashid, $samples[$key])) {
        $samples[$key][] = $dashid;
    }
}

function flag($name, $dashid)
{
    global $c;
    if (!isset($c['flags'][$name])) {
        $c['flags'][$name] = 0;
    }
    $c['flags'][$name]++;
    sample("flag:$name", $dashid);
}


// Tags that have no business in dashboard content
$tag_flags = ['script', 'iframe', 'object', 'embed', 'link', 'meta', 'base',
    'form', 'input', 'button', 'svg', 'math', 'style', 'frame', 'frameset'
];

// Attributes that carry a url
$url_attrs = ['href', 'src', 'action', 'formaction', 'data', 'poster', 'xlink:href', 'background'];

// The widgets whose only option is html, see widgetlist.js. Container-* widgets
// hold html too, but they are a grouping box rather than a text box.
$text_widgets = ['paragraph', 'heading', 'heading-center'];

// Styling that wraps text without dividing it
$inline_tags = ['b', 'strong', 'i', 'em', 'u', 'sub', 'sup', 'font', 'span', 'small'];
// Structure that divides text into blocks
$block_tags = ['p', 'div', 'center', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
// Rows and bullets
$table_tags = ['table', 'thead', 'tbody', 'tr', 'th', 'td', 'colgroup', 'col', 'ul', 'ol', 'li'];

// Written by the designer on every widget box, not by the author, so their
// values say nothing about what an author wanted.
$generated_box_props = ['position', 'top', 'left', 'width', 'height', 'margin',
    'margin-top', 'margin-right', 'margin-bottom', 'margin-left'
];

// ---------------------------------------------------------------------------
// Parsing
// ---------------------------------------------------------------------------



// Collapse a libxml message down to its shape so the same complaint about
// different tag names counts as one kind of problem.
function error_shape($msg)
{
    $rules = [
        '/mismatch:\s*\S+ and \S+/i' => 'mismatch: X and Y',
        '/\bTag [A-Za-z0-9_:-]+ invalid/i' => 'Tag X invalid',
        '/end tag\s*:\s*[A-Za-z0-9_:-]+/i' => 'end tag : X',
        '/Attribute [A-Za-z0-9_:.-]+ redefined/i' => 'Attribute X redefined',
        '/[A-Za-z0-9_:-]+ line \d+/' => 'X line N',
        '/\d+/' => 'N',
    ];
    $m = $msg;
    foreach ($rules as $re => $to) {
        $m = preg_replace($re, $to, $m);
    }
    return $m;
}

function parse_style($style)
{
    $out = [];
    foreach (explode(';', $style) as $decl) {
        if (strpos($decl, ':') === false) {
            continue;
        }
        list($prop, $val) = explode(':', $decl, 2);
        $prop = strtolower(trim($prop));
        if ($prop === '') {
            continue;
        }
        $out[$prop] = trim($val);
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Text widgets
//
// What paragraph, heading and heading-center actually hold, so the options for
// a text widget that takes options instead of html can be drawn from the corpus
// rather than guessed. See notes/TEXT-IMAGE-PANEL.md.
// ---------------------------------------------------------------------------

function census_url_kind($url)
{
    $u = trim($url);
    if ($u === '') {
        return '(empty)';
    }
    $l = strtolower($u);
    if (strpos($l, 'data:') === 0) {
        return 'data uri';
    }
    if (strpos($l, '//') === 0) {
        return 'protocol relative';
    }
    if (strpos($l, 'http://') === 0) {
        return 'http';
    }
    if (strpos($l, 'https://') === 0) {
        return 'https';
    }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/', $l)) {
        return 'other scheme';
    }
    if (substr($u, 0, 1) === '/') {
        return 'absolute path';
    }
    return 'relative path';
}

function census_text_style_value($bucket, $prop, $val)
{
    global $c, $generated_box_props;

    // On the box these six are written by the designer, so they are skipped.
    // Inside the html they are written by the author and are kept.
    if ($bucket === 'box_style_values' && in_array($prop, $generated_box_props)) {
        return;
    }
    $val = trim($val);
    if ($val === '') {
        return;
    }
    if (!isset($c['text_widget'][$bucket][$prop])) {
        $c['text_widget'][$bucket][$prop] = [];
    }
    $vals =& $c['text_widget'][$bucket][$prop];
    if (isset($vals[$val]) || count($vals) < 40) {
        bump($vals, $val);
    } else {
        bump($vals, '(further values)');
    }
}

// Does every element in this widget wrap all of its text, so one set of options
// could describe the whole box? A br is a line break within the text, not a
// division of it, so it does not count against this.
function census_text_one_style($node)
{
    $current = $node;
    while (true) {
        $elements = [];
        $text = false;
        foreach ($current->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->nodeValue) !== '') {
                    $text = true;
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                if (strtolower($child->nodeName) !== 'br') {
                    $elements[] = $child;
                }
            }
        }
        if (!count($elements)) {
            return true;
        }
        if (count($elements) > 1) {
            return false;
        }
        // Text beside an element is text styled two ways
        if ($text) {
            return false;
        }
        $current = $elements[0];
    }
}

function census_text_element($node, $tag)
{
    global $c;

    if ($tag === 'font') {
        foreach ($node->attributes as $attr) {
            $name = strtolower($attr->nodeName);
            bump($c['text_widget']['font_tag'], $name);
            $value = strtolower(trim($attr->nodeValue));
            if ($value === '' || !in_array($name, ['size', 'color', 'face'])) {
                continue;
            }
            if (!isset($c['text_widget']['font_tag_values'][$name])) {
                $c['text_widget']['font_tag_values'][$name] = [];
            }
            $vals =& $c['text_widget']['font_tag_values'][$name];
            if (isset($vals[$value]) || count($vals) < 40) {
                bump($vals, $value);
            } else {
                bump($vals, '(further values)');
            }
        }
    } elseif ($tag === 'img') {
        bump($c['text_widget']['img_src'], census_url_kind($node->getAttribute('src')));
    } elseif ($tag === 'a') {
        bump($c['text_widget']['link_href'], census_url_kind($node->getAttribute('href')));
    }
}

// Sort one text widget into the most demanding thing it contains. The tiers are
// exclusive and they add up to the widget count, so the share an options only
// widget could express can be read straight off them.
$tier_order = ['empty', 'plain text', 'one style throughout', 'image, no text',
    'mixed, inline', 'mixed, inline with a link', 'mixed, across blocks',
    'table or list', 'embed or nested widget'
];

function census_text_widget($node, $class, $dashid, &$per)
{
    global $c, $inline_tags, $block_tags, $table_tags;

    $c['text_widget']['widgets']++;

    $tags = [];
    $has_text = false;
    $stack = [$node];
    while (count($stack)) {
        $current = array_pop($stack);
        foreach ($current->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->nodeValue) !== '') {
                    $has_text = true;
                }
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $tag = strtolower($child->nodeName);
            bump($tags, $tag);
            census_text_element($child, $tag);
            $stack[] = $child;
        }
    }
    if (isset($tags['br'])) {
        $c['text_widget']['holds_br']++;
    }

    $names = array_keys($tags);
    $expected = array_merge($inline_tags, $block_tags, $table_tags, ['a', 'img', 'br']);
    $other = array_diff($names, $expected);

    if (count($other)) {
        $tier = 'embed or nested widget';
    } elseif (count(array_intersect($names, $table_tags))) {
        $tier = 'table or list';
    } elseif (!count($tags)) {
        $tier = $has_text ? 'plain text' : 'empty';
    } elseif (!$has_text && in_array('img', $names)) {
        $tier = 'image, no text';
    } elseif (census_text_one_style($node)) {
        $tier = 'one style throughout';
    } elseif (count(array_intersect($names, $block_tags))) {
        $tier = 'mixed, across blocks';
    } elseif (in_array('a', $names)) {
        $tier = 'mixed, inline with a link';
    } else {
        $tier = 'mixed, inline';
    }

    bump($c['text_widget']['tiers'], $tier);
    if (!isset($c['text_widget']['tiers_by_class'][$class])) {
        $c['text_widget']['tiers_by_class'][$class] = [];
    }
    bump($c['text_widget']['tiers_by_class'][$class], $tier);

    if (!isset($c['text_widget']['tier_tags'][$tier])) {
        $c['text_widget']['tier_tags'][$tier] = [];
    }
    foreach ($tags as $t => $n) {
        bump($c['text_widget']['tier_tags'][$tier], $t, $n);
    }

    $per['text_tiers'][$tier] = true;
    sample('text_widget:' . $tier, $dashid);
}

// Walk one element. $widget is the top level widget class this sits inside.
function walk($node, $depth, $widget, $dashid, &$per)
{
    global $c, $tag_flags, $url_attrs, $max_samples, $text_widgets;

    foreach ($node->childNodes as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            if ($depth === 0 && trim($child->nodeValue) !== '') {
                $c['toplevel_text']++;
                sample('toplevel_text', $dashid);
            }
            continue;
        }
        if ($child->nodeType === XML_COMMENT_NODE) {
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }

        $tag = strtolower($child->nodeName);
        bump($c['tags'], $tag);
        if ($depth + 1 > $per['max_depth']) {
            $per['max_depth'] = $depth + 1;
        }

        if (in_array($tag, $tag_flags)) {
            flag("tag:$tag", $dashid);
        }

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

            if (in_array($this_widget, $text_widgets)) {
                census_text_widget($child, $this_widget, $dashid, $per);
            }
        } else {
            $wkey = $widget === null ? '(none)' : $widget;
            if (!isset($c['nested_tags_by_class'][$wkey])) {
                $c['nested_tags_by_class'][$wkey] = [];
            }
            bump($c['nested_tags_by_class'][$wkey], $tag);
        }

        // Attributes
        foreach ($child->attributes as $attr) {
            $name = strtolower($attr->nodeName);
            $value = $attr->nodeValue;

            if ($depth === 0) {
                if (!isset($c['attrs_by_class'][$this_widget])) {
                    $c['attrs_by_class'][$this_widget] = [];
                }
                bump($c['attrs_by_class'][$this_widget], $name);
                // An unquoted attribute value that contains a space is parsed as
                // the first word plus one valueless attribute per remaining word,
                // so align=text-align: center arrives as align="text-align:" and
                // center="". A name carrying css punctuation or a leading digit
                // can only have come from a value, never from an option name. An
                // option that is simply blank is a different thing and normal.
                if ($value !== '') {
                    $c['names_with_value'][$name] = true;
                }
                if (preg_match('/[:()\/;]|^[0-9]/', $name)) {
                    bump($c['malformed_attrs'], $name);
                    sample('malformed_attr', $dashid);
                } elseif ($value === '') {
                    bump($c['blank_option_attrs'], $name);
                    sample('blank_or_malformed:' . $name, $dashid);
                }
            } else {
                bump($c['nested_attrs'], $name);
            }

            if (substr($name, 0, 2) === 'on') {
                flag("event_handler:$name", $dashid);
            }

            if (in_array($name, $url_attrs)) {
                $v = strtolower(ltrim($value));
                $v = preg_replace('/[\x00-\x20]/', '', $v);
                if (strpos($v, 'javascript:') === 0) {
                    flag('url:javascript', $dashid);
                } elseif (strpos($v, 'vbscript:') === 0) {
                    flag('url:vbscript', $dashid);
                } elseif (strpos($v, 'data:') === 0) {
                    flag('url:data', $dashid);
                }
            }

            if ($name === 'style') {
                // Split by widget type, and by whether the style sits on the
                // widget box or on something inside it. A data widget's box
                // style is written by its render script at draw time, so only
                // the box style of a text or container widget was authored.
                $wkey = $this_widget === null ? '(none)' : $this_widget;
                $where = $depth === 0 ? 'box_style_props_by_class' : 'inner_style_props_by_class';
                if (!isset($c[$where][$wkey])) {
                    $c[$where][$wkey] = [];
                }
                $text_bucket = null;
                if (in_array($wkey, $text_widgets)) {
                    $text_bucket = $depth === 0 ? 'box_style_values' : 'inner_style_values';
                }

                foreach (parse_style($value) as $prop => $val) {
                    bump($c['style_props'], $prop);
                    bump($c[$where][$wkey], $prop);
                    if ($text_bucket !== null) {
                        census_text_style_value($text_bucket, $prop, $val);
                    }
                    if (!isset($c['style_values'][$prop])) {
                        $c['style_values'][$prop] = [];
                    }
                    if (count($c['style_values'][$prop]) < 25 && !in_array($val, $c['style_values'][$prop])) {
                        $c['style_values'][$prop][] = $val;
                    }
                }
                $lv = strtolower($value);
                if (strpos($lv, 'expression(') !== false) {
                    flag('style:expression', $dashid);
                }
                if (strpos($lv, 'url(') !== false) {
                    flag('style:url', $dashid);
                }
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

global $tier_order;

while (($line = fgets($fh)) !== false) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    $row = json_decode($line, true);
    if (!is_array($row) || !isset($row['content'])) {
        continue;
    }

    $c['dashboards']++;
    $dashid = $row['id'];
    $content = $row['content'];

    if (trim($content) === '') {
        $c['empty']++;
        continue;
    }

    $nerrors = 0;
    $messages = [];
    $root = parse_fragment($content, $nerrors, $messages);
    if ($nerrors > 0) {
        $c['parse_errors']++;
        sample('parse_errors', $dashid);
        $seen = [];
        foreach ($messages as $msg) {
            $shape = error_shape($msg);
            bump($c['parse_error_shapes'], $shape);
            if (!isset($seen[$shape])) {
                sample('parse_error:' . $shape, $dashid);
                $seen[$shape] = true;
            }
        }
    }
    if ($root === null) {
        flag('parse_failed', $dashid);
        continue;
    }

    $per = ['max_depth' => 0, 'widgets' => 0, 'classes' => [],
        'text_tiers' => []
    ];
    walk($root, 0, null, $dashid, $per);

    // A dashboard needs the html widget if any of its text widgets does, so it
    // is counted by the hardest tier it holds.
    $hardest = null;
    foreach (array_keys($per['text_tiers']) as $tier) {
        bump($c['text_widget']['tier_dashboards'], $tier);
        $rank = array_search($tier, $tier_order);
        if ($hardest === null || $rank > $hardest) {
            $hardest = $rank;
        }
    }
    if ($hardest !== null) {
        bump($c['text_widget']['hardest_tier'], $tier_order[$hardest]);
    }

    bump($c['depth'], $per['max_depth']);
    bump($c['widgets_per_dashboard'], $per['widgets']);
    foreach (array_keys($per['classes']) as $cl) {
        bump($c['toplevel_class_dashboards'], $cl);
    }

    fwrite($per_fh, json_encode([
        'id' => $dashid,
        'user' => isset($row['user']) ? $row['user'] : null,
        'bytes' => strlen($content),
        'widgets' => $per['widgets'],
        'max_depth' => $per['max_depth'],
        'parse_errors' => $nerrors,
        'classes' => array_keys($per['classes'])
    ]) . "\n");
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
    if (strpos($k, 'blank_or_malformed:') === 0) {
        unset($samples[$k]);
    }
}
$c['names_with_value'] = count($c['names_with_value']);

foreach (
    ['toplevel_class', 'toplevel_class_dashboards', 'unknown_class', 'tags',
        'nested_attrs', 'style_props', 'flags', 'parse_error_shapes',
        'malformed_attrs', 'blank_option_attrs'
    ] as $k
) {
    if (isset($c[$k]) && is_array($c[$k])) {
        arsort($c[$k]);
    }
}
ksort($c['depth']);

foreach (['box_style_props_by_class', 'inner_style_props_by_class'] as $k) {
    foreach ($c[$k] as $cls => $props) {
        arsort($props);
        $c[$k][$cls] = $props;
    }
    uasort($c[$k], function ($a, $b) {
        return array_sum($b) - array_sum($a);
    });
}
foreach (['tiers', 'font_tag', 'img_src', 'link_href'] as $k) {
    arsort($c['text_widget'][$k]);
}
foreach (['box_style_values', 'inner_style_values'] as $bucket) {
    foreach ($c['text_widget'][$bucket] as $prop => $vals) {
        arsort($vals);
        $c['text_widget'][$bucket][$prop] = $vals;
    }
    uasort($c['text_widget'][$bucket], function ($a, $b) {
        return array_sum($b) - array_sum($a);
    });
}
foreach (['tier_dashboards', 'hardest_tier'] as $k) {
    arsort($c['text_widget'][$k]);
}
foreach ($c['text_widget']['font_tag_values'] as $name => $vals) {
    arsort($vals);
    $c['text_widget']['font_tag_values'][$name] = $vals;
}
foreach ($c['text_widget']['tier_tags'] as $tier => $tags) {
    arsort($tags);
    $c['text_widget']['tier_tags'][$tier] = $tags;
}

$c['registry'] = array_keys($registry);
$c['samples'] = $samples;

file_put_contents($opts['out'] . ".json", json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

function table($title, $arr, $limit = 0)
{
    echo "\n$title\n";
    if (!count($arr)) {
        echo "  none\n";
        return;
    }
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
    uasort($nb, function ($a, $b) {
        return array_sum($b) - array_sum($a);
    });
    $n = 0;
    foreach ($nb as $cls => $tags) {
        arsort($tags);
        $parts = [];
        foreach ($tags as $t => $v) {
            $parts[] = "$t($v)";
        }
        printf("  %-30s %s\n", substr((string) $cls, 0, 30), implode(' ', array_slice($parts, 0, 12)));
        if (++$n >= 30) {
            echo "  ... see " . $GLOBALS['opts']['out'] . ".json\n";
            break;
        }
    }
}

// ---------------------------------------------------------------------------
// Text widgets
// ---------------------------------------------------------------------------

function pairs($arr, $limit = 12)
{
    $parts = [];
    foreach ($arr as $k => $v) {
        $parts[] = "$k($v)";
    }
    $out = implode(' ', array_slice($parts, 0, $limit));
    if (count($parts) > $limit) {
        $out .= ' ...';
    }
    return $out;
}

$tw = $c['text_widget'];

echo "\n=== Text widgets ===\n";
echo "paragraph, heading and heading-center\n";
echo "widgets           " . $tw['widgets'] . "\n";
echo "holding a br      " . $tw['holds_br'] . "\n";

// The first three tiers are what a widget with options and a plain text field
// could express as it stands. The rest need a decision, see the note.
table("What each text widget would need to be expressible", $tw['tiers']);

echo "\nThe same split per widget type\n";
if (!count($tw['tiers_by_class'])) {
    echo "  none\n";
} else {
    foreach ($tw['tiers_by_class'] as $cls => $tiers) {
        arsort($tiers);
        printf("  %-16s %s\n", $cls, pairs($tiers, 12));
    }
}

echo "\nTags driving each tier\n";
if (!count($tw['tier_tags'])) {
    echo "  none\n";
} else {
    foreach ($tw['tier_tags'] as $tier => $tags) {
        printf("  %-28s %s\n", substr($tier, 0, 28), pairs($tags, 10));
    }
}

// Only the box style of a text widget was typed by an author, so this is the
// list the style options should be drawn from.
$tw_box = [];
$tw_box_generated = 0;
foreach ($text_widgets as $t) {
    if (!isset($c['box_style_props_by_class'][$t])) {
        continue;
    }
    foreach ($c['box_style_props_by_class'][$t] as $prop => $n) {
        if (in_array($prop, $generated_box_props)) {
            $tw_box_generated += $n;
        } else {
            bump($tw_box, $prop, $n);
        }
    }
}
arsort($tw_box);
table("Authored style properties on a text widget box", $tw_box, 40);
echo "  (" . $tw_box_generated . " declarations of position, top, left, width, height and"
   . " margin are not listed, the designer writes those)\n";

echo "\nThe values they hold, the option ranges come from here\n";
if (!count($tw['box_style_values'])) {
    echo "  none\n";
} else {
    foreach ($tw['box_style_values'] as $prop => $vals) {
        printf("  %-20s %s\n", substr($prop, 0, 20), pairs($vals, 10));
    }
}

// Most styling is on the elements inside the box, so this list is the source
// of the options.
$tw_inner = [];
foreach ($text_widgets as $t) {
    if (!isset($c['inner_style_props_by_class'][$t])) {
        continue;
    }
    foreach ($c['inner_style_props_by_class'][$t] as $prop => $n) {
        bump($tw_inner, $prop, $n);
    }
}
arsort($tw_inner);
table("Authored style properties inside a text widget", $tw_inner, 30);

echo "\nThe values those hold\n";
if (!count($tw['inner_style_values'])) {
    echo "  none\n";
} else {
    $n = 0;
    foreach ($tw['inner_style_values'] as $prop => $vals) {
        printf("  %-20s %s\n", substr($prop, 0, 20), pairs($vals, 10));
        if (++$n >= 20) {
            echo "  ... see " . $GLOBALS['opts']['out'] . ".json\n";
            break;
        }
    }
}

table("Dashboards holding a text widget of each tier", $tw['tier_dashboards']);
table("Dashboards by the hardest tier they hold", $tw['hardest_tier']);

table("font tag attributes inside a text widget", $tw['font_tag'], 15);
echo "\nfont tag values\n";
if (!count($tw['font_tag_values'])) {
    echo "  none\n";
} else {
    foreach ($tw['font_tag_values'] as $name => $vals) {
        printf("  %-20s %s\n", $name, pairs($vals, 12));
    }
}

table("img src inside a text widget", $tw['img_src']);
table("a href inside a text widget", $tw['link_href']);

echo "\n=== The rest of the corpus ===\n";

table("Why the html parser complained", $c['parse_error_shapes'], 25);
table("Attribute names that can only be fragments of an unquoted value", $c['malformed_attrs'], 30);
table("Options present but left blank, this is normal", $c['blank_option_attrs'], 15);

table("Findings", $c['flags']);
table("Max nesting depth", $c['depth']);

echo "\nWritten " . $opts['out'] . ".json and " . $opts['out'] . "_dashboards.jsonl\n";
