<?php

/**
 * This file contains examples for downloading documents from an archive and changing its meta data (index fields).
 *
 * To use the functionality an active archive and archive profile for the authenticated user are needed (see conig.php).
 * Furthermore you need to pass a document ID when executing the script.
 *
 * Uncomment function call at the end of the file to execute desired action.
 *
 * Implemented examples:
 *  - getDocumentFile - downloads the main file of the given document to a my_file-file in the current directory
 *  - getDocumentFiles - downloads all files of the given document to a my_file.zip-file in the current directory
 *  - getDocumentClippedFiles - downloads the attachments of the given document to a my_file.zip-file in the current directory
 *  - clipFilesToDocument - clips files to the given document. This will create a new revision.
 *  - clipFilesToDocumentWithError - tries to clip a this file to the given document. As php files are forbidden by default configuration, an error is thrown.
 *  - changeSomeIndexDataForASingleDocument - changes an index field value for one document
 *  - changeAllIndexDataForASingleDocument - changes all index data for one document
 *  - changeSomeIndexDataForMultipleDocuments - changes index field values for multiple documents at once
 *  - changeAllIndexDataForMultipleDocuments - changes all index data for multiple documents at once
 *
 * Differences in PATCH/PUT-indexdata routes:
 *  - PATCH /application/jobarchive/archives/:archive/documents/:revisionId/indexdata - change one or more fields of a single document. Not all index fields must be passed (in contrast to PUT with same route).
 *  - PUT  /application/jobarchive/archives/:archive/documents/:revisionId/indexdata - change all fields of a single document. All index fields must be passed.
 *  - PATCH /application/jobarchive/archives/:archive/documents/indexdata - change one or more fields of multiple documents. Not all index fields must be passed (in contrast to PUT with same route).
 *  - PUT /application/jobarchive/archives/:archive/documents/indexdata - change all fields of multiple documents. All index fields must be passed.
 */

require_once(__DIR__ . '/../init.php');
require_once('DocumentManager.php');

function getDocumentFile($revisionId)
{
    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->getDocumentFile($revisionId);
}

function getDocumentFiles($revisionId)
{
    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->getDocumentFiles($revisionId);
}

function getDocumentClippedFiles($revisionId)
{
    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->getDocumentClippedFiles($revisionId);
}

function clipFilesToDocument($revisionId)
{
    $files = [
        [
            'name' => 'files[0]',
            'contents' => fopen(__DIR__ . '/test_files/whitepaper.pdf', 'r'),
        ],
        [
            'name' => 'files[1]',
            'contents' => fopen(__DIR__ . '/test_files/myTest1.txt', 'r'),
        ],
    ];
    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->clipFilesToDocument($revisionId, $files);
}

function clipFilesToDocumentWithError($revisionId)
{
    $files = [
        [
            'name' => 'files[0]',
            'contents' => fopen(__FILE__, 'r'),
        ],
    ];
    $documentManager = new DocumentManager(new Authenticator(['http_errors' => false]));
    $documentManager->clipFilesToDocument($revisionId, $files);
}

function changeSomeIndexDataForASingleDocument($revisionId)
{
    $indexData = [
        'indexFields' => [
            [
                'name' => 'numberField',
                'value' => '111',
            ],
            [
                'name' => 'commentfield',
                'value' => 'changed via REST API',
            ],
        ],
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->changeIndexData($revisionId, $indexData, 'PATCH');
}

function changeAllIndexDataForASingleDocument($revisionId)
{
    $indexData = [
        'indexFields' => [
            [
                'name' => 'numberField',
                'value' => '2222',
            ],
            [
                'name' => 'commentfield',
                'value' => 'this content has been changed via REST API',
            ],
            [
                'name' => 'dateField',
                'value' => '2018-08-21T11:40:40+01:00',
            ],
            [
                'name' => 'titleField',
                'value' => 'my REST test',
            ],
        ],
        'keywordFields' => [
            [
                'name' => 'keywordField',
                'keywords' => 'REST',  // enter more keywords separated with comma
            ],
        ],
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->changeIndexData($revisionId, $indexData, 'PUT');
}

function changeSomeIndexDataForMultipleDocuments($revisionIds)
{
    $revisions = [];
    foreach (explode(',', $revisionIds) as $id) {
        $revisionData = [
            'revisionId' => $id,
            'indexFields' => [
                [
                    'name' => 'dateField',
                    'value' => '2018-08-27T14:40:40+01:00',
                ],
                [
                    'name' => 'commentfield',
                    'value' => 'date changed via REST API',
                ],
            ],
        ];
        $revisions[] = $revisionData;
    }

    $indexData = [
        'revisions' => $revisions,
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->changeIndexDataMultiple($indexData, 'PATCH');
}

function changeAllIndexDataForMultipleDocuments($revisionIds)
{
    $revisions = [];
    $i = 1;
    foreach (explode(',', $revisionIds) as $id) {

        $revisionData = [
            'revisionId' => $id,
            'indexFields' => [
                [
                    'name' => 'numberField',
                    'value' => 1234 + $i,
                ],
                [
                    'name' => 'commentfield',
                    'value' => 'this content has been changed via REST API',
                ],
                [
                    'name' => 'dateField',
                    'value' => '2018-08-21T11:40:40+01:00',
                ],
                [
                    'name' => 'titleField',
                    'value' => 'test' . $i,
                ],
            ],
            'keywordFields' => [
                [
                    'name' => 'keywordField',
                    'keywords' => 'REST',  // enter more keywords separated with comma
                ],
            ],
        ];
        $revisions[] = $revisionData;
        $i++;
    }

    $indexData = [
        'revisions' => $revisions,
    ];

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->changeIndexDataMultiple($indexData, 'PUT');
}

/**
 * uncomment function call to execute desired action
 */

if (!isset($argv[1])) {
    echo "Please provide a document ID\n";

    return;
}

//getDocumentFile($argv[1]);
//getDocumentFiles($argv[1]);
//getDocumentClippedFiles($argv[1]);
//clipFilesToDocument($argv[1]);
//clipFilesToDocumentWithError($argv[1]);
//changeSomeIndexDataForASingleDocument($argv[1]);
//changeAllIndexDataForASingleDocument($argv[1]);
//changeSomeIndexDataForMultipleDocuments($argv[1]);
//changeAllIndexDataForMultipleDocuments($argv[1]);

