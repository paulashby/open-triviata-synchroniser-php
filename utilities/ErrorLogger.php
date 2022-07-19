<?php

Class ErrorLogger {

	public function __construct() {

		//set error handler
		set_error_handler(array($this, 'log_then_die'), E_USER_ERROR);
	}

	public function log_then_die($code, $message, $file, $line_number) {

		$error_message = "$code Error: ($file, #$line_number): $message";

		error_log($error_message);
		die($error_message);	
	}
}