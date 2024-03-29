<?php

Class ResponseProcessor {

	/**
	 * React to API response codes
	 * 
	 * @param array $query_details:
	 * 			array req_details: the url fragments
	 * 			array api_response: Data returned by api
	 * 			string req_url: the assembled url that was used for the api call
	 * 			token: instance of the Token class
	 * 			api: instance of the API class
	 * @return Processed data or false
	 */
	public static function process($query_details) {

		$req_details = $query_details['req_details'];
		$api_response = $query_details['api_response'];
		$req_url = $query_details['req_url'];
		$token = $query_details['token'];
		$api = $query_details['api'];

		try {
			if (! array_key_exists('response_code', $api_response)) {
				// Request is a lookup. Lookup callbacks are passed in with req_details
				if (isset($req_details['callback'])) {
					return call_user_func($req_details['callback'], $api_response);
				}
				return $api_response;
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
				// Use provided callback
				return call_user_func($req_details['callback'], $api_data, $req_details);				

				case 1:
				trigger_error("(Response code 1): Quantity unavailable - API unable to return data for the query $req_url.", E_USER_ERROR);

				case 2:
				trigger_error("(Response code 2): Invalid parameter passed to Open Trivia API: $req_url.", E_USER_ERROR);

				case 3:
				trigger_error("(Response code 3): Token not found. Run the app without the -t flag to use a new token. Request was $req_url", E_USER_ERROR);

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