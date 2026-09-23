<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
*/

// Lists the dashboards that hold content the converter will not carry over, so
// they can be looked at by hand before the migration runs. Read only, it makes
// no changes to the database.
//
// Run from the emoncms root:
//   php Modules/dashboard/tools/find.php nested
//   php Modules/dashboard/tools/find.php iframe --full
//   php Modules/dashboard/tools/find.php --list
//
// Reads the dashboard table directly, so it reports real dashboard ids and
// userids.

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') {
    die("cli only\n");
}

require_once dirname(__FILE__) . "/cli.php";
require_once dirname(__FILE__) . "/../widget_registry.php";
require_once dirname(__FILE__) . "/../dashboard_convert.php";

$opts = cli_options($argv, $args);

$categories = [
    'nested' => 'a widget sitting inside another widget',
    'wrapped' => 'a widget inside an element that is not a widget, lost only when that element is stripped',
    'iframe' => 'an iframe the author added, not one a vis or graph widget drew',
    'unknown' => 'a widget type no module declares',
    'broken-style' => 'a style attribute broken up into stray attributes',
    'script' => 'a script, style, meta, link, object, embed or svg tag',
    'position-fixed' => 'a widget positioned fixed rather than absolute',
    'url' => 'a src or href the allowlist drops, a url pointing back at emoncms included',
    'option-value' => 'an option value the widget will not accept as written, a tag or over 512 characters',
    'style-value' => 'a negative margin, or an opacity that is raised or dropped'
];

if (isset($opts['list']) || isset($opts['help']) || !count($args)) {
    echo "usage: php find.php CATEGORY [CATEGORY...] [options]\n\n";
    echo "categories:\n";
    foreach ($categories as $name => $description) {
        printf("  %-15s %s\n", $name, $description);
    }
    echo "\noptions:\n";
    echo "  --full       show what was found in each dashboard, not just a count\n";
    echo "  --ids        print bare dashboard ids, for piping into another command\n";
    echo "  --limit=N    stop after N dashboards\n";
    exit(0);
}

foreach ($args as $name) {
    if (!isset($categories[$name])) {
        fwrite(STDERR, "Unknown category: $name. Use --list to see them.\n");
        exit(1);
    }
}
$wanted = array_flip($args);

$registry = widget_registry();
if (!count($registry)) {
    fwrite(STDERR, "No widget declarations found. Generate them with:\n"
        . "  node Modules/dashboard/tools/extract_registry.js\n");
    exit(1);
}

// A widget whose declaration is not deployed looks like an unknown type, and
// its generated iframe looks like one the author added, so say so rather than
// reporting a pile of false hits.
$missing = widget_registry_missing();
if (count($missing)) {
    fwrite(STDERR, "Warning: these widgets have no declaration, so their types will be\n"
        . "reported as unknown and any iframe they draw as hand added:\n");
    foreach ($missing as $script => $declaration) {
        fwrite(STDERR, "  $script\n");
    }
    fwrite(STDERR, "Generate them with: node Modules/dashboard/tools/extract_registry.js\n\n");
}

// A vis or graph widget draws its own iframe, so an iframe inside one of these
// is generated rather than something the author wrote.
$iframe_widgets = [];
foreach ($registry as $type => $widget) {
    if ($widget['module'] === 'vis' || $widget['module'] === 'graph') {
        $iframe_widgets[$type] = true;
    }
}

$limit = isset($opts['limit']) ? (int) $opts['limit'] : 0;
$bare = isset($opts['ids']);
$full = isset($opts['full']);

$mysqli = cli_connect();
$rows = cli_dashboards($mysqli, ['id', 'userid', 'name', 'content'], 0, true);

$found = 0;
$scanned = 0;
$totals = [];

foreach ($rows as $row) {
    if (trim($row['content']) === '') {
        continue;
    }
    $scanned++;
    if ($limit > 0 && $found >= $limit) {
        break;
    }

    $hits = inspect($row['content'], $registry, $iframe_widgets, $wanted);
    if (!count($hits)) {
        continue;
    }
    $found++;

    foreach ($hits as $name => $detail) {
        if (!isset($totals[$name])) {
            $totals[$name] = 0;
        }
        $totals[$name]++;
    }

    if ($bare) {
        echo $row['id'] . "\n";
        continue;
    }

    $line = "dashboard " . str_pad($row['id'], 6);
    if ($row['userid'] !== null) {
        $line .= " user " . str_pad($row['userid'], 6);
    }
    if ($row['name'] !== null && $row['name'] !== '') {
        $line .= " " . $row['name'];
    }
    echo rtrim($line) . "\n";

    foreach ($hits as $name => $detail) {
        echo "  $name: " . count($detail) . "\n";
        if (!$full) {
            continue;
        }
        foreach ($detail as $item) {
            echo "    $item\n";
        }
    }
}

if (!$bare) {
    echo "\n$found of $scanned dashboards with content\n";
    foreach ($totals as $name => $count) {
        printf("  %-15s %d dashboards\n", $name, $count);
    }
}



// ---------------------------------------------------------------------------
// Inspection
// ---------------------------------------------------------------------------

// Returns category name => list of what was found, for the wanted categories
// only. Content that will not parse at all is reported under every category
// asked for, so it cannot slip through as clean.
function inspect($content, $registry, $iframe_widgets, $wanted)
{
    $root = parse_fragment($content);
    if ($root === null) {
        $hits = [];
        foreach ($wanted as $name => $ignored) {
            $hits[$name] = ['content would not parse'];
        }
        return $hits;
    }

    $hits = [];
    walk($root, null, $registry, $iframe_widgets, $wanted, $hits);
    return $hits;
}



