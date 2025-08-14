<?php

/**
 * This file contains examples for listing content of boxes.
 *
 * Uncomment function call at the end of this file to execute desired action.
 *
 * Implemented examples:
 *  - listBoxSteps - lists the steps in the given box
 *  - listProcessBoxSteps - lists the steps of a configured process box
 */

require_once(__DIR__ . '/../init.php');
require_once('ProcessManager.php');

function listBoxSteps($args)
{
    if (!isset($args[1])) {
        echo "Please provide a box type!\n";

        return;
    }

    $processManager = new ProcessManager(new Authenticator());
    $processManager->getBoxSteps($args[1]);
}

function listProcessBoxSteps()
{
    $processManager = new ProcessManager(new Authenticator());
    $processManager->getProcessBoxSteps();
}



/**
 * uncomment function call to execute desired action
 */

//listBoxSteps($argv);
//listProcessBoxSteps();
