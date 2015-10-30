<?php
/*
VERSION 0.1
October 2015
More information about this script at http://rokdd.de

*/
class steplator
{
	//keep strings that are usual in your envoirement like projecttitle,paths,..
	static $strings = Array();
	//temp keeps an array of data which is send when the replacement starts
	//IDEA: this array can be modified later if you found a pattern and you used once you can remove it from the array..
	static $temp = Array();
	//the chars with its functions.
	//key: char when found twice it should stop
	//end: the end char which will end this replacement 
	//handler: function name referring to this class as callback
	static $stop_chars = Array(
		'{' => Array(
			'end' => '}',
			'handler' => 'cb_replace_vars'
		) ,
		'[' => Array(
			'end' => ']',
			'handler' => 'cb_replace_condition'
		)
	);
	//callback example for replacing variables by its content
	static function cb_replace_vars($name, $stop_char) {
		//pattern like class::function is use to convert a string to callback
		if (stristr($name, '::')) {
			$name = explode('::', $name);
			if (method_exists($name[0], $name[1])) return call_user_func_array(array(
				$name[0],
				$name[1]
			) , Array(
				match
			));
				//if callback is not valid return without content
			else return $stop_char . $stop_char . steplator::$stop_chars[$stop_char]['end'] . steplator::$stop_chars[$stop_char]['end'];
		}
		//or search in the string data (first priority)
		if (isset(steplator::$strings[$name]) && strlen(steplator::$strings[$name]) > 0) {
			return steplator::$strings[$name];
		} 
		//or search in the temp array (2nd priority)
		elseif (isset(steplator::$temp[$name]) && strlen(steplator::$temp[$name]) > 0) {
			
			return steplator::$temp[$name];
		} 
		else {
			
			//return something like {{}} or [[]] for replacing with condition
			return $stop_char . $stop_char . steplator::$stop_chars[$stop_char]['end'] . steplator::$stop_chars[$stop_char]['end'];
		}
	}
	//callback example for checking wether all variables are replaced else return nothing
	static function cb_replace_condition($name, $stop_char) {
		if (stristr($name, '{{}}')) {
			return '';
		} 
		else return $name;
	}
	//function to return the replaced string 
	static function export($str_content, $arr_data = Array() , $options = Array()) {
		//load the data as array into the temp for replacing later
		self::$temp = $arr_data;
		//split the htmlstring for being unicode conform
		$arr_content = preg_split("/(.|\\\\n)/u", $str_content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);	
		//now start the handler. -1 because of increment to start by zero and null for telling that is the root layer. the verbose option for telling the handler immediately outputting the root layer char by char
		$x = self::handler($arr_content, -1, null, Array(
			'no_verbose' => true
		));
		
		return $x;
	}
	//function to echo the replaced string immediatelly when the root layer is finished (the other layers are in process)
	static function output($str_content, $arr_data = Array() , $options = Array()) {
		//load the data as array into the temp for replacing later
		self::$temp = $arr_data;
		//split the htmlstring for being unicode conform
		$arr_content = preg_split("/(.|\\\\n)/u", $str_content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		//now start the handler. -1 because of increment to start by zero and null for telling that is the root layer
		self::handler($arr_content, -1, null);
	}
	//handler which calls itself
	static function handler($arr_content, $pos, $stop_char = null, $options = Array()) {
		$str = "";
		for ($int_char = $pos + 1; $int_char < count($arr_content); $int_char++) {
			if (isset(self::$stop_chars[$arr_content[$int_char]]) && isset(self::$stop_chars[$arr_content[$int_char + 1]])) {
				
				//initiate a new handler for searching further and waiting for its content
				list($arr_content[$int_char], $int_char) = self::handler($arr_content, $int_char + 1, $arr_content[$int_char]);
			}
			
			//check for closure for OUR char and only when stop_char is isset
			elseif ($stop_char != null && self::$stop_chars[$stop_char]['end'] == $arr_content[$int_char] && self::$stop_chars[$stop_char]['end'] == $arr_content[$int_char + 1]) {
				return Array(
					
					forward_static_call_array(Array(
						self,
						self::$stop_chars[$stop_char]['handler']
					) , Array(
						$str,
						$stop_char
					)) ,
					$int_char + 1
				);
			}
			
			//just print when rootlayer AND when not isset to output
			if ($stop_char == null && !isset($options['no_verbose'])) {
				print ($arr_content[$int_char]);
			} 
			else {
			 //COLLECT the string
				$str.= $arr_content[$int_char];
			}
		}
		//at the end always return the string
		return $str;
	}
}
?>