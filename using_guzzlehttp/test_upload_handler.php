<?php
// Simple debug test for upload_handler.php

// Simulate a file upload for testing
$_FILES['file'] = [
    'name' => 'test.txt',
    'tmp_name' => 'c:\\Users\\MAlbasha\\Desktop\\php-api\\test_files\\hello.txt',
    'size' => 15,
    'error' => UPLOAD_ERR_OK
];

$_POST['mandant'] = 'Test Mandant';
$_POST['beschreibung'] = 'Test Upload via Debug';

// Include the upload handler
include 'upload_handler.php';
?>
