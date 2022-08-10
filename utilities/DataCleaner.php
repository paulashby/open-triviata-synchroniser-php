<?php

Class DataCleaner {

	private const PARAMS = array(
		'type'			=> array(
			"boolean",
			"multiple"
		),
		'difficulty'	=> array(
			"easy",
			"medium",
			"hard"
		)
	);

	public static function clean($data, $method, $more = false) {
		// permitted_vals is constant to check values against (TYPE etc)
		return $more ? self::$method($data, $more) : self::$method($data);	
	}

	private function categoryName($str) {
		$clean = filter_var($str, FILTER_SANITIZE_STRING);
		$valid = preg_match('/^[\.a-zA-Z&: ]*$/', $clean) === 1 ? $clean : false;

		if ($valid === false) {
			trigger_error("Invalid category name: expected string containing only letters, ampersands, colons and spaces.\n", E_USER_ERROR);
		}
		return $valid;				
	}

	private function parameter($str, $param) {
		$permitted = self::PARAMS[$param];
		if (in_array($str, $permitted)) {
			return $str;
		}
		$permitted = implode(", ", $permitted);
		trigger_error("Invalid value: type must be one of $permitted", E_USER_ERROR);
	}

	private function difficultyLevel($str) {
		$clean = filter_var($str, FILTER_SANITIZE_STRING);
		$valid = preg_match('/^[\.a-zA-Z_]*$/', $clean) === 1 ? $clean : false;

		if ($valid === false) {
			trigger_error("Invalid difficulty level: expected string containing only letters and underscores.\n", E_USER_ERROR);
		}
		return $valid;	
	}

	private function difficultyLevelArray($breakdown) {

		foreach ($breakdown as $difficulty_level => $count) {
			$difficulty_level_name = self::difficultyLevel($difficulty_level);
			$difficulty_level_count = self::integer($count);
		}
		return $breakdown;
	}

	private function string($str) {
		$clean = filter_var($str, FILTER_SANITIZE_STRING);
		if ($clean !== false) {
			return $clean;
		}
		$str = preg_replace('/[^A-Za-z0-9. -]/', '', $str);
		trigger_error("The following string was rejected (unsafe characters removed): $str\n", E_USER_ERROR);
	}

	private function alpha($str) {
		$clean = filter_var($str, FILTER_SANITIZE_STRING);
		$valid = ctype_alpha(str_replace(' ', '', $clean));

		if ($valid === false) {
			trigger_error("Invalid value: expected string containing only letters.\n", E_USER_ERROR);
		}
		return $valid;	
	}

	private function integer($int) {
		$clean = filter_var($int, FILTER_SANITIZE_NUMBER_INT);
		$valid = filter_var($clean, FILTER_VALIDATE_INT);

		if ($valid === false) {
			trigger_error("Invalid value: expected integer.\n", E_USER_ERROR);
		}
		return $valid; 
	}
}