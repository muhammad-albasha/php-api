<?php

class DatabaseManager
{
    private $authenticator;
    private $client;
    private $sessionId;

    const RESOURCE_URI = 'application/jobdata/tables/' . JOBDATA_TABLE_GUID . '/datasets';

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->sessionId = $this->authenticator->createSession();
        $this->client = $this->authenticator->getClient();
    }

    /**
     * @param array $input - array with values for the database columns
     */
    public function createDataSet($input)
    {
        try {
            $response = $this->client->request(
                'POST',
                static::RESOURCE_URI,
                [
                    'json' => $input,
                ]
            );

            echo "Status code: " . $response->getStatusCode() . "\n";
            $body = json_decode($response->getBody(), true);
            echo "Data set ID: " . $body['datasets'][0]['jrid'] . "\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    /**
     * @param array $input - array with values for the database columns
     * @param string $id - data set ID
     */
    public function changeDataSet($input, $id)
    {
        try {
            $response = $this->client->request(
                'PUT',
                static::RESOURCE_URI . '/' . $id,
                [
                    'json' => $input,
                ]
            );

            echo "Status code: " . $response->getStatusCode() . "\n";
            $body = json_decode($response->getBody(), true);
            foreach ($body['datasets'][0] as $columnName => $columnValue) {
                echo $columnName . ": " . $columnValue . "\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function listDataSets()
    {
        try {
            $response = $this->client->request('GET', static::RESOURCE_URI);

            echo "Status code: " . $response->getStatusCode() . "\n\n";
            $body = json_decode($response->getBody(), true);

            echo "Total count: " . $body['meta']['pagination']['total'] . "\n\n";

            foreach ($body['datasets'] as $dataSet) {
                foreach ($dataSet as $columnName => $columnValue) {
                    echo $columnName . ": " . $columnValue . "\n";
                }
                echo "\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }
}
