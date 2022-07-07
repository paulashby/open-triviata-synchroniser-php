<?php

Class Triviata {
	
	// DB stuff
	private $conn;
	private $categories;

	public function __construct($db) {
		$this->conn = $db;

		// Initialise $categories as per Python trivia_categories - this requires a call to the Open Trivia Database, so we need a class for this in config directory
		// Also, the question_breakdown() function in the Python is called every time we get next category, so that should also be intialised here...
				// actually, I think we might rely on it returning more detailed data when a category is provided, so calling every time might in fact be returning different results.
				// Check it, but it looks like maybe not needed here
				// Unless we make multiple calls without providing. I actually can't find ANY calls without the ID! Maybe that boolean default value for the parameter is unecessary?
	}

	/**
	 * Get category data from Open Trivia Database
	 *
	 * @return Boolean
	 */ 
	public function nextCategory() {
		// Populate trivia_categories with category number/name pairs
		// trivia_categories[category['id']] = category['name']

	}


}