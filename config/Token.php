<?php

Class Token {

	private $config;
	private $api;

	public function initialise($config, $api) {

		// $config allows us to update ini file
		$this->config = $config;

		// $api for calls to Open Trivia api
		$this->api = $api;

		// Update session token
		$this->sessionToken();
	}	

	/**
	 * Retrieve a session token
	 * 
	 * @param boolean $expired: Do not read from config - get new from API
	 * @return string - session cookie string
	 */
	public function sessionToken($expired = false) {

		$session_token = $this->config->get('api_token', 'tokenconfig');

		if($expired || (! $session_token) || ! strlen($session_token)) {
			$session_token = $this->newToken();
		}

		return $session_token;
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
	 * Store new session token in config file
	 * 
	 * @param string $token
	 * @param array $req_details
	 * @return string - session cookie string
	 */
	public function setToken($token, $req_details) {

		if(!ctype_alnum($token)) {
			trigger_error("Function argument 1 must be an alphanumeric string.", E_USER_ERROR);
		}
		$this->config->set($token, 'api_token', 'tokenconfig');
		return $token;
	}
}