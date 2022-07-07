<?php

Class Database {
	// DB Params
	private $host;
	private $db_name;
	private $username;
	private $password;
	private $conn;

	// Constructor with DB
	public function __construct($config) {

		// Parse ini file, second arg is process_sections which makes the return value a multidimensional array with section names and setting included
		// Will need to pass $config to the open-triviata-api database like this too if we're incorporating the scraper in the same directory structure
		$credentials = parse_ini_file($config);

		$this->host = $credentials['host'];
		$this->db_name = $credentials['db_name'];
		$this->username = $credentials['username'];
		$this->password = $credentials['password'];
	}

	// DB connect
	public function connect() {
		$this->conn = null;
		try {
			$this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
			// Set the error mode
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo "Connection Error " . $e->getMessage();
		}

		return $this->conn;
	}
}