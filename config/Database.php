<?php
// Database connection setup
// https://www.youtube.com/watch?v=OEWXbpUMODk

Class Database {
	
	private $host;
	private $db_name;
	private $username;
	private $password;
	private $conn;

	public function __construct($credentials) {

		$this->host = $credentials['host'];
		$this->db_name = $credentials['db_name'];
		$this->username = $credentials['username'];
		$this->password = $credentials['password'];
	}

	public function connect() {
		$this->conn = null;
		try {
			$this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
			// Set the error mode
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			trigger_error("Connection Error " . $e->getMessage(), E_USER_ERROR);
		}

		return $this->conn;
	}

	/**
	 * Execute the provided list of MySQL queries
	 * @param array $db_queries - array of parameterised request arrays/SQL query strings
	 * @param array $options - has member 'insert' when running INSERT query,  'questions' when inserting questions
	 *
	 * @return ID of added row if questions parameter is true, else result of MySQL Query
	 */
	public function query($queries, $options = array()) {

		try {
			$this->conn = $this->connect();

			foreach ($queries as $db_query) {

				$use_prepared = is_array($db_query);

				if ($use_prepared) {
					$stmt = $this->conn->prepare($db_query['query']);
					$stmt->execute($db_query['values']);
				} else {
					$stmt = $this->conn->prepare($db_query);
					$stmt->execute();
				}

				if (in_array('insert', $options)) {
					if (in_array('questions', $options)) {				
						return $this->conn->lastInsertId('id');
					}
					$query_results = $stmt;
				} else {
					$query_results = $stmt->fetchAll();
				}
			}

			if (is_countable($query_results) && count($query_results)) {
				return $query_results;
			}
			return array(0);
		}
		catch (Exception $error) {
			trigger_error($error->getMessage(), E_USER_ERROR);
		}
	}
}