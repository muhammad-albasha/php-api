<?php

class DocumentManager
{
    protected $authenticator;
    protected $client;
    protected $sessionId;

    const RESOURCE_URI = 'application/jobarchive/archives/' . ARCHIVE . '/documents';
    const RESOURCE_URI_MAIN_FILE = self::RESOURCE_URI . '/##revision##/file';
    const RESOURCE_URI_CLIPPED_FILES = self::RESOURCE_URI . '/##revision##/clippedfiles';
    const RESOURCE_URI_ALL_FILES = self::RESOURCE_URI . '/##revision##/files';
    const RESOURCE_URI_CHANGE_INDEX_SINGLE = self::RESOURCE_URI . '/##revision##/indexdata';
    const RESOURCE_URI_CHANGE_INDEX_MULTIPLE = 'application/jobarchive/archives/' . ARCHIVE . '/indexdata';
    const RESOURCE_URI_ACTIONS = self::RESOURCE_URI . '/##revision##';
    const RESOURCE_URI_SEARCH = 'application/jobarchive/archives/' .  ARCHIVE . '/index';

    const ROOT = 'archivedocumentrevisions';

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->sessionId = $this->authenticator->createSession();
        $this->client = $this->authenticator->getClient();
    }

    /**
     * @param array $documentContentAndMetaData Array containing index values and file content.
     * The array contains name/contents values. For each index field or keyword there should be a name/value pair.
     * File are sent using the 'files' key.
     * Example:
     * [
     *     'name'     => 'indexFields[0][name]',
     *     'contents' => 'myField1'
     * ],
     * [
     *     'name'     => 'indexFields[0][value]',
     *     'contents' => 'value for my field'
     * ],
     * [
     *     'name'     => 'indexFields[1][name]',
     *     'contents' => 'myNumberField'
     * ],
     * [
     *     'name'     => 'indexFields[1][value]',
     *     'contents' => '123'
     * ],
     * [
     *     'name'     => 'indexFields[2][name]',
     *     'contents' => 'myDateField'
     * ],
     * [
     *     'name'     => 'indexFields[2][value]',
     *     'contents' => '2018-05-21T11:20:40+01:00'
     * ],
     * [
     *     'name'     => 'keywordFields[0][name]',
     *     'contents' => 'myKeywordField1'
     * ]
     * [
     *     'name'     => 'keywordFields[0][value]',
     *     'contents' => 'Tests'
     * ]
     * [
     *     'name'     => 'files',
     *     'contents' => fopen('/path/to/some_filename.txt', 'r')
     * ]
     *
     * The request is send as multipart/form-data.
     * All values should be strings. Date values are send in the format YYYY-MM-DDTHH:II:SSZ,
     * where Z is the timezone offset (difference between current timezone and UTC)
     *
     * See Hypertext Transfer Protocol and POST Requests with Guzzle for further information.
     */
    public function archiveDocument($documentContentAndMetaData)
    {
        try {
            $response = $this->client->request(
                'POST',
                static::RESOURCE_URI,
                [
                    'multipart' => $documentContentAndMetaData,
                ]
            );

            if ($response->getStatusCode() == 201) {
                $body = json_decode($response->getBody(), true);
                echo "Document ID: " . $body[static::ROOT][0]['revisionId'] . "\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function getDocumentFile($revisionId)
    {
        try {

            $url = str_replace('##revision##', $revisionId, static::RESOURCE_URI_MAIN_FILE);
            $this->client->request(
                'GET',
                $url,
                [
                    'sink' => './my_file',
                ]
            );
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function getDocumentFiles($revisionId)
    {
        try {

            $url = str_replace('##revision##', $revisionId, static::RESOURCE_URI_ALL_FILES);
            $this->client->request(
                'GET',
                $url,
                [
                    'sink' => './my_files.zip',
                ]
            );
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function getDocumentClippedFiles($revisionId)
    {
        try {

            $url = str_replace('##revision##', $revisionId, static::RESOURCE_URI_CLIPPED_FILES);
            $this->client->request(
                'GET',
                $url,
                [
                    'sink' => './my_files.zip',
                ]
            );
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function clipFilesToDocument($revisionId, $files)
    {
        try {
            $url = str_replace('##revision##', $revisionId, static::RESOURCE_URI_CLIPPED_FILES);
            $response = $this->client->request(
                'POST',
                $url,
                [
                    'multipart' => $files,
                ]
            );

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() == 200) {
                echo "Revision ID: " . $body[static::ROOT][0]['revisionId'] . "\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function changeIndexData($revisionId, $indexData, $method)
    {
        try {
            $url = str_replace('##revision##', $revisionId, static::RESOURCE_URI_CHANGE_INDEX_SINGLE);
            $response = $this->client->request(
                $method,
                $url,
                [
                    'json' => $indexData,
                ]
            );

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() == 200) {
                echo "Revision ID: " . $body[static::ROOT][0]['revisionId'] . "\n";
                echo "Index data: " . var_export($body[static::ROOT][0]['indexFields'], true) . "\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function changeIndexDataMultiple($indexData, $method)
    {
        try {
            $response = $this->client->request(
                $method,
                static::RESOURCE_URI_CHANGE_INDEX_MULTIPLE,
                [
                    'json' => $indexData,
                ]
            );

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() == 200) {
                foreach ($body[static::ROOT] as $documentRevision) {
                    echo "Revision ID: " . $documentRevision['revisionId'] . "\n";
                    echo "Index data: " . var_export($documentRevision['indexFields'], true) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function lockDocument($revisionId)
    {
        try {
            $url = str_replace('##revision##', $revisionId, static::RESOURCE_URI_ACTIONS);
            $response = $this->client->request(
                'PATCH',
                $url,
                [
                    'json' => [
                        'locked' => true
                    ]
                ]
            );

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() == 200) {
                echo "Revision ID: " . $body[static::ROOT][0]['revisionId'] . "\n";
                echo "Locked: " . $body[static::ROOT][0]['locked'] . "\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function deleteDocument($revisionId)
    {
        try {
            $url = str_replace('##revision##', $revisionId, static::RESOURCE_URI_ACTIONS);
            $response = $this->client->request('DELETE', $url);

            if ($response->getStatusCode() !== 204) {
                echo "Action could not be executed\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }

    public function searchDocuments()
    {
        try {
            $filter = '?where[numberField][gt]=1000&where[numberField][lt]=3000';
            $response = $this->client->request('GET', static::RESOURCE_URI_SEARCH . $filter);
//            $response = $this->client->request('GET', static::RESOURCE_URI . $filter);   // use this resource to get also keywords in response

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() == 200) {
                foreach ($body['archivedocuments'] as $document) {
                    echo "Revision ID: " . $document['revisionId'] . "\n";
                    echo "Index data: " . var_export($document['indexFields'], true) . "\n";
                    if (isset($document['keywordFields'])) {
                        echo "Keywords: " . var_export($document['keywordFields'], true) . "\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->authenticator->deleteSession($this->sessionId);
    }
}


