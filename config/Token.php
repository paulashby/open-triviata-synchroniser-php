<?php

Class Token {

	private $token_file;
	private $api;

	public function __construct($api, $new_token) {

		$this->token_file = realpath(__DIR__ . "/../") . "/session_token.txt";

		// $api for calls to Open Trivia api
		$this->api = $api;

		// Update session token
		self::sessionToken($new_token);

	}

	/**
	 * Retrieve a session token
	 * 
	 * @param boolean $new_token: Do not read from config - get new from API
	 * @return string - session cookie string
	 */
	public function sessionToken($new_token = false) {

		$session_token = file($this->token_file);

		if($new_token || (! $session_token) || ! count($session_token)) {
			$session_token = $this->newToken();
		}

		return $session_token[0];
	}

	/**
	 * Request new token
	 * 
	 * @return string - session cookie string
	 */
	private function newToken() {

		$req_details = array(
			'callback'	 => array($this, 'setToken'),
			'endpoint' 	 => 'api_token.php',
			'parameters' => array(
				'command' 	 => 'request'
			)
		);
		return $this->api->request($req_details, False);
	}

	/**
	 * Store new session token in session_token file
	 * 
	 * @param string $token
	 * @return string - session cookie string
	 */
	public function setToken($token) {

		if(!ctype_alnum($token)) {
			trigger_error("First argument must be an alphanumeric string.", E_USER_ERROR);
		}
		// Write token to session_token text file
		file_put_contents($this->token_file, $token);
		return $token;
	}
}