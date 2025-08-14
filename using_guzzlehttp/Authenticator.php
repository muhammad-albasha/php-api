<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Authenticator
{
    private $client;

    public function __construct($options = [])
    {
        $defaults = [
            'base_uri' => BASE_URL,
            'http_errors' => true,
            'cookies' => true,
            'verify' => CERTIFICATE_FOR_SSL_COMMUNICATION,
        ];

        $defaults = array_merge($defaults, $options);

        $this->client = new Client($defaults);
    }

    public function createSession()
    {
        try {
            $response = $this->client->post(
                'application/sessions',
                [
                    'json' => [
                        'username' => USERNAME,
                        'password' => PASSWORD,
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode != 201) {
                $message = "The resource returned code " . $statusCode . "\n";
                throw new Exception($message);
            }

            $body = $response->getBody();
            $sessionData = json_decode($body, true);

        } catch (ClientException $e) {
            echo $e->getMessage() . "\n";
        }


        return $sessionData['sessions'][0]['sessionId'];
    }

    public function deleteSession($sessionId)
    {
        try {
            $response = $this->client->delete('application/sessions/' . $sessionId);

            $statusCode = $response->getStatusCode();
            if ($statusCode != 204) {
                $message = "The resource returned code " . $statusCode . ", body: " . $response->getBody();
                throw new Exception($message);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    public function getClient()
    {
        return $this->client;
    }
}

