<?php

class CurlClient
{
    private $curlHandle;
    private $cookieFile;

    private $sessionId;
    private $jwt;
    private $isJson = false;
    private $debugMode = false;

    public function __construct()
    {
        $this->cookieFile = CURL_COOKIE_PATH . '/' . USERNAME . microtime(true) . '.cookie';
    }

    public function authenticate()
    {
        $this->init();
        $data = '{
                "username": "' . USERNAME . '",
                "password": "' . PASSWORD . '"
            }';

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $url = BASE_URL . 'application/sessions';

        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curlHandle, CURLOPT_URL, $url);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_POST, 1);
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->curlHandle, CURLOPT_COOKIEJAR, $this->cookieFile);

        $response = curl_exec($this->curlHandle);

        $code = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($this->curlHandle);

        if ($code !== 201) {
            $this->close();
            $this->emptyJar();
            $errorMsg = 'Authentication Error: HTTP ' . $code;
            if (!empty($curlError)) {
                $errorMsg .= '. cURL Error: ' . $curlError;
            }
            $errorMsg .= '. Response: ' . var_export($response, true);
            throw new Exception($errorMsg);
        }

        $response = json_decode($response, true);
        $this->sessionId = $response['sessions'][0]['sessionId'];
        $this->jwt = isset($response['token'][0]) ? $response['token'][0] : null;
    }

    public function setJson($value)
    {
        $this->isJson = $value;
    }

    public function getCookieFile()
    {
        return $this->cookieFile;
    }

    public function get($route)
    {
        $this->reset();

        curl_setopt($this->curlHandle, CURLOPT_URL, BASE_URL . $route);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);

        $response = curl_exec($this->curlHandle);
        $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($statusCode !== 200) {
            echo "\nGET-Route returned error code " . $statusCode . ". Response: " . var_export($response, true) . "\n";
            return [];
        }

        return json_decode($response, true);
    }

    public function download($route)
    {
        $this->reset();

        curl_setopt($this->curlHandle, CURLOPT_URL, BASE_URL . $route);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);

        $tempFile = fopen('./downloadedFiles.zip', 'w+');
        curl_setopt($this->curlHandle, CURLOPT_FILE , $tempFile);

        $response = curl_exec($this->curlHandle);

        fclose($tempFile);

        $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($statusCode !== 200) {
            echo "\nCould not download! Returned error code " . $statusCode . ". Response: " . var_export($response, true) . "\n";
        }
    }

    public function post($route, $data, $successCode = 201)
    {
        $this->reset();

        curl_setopt($this->curlHandle, CURLOPT_URL, BASE_URL . $route);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_POST, 1);
        curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);

        if ($this->isJson) {
            $this->setJsonHeaders();
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        }

        if ($this->debugMode) {
            curl_setopt($this->curlHandle, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($this->curlHandle);
        $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($statusCode !== $successCode) {
            echo "\nPOST-Route returned error code " . $statusCode . ". Response: " . var_export($response, true) . "\n";
            return [];
        }

        return json_decode($response, true);
    }

    public function put($route, $data)
    {
        $this->reset();

        curl_setopt($this->curlHandle, CURLOPT_URL, BASE_URL . $route);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);

        if ($this->isJson) {
            $this->setJsonHeaders();
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        }

        if ($this->debugMode) {
            curl_setopt($this->curlHandle, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($this->curlHandle);
        $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($statusCode !== 200) {
            echo "\nPUT-Route returned error code " . $statusCode . ". Response: " . var_export($response, true) . "\n";
            return [];
        }

        return json_decode($response, true);
    }

    public function patch($route, $data)
    {
        $this->reset();

        curl_setopt($this->curlHandle, CURLOPT_URL, BASE_URL . $route);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);

        if ($this->isJson) {
            $this->setJsonHeaders();
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $data);
        }

        if ($this->debugMode) {
            curl_setopt($this->curlHandle, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($this->curlHandle);
        $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($statusCode !== 200) {
            echo "\nPUT-Route returned error code " . $statusCode . ". Response: " . var_export($response, true) . "\n";
            return [];
        }

        return json_decode($response, true);
    }

    public function delete($route)
    {
        $this->reset();

        curl_setopt($this->curlHandle, CURLOPT_URL, BASE_URL . $route);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);

        if ($this->debugMode) {
            curl_setopt($this->curlHandle, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($this->curlHandle);
        $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        if ($statusCode !== 204) {
            echo "\nDELETE-Route returned error code " . $statusCode . ". Response: " . var_export($response, true) . "\n";
            return [];
        }

        return json_decode($response, true);
    }

    public function destroySession()
    {
        $this->reset();

        curl_setopt($this->curlHandle, CURLOPT_URL, BASE_URL . 'application/sessions/' . $this->sessionId);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $this->cookieFile);

        $response = curl_exec($this->curlHandle);
        $statusCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);

        $this->close();
        $this->emptyJar();

        if ($statusCode !== 204 && $statusCode !== 404) {
            // 404 ist OK - Session existiert möglicherweise nicht mehr
            throw new Exception($statusCode . ' - Could not delete session: ' . var_export($response, true));
        }
    }

    public function setDebugMode($value)
    {
        $this->debugMode = $value;
    }

    private function init()
    {
        $this->curlHandle = curl_init();
        
        // SSL-Optionen für Demo-Umgebungen
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYHOST, false);
    }

    private function reset()
    {
        curl_reset($this->curlHandle);
        
        // SSL-Optionen für Demo-Umgebungen nach Reset wiederherstellen
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curlHandle, CURLOPT_SSL_VERIFYHOST, false);
    }

    private function setJsonHeaders()
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $headers);
    }

    private function close()
    {
        curl_close($this->curlHandle);
    }

    private function emptyJar()
    {
        if (file_exists($this->cookieFile)) {
            echo "delete file " . $this->cookieFile . "\n";
            unlink($this->cookieFile);
        }
    }
}

