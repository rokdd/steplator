<?php
/**
 VERSION 0.2
 Robert Kühn
 
 More Information about this script at rokdd.de or at github/rokondo
 */
define('TEMPLATE_CLOSURE_A', '{');
define('TEMPLATE_CLOSURE_B', '}');
class template
{
	static $strings = Array();
	static $temp = Array();
	static function wrap($str) {
		return TEMPLATE_CLOSURE_A . TEMPLATE_CLOSURE_A . $str . TEMPLATE_CLOSURE_B . TEMPLATE_CLOSURE_B;
	}
	static function execute($str, $strings = Array() , $options = Array()) {
		self::$temp = $strings;
		$str = preg_replace_callback('/\\' . TEMPLATE_CLOSURE_A . '\\' . TEMPLATE_CLOSURE_A . '(\w+)' . TEMPLATE_CLOSURE_B . TEMPLATE_CLOSURE_B . '/', array(
			'template',
			'replace_vars'
		) , $str);
		
		$str = preg_replace('/\[{2}([^\{]*?)\{\{(\w+[\}]?)\}\}([^\}]*?)\]{2}/Ui', '', $str);
		$str = preg_replace('/\\' . TEMPLATE_CLOSURE_A . '\\' . TEMPLATE_CLOSURE_A . '(\w*)' . TEMPLATE_CLOSURE_B . TEMPLATE_CLOSURE_B . '/U', '', $str);
		$str = preg_replace('~\[{2}(.+)\]{2}~mU', '$1', $str);
		return $str;
	}
	static function replace_vars($match) {
		
		list($_, $name) = $match;
		if (stristr($name, '::')) {
			$name = explode('::', $name);
			if (method_exists($name[0], $name[1])) return call_user_func_array(array(
				$name[0],
				$name[1]
			) , Array(
				match
			));
			else return '{{}}';
		}
		if (isset(template::$strings[$name]) && strlen(template::$strings[$name]) > 0) return template::$strings[$name];
		elseif (isset(template::$temp[$name]) && strlen(template::$temp[$name]) > 0) {
			
			return template::$temp[$name];
		} 
		else {
			
			return '{{}}';
		}
	}
}

class stepped_template
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
	//will recognize which stopchars are still open
	static $nesting=Array();
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
			else return $stop_char . $stop_char . stepped_template::$stop_chars[$stop_char]['end'] . stepped_template::$stop_chars[$stop_char]['end'];
		}
		//or search in the string data (first priority)
		if (isset(stepped_template::$strings[$name]) && strlen(stepped_template::$strings[$name]) > 0) {
			return stepped_template::$strings[$name];
		} 
		//or search in the temp array (2nd priority)
		elseif (isset(stepped_template::$temp[$name]) && strlen(stepped_template::$temp[$name]) > 0) {
			
			return stepped_template::$temp[$name];
		} 
		elseif(in_array('[',stepped_template::$nesting)) {
			
			//return something like {{}} or [[]] for replacing with condition
			return $stop_char . $stop_char . stepped_template::$stop_chars[$stop_char]['end'] . stepped_template::$stop_chars[$stop_char]['end'];
		}
		else
			return '';
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
		//reset nestingt
		self::$nesting=Array();
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
		//reset nestingt
		self::$nesting=Array();
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
			
			//console::debug($stop_char.' nesting: '.pp(self::$nesting));
			//check whether the char isset as stopchar and is also included next and should be same
			if (isset(self::$stop_chars[$arr_content[$int_char]]) && $arr_content[$int_char]==$arr_content[$int_char+1] && isset(self::$stop_chars[$arr_content[$int_char + 1]])) {
				//mark the beginning in the nestint array
				self::$nesting[$int_char]=$arr_content[$int_char];
				//initiate a new handler for searching further and waiting for its content
				list($arr_content[$int_char], $int_char) = self::handler($arr_content, $int_char + 1, $arr_content[$int_char]);
			}
			
			//check for closure for OUR char and only when stop_char is isset
			elseif ($stop_char != null && self::$stop_chars[$stop_char]['end'] == $arr_content[$int_char] && self::$stop_chars[$stop_char]['end'] == $arr_content[$int_char + 1]) {
				$temp=Array(
					
					forward_static_call_array(Array(
						'stepped_template',
						self::$stop_chars[$stop_char]['handler']
					) , Array(
						$str,
						$stop_char
					)) ,
					$int_char + 1
				);
				//mark as end in the array;
				$keys = array_keys(self::$nesting);
				$last = end($keys);
				unset(self::$nesting[$last]);
				return $temp;
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