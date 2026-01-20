<?php

$EM_CONF['ns_ext_compatibility'] = [
    'title' => 'TYPO3 Extension Upgrade Checker',
    'description' => 'Analyze the compatibility of your TYPO3 extensions before upgrading. Get insights, reports, and recommendations to ensure smooth version upgrades and better system stability.',
    
    'category' => 'module',
    'author' => 'Team T3Planet',
    'author_email' => 'info@t3planet.de',
    'author_company' => 'T3Planet',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'version' => '12.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-12.9.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
