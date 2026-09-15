<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\DataProcessing;

use Netwerk\AiImageMetadata\Domain\Repository\CopyrightRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

#[Autoconfigure(
    tags: [['name' => 'data.processor', 'identifier' => 'copyright-list']],
    public: true
)]
final readonly class CopyrightListProcessor implements DataProcessorInterface
{
    public function __construct(
        private CopyrightRepository $copyrightRepository,
    ) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $targetVariableName = (string)$cObj->stdWrapValue(
            'as',
            $processorConfiguration,
            'copyrights'
        );

        $processedData[$targetVariableName] = $this->copyrightRepository->findUsedCopyrights();

        return $processedData;
    }
}
