<?php

include_once realpath(__DIR__ ) . "/INI.php";
include_once realpath(__DIR__ ) . "/Database.php";
include_once realpath(__DIR__ ) . "/Token.php";
include_once realpath(__DIR__ ) . "/API.php";

Class Connector {
	
	public $database;
	public $api;

	public function __construct() {

		$ini_file = realpath(__DIR__ . "/../") . "/apiconfig.ini";

		// Instantiate ini for interactions with synchroniser config file
		$config = new INI($ini_file);
		$token = new Token();

		// API initalises $token
		$this->api = new API($config, "https://opentdb.com/", $token);
		$this->api->initialise();

		// Connection to local database - where we'll store the data obtained from the api
		$this->database = new Database($ini_file);
	}	
}