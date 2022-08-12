<?php

Class Category {
	
	private $connector;
	private $id;
	private $database_question_count;
	private $status;
	private $questions;

	public function __construct($id, $api_question_breakdown, $connector) {

		$this->connector = $connector;
		$this->id = $id;
		$this->initDatabase_question_count();
		
		// Is this category complete? Compare number of processed questions to the number available
		$done_in_database = $this->database_question_count['category'];
		$questions_done = $done_in_database['question_count'];
		$source_questions = $api_question_breakdown['category']['total_question_count'];

		$this->status['completed'] = $questions_done == $source_questions;
		$this->status['next'] = $api_question_breakdown['category'];
	}
	
    /**
     * Get id
     *
     * @return int $id
     */
    public function id() {
    	return $this->id;
    }
    
    /**
     * Get category status
     *
     * @return Status array
     */
    public function status() {
    	return $this->status;
    }
    
    /**
     * Synchronise category with Api (make sure we've got all the questions)
     *
     * @return int $id
     */
    public function synchronise() {
    	
    	$this->questions = new Questions($this->id, $this, $this->connector);
    	$this->process($this->questions->unsynced());
    	return $this->id;
    }
    
    /**
     * Synchronise category with Api (make sure we've got all the questions)
     *
     * @param array $unsynced - contains total question count and levels array eg ['easy': 100, ...]
     */
    private function process($unsynced) {

    	$unsynced_levels = $unsynced['levels'];

    	if (!$unsynced_levels) {
    		// No details for individual difficulty levels - we need to sync all
    		$all = array(
    			'level' => "all",
    			'count' => $unsynced['total']
    		);    		
    		// Add all questions for given category to database
    		$this->questions->syncLevel($all);
    	} else {

    		// Sync just the provided unsynced difficulty levels
    		foreach ($unsynced_levels as $level => $count) {
    			$levels = array(
    				'level' => $level,
    				'count' => $count
    			);    			
    			$this->questions->syncLevel($levels);
    		}
    	}
    }

    /**
     * Get the number of questions already added to the local database for this category
     *
     * @return associative array of question counts for this category
     */
    private function initDatabase_question_count() {

    	$db_query = array(
    		array(
    			'query' => "SELECT COUNT(id) AS question_count FROM questions WHERE category = ?", 
    			'values' => array(
    				$this->id
    			)
    		)
    	);

    	$category_done = $this->connector->database->query($db_query)[0];
    	$this->database_question_count['category'] = $category_done;
    }
}