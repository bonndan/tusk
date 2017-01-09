<?php

require './vendor/autoload.php';
ini_set('xdebug.max_nesting_level', 3000);

$t = new \Tusk\Tusk(Tusk\Configuration::create(
    [
        'source' => dirname(__DIR__) . '/mylegacyproject',
        'target' => dirname(__DIR__) . '/myshinynewgroovyproject',
        'namespaces' => [
            'src' => '__AUTO__',
            
            //folder actions go into package Shiny.Actions
            'actions' => 'Shiny.Actions',
            //include files go into package Shiny.Common
            'include' => 'Shiny.Common',
        ],
        
        //these folders are copied into the resources dir
        'resources' => [
            'templates' => 'templates',
            'languages' => 'languages',
        ],
        
        //the docs folder is copied into a new dir documentation
        'other' => [
            'docs' => 'documentation'
        ],
        
        //if these class names appear, import statements are added
        'onDemandImport' => [
            'Controller' => 'Shiny.Controller',
            'DatabaseInterface' => 'Shiny.DatabaseInterface',
            'Event' => 'Shiny.Event',
            'Base' => 'Shiny.Base',
            'Pagination' => 'Shiny.Pagination',
        ],
        
        //
        'replaceNames' => [
            //use function > import static
            'between' => 'Shiny.functions.between', 
            'valNum' => 'Shiny.functions.valNum',
            
            //simple name replacement, here a constant
            'DIR_SEPERATOR' => "'/'",
        ]
    ]
));

$t->run();