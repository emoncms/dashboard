<?php

$schema['dashboard'] = [
    'id' => ['type' => 'int(11)', 'Null' => 'NO', 'Key' => 'PRI', 'Extra' => 'auto_increment'],
    'userid' => ['type' => 'int(11)'],
    'content' => ['type' => 'text'],
    // The widgets as JSON, see tools/SCHEMA.md. Both writes go through the
    // converter: a dashboard still holding html is converted the first time it
    // is loaded, and a save from the editor converts the page it posts. Once
    // this is set the content column is no longer read, it is left as the html
    // it has always been until it is dropped. mediumtext rather than text
    // because text caps at 64KB.
    'content_json' => ['type' => 'mediumtext'],
    'height' => ['type' => 'int(11)', 'default' => '600'],
    'name' => ['type' => "varchar(30)", 'default' => 'no name'],
    'alias' => ['type' => "varchar(20)", 'default' => ''],
    'description' => ['type' => "varchar(255)", 'default' => 'no description'],
    'main' => ['type' => 'tinyint(1)', 'default' => '0'],
    'public' => ['type' => 'tinyint(1)', 'default' => '0'],
    'published' => ['type' => 'tinyint(1)', 'default' => '0'],
    'showdescription' => ['type' => 'tinyint(1)', 'default' => '0'],
    'backgroundcolor' => ['type' => "varchar(6)", 'default' => 'EDF7FC'],
    'gridsize' => ['type' => 'tinyint(1)', 'default' => '20'],
    'fullscreen' => ['type' => 'tinyint(1)', 'default' => '0'],
    'feedmode' => ['type' => "varchar(8)", 'default' => 'feedid']
];
