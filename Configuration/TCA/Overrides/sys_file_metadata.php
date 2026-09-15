<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// EXT:filemetadata ships its own `copyright` field. Only define ours when that
// extension is not active, so both can never fight over the same column.
if (!isset($GLOBALS['TCA']['sys_file_metadata']['columns']['copyright'])) {
    $GLOBALS['TCA']['sys_file_metadata']['columns']['copyright'] = [
        'label' => 'LLL:EXT:ai_image_metadata/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.copyright',
        'description' => 'LLL:EXT:ai_image_metadata/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.copyright.description',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'max' => 255,
            'eval' => 'trim',
        ],
    ];

    ExtensionManagementUtility::addToAllTCAtypes(
        'sys_file_metadata',
        'copyright',
        '',
        'after:title'
    );
}
