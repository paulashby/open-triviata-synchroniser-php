<?php


include_once "../config/INI.php";
include_once "../config/Database.php";
include_once "../config/API.php";

// Changed permissions on local ini file, but remote is fine as is
$ini_file = realpath(__DIR__ . "/../") . "/apiconfig.ini";

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



