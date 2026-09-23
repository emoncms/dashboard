<?php

/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
*/

/*
 Reads the widget registry, which says which widgets exist and which options
 each one accepts. Dashboard content is validated against it before it is
 stored, so the rules have to be readable from PHP.

 Each widget declares itself in a *_widgets.json file beside its render script,
 following the same naming as load_widget in Views/loadwidgets.php:

   Modules/<module>/widget/<module>_widgets.json
   Modules/<module>/widget/<name>/<name>_widgets.json

 The files are generated from the JavaScript widget lists by
 tools/extract_registry.js, which is also how they are kept in step. Run it
 with --check to see whether a widget has gained an option that the registry
 does not know about yet.

 Option types, which follow the designer in Views/js/designer.js:

   feedid          a feed id or a tag:name association
   feedid_realtime as feedid
   value           free text
   dropbox         one of a fixed list, given in values
   dropbox_other   a list of suggestions, any text may be stored
   colour_picker   a hex colour, or none for no colour at all
   boolean         0 or 1
   number          an integer, within min and max where the declaration gives them
   url             an address a person follows
   image_url       an address the browser fetches
   html            the widget body rather than an attribute
   text            the widget body, held to a narrower vocabulary than html

 A dropbox marked dynamic is filled from the database for the logged in user,
 for example the saved graph list, so there is no fixed set to check against.

 A declaration may carry a config block, saying what a nested config on that
 widget may hold. A config is for settings that do not fit in an attribute,
 such as the feed list of a chart. See the inline config section of
 notes/SCHEMA.md.

 A declaration may also carry a legacy block, listing options the designer no
 longer offers but the render script still reads, such as units and unitend on
 feedvalue. These are merged into the options and marked legacy, so stored
 dashboards keep working while nothing new is written with them.

 A declaration marked iframe is a retired type with no render script, kept so
 the html converter reads its options. The widget drew an iframe, which the
 converter discards without comment, and dashboard_migrate.php replaces the
 widget before it is drawn. See widget/retired/retired_widgets.json.

 Option names are matched without regard to case. The designer declares some
 of them in mixed case, for example gradNumber on bar and periodLength on
 kwhperiod, but the browser lowercases attribute names, so that is the form
 stored content holds.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

/**
 * The full registry, keyed by widget type.
 *
 * Each entry holds:
 *   options  option name => array('type'=>..., 'values'|'suggested'=>..., 'dynamic'=>...)
 *   iframe   true for a retired type that drew an iframe
 *   module   the module the widget came from
 *   source   the declaration file, relative to the Emoncms root
 *
 * @param bool $reload read the files again rather than using the cached copy
 * @return array
 */
function widget_registry($reload = false)
{
    static $registry = null;
    if ($registry !== null && !$reload) {
        return $registry;
    }

    $registry = [];
    $modules = dirname(__DIR__);

    foreach (widget_registry_files($modules) as $file => $module) {
        $declared = json_decode(file_get_contents($file), true);
        if (!is_array($declared) || empty($declared['widgets']) || !is_array($declared['widgets'])) {
            continue;
        }
        $source = ltrim(str_replace($modules, 'Modules', $file), '/');

        foreach ($declared['widgets'] as $type => $widget) {
            // Widget types share one namespace because the class attribute on a
            // dashboard box is the type. First declaration wins, matching the
            // order loadwidgets.php loads the render scripts in.
            if (isset($registry[$type])) {
                continue;
            }

            $options = [];
            widget_registry_read_options($options, $widget, 'options', false);
            widget_registry_read_options($options, $widget, 'legacy', true);

            // Attribute names arrive lowercased from the browser, so keep an
            // index back to the name the declaration uses.
            $lowercase = [];
            foreach ($options as $name => $option) {
                $lowercase[strtolower($name)] = $name;
            }

            $registry[$type] = [
                'options' => $options,
                'lowercase' => $lowercase,
                'config' => widget_registry_read_config($widget),
                'iframe' => !empty($widget['iframe']),
                'module' => $module,
                'source' => $source
            ];
        }
    }

    ksort($registry);
    return $registry;
}

/**
 * Copies one block of option declarations into the options of a widget.
 *
 * @param array $options collected options, added to in place
 * @param array $widget the declaration read from the JSON file
 * @param string $block which block to read, options or legacy
 * @param bool $legacy whether to mark what is read as legacy
 */
function widget_registry_read_options(&$options, $widget, $block, $legacy)
{
    if (empty($widget[$block]) || !is_array($widget[$block])) {
        return;
    }

    foreach ($widget[$block] as $name => $option) {
        if (!is_string($name) || $name === '' || !is_array($option)) {
            continue;
        }
        if (isset($options[$name])) {
            continue;
        }
        $options[$name] = [
            'type' => isset($option['type']) ? $option['type'] : 'value',
            'values' => isset($option['values']) ? $option['values'] : null,
            'suggested' => isset($option['suggested']) ? $option['suggested'] : null,
            'min' => isset($option['min']) ? (int) $option['min'] : null,
            'max' => isset($option['max']) ? (int) $option['max'] : null,
            'dynamic' => !empty($option['dynamic']),
            'legacy' => $legacy
        ];
    }
}

