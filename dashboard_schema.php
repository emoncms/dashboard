<?php

$schema['dashboard'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'content' => array('type' => 'text'),
    'height' => array('type' => 'int(11)', 'default'=>'600'),
    'name' => array('type' => "varchar(30)", 'default'=>'no name'),
    'alias' => array('type' => "varchar(20)", 'default'=>''),
    'description' => array('type' => "varchar(255)", 'default'=>'no description'),
    'main' => array('type' => 'tinyint(1)', 'default'=>'0'),
    'public' => array('type' => 'tinyint(1)', 'default'=>'0'),
    'published' => array('type' => 'tinyint(1)', 'default'=>'0'),
    'showdescription' => array('type' => 'tinyint(1)', 'default'=>'0'),
    'backgroundcolor' => array('type' => "varchar(6)", 'default'=>'EDF7FC'),
    'gridsize' => array('type' => 'tinyint(1)', 'default'=>'20'),
    'fullscreen' => array('type' => 'tinyint(1)', 'default'=>'0'),
    'feedmode' => array('type' => "varchar(8)", 'default'=>'feedid')
);
