<?php

$schema['dashboard'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
    'content' => array('type' => 'text'),
    // The widgets as JSON, see tools/SCHEMA.md. Both writes go through the
    // converter: a dashboard still holding html is converted the first time it
    // is loaded, and a save from the editor converts the page it posts. Once
    // this is set the content column is no longer read, it is left as the html
    // it has always been until it is dropped. mediumtext rather than text
    // because text caps at 64KB.
    'content_json' => array('type' => 'mediumtext'),
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