/**
 * The config contract of a widget, or null if it does not take one.
 *
 * A config is a nested object the dashboard document holds beside the options,
 * for a widget whose settings do not fit in attributes. The declaration names
 * the blocks it may hold and what may go in each, shaped like an option, so
 * the same per type checks apply.
 *
 * @param array $widget the declaration read from the JSON file
 * @return array|null block name => entry name => option shaped array
 */
function widget_registry_read_config($widget)
{
    if (empty($widget['config']) || !is_array($widget['config'])) {
        return null;
    }

    $config = [];
    foreach ($widget['config'] as $block => $entries) {
        if (!is_string($block) || $block === '' || !is_array($entries)) {
            continue;
        }

        $kept = [];
        foreach ($entries as $name => $entry) {
            if (!is_string($name) || $name === '' || !is_array($entry)) {
                continue;
            }
            $kept[$name] = [
                'name' => $name,
                'type' => isset($entry['type']) ? $entry['type'] : 'value',
                'values' => isset($entry['values']) ? $entry['values'] : null,
                'suggested' => isset($entry['suggested']) ? $entry['suggested'] : null,
                'dynamic' => !empty($entry['dynamic']),
                'legacy' => false
            ];
        }
        if (count($kept)) {
            $config[$block] = $kept;
        }
    }
    return count($config) ? $config : null;
}

/**
 * Finds the declaration files, in the order loadwidgets.php loads widgets.
 *
 * @param string $modules absolute path of the Modules directory
 * @return array file path => module name
 */
function widget_registry_files($modules)
{
    $files = [];
    if (!is_dir($modules)) {
        return $files;
    }

    $names = scandir($modules);
    sort($names);

    foreach ($names as $module) {
        if ($module === '.' || $module === '..') {
            continue;
        }
        $base = $modules . '/' . $module . '/widget';
        if (!is_dir($base)) {
            continue;
        }

        $file = $base . '/' . $module . '_widgets.json';
        if (is_file($file)) {
            $files[$file] = $module;
        }

        $entries = scandir($base);
        sort($entries);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $nested = $base . '/' . $entry;
            if (!is_dir($nested)) {
                continue;
            }
            $file = $nested . '/' . $entry . '_widgets.json';
            if (is_file($file)) {
                $files[$file] = $module;
            }
        }
    }
    return $files;
}

/**
 * Render scripts that define widgets but have no declaration beside them.
 *
 * A missing declaration is not an error the registry can see on its own, the
 * widgets simply do not appear. It matters because the graph widget lives in
 * another repository, so a partial deployment leaves its type looking
 * unknown. Tools that care report what this returns.
 *
 * @return array render script path => expected declaration path, both relative
 */
function widget_registry_missing()
{
    $missing = [];
    $modules = dirname(__DIR__);
    if (!is_dir($modules)) {
        return $missing;
    }

    $names = scandir($modules);
    sort($names);

    foreach ($names as $module) {
        if ($module === '.' || $module === '..') {
            continue;
        }
        $base = $modules . '/' . $module . '/widget';
        if (!is_dir($base)) {
            continue;
        }

        widget_registry_check_folder($missing, $modules, $base, $module);

        $entries = scandir($base);
        sort($entries);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $nested = $base . '/' . $entry;
            if (is_dir($nested)) {
                widget_registry_check_folder($missing, $modules, $nested, $entry);
            }
        }
    }
    return $missing;
}

function widget_registry_check_folder(&$missing, $modules, $folder, $name)
{
    $script = $folder . '/' . $name . '_render.js';
    $declaration = $folder . '/' . $name . '_widgets.json';
    if (!is_file($script) || is_file($declaration)) {
        return;
    }
    if (!preg_match('/function\s+[A-Za-z0-9_$]+_widgetlist/', file_get_contents($script))) {
        return;
    }

    $short = function ($path) use ($modules) {
        return ltrim(str_replace($modules, 'Modules', $path), '/');
    };
    $missing[$short($script)] = $short($declaration);
}

/**
 * The config contract of one widget type, or false if it takes no config.
 *
 * @param string $type widget type
 * @return array|false
 */
function widget_registry_config($type)
{
    $registry = widget_registry();
    if (!isset($registry[$type]) || empty($registry[$type]['config'])) {
        return false;
    }
    return $registry[$type]['config'];
}

/**
 * The declaration of one option, or false if the widget or option is unknown.
 *
 * The name is matched without regard to case, and the declared name is
 * returned in the entry so a converter can write the canonical form.
 *
 * @param string $type widget type
 * @param string $name option name as stored
 * @return array|false
 */
function widget_registry_option($type, $name)
{
    $registry = widget_registry();
    if (!isset($registry[$type])) {
        return false;
    }

    $key = strtolower($name);
    if (!isset($registry[$type]['lowercase'][$key])) {
        return false;
    }

    $declared = $registry[$type]['lowercase'][$key];
    $option = $registry[$type]['options'][$declared];
    $option['name'] = $declared;
    return $option;
}
