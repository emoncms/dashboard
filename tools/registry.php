<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org

 Prints the widget registry and compares it against what dashboards actually
 hold, so gaps can be found before the converter runs.

   php tools/registry.php                       list the widget types
   php tools/registry.php --type=feedvalue      show one widget in full
   php tools/registry.php --census=census.json  compare against stored content
   php tools/registry.php --audit               compare against the render code

 The census file is written by tools/census.php. The comparison reports widget
 types in use that no module declares, and attributes in use that the widget
 does not declare as an option. Both are cases the converter has to decide what
 to do with, see tools/SCHEMA.md.
*/

define('EMONCMS_EXEC', 1);

if (php_sapi_name() !== 'cli') die("cli only\n");

require_once dirname(__DIR__) . '/widget_registry.php';

$options = getopt('', array('type::', 'census::', 'audit', 'root::'));
$registry = widget_registry();

if (!count($registry)) {
    fwrite(STDERR, "No widget declarations found. Generate them with:\n"
        . "  node Modules/dashboard/tools/extract_registry.js\n");
    exit(1);
}

if (isset($options['type'])) {
    show_type($registry, $options['type']);
    exit(0);
}

if (isset($options['census'])) {
    exit(compare($registry, $options['census']));
}

if (isset($options['audit'])) {
    exit(audit($registry));
}

// Default listing, grouped by the module that declares each widget.
$by_module = array();
foreach ($registry as $type => $widget) {
    $by_module[$widget['module']][$type] = count($widget['options']);
}

foreach ($by_module as $module => $types) {
    echo "$module\n";
    foreach ($types as $type => $count) {
        printf("  %-20s %2d options\n", $type, $count);
    }
}
echo "\n" . count($registry) . " widget types in " . count($by_module) . " modules\n";

function show_type($registry, $type)
{
    if (!isset($registry[$type])) {
        fwrite(STDERR, "Unknown widget type: $type\n");
        exit(1);
    }
    $widget = $registry[$type];
    echo "$type\n";
    echo "  module: " . $widget['module'] . "\n";
    echo "  source: " . $widget['source'] . "\n";
    echo "  options:\n";

    foreach ($widget['options'] as $name => $option) {
        $note = $option['legacy'] ? 'legacy, still read by the render script' : '';
        if ($note !== '') {
            // nothing more to say about it
        } else if ($option['dynamic']) {
            $note = 'filled per user';
        } else if ($option['values'] !== null) {
            $note = implode(' ', $option['values']);
        } else if ($option['suggested'] !== null) {
            $note = count($option['suggested']) . ' suggested, any value accepted';
        }
        printf("    %-24s %-14s %s\n", $name, $option['type'], $note);
    }
}

/*
 Compares the registry against the attributes the render scripts read.

 An option a render script reads but the widget list does not declare is one a
 dashboard may hold that the designer cannot edit. Where the render script
 still honours it, it belongs in the legacy block of the declaration. This is
 how the existing legacy blocks were arrived at.

 The other direction is only worth reading for the dashboard widgets. The vis
 and graph widgets pass every attribute into an iframe URL rather than reading
 them by name, so nothing there is read by name.
*/
function audit($registry)
{
    $modules = dirname(__DIR__, 2);
    $gaps = 0;

    // Group the declared options by the render script that produced them.
    $by_source = array();
    foreach ($registry as $type => $widget) {
        $script = preg_replace('/_widgets\.json$/', '_render.js', $widget['source']);
        foreach ($widget['options'] as $name => $option) {
            $by_source[$script][strtolower($name)] = true;
        }
    }

    foreach ($by_source as $script => $declared) {
        $file = $modules . '/' . preg_replace('#^Modules/#', '', $script);
        if (!is_file($file)) continue;

        preg_match_all('/attr\(\s*["\']([A-Za-z0-9_-]+)["\']/',
            file_get_contents($file), $matches);

        $read = array();
        foreach ($matches[1] as $name) {
            $name = strtolower($name);
            if (in_array($name, array('id', 'class', 'style', 'width', 'height'))) continue;
            $read[$name] = true;
        }

        $missing = array_diff_key($read, $declared);
        if (!count($missing)) continue;

        echo $script . "\n";
        foreach (array_keys($missing) as $name) {
            echo "  read by the render script, not declared: $name\n";
        }
        $gaps += count($missing);
    }

    if (!$gaps) echo "No gaps. Every attribute the render scripts read is declared.\n";
    return 0;
}

