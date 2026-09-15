<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\Service\Provider;

interface AiProviderInterface
{
    /**
     * @param string $imageData Die rohen Bilddaten (Dateiinhalt)
     * @param string $mimeType z.B. "image/jpeg"
     * @param string $fileName Originaler Dateiname als Kontext, z.B. "zillertal-sommer-wandern.jpg"
     * @return array{title: string, alternative: string, copyright: string}
     */
    public function describeImage(string $imageData, string $mimeType, string $fileName): array;

}