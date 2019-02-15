<?php
    global $mysqli,$route,$session;
    require_once "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);

    // Contains a list for the drop down with dashboards available for user session type
    $listmenu = $dashboard->build_menu_array('view');


    // sidebar nav
    foreach ($listmenu as $dash) {
        $menu['dashboard'][] = array(
            'title' => $dash['desc'],
            'text' => $dash['name'],
            'path' => $dash['path'],
            'icon' => 'dashboard'
        );
    }
    $menu['category'][] = array(
        'li_class'=>'btn-li',
        'icon'=>'dashboard',
        'title'=> _("Dashboards"),
        'path'=> 'dashboard/view',
        'active'=> 'dashboard',
        'sort' => 3
    );
    $menu['dashboard'][] = array(
        'li_class'=>'btn-li',
        'text'=> _("All Dashboards"),
        'path'=> 'dashboard/list',
        'active'=> 'dashboard',
        'sort' => 1
    );