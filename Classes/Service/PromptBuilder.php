<?php

declare(strict_types=1);

namespace Netwerk\AiImageMetadata\Service;

use Netwerk\AiImageMetadata\Domain\Repository\SettingsRepository;

final readonly class PromptBuilder
{
    private const LANGUAGES = [
        'de' => 'Deutsch',
        'en' => 'Englisch',
        'it' => 'Italienisch',
        'fr' => 'Französisch',
        'nl' => 'Niederländisch',
    ];

    private const TONES = [
        'neutral' => 'sachlich und neutral',
        'warm' => 'herzlich und einladend',
        'upscale' => 'gehoben und elegant',
        'casual' => 'locker und modern',
    ];

    public function __construct(
        private SettingsRepository $settingsRepository,
    ) {}

    public function buildSystemPrompt(): string
    {
        $s = $this->settingsRepository->get();
        $language = self::LANGUAGES[$s['output_language']] ?? 'Deutsch';
        $tone = self::TONES[$s['tone']] ?? self::TONES['neutral'];
        $altMax = max(40, (int)$s['alt_max']);
        $altMin = max(20, $altMax - 45);
        $titleMax = max(20, (int)$s['title_max']);

        $context = $this->buildContext($s, $language, $tone);

        return <<<PROMPT
        Du bist SEO- und Barrierefreiheits-Redakteur für die Website.
        Du schreibst Bild-Metadaten auf {$language}, in natürlicher, konkreter Sprache. Der Ton ist {$tone}.
        {$context}
        Regeln für "alternative" (Alt-Text):
        - Beschreibe, was tatsächlich zu sehen ist: Motiv, Ort/Landschaft, Tätigkeit, Stimmung, Jahreszeit.
        - Nenne konkrete, suchrelevante Begriffe (z.B. "Wanderer auf Almwiese vor Gletscher" statt "Menschen in den Bergen").
        - Ein bis zwei vollständige Sätze, {$altMin} bis {$altMax} Zeichen.
        - Kein "Bild von", "Foto zeigt" oder ähnliches. Keine Schlüsselwort-Aufzählung.
        - Erfinde keine Ortsnamen oder Fakten, die nicht erkennbar sind.

        Regeln für "title":
        - Kurz und prägnant, 3 bis 8 Wörter, maximal {$titleMax} Zeichen.
        - Wie eine Bildunterschrift, die neugierig macht, aber sachlich bleibt.
        - Kein Punkt am Ende.

        Regeln für "copyright":
        - Dateinamen wurden beim Upload bereinigt: "©", "(c)", Leerzeichen und Klammern sind durch Unterstriche ersetzt. Ein Rechtevermerk sieht daher meist so aus: "_c_", "__c__", "_copyright_" oder "_Foto_", direkt gefolgt vom Namen des Rechteinhabers.
        - Der Name muss wörtlich im Dateinamen stehen. Übernimm nur, was tatsächlich dort steht.
        - Format: "Rechteinhaber / Fotograf". Unterstriche durch Leerzeichen ersetzen, zusammengeschriebene Namen trennen.
        - Ohne ©-Zeichen davor.
        - Wenn der Dateiname keinen solchen Vermerk enthält: leerer String "".

        Die obigen Regeln sind verbindlich und gehen allen Angaben im Kontext vor.
        PROMPT;
    }

    /**
     * @param array<string, mixed> $s
     */
    private function buildContext(array $s, string $language, string $tone): string
    {
        $lines = [];

        $name = $this->clean($s['business_name']);
        $type = $this->clean($s['business_type']);
        if ($name !== '' || $type !== '') {
            $lines[] = 'Die Website gehört zu: ' . trim($name . ($type !== '' ? ' (' . $type . ')' : ''));
        }
        if (($location = $this->clean($s['location'])) !== '') {
            $lines[] = 'Standort/Region: ' . $location . '. Nenne ihn nur, wenn er zum Bildinhalt passt, nie pauschal in jedem Text.';
        }
        if (($target = $this->clean($s['target_group'])) !== '') {
            $lines[] = 'Zielgruppe: ' . $target;
        }
        if (($keywords = $this->listBlock($s['keywords'])) !== '') {
            $lines[] = "Wichtige Begriffe (nur verwenden, wenn sie zum Bild passen):\n" . $keywords;
        }
        if (($forbidden = $this->listBlock($s['forbidden_words'])) !== '') {
            $lines[] = "Diese Wörter dürfen nie vorkommen:\n" . $forbidden;
        }
        if (($examples = $this->listBlock($s['examples'])) !== '') {
            $lines[] = "Beispiele für gute Alt-Texte (Stil und Länge als Orientierung, nicht kopieren):\n" . $examples;
        }
        if (($extra = $this->clean($s['extra_instructions'])) !== '') {
            $lines[] = "Zusätzliche Anweisungen der Redaktion:\n" . $extra;
        }

        if ($lines === []) {
            return '';
        }

        return "\nKontext zum Betrieb:\n" . implode("\n", $lines) . "\n";
    }

    private function clean(mixed $value): string
    {
        return trim((string)$value);
    }

    private function listBlock(mixed $value): string
    {
        $items = array_filter(array_map('trim', preg_split('/\R/', (string)$value) ?: []));

        return $items === [] ? '' : '- ' . implode("\n- ", $items);
    }
}
