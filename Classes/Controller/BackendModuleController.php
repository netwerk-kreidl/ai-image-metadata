<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use Netwerk\AiImageMetadata\Service\ImageMetadataGeneratorService;

#[AsController]
final readonly class BackendModuleController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private PageRenderer $pageRenderer,
        private ResourceFactory $resourceFactory,
        private MetaDataRepository $metaDataRepository,
        private ImageMetadataGeneratorService $imageMetadataGeneratorService,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle('AI Image Metadata');
        $this->pageRenderer->addCssFile(
            'EXT:ai_image_metadata/Resources/Public/Css/backend.css'
        );
        $this->pageRenderer->loadJavaScriptModule(
            '@typo3/backend/element/progress-bar-element.js'
        );

        $parsedBody = (array)($request->getParsedBody() ?? []);
        $queryParams = $request->getQueryParams();

        $action = (string)($parsedBody['action'] ?? '');

        // Welches Bild der Liste gerade angezeigt wird (0 = das erste)
        $position = (int)($parsedBody['position'] ?? $queryParams['position'] ?? 0);
        $fileUid = 0;

        $successMessage = '';

        if ($action === 'saveMetadata' || $action === 'saveAndNext') {
            $fileUid = (int)($parsedBody['fileUid'] ?? 0);
            $metadata = (array)($parsedBody['metadata'] ?? []);

            $title = trim((string)($metadata['title'] ?? ''));
            $alternative = trim((string)($metadata['alternative'] ?? ''));
            $description = trim((string)($metadata['description'] ?? ''));
            $copyright = trim((string)($metadata['copyright'] ?? ''));

            if ($fileUid > 0) {
                $fileToUpdate = $this->resourceFactory->getFileObject($fileUid);
                

                if ($fileToUpdate->checkActionPermission('editMeta')) {
                    
                    $fileToUpdate->getMetaData()->add([
                        'title' => $title,
                        'alternative' => $alternative,
                        'description' => $description,
                        'copyright' => $copyright,
                    ])->save();
                    $successMessage = 'Metadata saved.';
                }
            }
        }

        $suggestion = null;
        $suggestionFileUid = 0;

        if ($action === 'generateMetadata') {
            $suggestionFileUid = (int)($parsedBody['fileUid'] ?? 0);

            if ($suggestionFileUid > 0) {
                $fileToDescribe = $this->resourceFactory->getFileObject($suggestionFileUid);
                $suggestion = $this->imageMetadataGeneratorService->generate($fileToDescribe);
            }
        }

        $id = (string)($parsedBody['id'] ?? $queryParams['id'] ?? '1:/');

        $folder = null;
        $images = [];
        $totalImages = 0;
        $completeImages = 0;

        if ($id !== '') {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($id);

            foreach ($folder->getFiles() as $file) {
                if (!$file->isImage()) {
                    continue;
                }

                $metadata = $this->metaDataRepository->findByFile($file);

                $title = trim((string)($metadata['title'] ?? ''));
                $alternative = trim((string)($metadata['alternative'] ?? ''));

                $isIncomplete = $title === '' || $alternative === '';

                $totalImages++;

                if (!$isIncomplete) {
                    $completeImages++;
                }

                if ($isIncomplete) {
                    $images[] = [
                        'file' => $file,
                        'metadata' => $metadata,
                        'suggestion' => $file->getUid() === $suggestionFileUid ? $suggestion : null,
                    ];
                }
            }
        }

        // Nach "Speichern & weiter": zum Bild nach dem gerade gespeicherten springen.
        // Ist das gespeicherte Bild durch den Filter aus der Liste verschwunden,
        // rutscht das nächste automatisch auf seine Position - dann bleibt $position.
        if ($action === 'saveAndNext') {
            foreach ($images as $index => $image) {
                if ($image['file']->getUid() === $fileUid) {
                    $position = $index + 1;
                    break;
                }
            }
        }

        // Hinter dem letzten Bild angekommen? Dann Abschluss statt Bild anzeigen.
        $isFinished = $images !== [] && $position > count($images) - 1;

        $position = max(0, min($position, count($images) - 1));
        $currentImage = $isFinished ? null : ($images[$position] ?? null);

        $percentComplete = $totalImages > 0
            ? (int)round($completeImages / $totalImages * 100)
            : 0;

        $moduleTemplate->assignMultiple([
            'id' => $id,
            'folder' => $folder,
            'images' => $images,
            'successMessage' => $successMessage,
            'totalImages' => $totalImages,
            'completeImages' => $completeImages,
            'percentComplete' => $percentComplete,
            'currentImage' => $currentImage,
            'position' => $position,
            'previousPosition' => $position - 1,
            'nextPosition' => $position + 1,
            'hasPrevious' => $position > 0,
            'hasNext' => $position < count($images) - 1,
            'listCount' => count($images),
            'isFinished' => $isFinished,
        ]);

        return $moduleTemplate->renderResponse('BackendModule/Index');
    }
}
