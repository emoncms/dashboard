<?php
    /*

    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org

    */
    
    define("MODULE_PATH","Modules");
    echo "<script type='application/javascript'>var requestTime = Date.now() /1000;var offsetofTime = 0;offsetofTime = Math.round(requestTime - ".time()."); // Offset in s from local to server time</script>";
    $widgets = array();

    // Load module specific widgets
    $basedir = scandir(MODULE_PATH);
    for ($i=2; $i<count($basedir); $i++) {
        $base = MODULE_PATH."/".$basedir[$i]."/widget";
        if (is_dir($base)) {
            // Look for /Modules/[module_name]/widget/[module_name].js or php files
            if (load_widget($base,$basedir[$i]))
                $widgets[] = $basedir[$i];
            $extendeddir = scandir($base);
            for ($j=2; $j<count($extendeddir); $j++) {
                $extended = $base."/".$extendeddir[$j];
                if (is_dir($extended)) {
                    // Look for /Modules/[module_name]/widget/[widget_name]/[widget_name].js or php files
                    if (load_widget($extended,$extendeddir[$j])) 
                        $widgets[] = $extendeddir[$j];
                }
            }
        }
    }
    
    function load_widget($folder,$widgetname)
    {
        global $path;
        $gotWidget = false;
        if (is_file($folder."/".$widgetname."_widget.php"))
        {
            require_once $folder."/".$widgetname."_widget.php";
            $gotWidget = true;
        }
        if (is_file($folder."/".$widgetname."_render.js"))
        {
            echo "<script type='text/javascript' src='".$path.$folder."/".$widgetname."_render.js?v=3'></script>";
            $gotWidget = true;
        }
        return $gotWidget;
    }
