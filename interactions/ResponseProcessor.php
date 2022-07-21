<?php

Class ResponseProcessor {

	private $token;
	private $api;

	public function initialise($token, $api) {

		$this->token = $token;
		$this->api = $api;
	}

	/**
	 * React to API response codes
	 * 
	 * @param array $req_details: the url fragments
	 * @param array api_response: Data returned by api
	 * @param string req_url: the assembled url that was used for the api call
	 * @return Processed data or false
	 */
	public function process($req_details, $api_response, $req_url) {

		try {
			if (! array_key_exists('response_code', $api_response)) {
				// Request is a lookup. Lookup callbacks are passed in with req_details
				return call_user_func($req_details['callback'], $api_response);
			}

			$response_code = $api_response['response_code'];

			switch ($response_code) {

				case 0:
		    		// Successfully retrieved token or questions
				if ($req_details['endpoint'] == 'api_token.php') {
				    	// Token string will be passed to callback
					$api_data = $api_response['token'];
				} else {
						// Questions in results array will be passed to callback
					$api_data = $api_response['results'];
				}
				   // NOTE: $req_details['callback'] should be array(object, method_name)
				return call_user_func($req_details['callback'], $api_data, $req_details);

				case 1:
				trigger_error("(Response code 1): Quantity unavailable - API unable to return data for the query $req_url.", E_USER_ERROR);

				case 2:
				trigger_error("(Response code 2): Invalid parameter passed to Open Trivia API: $req_url.", E_USER_ERROR);

				case 3:
			    	// Token not found. Attempt to recover - duplicate questions will not trigger an SQL error as we're using ON DUPLICATE KEY UPDATE id=id.
			    	// get new token to ensure api returns unique questions (going forward - they're only unique to the new token)
				$this->token->sessionToken(true);
		       		// Make the request again
				return $this->api->request($req_details, True);

				case 4:
				trigger_error("(Response code 4): All requested questions have been returned for the current token. Run the app without the -t flag to use a new token. Request was $req_url", E_USER_ERROR);
				
				default:
				trigger_error("(Response code $response_code): Open Trivia API returned an unknown error code: $req_url.", E_USER_ERROR);

			}
		}
		catch (exception $e) {
			error_log($e);
			return false;
		}
	}
}