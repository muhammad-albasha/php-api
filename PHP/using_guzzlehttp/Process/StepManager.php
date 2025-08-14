<?php

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

class StepManager
{
    private $authenticator;
    private $client;
    private $sessionId;

    const RESOURCE_URI = 'application/steps';

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->sessionId = $this->authenticator->createSession();
        $this->client = $this->authenticator->getClient();
    }

    /**
     * @param
     */
    public function performStepAction($input)
    {
        if (!isset($input['workflowId'])) {
            echo "Please provide a workflow ID";
            return;
        }

        try {
            $response = $this->client->request(
                'PUT',
                static::RESOURCE_URI . '/' . $input['workflowId'],
                [
                    'json' => $input,
                ]
            );

            $this->handleResponse($response);

        } catch (ClientException $e) {
            echo $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    private function handleResponse(Response $response)
    {
        echo "Status code: " . $response->getStatusCode() . "\n";

        if ($response->getStatusCode() === 200) {
            $body = $response->getBody();
            $decodedBody = json_decode($body->getContents(), true);
            $stepData = $decodedBody['steps'][0];

            echo "Workflow ID: " . $stepData['workflowId'] . "\n";
            echo "Process ID: " . $stepData['processId'] . "\n";
            echo "Action: " . $stepData['action'] . "\n";
            echo "Dialog: " . $stepData['dialog'] . "\n";
        }
    }
}


