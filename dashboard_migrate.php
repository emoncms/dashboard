<?php
/*
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

---------------------------------------------------------------------
Replaces the old text and container widgets of a document with text, image
and panel widgets where the converters accept them.

Called on every save by dashboard_model::set_content, so a dashboard moves to
the new widgets the first time its author edits it, and by
tools/migrate_widgets.php to do the same for a whole install. A widget the
converters refuse is left as it is and keeps drawing as before.

See notes/TEXT-AND-IMAGE-WIDGETS.md and notes/PANEL-WIDGET.md.
*/

defined('EMONCMS_EXEC') or die('Restricted access');

require_once dirname(__FILE__) . "/dashboard_convert_text.php";
require_once dirname(__FILE__) . "/dashboard_convert_panel.php";

// Old widget types and the converter that replaces each.
function dashboard_migrate_old_types()
{
    $types = array();
    foreach (array_keys(dashboard_convert_text_widget_defaults()) as $type) {
        $types[$type] = 'dashboard_convert_text_widget';
    }
    foreach (array_keys(dashboard_convert_panel_defaults()) as $type) {
        $types[$type] = 'dashboard_convert_panel_widget';
    }
    return $types;
}

/**
 * Replaces the old widgets of a document in place.
 *
 * @param array $document a document with a widgets list
 * @param array $kept set to the old widgets left as they are, each as
 *                    array('index', 'type', 'reason')
 * @return array the widgets replaced, each as array('index', 'from', 'to')
 */
function dashboard_migrate_widgets(&$document, &$kept = array())
{
    $kept = array();
    $swapped = array();
    if (!is_array($document) || !isset($document['widgets']) || !is_array($document['widgets'])) {
        return $swapped;
    }

    $converters = dashboard_migrate_old_types();

    foreach ($document['widgets'] as $index => $widget) {
        $type = isset($widget['type']) ? (string) $widget['type'] : '';
        if (!isset($converters[$type])) continue;

        $convert = $converters[$type];
        $reason = '';
        $new = $convert($widget, $reason);
        if ($new === null) {
            $kept[] = array('index' => $index, 'type' => $type, 'reason' => $reason);
            continue;
        }

        $document['widgets'][$index] = $new;
        $swapped[] = array('index' => $index, 'from' => $type, 'to' => $new['type']);
    }

    return $swapped;
}

// Counts of a migration by old type and new type, for a log line or a
// message. "paragraph to text: 3, Container-Grey to panel: 1".
function dashboard_migrate_summary($swapped)
{
    $counts = array();
    foreach ($swapped as $swap) {
        $key = $swap['from'] . ' to ' . $swap['to'];
        if (!isset($counts[$key])) $counts[$key] = 0;
        $counts[$key]++;
    }
    $parts = array();
    foreach ($counts as $key => $count) $parts[] = "$key: $count";
    return implode(', ', $parts);
}