// $widget is the widget class this node sits inside, or null at the top level.
// $top is true for the children of the page itself, which is where a widget
// box belongs. The converter reads those and does not descend into anything
// else, see the top level loop in dashboard_convert.
function walk($node, $widget, $registry, $iframe_widgets, $wanted, &$hits, $top = true)
{
    foreach ($node->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }

        $tag = strtolower($child->nodeName);
        $class = $child->hasAttribute('class') ? trim($child->getAttribute('class')) : '';
        $declared = $class !== '' && isset($registry[$class]);

        if ($widget === null && $class !== '' && !$declared) {
            record($hits, $wanted, 'unknown', $class);
        }

        if ($widget !== null && $declared) {
            record($hits, $wanted, 'nested', "$class inside $widget");
        }

        // A widget box that is not a child of the page. Two shapes turn up: a
        // page wrapped in a tag that was opened and never closed, which the
        // converter looks through, and a page pasted inside the editor's own
        // textarea, which it does not, see dashboard_convert_holds_box.
        if ($widget === null && $declared && !$top) {
            record(
                $hits,
                $wanted,
                'wrapped',
                "$class inside <" . strtolower($node->nodeName) . ">"
            );
        }

        if ($tag === 'iframe' && !($widget !== null && isset($iframe_widgets[$widget]))) {
            $src = $child->hasAttribute('src') ? $child->getAttribute('src') : '(no src)';
            record($hits, $wanted, 'iframe', substr($src, 0, 120));
        }

        // A url the allowlist will not keep. The iframe a vis or graph widget
        // draws is generated and goes whatever its src says, so it is left out
        // rather than reported as a url the author loses.
        if (!($tag === 'iframe' && $widget !== null && isset($iframe_widgets[$widget]))) {
            foreach (['src', 'href'] as $attribute) {
                if (!$child->hasAttribute($attribute)) {
                    continue;
                }
                $url = $child->getAttribute($attribute);
                if (dashboard_convert_url_allowed($url, $attribute)) {
                    continue;
                }
                record($hits, $wanted, 'url', $attribute . '=' . substr($url, 0, 120));
            }
        }

        // An option value the widget will not accept. Only read on a widget box,
        // which is the only place an option lives. An empty value and an option
        // no widget declares are dropped for their own reasons and are not this.
        if ($widget === null && $declared) {
            foreach ($child->attributes as $attr) {
                $name = strtolower($attr->nodeName);
                if ($name === 'id' || $name === 'class' || $name === 'style') {
                    continue;
                }
                if ((string) $attr->nodeValue === '') {
                    continue;
                }

                $option = widget_registry_option($class, $name);
                if ($option === false) {
                    continue;
                }
                if (dashboard_convert_option_valid($option, (string) $attr->nodeValue)) {
                    continue;
                }

                record(
                    $hits,
                    $wanted,
                    'option-value',
                    "$class $name=" . substr((string) $attr->nodeValue, 0, 120)
                );
            }
        }

        if ($child->hasAttribute('style')) {
            find_style_values($child, $class, $tag, $widget, $wanted, $hits);
        }

        if (in_array($tag, ['script', 'style', 'meta', 'link', 'object', 'embed', 'svg'])) {
            record($hits, $wanted, 'script', "<$tag>" . ($widget === null ? '' : " inside $widget"));
        }

        if (
            $child->hasAttribute('style')
            && preg_match('/position\s*:\s*fixed/i', $child->getAttribute('style'))
        ) {
            record($hits, $wanted, 'position-fixed', $class === '' ? $tag : $class);
        }

        // A style attribute that lost its quoting spills the rest of the style
        // into stray attributes, recognisable by the trailing colon of a
        // property name. See the htmlspecialchars_decode note in SCHEMA.md.
        foreach ($child->attributes as $attr) {
            if (substr($attr->nodeName, -1) === ':') {
                record(
                    $hits,
                    $wanted,
                    'broken-style',
                    ($class === '' ? $tag : $class) . ': ' . $attr->nodeName
                );
                break;
            }
        }

        // Widget boxes sit at the top level, so the first class that is in the
        // registry is the widget everything below it belongs to.
        $inside = $widget;
        if ($inside === null && $declared) {
            $inside = $class;
        }

        walk($child, $inside, $registry, $iframe_widgets, $wanted, $hits, false);
    }
}

// A style declaration the converter changes rather than keeps. Margin is only
// read inside the html of a widget: on the box it is dropped whatever it says,
// because the renderer writes the geometry from the document.
function find_style_values($child, $class, $tag, $widget, $wanted, &$hits)
{
    $where = ($class === '' ? $tag : $class) . ': ';

    foreach (dashboard_convert_parse_style($child->getAttribute('style')) as $property => $value) {
        if (
            $widget !== null && substr($property, 0, 6) === 'margin'
            && preg_match('/-\s*[\d.]/', $value)
        ) {
            record($hits, $wanted, 'style-value', $where . "$property: $value");
        }

        if ($property === 'opacity') {
            $floored = dashboard_convert_style_opacity($value);
            if ($floored === false) {
                record($hits, $wanted, 'style-value', $where . "opacity: $value dropped");
            } elseif ($floored !== $value) {
                record($hits, $wanted, 'style-value', $where . "opacity: $value raised to $floored");
            }
        }
    }
}

function record(&$hits, $wanted, $name, $detail)
{
    if (!isset($wanted[$name])) {
        return;
    }
    if (!isset($hits[$name])) {
        $hits[$name] = [];
    }
    // Keep the report readable on a dashboard that repeats the same thing.
    if (count($hits[$name]) < 20) {
        $hits[$name][] = $detail;
    }
}
