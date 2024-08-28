<?php

return [
    'ns_ext_compatibility' => [
     'parent' => 'web',
     'position' => ['after' => ''], // Adjust position as needed
     'access' => 'user,group', // Adjust access rights as needed
     'workspaces' => 'live',
     'icon' => 'EXT:ns_ext_compatibility/Resources/Public/Icons/ns_ext_compatibility.svg', // Adjust icon identifier as needed
     'extensionName' => 'NsExtCompatibility',
     'labels' => 'LLL:EXT:ns_ext_compatibility/Resources/Private/Language/locallang.xlf:module.title', // Adjust language file path as needed
     'controllerActions' => [

       \NITSAN\NsExtCompatibility\Controller\NsExtCompatibilityController::class
        => [
                     'list',
                     'detail'
         ],
     ],

 ],

 ];
