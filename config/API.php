<?php

Class API {

	private $config;
	private $req_url;
	private $response_processor;
	private $token;

	public function __construct($config, $req_url, $response_processor, $token) {

		$this->config = $config;
		$this->req_url = $req_url;
		$this->token = $token;
		$this->response_processor = $response_processor;
	}

	public function initialise() {

		// $token needs $config and API to make api calls and update ini file
		$this->token->initialise($this->config, $this);

		// $response_processor needs $token and $api for api calls
		$this->response_processor->initialise($this->token, $this);	
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
		$curl_options = array(
			CURLOPT_FAILONERROR 	=> true,
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_HTTPHEADER 		=> array("Cookie: PHPSESSID=1b01789fb2d1898c5d3358944fec0590"),
			CURLOPT_URL 			=> $req_url
		);
		curl_setopt_array($ch, $curl_options);

		// Make the call
		$response = curl_exec($ch);

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
			return $this->response_processor->process($req_details, json_decode($response, true), $req_url);			
		}
	}
}