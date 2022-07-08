<?php

Class INI {

	private $file;
	private $ini_data;

	public function __construct($file) {

		$this->file = $file;
		$this->ini_data = parse_ini_file($file, true);
	}

	/**
     * Set a property to the given value
     * 
     * @param string/array $value: array if multiple items submitted, else string
     * @param string $property: name of property to set
     * @param string $section: name of section the property belongs in (optional)
     */
	public function set($value, $property = false, $section = false) {

		if (is_array($value)) {
			// Add multiple properties
			foreach ($value as $config_entry) {
				$this->setProperty(...$value);
			}
			return;
		} 
		// Add single properrty
		$this->setProperty($value, $property, $section);
		return;
	}

	/**
     * Get property
     * 
     * @param string $property: name of property to get
     * @param string $section: name of section the property belongs in (optional)
     */
	public function get($property, $section = false) {

		// Reference to whichever array the property is in
		$property_parent = $this->ini_data;

		if ($section) {
			// Make sure section exists
			if (array_key_exists($section, $this->ini_data)) {
				// Update reference
				$property_parent = $this->ini_data[$section];
			} else {
				error_log("$section section could not be found in ini file");
				return false;
			}
		}
		if (array_key_exists($property, $property_parent)) {
			return $property_parent[$property];
		}
		error_log("$property property could not be found in ini file");
		return false;
	}

	/**
     * Write ini file
     * This function is lifted directly from Lawrence Cherone's answer of Jan 14, 2018, here https://stackoverflow.com/questions/5695145/how-to-read-and-write-to-an-ini-file-with-php
     * 
     * @return bool
     */
	public function save() {

        // process $ini_data array
        $data = array();
        foreach ($this->ini_data as $key => $val) {
            if (is_array($val)) {
                $data[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $_skey => $_sval) {
                            if (is_numeric($_skey)) {
                                $data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                            } else {
                                $data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                            }
                        }
                    } else {
                        $data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
                    }
                }
            } else {
                $data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
            }
            // empty line
            $data[] = null;
        }

        // open file pointer, init flock options
        $fp = fopen($this->file, 'w');
        $retries = 0;
        $max_retries = 100;

        if (!$fp) {
            return false;
        }

        // loop until get lock, or reach max retries
        do {
            if ($retries > 0) {
                usleep(rand(1, 5000));
            }
            $retries += 1;
        } while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

        // couldn't get the lock
        if ($retries == $max_retries) {
            return false;
        }

        // got lock, write data
        fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);

        // release lock
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

	/**
     * Update or add a property
     * 
     * @param string $value: value of the property
     * @param string $property: name of the property
     * @param @param string $section: name of section the property belongs in (optional)
     */
	private function setProperty($value, $property, $section = false) {

		if ($section){
			// Make sure section exists
			if (!array_key_exists($section, $this->ini_data)) {
				$this->ini_data[$section] = $array();
			}
			$this->ini_data[$section][$property] = $value;
			$this->save();
			return;
		}
		$this->ini_data[$property] = $value;
		$this->save();
		return;
	}
}