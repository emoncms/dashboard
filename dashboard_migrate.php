<?php

/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

---------------------------------------------------------------------
Replaces the old widgets of a document with the widgets that succeed them:
text and container widgets with text, image and panel widgets where the
converters accept them, and the chart widgets of the retired vis module with
graph widgets holding their chart. Also folds the saved graph a graph widget
once pointed at into the widget, see dashboard_migrate_graph_pointers.

Called on every load by dashboard_model::content_html, so a dashboard moves
to the new widgets the first time it is opened, on every save by
dashboard_model::set_content, and by tools/migrate.php to do the same
for a whole install. A widget the converters refuse is left as it is.

See notes/TEXT-IMAGE-PANEL.md and
notes/CHARTS.md.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

require_once dirname(__FILE__) . "/dashboard_convert_text.php";
require_once dirname(__FILE__) . "/dashboard_convert_panel.php";
require_once dirname(__FILE__) . "/dashboard_convert_chart.php";

/**
 * Brings a document up to the current version, in place.
 *
 * Version 1 has no widget ids. Version 2 gives every widget an id and holds
 * next_id, the counter a fresh id is minted from, see SCHEMA.md. The ids
 * are numbered from the array index, which is what the renderer drew a
 * version 1 document with, so a dashboard draws with the same ids before
 * and after.
 *
 * @param array $document
 * @return bool whether anything changed
 */
function dashboard_upgrade_document(&$document)
{
    if (!is_array($document) || !isset($document['widgets']) || !is_array($document['widgets'])) {
        return false;
    }
    $version = isset($document['version']) ? (int) $document['version'] : 0;
    if ($version !== 1) {
        return false;
    }

    $widgets = [];
    foreach ($document['widgets'] as $index => $widget) {
        if (!is_array($widget)) {
            $widget = [];
        }
        $widgets[] = ['id' => $index + 1] + $widget;
    }

    $upgraded = ['version' => DASHBOARD_DOCUMENT_VERSION, 'next_id' => count($widgets) + 1];
    foreach ($document as $key => $value) {
        if ($key === 'version' || $key === 'next_id') {
            continue;
        }
        $upgraded[$key] = $key === 'widgets' ? $widgets : $value;
    }
    $document = $upgraded;
    return true;
}

// Old widget types and the converter that replaces each.
function dashboard_migrate_old_types()
{
    $types = [];
    foreach (array_keys(dashboard_convert_text_widget_defaults()) as $type) {
        $types[$type] = 'dashboard_convert_text_widget';
    }
    foreach (array_keys(dashboard_convert_panel_defaults()) as $type) {
        $types[$type] = 'dashboard_convert_panel_widget';
    }
    foreach (dashboard_convert_chart_types() as $type) {
        $types[$type] = 'dashboard_convert_chart_widget';
    }
    return $types;
}

/**
 * Replaces the old widgets of a document in place.
 *
 * @param array $document a document with a widgets list
 * @param array $kept set to the old widgets left as they are, each as
 *                    array('index', 'type', 'reason')
 * @param array $context passed to each converter. The chart converter reads
 *                       multigraph from it, see dashboard_convert_chart_widget
 * @param array|null $only the old types to replace, or null for all of them
 * @return array the widgets replaced, each as array('index', 'from', 'to')
 */
function dashboard_migrate_widgets(&$document, &$kept = [], $context = [], $only = null)
{
    $kept = [];
    $swapped = [];
    if (!is_array($document) || !isset($document['widgets']) || !is_array($document['widgets'])) {
        return $swapped;
    }

    $converters = dashboard_migrate_old_types();
    if (is_array($only)) {
        $converters = array_intersect_key($converters, array_flip($only));
    }

    foreach ($document['widgets'] as $index => $widget) {
        $type = isset($widget['type']) ? (string) $widget['type'] : '';
        if (!isset($converters[$type])) {
            continue;
        }

        $convert = $converters[$type];
        $reason = '';
        $new = $convert($widget, $reason, $context);
        if ($new === null) {
            $kept[] = ['index' => $index, 'type' => $type, 'reason' => $reason];
            continue;
        }

        // A replaced widget is the same widget, so it keeps its id.
        if (isset($widget['id'])) {
            $new = ['id' => $widget['id']] + $new;
        }
        $document['widgets'][$index] = $new;
        $swapped[] = ['index' => $index, 'from' => $type, 'to' => $new['type']];
    }

    return $swapped;
}

/**
 * Folds the saved graph a graph widget points at into the widget.
 *
 * A graph widget once held graphid, a pointer to a row in the graph table,
 * and drew the saved graph. The chart is held in the widget as config now,
 * and a saved graph is a starting point the designer loads into the form.
 * A widget with a graphid and no config is given the chart of the saved
 * graph, read through $context['graph']. The pointer is then removed, from
 * a widget that holds a config too. A widget whose saved graph is gone, or
 * not readable by the dashboard owner, is left with no chart. A read that
 * fails leaves the widget as it is for the next load.
 *
 * $context['graph'] takes a graph id and returns the saved graph as an
 * array, null when it is gone or not readable, or false when the read
 * failed. See dashboard_migrate_graph_loader.
 *
 * @param array $document a document with a widgets list
 * @param array $context
 * @return array the widgets changed, each as array('index', 'graphid',
 *               'result') where result is loaded, gone or cleared. cleared
 *               is a pointer removed from a widget that held a config, or
 *               an empty pointer removed
 */
