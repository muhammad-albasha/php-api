<?php

/**
 * This file contains examples for uploading documents to an archive.
 *
 * To use the functionality an active archive and archive profile for the authenticated user are needed (see conig.php).
 *
 * Uncomment function call at the end of the file to execute desired action.
 *
 * Implemented examples:
 *  - createDocumentWithoutIndexFields - archive a document only with file and no index data (if your archive has required index fields, this example will throw error
 *  - createDocumentWithMultipleIndexFields - archive a document with index field values
 *  - createDocumentWithMultipleFiles - archive a document with multiple files
 *  - createDocumentWithKeywords - archive a document with index data containing keywords (explicit example because keywords use a different array)
 */

require_once(__DIR__ . '/../init.php');
require_once('DocumentManager.php');

const FILES_STORAGE = __DIR__ . '/../../test_files/';

function createDocumentWithoutIndexFields()
{
    $documentContentAndMetaData = [
        [
            'name' => 'files',
            'contents' => fopen(FILES_STORAGE . 'hello.txt', 'r'),
        ],
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->archiveDocument($documentContentAndMetaData);
}

function createDocumentWithMultipleIndexFields()
{
    $documentContentAndMetaData = [
        [
            'name' => 'indexFields[0][name]',
            'contents' => 'titleField',
        ],
        [
            'name' => 'indexFields[0][value]',
            'contents' => 'My REST document example with one file',
        ],
        [
            'name' => 'indexFields[1][name]',
            'contents' => 'numberField',
        ],
        [
            'name' => 'indexFields[1][value]',
            'contents' => '123',
        ],
        [
            'name' => 'files',
            'contents' => fopen(FILES_STORAGE . 'myTest1.txt', 'r'),
        ],
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->archiveDocument($documentContentAndMetaData);
}

function createDocumentWithMultipleFiles()
{
    $documentContentAndMetaData = [
        [
            'name' => 'indexFields[0][name]',
            'contents' => 'titleField',
        ],
        [
            'name' => 'indexFields[0][value]',
            'contents' => 'My REST document example with multiple files',
        ],
        [
            'name' => 'indexFields[1][name]',
            'contents' => 'numberField',
        ],
        [
            'name' => 'indexFields[1][value]',
            'contents' => '123',
        ],
        [
            'name' => 'indexFields[1][name]',
            'contents' => 'dateField',
        ],
        [
            'name' => 'indexFields[1][value]',
            'contents' => '2018-05-21T11:20:40+01:00',
        ],
        [
            'name' => 'files[0]',
            'contents' => fopen(FILES_STORAGE . 'whitepaper.pdf', 'r'),
        ],
        [
            'name' => 'files[1]',
            'contents' => fopen(FILES_STORAGE . 'myTest1.txt', 'r'),
        ],
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->archiveDocument($documentContentAndMetaData);
}

function createDocumentWithKeywords()
{
    $documentContentAndMetaData = [
        [
            'name' => 'indexFields[0][name]',
            'contents' => 'status',
        ],
        [
            'name' => 'indexFields[0][value]',
            'contents' => 'example',
        ],
        [
            'name' => 'indexFields[1][name]',
            'contents' => 'number',
        ],
        [
            'name' => 'indexFields[1][value]',
            'contents' => '123',
        ],
        [
            'name' => 'keywordFields[0][name]',
            'contents' => 'keywords',
        ],
        [
            'name' => 'keywordFields[0][keywords]',
            'contents' => 'RESTDoc',
        ],
        [
            'name' => 'files',
            'contents' => fopen(FILES_STORAGE . 'myTest1.txt', 'r'),
        ],
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->archiveDocument($documentContentAndMetaData);
}

/**
 * uncomment function call to execute desired action
 */

//createDocumentWithoutIndexFields();
//createDocumentWithMultipleIndexFields();
//createDocumentWithMultipleFiles();
//createDocumentWithKeywords();

