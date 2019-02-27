<?php
    global $mysqli,$route,$session;
    require_once "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);

    // Contains a list for the drop down with dashboards available for user session type
    $listmenu = $dashboard->build_menu_array('view');

    
    // sidebar nav
    foreach ($listmenu as $dash) {
        $menu['sidebar']['dashboard'][] = array(
            'title' => $dash['desc'],
            'text' => $dash['name'],
            'path' => str_replace('dashboard/view&id','dashboard/view?id',$dash['path']),
            'order' => $dash['order']
        );
    }
    $menu['tabs'][] = array(
        'icon'=>'dashboard',
        'title'=> _("Dashboards"),
        'path'=> 'dashboard/view',
        'active'=> 'dashboard',
        'order' => 3,
        'data'=> array('sidebar' => '#sidebar_dashboard')
    );
    $menu['sidebar']['dashboard'][] = array(
        'text'=> _("All Dashboards"),
        'path'=> 'dashboard/list',
        'active'=> 'dashboard',
        'order' => 1
    );