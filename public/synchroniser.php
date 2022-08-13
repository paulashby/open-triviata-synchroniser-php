<?php

include_once realpath(__DIR__ . "/../") . "/models/Categories.php";

$new_token = true;

if (isset($argc) && $argc > 1) {

	if($argv[1] === "-h") {
		// Show usage info
		die("-t Use existing token if available - API will not return questions already provided within the last 6 hours");

	} else if ($argv[1] === "-t") {
		// See usage info above
		$new_token = false;
	}
}

$categories = new Categories($new_token);

// Synchronise categories with Open Trivia API
$categories->synchronise();
