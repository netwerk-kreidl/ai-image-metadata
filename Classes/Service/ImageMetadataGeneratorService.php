<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\Service;

use Netwerk\AiImageMetadata\Service\Provider\AnthropicProvider;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\FileProcessingAspect;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;

final readonly class ImageMetadataGeneratorService
{
    public function __construct(
        private AnthropicProvider $provider,
        private Context $context,
    ) {}

    /**
     * @return array{title: string, alternative: string, copyright: string}
     */
    public function generate(File $file): array
    {
        // Im Backend verschiebt TYPO3 die Bildverarbeitung normalerweise auf später.
        // Wir brauchen die verkleinerte Datei aber sofort - also abschalten.
        $this->context->setAspect('fileProcessing', new FileProcessingAspect(false));

        $preview = $file->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, [
            'width' => 1000,
            'height' => 1000,
        ]);

        $result = $this->provider->describeImage(
            $preview->getContents(),
            $preview->getMimeType(),
            $file->getName(),
        );

        // Agentur-Konvention: Copyright wird mit ©-Zeichen an den Titel angehängt
        if ($result['copyright'] !== '' && !str_contains($result['title'], '©')) {
            $result['title'] .= ' © ' . $result['copyright'];
        }

        return $result;
    }
}