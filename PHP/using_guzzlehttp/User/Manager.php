<?php

use GuzzleHttp\Exception\ClientException;

class Manager
{
    private $authenticator;
    private $client;
    private $sessionId;

    const RESOURCE_URI = 'application/users/##user##/archives';
    const RESOURCE_URI_FOR_ARCHIVE = 'application/users/##user##/archives/##archive##';

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->sessionId = $this->authenticator->createSession();

        if (!$this->sessionId) {
            return;
        }

        $this->client = $this->authenticator->getClient();
    }

    public function listArchivesForUser()
    {
        if (!$this->client) {
            return null;
        }

        try {
            $url = str_replace('##user##', USERNAME, static::RESOURCE_URI);
            $response = $this->client->request(
                'GET',
                $url
            );

            echo "Status code: " . $response->getStatusCode() . "\n";

            $body = $response->getBody();
            echo "Response body: " . $body . "\n";

            $body = json_decode($body, true);
            $archives = $body['users']['archives'];

            foreach ($archives as $archive) {
                echo "Name: " . $archive['name'] . "\n";
                echo "GUID: " . $archive['guid'] . "\n";
                echo "Table: " . $archive['table'] . "\n";
            }
        } catch (ClientException $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function showArchiveDetails()
    {
        if (!$this->client) {
            return null;
        }

        try {
            $url = str_replace(['##user##', '##archive##'], [USERNAME, ARCHIVE], static::RESOURCE_URI_FOR_ARCHIVE);
            $response = $this->client->request(
                'GET',
                $url
            );

            echo "Status code: " . $response->getStatusCode() . "\n";

            $body = $response->getBody();
            echo "Response body: " . $body . "\n";

            $body = json_decode($body, true);
            $archive = $body['users']['archives'][0];

            $requiredFields = [];
            foreach ($archive['indexFieldDefinitions'] as $fieldDefinition) {
                echo "Name: " . $fieldDefinition['name'] . "\n";
                echo "Type: " . $fieldDefinition['type'] . "\n";
                if ($fieldDefinition['required']) {
                    array_push($requiredFields, $fieldDefinition['name']);
                }
            }

            if (count($requiredFields)) {
                echo "Required fields: " . implode(',', $requiredFields) . "\n";
            }
        } catch (ClientException $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }
}


