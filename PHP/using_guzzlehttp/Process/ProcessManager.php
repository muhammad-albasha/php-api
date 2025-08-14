<?php

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;

class ProcessManager
{
    private $authenticator;
    private $client;
    private $sessionId;

    const RESOURCE_URI = 'application/incidents/' . PROCESS;
    const BOX_RESOURCE_URI = 'application/workitems/';

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->sessionId = $this->authenticator->createSession();
        $this->client = $this->authenticator->getClient();
    }

    /**
     * @param
     */
    public function startProcess($input)
    {
        try {
            $response = $this->client->request(
                'POST',
                static::RESOURCE_URI,
                [
                    'multipart' => $input,
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

        $body = $response->getBody();
        $decodedBody = json_decode($body->getContents(), true);
        $incidentData = $decodedBody['incidents'][0];

        echo "Workflow ID: " . $incidentData['workflowId'] . "\n";
        echo "Step ID: " . $incidentData['stepId'] . "\n";
        echo "Process ID: " . $incidentData['processId'] . "\n";
        echo "Incident: " . $incidentData['incidentnumber'] . "\n";
        echo "Job function: " . $incidentData['jobfunction'] . "\n";
        echo "User: " . $incidentData['username'] . "\n";
    }

    public function getBoxSteps($type)
    {
        if (!$this->isTypeValid($type)) {
            echo "Invalid box type";
            return;
        }

        try {
            $response = $this->client->request('GET', static::BOX_RESOURCE_URI . $type);

            echo "Status code: " . $response->getStatusCode() . "\n\n";

            $body = json_decode($response->getBody(), true);

            echo "Number of steps: " . $body['meta']['pagination']['total'] . "\n\n";

            foreach ($body['workitems'] as $item) {
                echo "Process: " . $item['jrprocessname'] . "\n";
                echo "Step: " . $item['jrsteplabel'] . "\n";
                if (isset($item['jrworkflowid'])) {
                    echo "Workflow-ID: " . $item['jrworkflowid'] . "\n\n";
                }
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    private function isTypeValid($type)
    {
        $validTypes = ['inbox', 'start', 'completed', 'substitution'];
        if (!in_array($type, $validTypes)) {
            return false;
        }

        return true;
    }

    public function getProcessBoxSteps()
    {
        try {
            $response = $this->client->request('GET', static::BOX_RESOURCE_URI . PROCESS
                . '/' . PROCESS_VERSION . '/' . PROCESS_BOX_ID);

            echo "Status code: " . $response->getStatusCode() . "\n\n";

            $body = json_decode($response->getBody(), true);

            foreach ($body['workitems'] as $item) {
                echo "Step: " . $item['jrsteplabel'] . "\n";
                echo "Indate: " . $this->formatDate($item['jrindate']) . "\n";
                echo "Workflow ID: " . $item['jrworkflowid'] . "\n\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    private function formatDate($value)
    {
        $date = DateTime::createFromFormat(Datetime::ATOM, $value);
        return $date->format('d.m.y');

    }
}


