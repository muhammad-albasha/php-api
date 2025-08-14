<?php

require_once(__DIR__ . '/../init.php');

// Simple watcher: polls a folder and uploads any new .pdf/.png/.jpg as an archive document
// with index fields Aktiv = 1 and DocuID = generated id. Stores a state file to avoid re-upload.

const WATCH_DIR = FILE_STORAGE;               // by default use FILE_STORAGE; change if needed
const STATE_FILE = __DIR__ . '/.watch_state.json';
const SLEEP_SECONDS = 5;                      // polling interval

function loadState()
{
    if (!file_exists(STATE_FILE)) return [];
    $json = file_get_contents(STATE_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveState(array $state)
{
    file_put_contents(STATE_FILE, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function listCandidateFiles(string $dir): array
{
    if (!is_dir($dir)) return [];
    $files = scandir($dir);
    $out = [];
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $path = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f;
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'tif', 'tiff'])) {
            $out[] = $path;
        }
    }
    sort($out);
    return $out;
}

function generateDocuId(string $filepath): string
{
    // Use a deterministic but unique-ish id (time + hash of path)
    return date('YmdHis') . '_' . substr(sha1($filepath . '|' . microtime(true)), 0, 8);
}

function uploadInvoice(CurlClient $client, string $filepath, string $docuId): bool
{
    // Build multipart form according to archiveDocument.php example
    $file = new CURLFile($filepath);
    $indexFields = [
        // Map your archive index field names here; adjust to actual field names in your archive
        'indexFields[0][name]' => 'Aktiv',
        'indexFields[0][value]' => '1',
        'indexFields[1][name]' => 'DocuID',
        'indexFields[1][value]' => $docuId,
        'files[0]' => $file,
    ];

    $route = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
    $response = $client->post($route, $indexFields);
    if (!count($response)) {
        echo "Upload failed for $filepath\n";
        return false;
    }
    $revId = $response['archivedocumentrevisions'][0]['revisionId'] ?? null;
    echo "Uploaded $filepath as revision $revId with DocuID=$docuId\n";
    return true;
}

// Main loop
$state = loadState();
if (!isset($state['processed'])) $state['processed'] = [];

while (true) {
    $files = listCandidateFiles(WATCH_DIR);
    foreach ($files as $path) {
        if (!empty($state['processed'][$path])) continue; // already uploaded
        try {
            $client = new CurlClient();
            $client->authenticate();

            $docuId = generateDocuId($path);
            $ok = uploadInvoice($client, $path, $docuId);

            $client->destroySession();

            if ($ok) {
                $state['processed'][$path] = [
                    'docuId' => $docuId,
                    'uploadedAt' => date(DATE_ATOM),
                ];
                saveState($state);
            }
        } catch (Throwable $e) {
            // log, keep going
            echo "Error processing $path: " . $e->getMessage() . "\n";
        }
    }
    // sleep between scans
    sleep(SLEEP_SECONDS);
}
