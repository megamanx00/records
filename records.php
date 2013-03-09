<?php
//Get database driver class
if(defined(DB_DRIVER))
{
	$db_driver = DB_DRIVER;
}
else
{
	$db_driver = "mysqli";
}
//Include Database driver query class. 
include( "DBD/dbd_" . $db_driver . ".php");

//--Begin Records Class
class records implements Iterator, Countable, ArrayAccess{
    
	//Static variables common to all instances
	private static $_username;
	private static $_password;
	private static $_db_driver;
	private static $_database;
	private static $_init;
	private static $_db_host;
	
	//Variables for this instance
	private $_container = array();
	private $_table_name;
	private $_query; //for this instance of query class
	
	//--Constructor
    public function __construct($db_table_name)
	{
		$this->_table_name = $db_table_name;
		
		//Check if this class was previously initialized
		if(!isset(self::$_init))
		{
			if(defined("DB_USERNAME")) // assume constants set in a config file
			{
				self::$_username  = DB_USERNAME;
				self::$_password  = DB_PASSWORD;
				self::$_database  = DB_DATABASE;
				
				if (defined(DB_HOST))
				{
					self::$_db_host = DB_HOST;
				}
				else
				{
					self::$_db_host = "localhost";
				}
			}
			else //assume no config file
			{
				include_once("usedat.php"); // must have username, password, and database.
				self::$_username = $username;
				self::$_password = $password;
				self::$_database = $database;
				if (isset($db_host))
				{
					self::$_db_host = $db_host;
				}
				else
				{
					self::$_db_host = "localhost";
				}
			}
			self::$_init = true;
		}// end of initilized check
		
		//Create new instance of query
		//Query Class is defined in database driver
		
		$this->_query = new query($this->_table_name, self::$_database, self::$_db_host, self::$_username, self::$_password);
		
	}
	//--End Constructor

	//--Begin Array Access functions
	public function offsetSet($offset, $value) {
        $this->_container[$offset] = $value;
    }
    public function offsetExists($offset) {
        return isset($this->_container[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->_container[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->_container[$offset]) ? $this->_container[$offset] : null;
    }
	//--End Array Access functions
		
	//--Begin Iterator access functions
	 public function rewind() {
        reset($this->_container);
    }

    public function current() {
        return current($this->_container);
    }

    public function key() {
        return key($this->_container);
    }

    public function next() {
        return next($this->_container);
    }

	public function __isset($key) {
		return array_key_exists(key($this->_container), $this->_container);
	}
	
    public function valid() {
		$temp = $this->current() !== false;
		if($temp == false){ 
			return array_key_exists(key($this->_container), $this->_container);
		}
        return $temp;
    }   
	//--End Iterator access functions
	
	//--Function for countable
    public function count() {
    	return count($this->_container);
    }
	//--End for countable
	
	//--Begin Overload Functions
	
	function __set($key, $value) //overload __set else there be duplicate keys when setting values using the class as an array and then an object
   	{
       $this->set($key, $value);
       return true;
   	}
	
	function __get($key)//Overload __get to ensure proper object type retrieval. 
	{
		if(array_key_exists($key, $this->_container)){
			return $this->get($key);}
		else{return false;}
	}
	
	//Dump current record array to JSON encoded string
	function __toString() 
	{
		return json_encode($this->_container);
	}
	
	
	//Note: currently this is an expensive operation because it iterates the container
	//to create a copy of the values, except the one killed, and then copies it back. 
	function __unset($kill_key)
	{
		$temp_array = array();
		//copy values to temp array
		foreach ($this->_container as $key => $value)
		{
			if($key != $kill_key)
			{
				$temp_array[$key] = $value;
			}
		}
		
		//black out _container
		$this->_container = array();
		
		//put back in the values we want
		foreach ($temp_array as $key => $value)
		{
			$this->set($key, $value);
		}
	}
	
	function __call($name,$args)
	{
		$temp_arr = explode("Exists",$name);
		$temp = $temp_arr[0];
		
		if(count($temp_arr)==1){
			throw new Exception('Invalid function call');}
		else if(array_key_exists($temp, $this->_container)){
			return true;}
		else{return false;}
	}
	
	//--End Overload Functions
	
	//--Public Functions
	public function set($key, $value)
	{
		if(is_string($key))
		{
			$valid_key = "^[a-zA-Z_]{1,1}([a-zA-Z0-9_]{1,})?$";
			if(preg_match("/$valid_key/", $key))
			{
				$this->_container[$key] = $value;
			}
			else
			{
				throw new InvalidArgumentException('Invalid key');
			}
		}
		else
		{
			throw new InvalidArgumentException('Invalid key');
		}
	}
	
	//Array accepting version of set.
	public function setValues($arr_values)
	{
		if(is_array($arr_values))
		{
			foreach ($arr_values as $key => $value)
			{
				$this->set($key, $value);
			}
		}
		else
		{
			throw new InvalidArgumentException('setValues only accepts arrays');
		}
	}
	
	public function get($key, $value = NULL)
	{
		
		if(($value == NULL) && ($key != 'not set'))
		{
			return $this[$key];
		}
		else if($value != NULL || func_num_args() == 2)
		{
			return $value;
		}
		else
		{
			throw new Exception('Get must have a key or a value');
		}
	}

	//Load new values that are JSON encoded.
	public function loadJson($j_str)
	{
		try{
			$temp = json_decode($j_str, true);
			$this->setValues($temp);
		}
		catch(Exception $e)
		{
		//nothing
		}
	}
	
	//Retrieve associative array from database.
	public function get_records ($field_name = "", $search_value = "", $comparison_type = "=", $first_record = 0, $max_records=-1, $order_by="")
	{
		$temp = $this->_query->get_records($field_name, $search_value, $comparison_type, $first_record, $max_records, $order_by);
		if($temp == NULL or $temp == false)
		{
			//No matching records found
			return false;
		}
		//Else if records found
		$this->setValues($temp);
		return true;
	}
	
	//Delete a database entry or multiple entries.
	public function delete_records ($field_name = "", $search_value = "", $comparison_type = "=", $first_record = 0, $max_records=-1, $order_by="")
	{
		return $this->_query->delete_records($field_name, $search_value, $comparison_type, $first_record, $max_records, $order_by);
	}
	
	//Move to next column in results
	public function next_record()
	{
		$temp = $this->_query->next_record();
		if($temp)
		{
			$this->setValues($temp);
			return true; //there is a next record
		}
		
		return false; //no more records
	}
	
	//Replace current column with new values
	function replace ()
	{
		$result = $this->_query->replace($this->_container);
		return $result; //last insert id on success, false on failure
	}
	
	//Replace insert a new column into the table with _container values
	function insert ()
	{
		$result = $this->_query->insert($this->_container);
		return $result; //last insert id on success, false on failure
	}
	
	//Count records matching given conditions
	function count_records ($field_name = "", $search_value = "", $comparison_type = "=", $first_record = 0, $max_records=-1, $group_by="")
	{
		return $this->_query->count_records($field_name, $search_value, $comparison_type, $first_record, $max_records, $order_by);
	}

	public function last_query()
	{
		//Return last query. Use to debug
		return $this->_query->last_query();	
	}

	
	//--End Public functions
	
}
//--End Records Class
?>