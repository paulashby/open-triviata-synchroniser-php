<?php

include_once realpath(__DIR__ . "/../") . "/config/Database.php";
include_once realpath(__DIR__ . "/../") . "/config/API.php";
include_once realpath(__DIR__ . "/../") . "/utilities/DataCleaner.php";
include_once realpath(__DIR__ . "/../") . "/models/Categories.php";


//set error handler
set_error_handler('log_then_die', E_USER_ERROR);

$new_token = true;

if (isset($argc) && $argc > 1) {

	if($argv[1] === "-h") {
		// Show usage info
		die("-t Use existing token if available - API will not return questions already provided within the last 6 hours");

	} else if ($argv[1] === "-t") {
		// See usage info above
		$new_token = false;
	}
}

// https://stackoverflow.com/questions/42700310/how-to-reference-to-a-folder-that-is-above-document-root-in-php
$config = parse_ini_file(realpath(__DIR__ . "/../") . "/appconfig.ini");

$connections = array(
	// Connection to API - our data source
	'api' 		=> new API("https://opentdb.com/", $new_token),
	// Connection to triviata database - where we'll store the data obtained from the api
	'database' 	=> new Database($config)
);

$categories = new Categories($connections);

// Synchronise categories with Open Trivia API
$categories->synchronise();

// Error handler
function log_then_die($code, $message, $file, $line_number) {

	$error_message = "$code Error: ($file, #$line_number): $message";

	error_log($error_message);
	die($error_message);	
}
