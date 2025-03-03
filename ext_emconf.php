<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "localizer"
 *
 * Auto generated by Extension Builder 2015-07-13
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF['localizer'] = [
    'title' => 'Localizer for TYPO3',
    'description' => 'This extension provides a fully automated workflow and GUI for the well known Localization Manager (l10nmgr). While L10nmgr still provides exports and imports, Localizer will take care of all necessary steps in between. Editors responsible for translations won\'t have to deal with L10nmgr configurations anymore and administrators just create one configuration per Localizer Project. Sponsor us here: https://coders.care/for/crowdfunding/l10nmgr-and-localizer',
    'category' => 'module',
    'author' => 'Jo Hasenau, Peter Russ, Stefano Kowalke',
    'author_email' => 'jh@cybercraft.de, peter.russ@4many.net, info@arroba-it.de',
    'state' => 'stable',
    'clearCacheOnLoad' => 0, // TODO: Remove when dropping v11 support. See https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/FileStructure/ExtEmconf.html#file-ext-emconf-php
    'version' => '12.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'scheduler' => '11.5.0-12.4.99',
            'static_info_tables' => '6.9.0-0.0.0',
            'l10nmgr' => '12.0.0-0.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
