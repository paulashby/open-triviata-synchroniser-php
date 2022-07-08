<?php

Class API {

	private $config;
	private $req_url;
	private $ch;

	public function __construct($config, $req_url) {

		$this->config = $config;
		$this->req_url = $req_url;
		$this->ch = curl_init();
	}

	/**
     * Make API call
     * 
     * @param string $req_details
     * @param boolean $use_token
     * @return Processed data
     */
	public function request($req_details, $use_token = true) {

		// Concatonate with empty string so we don't replace our base url
		$req_url = $this->req_url . "";
		$pre = "";

		// Assemble API request
		if(array_key_exists('endpoint', $req_details)){
			$req_url .= $req_details['endpoint'];
		}

		if(array_key_exists('parameters', $req_details)){
			
			$pre = "?";

			foreach ($req_details['parameters'] as $param_key => $param_val) {
				$req_url .=  "{$pre}{$param_key}={$param_val}";
				$pre = "&";
			}			
		}

		if($use_token) {
			// Append the token to ensure the api returns no duplicate questions
			$req_url .= "{$pre}token=" . $this->sessionToken();
		}

		// Configure curl		
		curl_setopt($this->ch, CURLOPT_FAILONERROR, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_URL, $req_url);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Cookie: PHPSESSID=1b01789fb2d1898c5d3358944fec0590"));

		// Make the call
		$response = curl_exec($this->ch);

		if (curl_errno($this->ch)) {
		    $error_mssg = curl_error($this->ch);
		}
		$response_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		curl_close($this->ch);

		if (isset($error_mssg)) {
		    error_log($error_mssg);
		    die($error_mssg);
		}

		if ($response_code != 204){
			// Return data which the calling function should process
			return $this->processResponse($req_details, json_decode($response, true), $req_url);			
		}
	}

	private function processQuestions($api_data, $req_details) {

		// echo "processQuestions callback called";
		return $api_data;

	}

	/**
     * React to API response codes
     * 
     * @param array $req_details: the url fragments
     * @param array api_response: Data returned by api
     * @param string req_url: the assembled url that was used for the api call
     * @return Processed data or false
     */
	private function processResponse($req_details, $api_response, $req_url) {

		try {
			if (! array_key_exists('response_code', $api_response)) {
				// This must be a lookup. Lookup callbacks are passed in with req_details
				return callback($api_response);
		   }

		   $response_code = $api_response['response_code'];

		   switch ($response_code) {

		    	case 0:
		    		// Successfully retrieved token or questions
					if ($req_details['endpoint'] == 'api_token.php') {
				    	// Pass the token string to the callback
				       	$api_data = $api_response['token'];
					} else {
						// Pass the questions in the results array to the callback
						$api_data = $api_response['results'];
				   }

				   return call_user_func(array($this, $req_details['callback']), $api_data, $req_details);

		    	case 1:
		    		error_log("\nError (Response code 1): Quantity unavailable - API unable to return data for the query {req_url}\n");
		    		die(1);

		    	case 2:
		    		error_log("\nError (Response code 2): Invalid parameter passed to Open Trivia API:\n{req_url}\n");
		    		die(1);

				case 3:
			    	// Token not found. Attempt to recover - duplicate questions will trigger an SQL error as the question_text field is UNIQUE.
			    	// get new token to ensure api returns unique questions (going forward - they're only unique to the new token)
		       		$this->sessionToken(true);
		       		// Make the request again
		       		return $this->request($req_details, True);

		    	case 4:
		    		error_log("\nNotification: (Response code 4): All requested questions have been returned for the current token. Run the app without the -t flag to use a new token\n");
		    		return;
		    	
		    	default:
		    		error_log("\nError $response_code: Open Trivia API returned an unknown error code:\n$req_url\n");
		    		die(1);
		    }
		}
		catch (exception $e) {
			error_log($e);
			return false;
		}
	}

	/**
     * Retrieve a session token
     * 
     * @param boolean $expired: Do not read from config - get new from API
     * @return string - session cookie string
     */
	private function sessionToken($expired = false) {

		$session_token = $this->config->get('api_token', 'tokenconfig');

		if($expired || (! $session_token) || ! strlen($session_token)) {
			$session_token = $this->newToken();
		}

		return $session_token;
	}

	/**
     * Request new token
     * 
     * @param string $token
     * @return string - session cookie string
     */
	private function newToken() {

		$req_details = array(
		    'callback'	 => 'setToken',
		    'endpoint' 	 => 'api_token.php',
		    'parameters' => array(
		    	'command' 	 => 'request'
		    )
		);
		return $this->request($req_details, False);
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
			throw new \InvalidArgumentException("Function argument 1 must be an alphanumeric string.");
		}
		$this->config->set($token, 'api_token', 'tokenconfig');
		return $token;
	}
	
}