<?php

class query{
	
	private $_table_name;
	private $_mysqli;
	private $_result;
	private $_row; // Row you're currently on
	private $_my_query;
	
	//--Constructor
    public function __construct($table_name, $database, $db_host, $username, $password)
	{
		//Connect to datagase
		$this->_mysqli = new mysqli($db_host, $username, $password, $database);	
		
		if (mysqli_connect_error())
		{		
			//connection error, throw exception
			throw new Exception('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());			
		}
		
		$this->_table_name = $table_name;
   
	}
	//--End Constructor
	
	//--Destructor
	public function __destruct()
	{
       $this->_mysqli->close();
	}
   	//--End Destructor
	
	//Retrieve associative array from database.
	public function get_records ($field_name = "", $search_value = "", $comparison_type = "=", $first_record = 0, $max_records=-1, $order_by="")
	{
		$temp_query = "SELECT * FROM " . $this->_table_name;
		if(is_array($field_name))
		{
			$temp_query.= " WHERE ";
			$size = count($field_name);
			
			if($size != count($search_value))
				{throw new Exception('Number of field names and search values not equal');}
				
			if(is_array($comparison_type))
			{
				if($size != count($search_value))
				{throw new Exception('Invalid number of comparison types. Refer to API');}
			
				for($i=0; $i < $size; $i++)
				{
					$temp_query.= $field_name[$i] . " " . $comparison_type[$i] . " '" . $this->_mysqli->real_escape_string($search_value[$i]) . "' AND ";
				}
			}
			else
			{		
				for($i=0; $i < $size; $i++)
				{
					$temp_query.= $field_name[$i] . " " . $comparison_type . " '" . $this->_mysqli->real_escape_string($search_value[$i]) . "' AND ";
				}
			}
			
			//Take off last "AND "
			$temp_query = substr($temp_query, 0 ,strlen($temp_query) - 5);
		}
		elseif($field_name != "")//is not an array
		{
			$temp_query.= " WHERE ";
			$temp_query.= $field_name . " " . $comparison_type . " '" . $this->_mysqli->real_escape_string($search_value) . "'";
		}
		 //End if array
		
		//Finish Query
		if($order_by)
		{
			$temp_query.= " ORDER BY " . $order_by;
		}
		if($max_records != -1)
		{
			$temp_query.= " LIMIT " . $first_record . ", " . $max_records;
		}

		$this->_my_query = $temp_query;		
		$this->_result = $this->_mysqli->query($this->_my_query);

		if($this->_result)
		{
			$this->_row = $this->_result->fetch_array(MYSQLI_ASSOC);
			return $this->_row;
		}
		else
		{
			return false;	
		}
	}

	//Delete Records
	public function delete_records ($field_name = "", $search_value = "", $comparison_type = "=", $first_record = 0, $max_records=-1, $order_by="")
	{
		$temp_query = "DELETE FROM " . $this->_table_name;
		if(is_array($field_name))
		{
			$temp_query.= " WHERE ";
			$size = count($field_name);
			
			if($size != count($search_value))
				{throw new Exception('Number of field names and search values not equal');}
				
			if(is_array($comparison_type))
			{
				if($size != count($search_value))
				{throw new Exception('Invalid number of comparison types. Refer to API');}
			
				for($i=0; $i < $size; $i++)
				{
					$temp_query.= $field_name[$i] . " " . $comparison_type[$i] . " '" . $this->_mysqli->real_escape_string($search_value[$i]) . "' AND ";
				}
			}
			else
			{		
				for($i=0; $i < $size; $i++)
				{
					$temp_query.= $field_name[$i] . " " . $comparison_type . " '" . $this->_mysqli->real_escape_string($search_value[$i]) . "' AND ";
				}
			}
			
			//Take off last "AND "
			$temp_query = substr($temp_query, 0 ,strlen($temp_query) - 5);
		}
		elseif($field_name != "")//is not an array
		{
			$temp_query.= " WHERE ";
			$temp_query.= $field_name . " " . $comparison_type . " '" . $this->_mysqli->real_escape_string($search_value) . "'";
		}
		 //End if array
		
		//Finish Query
		if($max_records != -1)
		{
			$temp_query.= " LIMIT " . $first_record . ", " . $max_records;
		}
		if($order_by)
		{
			$temp_query.= " ORDER BY " . $order_by;
		}

		$this->_my_query = $temp_query;		
		return $this->_mysqli->query($this->_my_query);;
		
	}


	//Retrieve next row from results.
	public function next_record()
	{
		$this->_row = $this->_result->fetch_array(MYSQLI_ASSOC);
		return $this->_row;
	}
	
	//Replace insert a new column into the table with _container values
	public function insert($db_values)
	{
		$temp_query = "INSERT INTO " . $this->_table_name;
		$keys=" (";
		$values=" (";
		
		foreach($db_values as $key=>$value)
		{
			$keys.= $key . ',';
			$values.= "'" . $this->_mysqli->real_escape_string($value) . "',";
		}
		
		//Take off last commas
		
		$keys   = substr($keys  , 0 ,strlen($keys) - 1);
		$values = substr($values, 0 ,strlen($values)-1);
		
		$temp_query.= $keys . ") VALUES" . $values . ") ";
		
		$this->_my_query = $temp_query;		
		$this->_result = $this->_mysqli->query($this->_my_query);
		
		return $this->_mysqli->insert_id; //last insert id on success, false on failure
	}
	
	public function replace($db_values)
	{
		$temp_query = "REPLACE INTO " . $this->_table_name;
		$keys=" (";
		$values=" (";
		
		foreach($db_values as $key=>$value)
		{
			$keys.= $key . ',';
			$values.= "'" . $this->_mysqli->real_escape_string($value) . "',";
		}
		
		//Take off last commas
		
		$keys   = substr($keys  , 0 ,strlen($keys) - 1);
		$values = substr($values, 0 ,strlen($values)-1);
		
		$temp_query.= $keys . ") VALUES" . $values . ") ";
		
		$this->_my_query = $temp_query;		
		$this->_mysqli->query($this->_my_query);
		
		return $this->_mysqli->insert_id; //last insert id on success, false on failure
		
	}
	
	//Count records matching given conditions
	function count_records ($field_name = "", $search_value = "", $comparison_type = "=", $first_record = 0, $max_records=-1, $group_by="")
	{
		$temp_query = "SELECT COUNT(*) FROM " . $this->_table_name;
		if(is_array($field_name))
		{
			$temp_query.= " WHERE ";
			$size = count($field_name);
			
			if($size != count($search_value))
				{throw new Exception('Number of field names and search values not equal');}
				
			if(is_array($comparison_type))
			{
				if($size != count($search_value))
				{throw new Exception('Invalid number of comparison types. Refer to API');}
			
				for($i=0; $i < $size; $i++)
				{
					$temp_query.= $field_name[$i] . " " . $comparison_type[$i] . " '" . $this->_mysqli->real_escape_string($search_value[$i]) . "' AND ";
				}
			}
			else
			{		
				for($i=0; $i < $size; $i++)
				{
					$temp_query.= $field_name[$i] . " " . $comparison_type . " '" . $this->_mysqli->real_escape_string($search_value[$i]) . "' AND ";
				}
			}
			
			//Take off last "AND "
			$temp_query = substr($temp_query, 0 ,strlen($temp_query) - 5);
		}
		elseif($field_name != "")//is not an array
		{
			$temp_query.= " WHERE ";
			$temp_query.= $field_name . " " . $comparison_type . " '" . $this->_mysqli->real_escape_string($search_value) . "'";
		}
		 //End if array
		
		//Finish Query
		if($max_records != -1)
		{
			$temp_query.= " LIMIT " . $first_record . ", " . $max_records;
		}
		if($group_by)
		{
			$temp_query.= "GROUP BY " . $group_by;
		}

		$this->_my_query = $temp_query;		
		$this->_result = $this->_mysqli->query($this->_my_query);

		if($this->_result)
		{
			$this->_row = $this->_result->fetch_row();
			return $this->_row[0];
		}
		else
		{
			return 0;	
		}
		
	}
	
	public function last_query()
	{
		//Return last query. Use to debug
		return $this->_my_query;	
	}

}
?>