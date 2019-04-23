<?php
    global $mysqli,$route,$session;
    require_once "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);

    // get the default dashboard 
    $default = array();
    foreach($dashboard->get_list($session['userid'],false,false) as $item){
        if($item['main']===true){
            $default = $item;
        }
    }

    // navbar link
    $menu['tabs'][] = array(
        'icon'=>'dashboard',
        'title'=> _("Dashboards"),
        'path'=> 'dashboard/view',
        'order' => 3,
        'data'=> array('sidebar' => '#sidebar_dashboard')
    );

    // sidebar nav list
    // Contains a list for the drop down with dashboards available for user session type
    $listmenu = $dashboard->build_menu_array('view');

    foreach ($listmenu as $dash) {
        preg_match('#dashboard\/view\&id=(\d+)#', $dash['path'], $matches);
        $id = !empty($matches[1]) ? (int) $matches[1]: '';
        $icon = !empty($default['id']) && $default['id'] === $id ? 'star': '';
        $menu['sidebar']['dashboard'][] = array(
            'title' => $dash['desc'],
            'text' => $dash['name'],
            'path' => str_replace('dashboard/view&id','dashboard/view?id',$dash['path']),
            'active' => str_replace('dashboard/view&id','dashboard/edit?id',$dash['path']),
            'order' => $dash['order'],
            'icon' => $icon,
            'data' => array(
                'id' => $id
            )
        );
    }

    $menu['sidebar']['dashboard'][] = array(
        'text'=> _("All Dashboards"),
        'path'=> 'dashboard/list',
        'order' => 1
    );