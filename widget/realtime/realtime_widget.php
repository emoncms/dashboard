<?php

defined('EMONCMS_EXEC') or die('Restricted access');

// Chart library, asked for through the loader so the graph widget and this
// one share the one copy, see dashboard_widget_script in Views/loadwidgets.php.
dashboard_widget_script("Lib/js/flot-5.1.0.mod.min.js");
// Frame, buttons and axis colours shared with the other chart widgets.
dashboard_widget_script("Modules/dashboard/Views/js/chart.helper.js");
dashboard_widget_style("Modules/dashboard/Views/js/chart.helper.css");
