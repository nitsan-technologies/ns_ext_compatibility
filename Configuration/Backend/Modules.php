<?php

use NITSAN\NsExtCompatibility\Controller\NsExtCompatibilityController;

return [
    'ns_ext_compatibility' => [
        'parent' => 'tools',
        'position' => ['before' => 'module-extensionmanager'],
        'access' => 'user',
        'path' => '/module/tools/nsextcompatibility',
        'icon' => 'EXT:ns_ext_compatibility/Resources/Public/Icons/ns_ext_compatibility.svg', // Adjust icon identifier as needed
        'extensionName' => 'NsExtCompatibility',
        'labels' => 'LLL:EXT:ns_ext_compatibility/Resources/Private/Language/locallang.xlf',
        'controllerActions' => [
            NsExtCompatibilityController::class => [
                'list',
                'detail',
                'viewAllVersion',
                'exportXls'
            ],
        ],
    ],
];