// Compares the registry against a census of stored dashboard content.
function compare($registry, $file)
{
    if (!is_file($file)) {
        fwrite(STDERR, "No such census file: $file\n");
        return 1;
    }
    $census = json_decode(file_get_contents($file), true);
    if (!is_array($census) || !isset($census['toplevel_class'])) {
        fwrite(STDERR, "Not a census file: $file\n");
        return 1;
    }

    // Written by the designer onto every widget box rather than being options.
    $structural = array('id', 'class', 'style');

    $unknown_types = array();
    $artefacts = array();
    foreach ($census['toplevel_class'] as $type => $count) {
        if (!isset($registry[$type])) $unknown_types[$type] = $count;
    }

    $undeclared = array();
    $undeclared_total = 0;
    if (isset($census['attrs_by_class'])) {
        foreach ($census['attrs_by_class'] as $type => $attrs) {
            if (!isset($registry[$type])) continue;
            foreach ($attrs as $name => $count) {
                if (in_array($name, $structural)) continue;
                if (widget_registry_option($type, $name)) continue;

                $reason = artefact_reason($registry, $type, $name);
                if ($reason !== false) {
                    if (!isset($artefacts[$reason])) $artefacts[$reason] = 0;
                    $artefacts[$reason] += $count;
                    continue;
                }
                $undeclared[$type][$name] = $count;
                $undeclared_total += $count;
            }
        }
    }

    $unused = array();
    foreach ($registry as $type => $widget) {
        $seen = isset($census['attrs_by_class'][$type]) ? $census['attrs_by_class'][$type] : array();
        $seen_lc = array();
        foreach ($seen as $name => $count) $seen_lc[strtolower($name)] = true;

        foreach ($widget['options'] as $name => $option) {
            if ($option['type'] === 'html' || $option['legacy']) continue;
            if (!isset($seen_lc[strtolower($name)])) $unused[$type][] = $name;
        }
    }

    echo "Census: " . $census['dashboards'] . " dashboards, "
        . ($census['dashboards'] - $census['empty']) . " with content\n";
    echo "Registry: " . count($registry) . " widget types\n\n";

    echo "Widget types in use that no module declares\n";
    if (!count($unknown_types)) {
        echo "  none\n";
    } else {
        arsort($unknown_types);
        foreach ($unknown_types as $type => $count) printf("  %-24s %d\n", $type, $count);
    }

    echo "\nAttributes in use that the widget does not declare ($undeclared_total in total)\n";
    if (!count($undeclared)) {
        echo "  none\n";
    } else {
        ksort($undeclared);
        foreach ($undeclared as $type => $attrs) {
            arsort($attrs);
            echo "  $type\n";
            foreach ($attrs as $name => $count) printf("    %-30s %d\n", $name, $count);
        }
    }

    echo "\nAttributes that are not options at all\n";
    if (!count($artefacts)) {
        echo "  none\n";
    } else {
        arsort($artefacts);
        foreach ($artefacts as $reason => $count) printf("  %-40s %d\n", $reason, $count);
    }

    echo "\nDeclared options never used in stored content\n";
    if (!count($unused)) {
        echo "  none\n";
    } else {
        ksort($unused);
        foreach ($unused as $type => $names) {
            echo "  " . str_pad($type, 24) . implode(' ', $names) . "\n";
        }
    }

    return 0;
}

// Some attributes in stored content were never options. Naming them keeps them
// out of the gap report, where they would look like registry holes.
function artefact_reason($registry, $type, $name)
{
    // Browser extensions add attributes to the page, and the designer saves
    // the page back with them in it.
    $extensions = array(
        'wfd-id' => 'Wappalyzer',
        'bis_skin_checked' => 'Bitdefender',
        '_msttexthash' => 'Microsoft Translator',
        '_msthash' => 'Microsoft Translator',
        'data-darkreader-inline-color' => 'Dark Reader',
        'data-ol-has-click-handler' => 'OneLaunch'
    );
    if (isset($extensions[$name])) {
        return 'browser extension (' . $extensions[$name] . ')';
    }

    // The Other box of a dropbox_other option is a second select carrying the
    // same class as the real inputs, so the designer saves its id as well.
    // See draw_options and the options-save handler in Views/js/designer.js.
    if (substr($name, -9) === '_dropdown') {
        $option = widget_registry_option($type, substr($name, 0, -9));
        if ($option && $option['type'] === 'dropbox_other') {
            return 'designer artefact (_dropdown companion select)';
        }
    }

    // A style attribute that lost its quoting spills the rest of the style
    // into stray attributes, see the htmlspecialchars_decode note in
    // tools/SCHEMA.md. The fragments are recognisable by the trailing colon
    // of a property name, or by being a word from a font family.
    if (substr($name, -1) === ':') {
        return 'broken style attribute (property name)';
    }
    $fragments = array('arial', 'black', 'narrow', 'helvetica', 'neue', 'comic',
        'sans', 'ms', 'courier', 'new', 'center', 'left', 'right', 'normal',
        'none', 'auto', 'absolute', 'rgb');
    if (in_array($name, $fragments)) {
        return 'broken style attribute (value fragment)';
    }

    return false;
}
