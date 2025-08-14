<?php
const BASE_URL = 'https://example.org/api/rest/v2/';  // REST API URL
const USERNAME = ''; // Username for authentication
const PASSWORD = ""; // Password for authentication

const ARCHIVE = ""; // Archive table or GUID for JobArchive examples

const PROCESS = ""; // Process name for process examples
const PROCESS_VERSION = 1; // Process version for process examples
const PROCESS_BOX_ID = 1; // Process box id for process examples

const JOBDATA_TABLE_GUID = ''; // GUID of JobData table for JobData examples

// Set this constant if you want to use https.
// It should be set to the path to the SSL certificate (including filename).
// See the guzzlehttp documentation for details (http://docs.guzzlephp.org/en/stable/request-options.html#verify).
// If you do not want to use verification set to false.
const CERTIFICATE_FOR_SSL_COMMUNICATION = '/path/to/certificate/file.pem';
