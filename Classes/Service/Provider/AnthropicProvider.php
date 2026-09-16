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

        public function describeImage(string $imageData, string $mimeType, string $fileName): array
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
                        'system' => <<<'PROMPT'
            Du bist SEO- und Barrierefreiheits-Redakteur für die Website.
            Du schreibst Bild-Metadaten auf Deutsch, in natürlicher, konkreter Sprache.

            Regeln für "alternative" (Alt-Text):
            - Beschreibe, was tatsächlich zu sehen ist: Motiv, Ort/Landschaft, Tätigkeit, Stimmung, Jahreszeit.
            - Nenne konkrete, suchrelevante Begriffe (z.B. "Wanderer auf Almwiese vor Gletscher" statt "Menschen in den Bergen").
            - Ein bis zwei vollständige Sätze, 80 bis 125 Zeichen.
            - Kein "Bild von", "Foto zeigt" oder ähnliches. Keine Schlüsselwort-Aufzählung.
            - Erfinde keine Ortsnamen oder Fakten, die nicht erkennbar sind.

            Regeln für "title":
            - Kurz und prägnant, 3 bis 8 Wörter, maximal 60 Zeichen.
            - Wie eine Bildunterschrift, die neugierig macht, aber sachlich bleibt.
            - Kein Punkt am Ende.

            Regeln für "copyright":
            - Dateinamen wurden beim Upload bereinigt: "©", "(c)", Leerzeichen und Klammern sind durch Unterstriche ersetzt. Ein Rechtevermerk sieht daher meist so aus: "_c_", "__c__", "_copyright_" oder "_Foto_", direkt gefolgt vom Namen des Rechteinhabers.
            - Der Name muss wörtlich im Dateinamen stehen. Übernimm nur, was tatsächlich dort steht.
            - Format: "Rechteinhaber / Fotograf". Unterstriche durch Leerzeichen ersetzen, zusammengeschriebene Namen trennen.
            - Ohne ©-Zeichen davor.
            - Wenn der Dateiname keinen solchen Vermerk enthält: leerer String "".
            PROMPT,
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