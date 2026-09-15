# AI Image Metadata

Erzeugt Alt-Texte und Titel für Bilder per KI (Claude) direkt im TYPO3-Backend
und stellt ein Content Element bereit, das alle Bildnachweise fürs Impressum sammelt.

## Installation

ddev composer config repositories.ai-image-metadata git git@github.com:netwerk-kreidl/ai-image-metadata.git

ddev auth ssh

ddev composer require netwerk/ai-image-metadata:^1.0

ddev typo3 extension:setup --extension=ai_image_metadata

## Configuration
1. Datei `.env` im Projekt-Root anlegen (lokal, und am Server per SSH/SFTP):
AI_IMAGE_METADATA_API_KEY=

2. In die `.gitignore` aufnehmen

3. den getenv-Block in config/system/additional.php eintragen (ganz unten):
   // API-Key für EXT:ai_image_metadata aus der .env übernehmen
  $aiApiKey = getenv('AI_IMAGE_METADATA_API_KEY');
  if ($aiApiKey !== false && $aiApiKey !== '') {
      $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_image_metadata']['apiKey'] = $aiApiKey;
  }
4. das Site Set netwerk/ai-image-metadata als Abhängigkeit im Sitepackage eintragen (für das Bildrechte-Element)
5. Das Modell auswählen: Backend → *Admin Tools → Settings → Extension Configuration → AI Image Metadata*:
Provider `Claude`, Modell z.B. `claude-sonnet-5`. Das Feld *API-Key* leer lassen.
