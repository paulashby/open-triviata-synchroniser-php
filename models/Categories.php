<?php

include_once realpath(__DIR__) . "/Category.php";

Class Categories {	
	
	private $connector;
	private $api_categories;
	private $database_categories;
	private $database_question_count;
	private $minimum_category_number;

	public function __construct($connector) {

		$this->connector = $connector;
		$this->initApiCategories();
		$this->initDatabaseCategories();
	}

	/**
	 * Get data for the given category
	 *
	 * @param int $category_id - the category in the local database to sync with remote api 
	 * @return false or associative array with category id keys and arrays containing question counts for each difficulty level
	 */
	public function nextUnsynced($category_id = null) {

		if(is_null($category_id)) {
			$category_id = $this->minimum_category_number;
		} else if (!$category_id) {
			return false;
		}
		if (array_key_exists($category_id, $this->api_categories)) {
			// Category_id exists with this id
			$category = new Category($category_id, $this->questionBreakdown($category_id), $this->connector);

			$category_status = $category->status();

			if ($category_status['completed']) {
				error_log("Category {$category_id}: no new questions available");
				return $this->nextUnsynced($this->getCategory(++$category_id));
			}

			error_log("Category {$category_id}: new questions available. Processing...");
			
			return $category;
		}
	}

    /**
	 * Add a new category to the local database if given category does not exist
	 *
	 * @param int $category_id - the id number for the new category
	 * @return false or the id number for the new category
	 */
    private function getCategory($category_id) {

    	if (!array_key_exists($category_id, $this->api_categories)) {
    		// Category doesn't exist in api - we're done
    		return false;
    	}

    	$cat_name = $this->api_categories[$category_id];
    	$category_exists = array_key_exists($category_id, $this->database_categories);

    	if (!$category_exists) {

    		$db_query = array(
    			array(
    				'query' =>"INSERT INTO categories (id, category) VALUES (?, ?)", 
    				'values' => array(
    					$category_id, 
    					$this->api_categories[$category_id]
    				)
    			)
    		);
    		$query_options = array(
    			'insert'
    		);
    		$this->connector->database->query($db_query, $query_options);
    	}

    	return $category_id;
    }

	/**
	 * Initialise $api_categories with category data from Open Trivia Database (category number/name pairs)
	 */
	private function initApiCategories() {

		$req_details = array(
			'callback'	=> array($this, 'extractTriviaCategories'),
			'endpoint'	=> 'api_category.php'
		);

		$latest_categories = $this->connector->api->request($req_details, false);

		foreach ($latest_categories as $category) {
	    	// Populate api_categories with category number/name pairs
			$this->api_categories[$category['id']] = $category['name'];
		}
		$minimum_category_number = array_key_first($this->api_categories);

		if (!is_numeric($minimum_category_number)) {
			trigger_error("Error retrieving category number.", E_USER_ERROR);
		}
		$this->minimum_category_number = $minimum_category_number;
	}

	/**
	 * Initialise $database_categories - set keys to category ids in local database (category id numbers), values to names in the already-populated api_categories array
	 */
	private function initDatabaseCategories() {

		$categories_in_db_query = array("SELECT id FROM categories ORDER BY id ASC");
		$database_categories = $this->connector->database->query($categories_in_db_query);
		$this->database_categories = array();

		foreach ($database_categories as $category_data) {
			$id = (int)$category_data['id'];
			if ($id > 0) {
				$this->database_categories[$id] = $this->api_categories[$id];
			}
		}
	}

	/**
	 * Callback to extract trivia categories from data returned by API
	 *
	 * @param api_data: Data returned by API
	 * @return associative array of category numbers and names
	 */
	public function extractTriviaCategories($api_data) {

		return $api_data['trivia_categories'];	
	}

	// TODO: This literally just returns the data it received, but need it to keep ResponseProcessor simple - is there a better way?
	public function returnApiData($api_data) {
		return $api_data;
	}

	/**
	 * Filter api_data to include only verified question counts
	 *
	 * @param api_data: Data returned by API
	 * @return associative array of verified question counts
	 */
	public function extractCounts($api_data) {

		$extracted_counts = array(
			'overall' => $api_data['overall']['total_num_of_verified_questions']
		);

		foreach ($api_data['categories'] as $cat_key => $cat_data) {
			$extracted_counts[(int) $cat_key] = $cat_data['total_num_of_verified_questions'];
		}

		return $extracted_counts;
	}

	/**
	 * Get category question counts from API
	 *
	 * @param category_id: id number of question category
	 * @return associative array of question counts by category id and additional entry for overall question count
	 * 		   Plus, if category_id provided, associative array with category id and question counts for each difficulty level
	 */
	public function questionBreakdown($category_id = false) {

		$req_details = array(
			'callback'	=> array($this, 'extractCounts'),
			'endpoint'	=> 'api_count_global.php'
		);

		$questions = array(
			'global' => $this->connector->api->request($req_details, false)
		);

		if(!$category_id) {
			return $questions;
		}

		$req_details = array(			
			'callback' => array($this, 'returnApiData'),
			'endpoint' => 'api_count.php',
			'parameters' => array(
				'category' => $category_id
			)
		);

		// Return a single associative array with category number and question counts for each difficulty level
		$breakdown = $this->connector->api->request($req_details, false);
		$breakdown['category_question_count']['id'] = $breakdown['category_id'];
		$questions['category'] = $breakdown['category_question_count'];

		return $questions;
	}
}