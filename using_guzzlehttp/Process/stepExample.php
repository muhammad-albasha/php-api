<?php

/**
 * This file contains examples for performing step actions.
 *
 * To use the functionality an active process is needed (see conig.php). See also the functions comments for further requirements.
 *
 * Uncomment function call at the end of this file to execute desired action.
 *
 * Implemented examples:
 *  - saveStep - saves an existing step with dialog data
 *  - sendStep - send an existing step with dialog data
 */

require_once(__DIR__ . '/../init.php');
require_once('StepManager.php');

/**
 * modify the input array with your step data before execution
 */
function saveStep()
{
    $input = [
        'processName' => PROCESS,
        'processVersion' => 1,
        'stepNo' => 1,
        'action' => 'save',
//    'simulation' => 0,  // set this to 1 for simulating the action in workflow designer
        'workflowId' => 'f11e44c73fc33a52e97bb224179987fc0000000071',
        'dialogType' => 'desktop',
        'dialog' => [
            'fields' => [
                [
                    'name' => 'txtField1',
                    'value' => 'a test value',
                    'required' => 0,
                ],
                [
                    'name' => 'bigint1',
                    'value' => 123456,
                    'required' => 0,
                ],
                [
                    'name' => 'int1',
                    'value' => 123,
                    'required' => 0,
                ],
                [
                    'name' => 'decimalField',
                    'value' => 12345.98,
                    'required' => 0,
                ],
            ],
        ],
    ];

    $stepManager = new StepManager(new Authenticator());
    $stepManager->performStepAction($input);
}

/**
 * modify the input array with your step data before execution
 */
function sendStep()
{
    $input = [
        'processName' => PROCESS,
        'processVersion' => 1,
        'stepNo' => 1,
        'action' => 'send',
//    'simulation' => 0,  // set this to 1 for simulating the action in workflow designer
        'workflowId' => 'f11e44c73fc33a52e97bb224179987fc0000000064',
        'dialogType' => 'desktop',
        'dialog' => [
            'fields' => [
                [
                    'name' => 'txtField1',
                    'value' => 'a test value to be send',
                    'required' => 0,
                ]
            ],
        ],
    ];

    $stepManager = new StepManager(new Authenticator());
    $stepManager->performStepAction($input);
}

/**
 * uncomment function call to execute desired action
 */

//saveStep();
//sendStep();
