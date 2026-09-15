### Installation

ddev composer config repositories.ai-image-metadata vcs git@github.com:netwerk-kreidl/ai-image-metadata.git

ddev auth ssh

ddev composer require netwerk/ai-image-metadata:^1.0

## Configuration
1. env-Datei anlegen (lokal im Public root + am Server per SFTP/SSH):
AI_IMAGE_METADATA_API_KEY=

2. den getenv-Block in config/system/additional.php eintragen (ganz unten):
   // API-Key für EXT:ai_image_metadata aus der .env übernehmen
  $aiApiKey = getenv('AI_IMAGE_METADATA_API_KEY');
  if ($aiApiKey !== false && $aiApiKey !== '') {
      $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ai_image_metadata']['apiKey'] = $aiApiKey;
  }
4. das Site Set netwerk/ai-image-metadata als Abhängigkeit im Sitepackage eintragen (für das Bildrechte-Element)
