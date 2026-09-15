<?php

declare(strict_types=1);

use Netwerk\AiImageMetadata\Controller\BackendModuleController;

return [
    'media_ai_image_metadata' => [
        'parent' => 'media',
        'access' => 'user',
        'path' => '/module/media/ai-image-metadata',
        'iconIdentifier' => 'module-file',
        'labels' => 'LLL:EXT:ai_image_metadata/Resources/Private/Language/Modules.xlf',
        'routes' => [
            '_default' => [
                'target' => BackendModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
