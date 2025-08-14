<?php

require_once(__DIR__ . '/init.php');

use GuzzleHttp\Client;

const SESSIONS_URI = 'application/sessions';

/**
 * Initialize REST Client. An exception will be thrown in case of errors.
 */
$client = new Client(
    [
        'base_uri' => BASE_URL,
        'http_errors' => true,
         'cookies' => true,
        'verify' => CERTIFICATE_FOR_SSL_COMMUNICATION,
    ]
);

try {
    echo "Create session\n";

    $response = $client->post(
        SESSIONS_URI,
        [
            'json' => [
                'username' => USERNAME,
                'password' => PASSWORD,
            ],
        ]
    );

    echo "Status code: " . $response->getStatusCode() . "\n";

    $body = $response->getBody();
    echo "Response body: " . $body . "\n";

    $sessionData = json_decode($body, true);
    $sessionId = $sessionData['sessions'][0]['sessionId'];
    echo "Session with ID " . $sessionId . " created. Deleting ...\n";

    $response = $client->delete('application/sessions/' . $sessionId);

    echo "Status code: " . $response->getStatusCode() . "\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

/**
 * Example for another error handling: The script handles error messages.
 */
$clientWithoutExceptions = new Client(
    [
        'base_uri' => BASE_URL,
        'http_errors' => false,
        'cookies' => true,
        'verify' => CERTIFICATE_FOR_SSL_COMMUNICATION,
    ]
);

try {
    echo "\nTry to create a session for non existant user and handle response error\n";

    $response = $clientWithoutExceptions->post(
        SESSIONS_URI,
        [
            'json' => [
                'username' => 'non_existant_user',
                'password' => PASSWORD,
            ],
        ]
    );

    echo "Status code: " . $response->getStatusCode() . "\n";

    $responseData = json_decode($response->getBody(), true);
    $errors = $responseData['errors'];

    foreach($errors as $error) {
        echo $error[0] . "\n";
    }

} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

/**
 * Example for usage of JSON web tokens (JWT). This example works with JobRouter version 4.2 and greater.
 */
try {
    echo "\nCreate a session for a jobrouter user, delete it and use the 10 minute token to send the next request\n";

    $response = $clientWithoutExceptions->post(
        SESSIONS_URI,
        [
            'json' => [
                'username' => USERNAME,
                'password' => PASSWORD,
            ],
        ]
    );

    $sessionData = json_decode($response->getBody(), true);
    $sessionId = $sessionData['sessions'][0]['sessionId'];
    $token = null;
    if (isset($sessionData['token'])) {
        $token = $sessionData['token'][0];
    } else {
        echo "Could not get token. Abort execution\n";
        return;
    }

    echo "Session with ID " . $sessionId . " created\n";

    $response = $clientWithoutExceptions->delete(SESSIONS_URI . '/' . $sessionId);

    if ($response->getStatusCode() !== 204) {
        echo "Could  not delete session. Abort execution.\n";
        return;
    } else {
        echo "Session deleted\n";
    }

    // initialize a client without cookies
    $someOtherClientUsingTheToken = new Client(
        [
            'base_uri' => BASE_URL,
            'http_errors' => true,
            'verify' => CERTIFICATE_FOR_SSL_COMMUNICATION,
        ]
    );

    // make an example request using the token to demonstrate that it can be handled by the server
    $response = $someOtherClientUsingTheToken->get(
        SESSIONS_URI,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]
    );

    // REST API returns the wanted information, not an error that the user could not be authenticated.
    echo "Response:\n";
    echo $response->getBody();
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
