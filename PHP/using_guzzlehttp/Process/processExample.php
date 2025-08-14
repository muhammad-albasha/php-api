<?php

/**
 * This file contains examples for process interactions.
 *
 * To use the functionality an active process is needed (see conig.php).
 *
 * Uncomment function call at the end of this file to execute desired action.
 *
 * Implemented examples:
 *  - startProcessWithoutOptionalInputFields - starts a process without values for process table or subtable fields
 *  - startProcessWithNonInputField - tries to start a process without input fields. To fill process table and subtable fields the "input" option must be activated in the field configuration (Workflow Designer). If non input fields are send in the request, the process won't be started (400 Bad Request).
 *  - startProcessWithMetadataAndProcesstableAndSubtableFields - starts a process with values for process table and subtable fields
 */

require_once(__DIR__ . '/../init.php');
require_once('ProcessManager.php');

function startProcessWithoutOptionalInputFields()
{
    $input = [
        [
            'name' => 'step',
            'contents' => '1',
        ],
    ];

    $processManager = new ProcessManager(new Authenticator());
    $processManager->startProcess($input);
}

function startProcessWithNonInputField()
{
    $input = [
        [
            'name' => 'step',
            'contents' => '1',
        ],
        [
            'name' => 'processtable[fields][0][name]',
            'contents' => 'TXTFIELD1',
        ],
        [
            'name' => 'processtable[fields][0][value]',
            'contents' => 'this is not an input field',
        ],
    ];

    $processManager = new ProcessManager(new Authenticator());
    $processManager->startProcess($input);
}

function startProcessWithMetadataAndProcesstableAndSubtableFields()
{
    $input = [
        [
            'name' => 'step',
            'contents' => '1',
        ],
        [
            'name' => 'initiator',
            'contents' => 'REST API Test',
        ],
        [
            'name' => 'jobfunction',
            'contents' => 'rolle1',
        ],
        [
            'name' => 'summary',
            'contents' => 'process initiated with REST API',
        ],
        [
            'name' => 'priority',
            'contents' => '1',
        ],
        [
            'name' => 'pool',
            'contents' => '2',
        ],
        [
            'name' => 'step_escalation_date',
            'contents' => '2017-08-27T11:00:00+01:00',
        ],
        [
            'name' => 'incident_escalation_date',
            'contents' => '2017-09-29T11:00:00+01:00',
        ],
        [
            'name' => 'processtable[fields][0][name]',
            'contents' => 'TEXTFIELD1',
        ],
        [
            'name' => 'processtable[fields][0][value]',
            'contents' => 'some value from rest',
        ],
        [
            'name' => 'processtable[fields][1][name]',
            'contents' => 'BIGFIELD1',
        ],
        [
            'name' => 'processtable[fields][1][value]',
            'contents' => '123456789',
        ],
        [
            'name' => 'processtable[fields][2][name]',
            'contents' => 'DATEFIELD1',
        ],
        [
            'name' => 'processtable[fields][2][value]',
            'contents' => '2017-08-29T11:00:00+01:00',
        ],
        [
            'name' => 'processtable[fields][3][name]',
            'contents' => 'DECIMALFIELD1',
        ],
        [
            'name' => 'processtable[fields][3][value]',
            'contents' => '1234.78',
        ],
        [
            'name' => 'processtable[fields][4][name]',
            'contents' => 'ENCFIELD1',
        ],
        [
            'name' => 'processtable[fields][4][value]',
            'contents' => 'some encrypted text',
        ],
        [
            'name' => 'processtable[fields][5][name]',
            'contents' => 'INTFIELD1',
        ],
        [
            'name' => 'processtable[fields][5][value]',
            'contents' => '123',
        ],
        [
            'name' => 'processtable[fields][6][name]',
            'contents' => 'LONGFIELD1',
        ],
        [
            'name' => 'processtable[fields][6][value]',
            'contents' => file_get_contents(__DIR__ . '/../test_files/myTest1.txt'),
        ],
        [
            'name' => 'processtable[fields][7][name]',
            'contents' => 'UPLOADEDFILE',
        ],
        [
            'name' => 'processtable[fields][7][value]',
            'contents' => fopen(__DIR__ . '/../test_files/whitepaper.pdf', 'r'),
        ],
        [
            'name' => 'subtables[0][name]',
            'contents' => 'SRESTALLFIELDTYPES' // database table name
        ],
        [
            'name' => 'subtables[0][rows][0][fields][0][name]',
            'contents' => 'INTFIELD1',
        ],
        [
            'name' => 'subtables[0][rows][0][fields][0][value]',
            'contents' => '10',
        ],
        [
            'name' => 'subtables[0][rows][0][fields][1][name]',
            'contents' => 'DATEFIELD1',
        ],
        [
            'name' => 'subtables[0][rows][0][fields][1][value]',
            'contents' => '2017-09-29T11:00:00+01:00',
        ],
        [
            'name' => 'subtables[0][rows][0][fields][2][name]',
            'contents' => 'DECIMALFIELD1',
        ],
        [
            'name' => 'subtables[0][rows][0][fields][2][value]',
            'contents' => '1235.89',
        ],
        [
            'name' => 'subtables[0][rows][0][fields][3][name]',
            'contents' => 'UPLOADEDFILE',
        ],
        [
            'name' => 'subtables[0][rows][0][fields][3][value]',
            'contents' => fopen(__DIR__ . '/../test_files/myTest1.txt', 'r'),
        ],
        [    // here begins the second row
            'name' => 'subtables[0][rows][1][fields][0][name]',
            'contents' => 'INTFIELD1',
        ],
        [
            'name' => 'subtables[0][rows][1][fields][0][value]',
            'contents' => '12',
        ],
        [
            'name' => 'subtables[0][rows][1][fields][1][name]',
            'contents' => 'DATEFIELD1',
        ],
        [
            'name' => 'subtables[0][rows][1][fields][1][value]',
            'contents' => '2017-09-30T11:00:00+01:00',
        ],
        [
            'name' => 'subtables[0][rows][1][fields][2][name]',
            'contents' => 'DECIMALFIELD1',
        ],
        [
            'name' => 'subtables[0][rows][1][fields][2][value]',
            'contents' => '10235.30',
        ],
        [
            'name' => 'subtables[0][rows][1][fields][3][name]',
            'contents' => 'UPLOADEDFILE',
        ],
        [
            'name' => 'subtables[0][rows][1][fields][3][value]',
            'contents' => fopen(__DIR__ . '/../test_files/hello.txt', 'r'),
        ],
    ];

    $processManager = new ProcessManager(new Authenticator());
    $processManager->startProcess($input);
}

/**
 * uncomment function call to execute desired action
 */

//startProcessWithoutOptionalInputFields();
//startProcessWithNonInputField();
//startProcessWithMetadataAndProcesstableAndSubtableFields();
