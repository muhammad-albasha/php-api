<?php

require_once(__DIR__ . '/../init.php');

$client = new CurlClient();

$client->authenticate();

$archives = $client->get('application/users/' . USERNAME . '/archives');

foreach ($archives['users']['archives'] as $archive) {
    echo "Name: " . $archive['name'] . "\n";
    echo "GUID: " . $archive['guid'] . "\n";
    echo "Table: " . $archive['table'] . "\n";
}

$client->destroySession();