function dashboard_migrate_graph_pointers(&$document, $context = [])
{
    $changed = [];
    if (!is_array($document) || !isset($document['widgets']) || !is_array($document['widgets'])) {
        return $changed;
    }

    foreach ($document['widgets'] as $index => $widget) {
        if (!is_array($widget) || !isset($widget['type']) || $widget['type'] !== 'graph') {
            continue;
        }
        if (!isset($widget['options']) || !is_array($widget['options'])) {
            continue;
        }
        if (!array_key_exists('graphid', $widget['options'])) {
            continue;
        }
        $graphid = trim((string) $widget['options']['graphid']);
        $held = isset($widget['config']) && is_array($widget['config']) && count($widget['config']);

        $result = 'cleared';
        if ($graphid !== '' && !$held) {
            if (!isset($context['graph']) || !is_callable($context['graph'])) {
                continue;
            }
            $saved = call_user_func($context['graph'], $graphid);
            if ($saved === false) {
                continue;
            }
            if (is_array($saved)) {
                $widget['config'] = dashboard_convert_multigraph_config($saved);
                $result = 'loaded';
            } else {
                $result = 'gone';
            }
        }

        unset($widget['options']['graphid']);
        $document['widgets'][$index] = $widget;
        $changed[] = ['index' => $index, 'graphid' => $graphid, 'result' => $result];
    }

    return $changed;
}

/**
 * A reader of saved graphs for dashboard_migrate_graph_pointers.
 *
 * Reads through the graph model with the access rule of its API: the
 * owner of the dashboard reads their own graphs, and another user's graph
 * only when every feed on it is public. That is what the widget could
 * fetch when it drew the saved graph. Returns null when the graph or feed
 * module is not installed.
 *
 * @param mysqli $mysqli
 * @param int $userid the dashboard owner
 * @param mixed $redis the redis connection, or false
 * @param array $settings the emoncms settings
 * @param EmonLogger|null $log
 * @return callable|null
 */
function dashboard_migrate_graph_loader($mysqli, $userid, $redis, $settings, $log = null)
{
    $root = dirname(dirname(__DIR__));
    if (
        !file_exists($root . "/Modules/graph/graph_model.php")
        || !file_exists($root . "/Modules/feed/feed_model.php")
    ) {
        return null;
    }
    // The models log through EmonLogger, which a command line tool has not
    // loaded.
    if (!class_exists('EmonLogger')) {
        require_once $root . "/Lib/EmonLogger.php";
    }
    require_once $root . "/Modules/feed/feed_model.php";
    require_once $root . "/Modules/graph/graph_model.php";

    $feed_settings = isset($settings['feed']) ? $settings['feed'] : [];
    $userid = (int) $userid;
    return function ($graphid) use ($mysqli, $redis, $feed_settings, $userid, $log) {
        if (!ctype_digit((string) $graphid)) {
            return null;
        }
        try {
            $feed = new Feed($mysqli, $redis, $feed_settings);
            $graph = new Graph($mysqli, $feed);
            $saved = $graph->get($userid, (int) $graphid);
        } catch (Throwable $e) {
            if ($log) {
                $log->warn("saved graph $graphid not read: " . $e->getMessage());
            }
            return false;
        }
        if (is_array($saved)) {
            // The model answers success false for a graph that is gone or
            // that the user may not read.
            return null;
        }
        return json_decode(json_encode($saved), true);
    };
}

// One log line for the graph pointers folded into a document.
// "widget 3 loaded saved graph 41, widget 5 saved graph 42 gone".
function dashboard_migrate_graph_summary($changed)
{
    $parts = [];
    foreach ($changed as $change) {
        if ($change['result'] === 'loaded') {
            $parts[] = "widget " . $change['index'] . " loaded saved graph " . $change['graphid'];
        } elseif ($change['result'] === 'gone') {
            $parts[] = "widget " . $change['index'] . " saved graph " . $change['graphid'] . " gone";
        }
    }
    return implode(', ', $parts);
}

// Counts of a migration by old type and new type, for a log line or a
// message. "paragraph to text: 3, Container-Grey to panel: 1".
function dashboard_migrate_summary($swapped)
{
    $counts = [];
    foreach ($swapped as $swap) {
        $key = $swap['from'] . ' to ' . $swap['to'];
        if (!isset($counts[$key])) {
            $counts[$key] = 0;
        }
        $counts[$key]++;
    }
    $parts = [];
    foreach ($counts as $key => $count) {
        $parts[] = "$key: $count";
    }
    return implode(', ', $parts);
}
