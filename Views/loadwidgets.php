<?php
/*

All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org

*/

defined('EMONCMS_EXEC') or die('Restricted access');

define("MODULE_PATH", "Modules");
echo "<script type='application/javascript'>var requestTime = Date.now() /1000;var offsetofTime = 0;offsetofTime = Math.round(requestTime - " . time() . "); // Offset in s from local to server time</script>";
$widgets = array();

// Widgets that act when clicked, rather than only displaying data. The button
// widget writes to a feed and the curl widget sends a request to an address
// held in the dashboard, both in the session of whoever is looking at the page.
// Any dashboard marked public is shown to any logged in visitor, so a dashboard
// author can aim these at another account. Off unless an admin turns them on,
// see enable_action_widgets in default-settings.ini
global $settings, $path;
$action_widgets = array("button", "curl");
$action_widgets_enabled = !empty($settings['dashboard']['enable_action_widgets']);

// A dashboard saved earlier may still hold these widgets. Without their render
// scripts they draw as an empty box, so label them instead.
if (!$action_widgets_enabled) {
    load_js("Modules/dashboard/Views/js/disabledwidgets.js");
    echo "<script type='text/javascript'>disabled_widgets_init(" . json_encode($action_widgets) . ");</script>";
}

// Load module specific widgets
$basedir = scandir(MODULE_PATH);
for ($i = 2; $i < count($basedir); $i++) {
    $base = MODULE_PATH . "/" . $basedir[$i] . "/widget";
    if (is_dir($base)) {
        // Look for /Modules/[module_name]/widget/[module_name].js or php files
        $skip = in_array($basedir[$i], $action_widgets) && !$action_widgets_enabled;
        if (!$skip && load_widget($base, $basedir[$i])) {
            $widgets[] = $basedir[$i];
        }
        $extendeddir = scandir($base);
        for ($j = 2; $j < count($extendeddir); $j++) {
            $extended = $base . "/" . $extendeddir[$j];
            if (is_dir($extended)) {
                if (in_array($extendeddir[$j], $action_widgets) && !$action_widgets_enabled) continue;
                // Look for /Modules/[module_name]/widget/[widget_name]/[widget_name].js or php files
                if (load_widget($extended, $extendeddir[$j])) {
                    $widgets[] = $extendeddir[$j];
                }
            }
        }
    }
}

function load_widget($folder, $widgetname)
{
    global $path;
    $gotWidget = false;
    if (is_file($folder . "/" . $widgetname . "_widget.php")) {
        require_once $folder . "/" . $widgetname . "_widget.php";
        $gotWidget = true;
    }
    if (is_file($folder . "/" . $widgetname . "_render.js")) {
        load_js($folder . "/" . $widgetname . "_render.js");
        $gotWidget = true;
    }
    return $gotWidget;
}
