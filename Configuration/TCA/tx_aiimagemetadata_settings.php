<?php

declare(strict_types=1);

defined('TYPO3') or die();

$ll = 'LLL:EXT:ai_image_metadata/Resources/Private/Language/locallang_settings.xlf:';

return [
    'ctrl' => [
        'title' => $ll . 'table',
        'label' => 'business_name',
        'rootLevel' => 1,
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
        'typeicon_classes' => [
            'default' => 'actions-cog',
        ],
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;' . $ll . 'tab.business,
                    business_name, business_type, location, target_group,
                --div--;' . $ll . 'tab.style,
                    tone, output_language, alt_max, title_max,
                --div--;' . $ll . 'tab.vocabulary,
                    keywords, forbidden_words, examples, extra_instructions',
        ],
    ],
    'columns' => [
        'business_name' => [
            'label' => $ll . 'business_name',
            'description' => $ll . 'business_name.description',
            'config' => ['type' => 'input', 'size' => 40, 'max' => 255, 'eval' => 'trim'],
        ],
        'business_type' => [
            'label' => $ll . 'business_type',
            'description' => $ll . 'business_type.description',
            'config' => ['type' => 'input', 'size' => 40, 'max' => 255, 'eval' => 'trim'],
        ],
        'location' => [
            'label' => $ll . 'location',
            'description' => $ll . 'location.description',
            'config' => ['type' => 'input', 'size' => 40, 'max' => 255, 'eval' => 'trim'],
        ],
        'target_group' => [
            'label' => $ll . 'target_group',
            'description' => $ll . 'target_group.description',
            'config' => ['type' => 'input', 'size' => 40, 'max' => 255, 'eval' => 'trim'],
        ],
        'tone' => [
            'label' => $ll . 'tone',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'neutral',
                'items' => [
                    ['label' => $ll . 'tone.neutral', 'value' => 'neutral'],
                    ['label' => $ll . 'tone.warm', 'value' => 'warm'],
                    ['label' => $ll . 'tone.upscale', 'value' => 'upscale'],
                    ['label' => $ll . 'tone.casual', 'value' => 'casual'],
                ],
            ],
        ],
        'output_language' => [
            'label' => $ll . 'output_language',
            'description' => $ll . 'output_language.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'de',
                'items' => [
                    ['label' => 'Deutsch', 'value' => 'de'],
                    ['label' => 'English', 'value' => 'en'],
                    ['label' => 'Italiano', 'value' => 'it'],
                    ['label' => 'Français', 'value' => 'fr'],
                    ['label' => 'Nederlands', 'value' => 'nl'],
                ],
            ],
        ],
        'alt_max' => [
            'label' => $ll . 'alt_max',
            'description' => $ll . 'alt_max.description',
            'config' => [
                'type' => 'number',
                'default' => 125,
                'range' => ['lower' => 40, 'upper' => 250],
            ],
        ],
        'title_max' => [
            'label' => $ll . 'title_max',
            'description' => $ll . 'title_max.description',
            'config' => [
                'type' => 'number',
                'default' => 60,
                'range' => ['lower' => 20, 'upper' => 120],
            ],
        ],
        'keywords' => [
            'label' => $ll . 'keywords',
            'description' => $ll . 'keywords.description',
            'config' => ['type' => 'text', 'cols' => 40, 'rows' => 4],
        ],
        'forbidden_words' => [
            'label' => $ll . 'forbidden_words',
            'description' => $ll . 'forbidden_words.description',
            'config' => ['type' => 'text', 'cols' => 40, 'rows' => 3],
        ],
        'examples' => [
            'label' => $ll . 'examples',
            'description' => $ll . 'examples.description',
            'config' => ['type' => 'text', 'cols' => 40, 'rows' => 6],
        ],
        'extra_instructions' => [
            'label' => $ll . 'extra_instructions',
            'description' => $ll . 'extra_instructions.description',
            'config' => ['type' => 'text', 'cols' => 40, 'rows' => 6],
        ],
    ],
];
