<?php

include_once realpath(__DIR__ . "/../") . "/config/INI.php";
include_once realpath(__DIR__ . "/../") . "/config/Database.php";
include_once realpath(__DIR__ . "/../") . "/config/Token.php";
include_once realpath(__DIR__ . "/../") . "/config/API.php";
include_once realpath(__DIR__ . "/../") . "/models/Questions.php";
include_once realpath(__DIR__ . "/../") . "/interactions/ResponseProcessor.php";

$ini_file = realpath(__DIR__ . "/../") . "/apiconfig.ini";

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

// Instantiate ini for interactions with scraper config file
$config = new INI($ini_file);
$response_processor = new ResponseProcessor();
$token = new Token();
$api = new API($config, "https://opentdb.com/", $response_processor, $token);
$database = new Database($ini_file);

// API initalises $response_processor and $token
$api->initialise();
$db = $database->connect();

// Going to switch to call_user_func for callbacks so we can also pass in the callback function's class
// The function call is
// call_user_func(array($api, $req_details['callback']), $api_data, $req_details);
// So if we use an array in the $req_details array, we can pass that directly

// Instantiated here just for mocked req_details - but may do this anyway
// https://stackoverflow.com/questions/15444215/php-passing-an-instance-of-a-class-to-another-class
$questions = new Questions($db);

// Mock request for dev
$mock_request = array(
	// Callback is an array which will be passed directly by API class as first arg of call_user_function()
	'callback'=>array($questions, 'testCallback'),
	'endpoint'=>'api.php',
	'parameters'=>array(
		'category'	=> 15,
		'amount'	=> 50
	)
);

$response = $api->request($mock_request);
var_dump($response);



