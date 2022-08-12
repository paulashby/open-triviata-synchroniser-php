<?php

include_once realpath(__DIR__ . "/../") . "/config/Connector.php";
include_once realpath(__DIR__ . "/../") . "/utilities/ErrorLogger.php";
include_once realpath(__DIR__ . "/../") . "/utilities/DataCleaner.php";
include_once realpath(__DIR__ . "/../") . "/interactions/ResponseProcessor.php";
include_once realpath(__DIR__ . "/../") . "/models/Categories.php";
include_once realpath(__DIR__ . "/../") . "/models/Questions.php";

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

// Log any error then die
$error_logger = new ErrorLogger();

// Access to Database and API
$connector = new Connector();

// Determine which categories are out of sync with Open Trivia API
$categories = new Categories($connector);

// Start with the next incomplete category
$unsynced_category = $categories->nextUnsynced();

while ($unsynced_category) {
	
	$syncing_category = $unsynced_category->synchronise();
	$unsynced_category = $categories->nextUnsynced($syncing_category);
}

error_log("SUCCESS: all questions have been processed.");
die();


