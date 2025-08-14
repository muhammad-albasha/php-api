<?php

/**
 * This file contains examples for managing JobData data sets.
 *
 * To use the functionality you should provide a valid table GUID in the config.php.
 *
 * Uncomment function call at the end of this file to execute desired action.
 *
 * Implemented examples:
 *  - createDataSet - creates a new data set in the configured database table
 *  - changeDataSet - changes a data set in the configured database table
 *  - listDataSets - lists the existing data sets in the database table
 */

require_once(__DIR__ . '/../init.php');
require_once('DatabaseManager.php');

function createDataSet()
{
    // adjust input before execution
    $input = [
        'dataset' => [
            'sname' => 'Umbrella Corp.',
            'sadress' => 'Umbrella Rd. 66, Ney York, USA',
            'sdate' => '2017-06-30T13:22:45+01:00',
            'sphone' => '076-343-5226',
            'srating' => 6,
        ],
    ];
    $databaseManager = new DatabaseManager(new Authenticator());
    $databaseManager->createDataSet($input);
}

function changeDataSet()
{
    // adjust input and data set ID before execution
    $dataSetID = 3;
    $input = [
        'dataset' => [
            'sphone' => '076-343-5236',
            'srating' => 5,
        ],
    ];
    $databaseManager = new DatabaseManager(new Authenticator());
    $databaseManager->changeDataSet($input, $dataSetID);
}

function listDataSets()
{
    $databaseManager = new DatabaseManager(new Authenticator());
    $databaseManager->listDataSets();
}

/**
 * uncomment function call to execute desired action
 */

//createDataSet();
//changeDataSet();
//listDataSets();
