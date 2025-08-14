<?php

require_once(__DIR__ . '/init.php');
require_once(__DIR__ . '/Archive/DocumentManager.php');

$filePath = 'c:\\Users\\MAlbasha\\Desktop\\php-api\\test_files\\hello.txt';

echo "Checking if file exists: " . $filePath . "\n";
if (!file_exists($filePath)) {
    echo "ERROR: File does not exist!\n";
    exit(1);
}

echo "File exists, size: " . filesize($filePath) . " bytes\n";
echo "File content: " . file_get_contents($filePath) . "\n";

$documentContentAndMetaData = [
    [
        'name' => 'files[0]',
        'contents' => fopen($filePath, 'r'),
        'filename' => 'hello.txt'
    ],
];

echo "Starting upload...\n";
$documentManager = new DocumentManager(new Authenticator());
$documentManager->archiveDocument($documentContentAndMetaData);
echo "Upload completed.\n";

?>
