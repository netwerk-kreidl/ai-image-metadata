<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\Service\Provider;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;

final readonly class AnthropicProvider implements AiProviderInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function __construct(
        private RequestFactory $requestFactory,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

        public function describeImage(string $imageData, string $mimeType, string $fileName, string $systemPrompt): array
    {
                // Umgebungsvariable hat Vorrang, sonst der Wert aus der Extension-Konfiguration
        $apiKey = (string)(getenv('AI_IMAGE_METADATA_API_KEY')
            ?: $this->extensionConfiguration->get('ai_image_metadata', 'apiKey'));
        $model = (string)$this->extensionConfiguration->get('ai_image_metadata', 'model');

        // 1. Anfrage zusammenbauen
        $body = [
            'model' => $model,
            'max_tokens' => 1024,
            'output_config' => [
                'effort' => 'low',
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'alternative' => ['type' => 'string'],
                            'copyright' => ['type' => 'string'],
                        ],
                        'required' => ['title', 'alternative', 'copyright'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => base64_encode($imageData),
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Erstelle Titel und Alt-Text für dieses Bild. '
                                . 'Der Dateiname lautet "' . $fileName . '" – nutze ihn als Hinweis auf Ort oder Thema, '
                                . 'aber nur, wenn er zum Bildinhalt passt.',
                        ],
                    ],
                ],
            ],
        ];

        // 2. Abschicken
        $response = $this->requestFactory->request(self::API_URL, 'POST', [
            'headers' => [
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ],
            'body' => json_encode($body, JSON_THROW_ON_ERROR),
        ]);

        // 3. Antwort auslesen
        $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($result['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $data = json_decode($block['text'], true, 512, JSON_THROW_ON_ERROR);
                $hasCopyrightMarker = preg_match('/(^|_)c_|copyright|foto/i', $fileName) === 1;
                
                return [
                    'title' => trim((string)($data['title'] ?? '')),
                    'alternative' => trim((string)($data['alternative'] ?? '')),
                    'copyright' => $hasCopyrightMarker ? trim((string)($data['copyright'] ?? '')) : '',
                ];
            }
        }

        throw new \RuntimeException('Claude hat keine verwertbare Antwort geliefert.', 1757930000);
    }
}