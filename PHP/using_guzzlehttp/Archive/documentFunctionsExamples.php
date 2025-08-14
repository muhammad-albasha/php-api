<?php

/**
 * This file contains examples for performing actions on archived documents.
 *
 * To use the functionality an active archive and archive profile for the authenticated user are needed (see conig.php).
 * Furthermore you need to pass a document ID when executing the script.
 *
 * Uncomment function call at the end of the file to execute desired action.
 *
 * Implemented examples:
 *  - lockDocument - locks the given document. Only the authenticated user can change its content and index data.
 *  - deleteDocument - deletes the given document. It depends on the archive settings, whether the document will be deleted physically at once.
 *  - searchDocuments - search documents with index filter
 */

require_once(__DIR__ . '/../init.php');
require_once('DocumentManager.php');

function lockDocument($args)
{
    if (!isset($args[1])) {
        echo "Please provide a document ID\n";

        return;
    }

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->lockDocument($args[1]);
}

function deleteDocument($args)
{
    if (!isset($args[1])) {
        echo "Please provide a document ID\n";

        return;
    }

    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->deleteDocument($args[1]);
}

function searchDocuments()
{
    $documentManager = new DocumentManager(new Authenticator());
    $documentManager->searchDocuments();
}

/**
 * uncomment function call to execute desired action
 */

//lockDocument($argv);
//deleteDocument($argv);
//searchDocuments();
