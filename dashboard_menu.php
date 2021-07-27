<?php
global $mysqli,$route,$session;

if ($session["read"]) {

    require_once "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);
    load_language_files("Modules/dashboard/locale", "dashboard_messages");

    // get the default dashboard 
    $default = array();
    foreach($dashboard->get_list($session['userid'],false,false) as $item){
        if($item['main']===true){
            $default = $item;
        }
    }

        
    // Level 1 top bar
    $menu["dashboards"] = array("name"=>_("Dashboards"), "order"=>3, "icon"=>"dashboard", "default"=>"dashboard/list", "l2"=>array());       
      
    if ($listmenu = $dashboard->build_menu_array('view')) {

        // Level 2 List of dashboards
        foreach ($listmenu as $dash) {
            $id = $dash['id'];
            $icon = !empty($default['id']) && $default['id'] === $id ? 'star': 'star_border';
            
            $menu["dashboards"]['l2'][] = array(
                "name"=>$dash['name'],
                "title"=>$dash['desc'],
                "href"=>str_replace('dashboard/view&id','dashboard/view?id',$dash['path']),
                "icon"=>$icon, 
                "order"=>$dash['order']
            );
            
            $menu["dashboards"]["default"] = str_replace('dashboard/view&id','dashboard/view?id',$dash['path']);
        }
    }

    if ($session["write"]) {
        $menu["dashboards"]['l2'][] = array(
            "name"=>dgettext("dashboard_messages","All Dashboards"),
            "href"=>"dashboard/list",
            "icon"=>"dashboard", 
            "order"=>99
        );
    }
}
