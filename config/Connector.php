<?php

include_once realpath(__DIR__ ) . "/INI.php";
include_once realpath(__DIR__ ) . "/Database.php";
include_once realpath(__DIR__ ) . "/Token.php";
include_once realpath(__DIR__ ) . "/API.php";
include_once realpath(__DIR__ . "/../") . "/interactions/ResponseProcessor.php";

Class Connector {
	
	public $database;
	public $api;

	public function __construct() {

		$ini_file = realpath(__DIR__ . "/../") . "/apiconfig.ini";

		// Instantiate ini for interactions with scraper config file
		$config = new INI($ini_file);
		$response_processor = new ResponseProcessor();
		$token = new Token();

		// API initalises $response_processor and $token
		$this->api = new API($config, "https://opentdb.com/", $response_processor, $token);
		$this->api->initialise();

		// Connection to local database - where we'll store the data obtained from the api
		$this->database = new Database($ini_file);
	}	
}