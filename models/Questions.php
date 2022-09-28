<?php

Class Questions {

	public const MAX_QUESTIONS = 50;
	private $connections;
	private $category_id;
	private $category_status;
	private $unsynced;


	public function __construct($id, $category, $connections) {

		$this->connections = $connections;
		$this->category_status = $category->status();
		$this->category_id = $id;
		$this->initUnsyncedList();
	}

	public function unsynced() {

		return $this->unsynced;
	}

    /**
     * Add questions to local database - restrict to difficulty level if provided
     *
     * @param array $unsynced - contains total question count and levels array eg ['easy': 100, ...]
     */
    public function syncLevel($unsynced) {

    	$max_questions = self::MAX_QUESTIONS;

    	$req_details = array(
    		'callback' 		=> array($this, 'processQuestions'),
    		'endpoint' 		=> 'api.php',
    		'parameters' 	=> array(
    			'category' 	=> $this->category_id,
    			'amount' 	=> $max_questions
    		)
    	);

    	$difficulty_level = $unsynced['level'];

    	if ($difficulty_level !== "all") {
    		$req_details['parameters']['difficulty'] = $difficulty_level;	
    	}

    	$total = $unsynced['count'];

    	for ($i=$max_questions; $i <= $total; $i+=$max_questions) { 
    		$this->connections['api']->request($req_details);
    	}

    	$remaining = $total % $max_questions;

    	if ($remaining) {
   		// Add any stragglers
    		$req_details['parameters']['amount'] = $remaining;
    		$this->connections['api']->request($req_details);
    	}

    }

    /**
     * Add the given questions to the local database
     * 
     * @param array $questions: An array of associative question arrays, each containing the details of a single question
     * @param array $req_details: An associative array of url segment strings used for the api request
     */
    public function processQuestions($questions, $req_details) {

    	$category_id = DataCleaner::clean($req_details['parameters']['category'], 'integer');

    	if (count($questions)) {    	

    		$category_name = DataCleaner::clean($questions[0]['category'], 'categoryName');

        	// Add category if not already created
    		$db_query = array(
    			array(
    				'query' => "INSERT INTO categories (id, category) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=id", 
    				'values' => array(
    					$category_id, 
    					$category_name
    				)
    			)
    		);

    		$query_options = array(
    			'insert',
    			'questions'
    		);

    		$this->connections['database']->query($db_query, $query_options);

    		$db_queries_answers = array();
    		$previous_question_id = NULL;

    		foreach ($questions as $question_details) {
	    		// Prepare requests for all questions
	    		$clean_type = DataCleaner::clean($question_details['type'], 'parameter', 'type');
	    		$clean_difficulty = DataCleaner::clean($question_details['difficulty'], 'parameter', 'difficulty');
	    		$decoded_question_details = $this->cleanAndDecode($question_details['question']);

    			$db_queries_questions = array(
    				array(
    					'query' => "INSERT INTO questions (category, type, difficulty, question_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id;", 
    					'values' => array(
    						$category_id,
    						$clean_type,
    						$clean_difficulty,
    						$decoded_question_details
    					)
    				)
    			);

		    	// Add question to database and set last inserted id
    			$last_insert_id = (int)$this->connections['database']->query($db_queries_questions, $query_options);

    			if ($last_insert_id > 0 && (is_null($previous_question_id) || $previous_question_id != $last_insert_id)) {

    				if (is_bool($question_details)) {
    					$db_queries_answers[] = array(
    						'query' => "INSERT INTO answers (question_id, correct) VALUES (?, ?)", 
    						'values' => array(
    							$last_insert_id + 0, 
    							$question_details['correct_answer'] == "True"
    						)
    					);
    				} else {
    					foreach ($question_details['incorrect_answers'] as $incorrect_answer) {
    						$decoded_incorrect_answer = $this->cleanAndDecode($incorrect_answer);

    						$db_queries_answers[] = array(
    							'query' => "INSERT INTO answers (question_id, answer, correct) VALUES (?, ?, 0)", 
    							'values' => array(
    								$last_insert_id + 0, 
    								$decoded_incorrect_answer
    							)
    						);
    					}
    					// Add correct answer
    					$decoded_correct_answer = $this->cleanAndDecode($question_details['correct_answer']);

    					$db_queries_answers[] = array(    						
    						'query' => "INSERT INTO answers (question_id, answer, correct) VALUES (?, ?, 1)", 
    						'values' => array(
    							$last_insert_id + 0, 
    							$decoded_correct_answer
    						)
    					);
    				}
    				$previous_question_id = $last_insert_id;
    			}
    		}

    		if (count($db_queries_answers)) {

    			$query_options = array(
    				'insert'
    			);

    			$this->connections['database']->query($db_queries_answers, $query_options);	
    		}
    	} else {
    		// No questions were provided - print a warning so it can be looked into if necessary
    		error_log("WARNING: No questions provided to process_questions() for category {$category_id}");
    	}
    }

    // Clean given string and decode all HTML entities
    private function cleanAndDecode($str) {
    	$clean_str = DataCleaner::clean($str, 'string');
    	return html_entity_decode($clean_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // Initalise array with question difficulty levels to sync
    private function initUnsyncedList() {

    	// Get number of questions already processed for each difficulty level
    	$synced = $this->levelCounts($this->category_id);

    	$this->unsynced = array(
    		'total'		=> $this->category_status['next']['total_question_count'],
    		'levels'	=> array()
    	);

    	foreach ($synced as $level => $done) {
    		// Check for unsynced difficulty levels
    		$available_questions = $this->category_status['next']["total_{$level}_question_count"];

    		if ($done < $available_questions) {
    			// There are more questions to process for this level - place in $unsynced array
    			$this->unsynced['levels'][$level] = $available_questions;
    		}
    	}
    }

	/**
	 * Get the number of questions added to the local database for each difficulty level
	 *
	 * @param int $category_id - the id number of the current category
	 * @return associative array of question counts by level
	 */
	private function levelCounts($category_id) {

		$counts = array(
			'easy'   => 0,
			'medium' => 0,
			'hard'   => 0
		);

		foreach ($counts as $level => $count) {
			$db_query = array(
				array(
					'query' => "SELECT COUNT(*) FROM questions WHERE category = ? AND difficulty = ?", 
					'values' => array(
						$this->category_id, 
						$level
					)
				)
			);
			$counts[$level] = $this->connections['database']->query($db_query)[0];
		}
		return $counts;
	}
}