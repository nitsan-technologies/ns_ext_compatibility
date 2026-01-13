<?php

$EM_CONF['ns_ext_compatibility'] = [
    'title' => 'Upgrade Extensions Compatibility',
    'description' => 'Are you in need of a TYPO3 Extension that offers features such as system information reporting, statistical analysis of TYPO3 extensions, downloadable compatibility options, and more? TYPO3 Extensions Compatibility Report, tailored to meet your specific requirements! Compatible with major versions of TYPO3 12.x, it assists you in assessing the feasibility and complexity of TYPO3 upgrades. 
    
    ***  Live-Demo: https://demo.t3planet.com/t3-extensions/ext-compatibility *** Premium Version, Documentation & Free Support: https://t3planet.com/typo3-upgrade-extension-compatibility/',

    'category' => 'module',
    'author' => 'T3Planet // NITSAN',
    'author_email' => 'sanjay@nitsan.in',
    'author_company' => 'T3Planet // NITSAN',
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
    'version' => '12.0.0',
    'constraints' => [
        'depends' => [
           'typo3' => '12.0.0-12.9.99',
           'ns_license' => '13.0.0-13.9.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
