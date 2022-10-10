<?php

include_once realpath(__DIR__ ) . "/Token.php";
include_once realpath(__DIR__ . "/../") . "/interactions/ResponseProcessor.php";

Class API {

	private $req_url;
	private $token;

	public function __construct($req_url, $new_token) {

		$this->req_url = $req_url;
		$this->token = new Token($this, $new_token);
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

		// Include api instance as we'll need to get a new token and retry if token is not found
		$req_details['api'] = $this;

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
			$req_url .= "{$pre}token=" . $this->token->sessionToken();
		}
		
		$ch = curl_init();

		// https://stackoverflow.com/questions/10524543/how-to-pass-custom-header-to-restful-call
		$curl_options = array(
			CURLOPT_FAILONERROR 	=> true,
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_HTTPHEADER 		=> array("Cookie: PHPSESSID=1b01789fb2d1898c5d3358944fec0590"),
			CURLOPT_URL 			=> $req_url
		);
		curl_setopt_array($ch, $curl_options);

		// Make the call
		$response = curl_exec($ch);

		// https://stackoverflow.com/questions/3987006/how-to-catch-curl-errors-in-php
		if (curl_errno($ch)) {
			$error_mssg = curl_error($ch);
		}
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if (isset($error_mssg)) {
			trigger_error($error_mssg, E_USER_ERROR);
		}

		if ($response_code != 204){
			// Return data which the calling function should process
			$query_details = array(
				'req_details'	=> $req_details,
				'api_response'	=> json_decode($response, true),
				'req_url'		=> $req_url,
				'token'			=> $this->token,
				'api'			=> $this

			);
			return ResponseProcessor::process($query_details);		
		}
	}
}