<?php

const BASE_URL = 'https://jracademy.demo.jobrouter.cloud/api/rest/v2/';  // REST API URL
const USERNAME = 'academy1_de'; // Username for authentication
const PASSWORD = "JRAcademy_Tr@1ning"; // Password for authentication

const ARCHIVE = "220BD671-D875-FF15-6C99-22022682020D"; // Archive table or GUID for JobArchive examples

const PROCESS = ""; // Process name for process examples
const PROCESS_VERSION = 1; // Process version for process examples
const PROCESS_BOX_ID = 1; // Process box id for process examples

const JOBDATA_TABLE_GUID = ''; // GUID of JobData table for JobData examples

// Use project-relative paths (portable across machines)
const CURL_COOKIE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'cookie_path';
const FILE_STORAGE = __DIR__ . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

// Ensure required local directories exist at runtime
if (!is_dir(CURL_COOKIE_PATH)) {
	@mkdir(CURL_COOKIE_PATH, 0777, true);
}
if (!is_dir(FILE_STORAGE)) {
	@mkdir(FILE_STORAGE, 0777, true);
}
