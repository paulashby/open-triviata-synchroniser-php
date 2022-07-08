<?php

include_once realpath(__DIR__ . "/../") . "/config/INI.php";
include_once realpath(__DIR__ . "/../") . "/config/Database.php";
include_once realpath(__DIR__ . "/../") . "/config/API.php";

$ini_file = realpath(__DIR__ . "/../") . "/apiconfig.ini";

$new_token = true;

if ($argv[1]) {
	if($argv[1] === "-h") {
		// Show usage info
		die("-t Use existing token if available - API will not return questions already provided within the last 6 hours");

	} else if ($argv[1] === "-t") {
		// See usage info above
		$new_token = false;
	}
}

// Instantiate ini for simple interactions with scraper config file
$config = new INI($ini_file);

// Instantiate API interface and initalise cURL
$opentrivia_api = new API($config, "https://opentdb.com/");

// Instantiate database and connect
$database = new Database($ini_file);
$db = $database->connect();

// Mock request for dev
$req_details = array(
	'callback'=>'processQuestions',
	'endpoint'=>'api.php',
	'parameters'=>array(
		'category'	=> 11,
		'amount'	=> 50
	)
);

$response = $opentrivia_api->request($req_details);
var_dump($response);



