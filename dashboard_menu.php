<?php
global $mysqli,$route,$session;

if ($session["read"] || $session["public_userid"]) {

    require_once "Modules/dashboard/dashboard_model.php";
    $dashboard = new Dashboard($mysqli);
    load_language_files("Modules/dashboard/locale", "dashboard_messages");
        
    $l2 = array();
      
    if ($listmenu = $dashboard->build_menu_array('view')) {
        // Level 2 List of dashboards
        foreach ($listmenu as $dash) {
          if ($dash['published']){
            $id = $dash['id'];
            if ($dash['main']) {
                $icon = 'star';
            } else {
                $icon = 'star_border';
            }
            
            $dashboard_item = array(
                "name"=>$dash['name'],
                "title"=>$dash['desc'],
                "href"=>str_replace('dashboard/view&id','dashboard/view?id',$dash['path']),
                "icon"=>$icon, 
                "order"=>$dash['order']
            );
            
            if ($session['public_userid']) {
                $dashboard_item["href"] = $session['public_username']."/".$dashboard_item["href"];
            }   
            
            $l2[] = $dashboard_item;
            
            $menu["dashboards"]["default"] = str_replace('dashboard/view&id','dashboard/view?id',$dash['path']);
          }
        }
    }

    if ($session["write"]) {
        $l2[] = array(
            "name"=>ctx_tr("dashboard_messages","Configuration"),
            "href"=>"dashboard/list",
            "icon"=>"cog", 
            "order"=>99
        );
    }
    
    // Level 1 top bar
    if (count($l2)) {
        $menu["dashboards"] = array("name"=>tr("Dashboards"), "order"=>3, "icon"=>"dashboard", "default"=>"dashboard/list", "l2"=>$l2);
    } 
}
