<?php

require_once(__DIR__ . '/../init.php');
require_once('Manager.php');

/**
 * This file contains examples for getting archive information for a user.
 *
 * To use the functionality an active archive and archive profile for the authenticated user are needed (see conig.php).
 *
 * Uncomment function call at the end of the file to execute desired action.
 *
 * Implemented examples:
 *  - listUsersArchives - lists the active archives which the authenticated user can access
 *  - showArchiveDetails - shows configuration settings including index fields information for the given archive
 */

function listUsersArchives()
{
    $archiveManager = new Manager(new Authenticator());
    $archiveManager->listArchivesForUser();
}

function showArchiveDetails()
{
    $archiveManager = new Manager(new Authenticator());
    $archiveManager->showArchiveDetails();
}


/**
 * uncomment function call to execute desired action
 */
//listUsersArchives();
//showArchiveDetails();

