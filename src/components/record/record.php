<?php
/**
 * General purpose Database access object
 *
 * @package minPHP
 * @subpackage minPHP.components.record
 */
class Record extends Model {
	/**
	 * @var int The number of open parentheses in where statements yet to be applied
	 */
	private $open = 0;
	/**
	 * @var string The type of query (select, update, delete, insert)
	 */
	private $type = null;
	/**
	 * @var string The SQL for a join
	 */
	private $join_sql = null;	
	/**
	 * @var array The tables involved in the query
	 */
	private $tables = array();
	/**
	 * @var array An array of keys to be used when creating or altering a table
	 */
	private $keys = array();
	/**
	 * @var array The columns involved in the query
	 */
	private $columns = array();
	/**
	 * @var array Key/value pairs of the fields involved in the query.
	 */
	private $fields = array();
	/**
	 * @var array Key/value pairs for the where clause
	 */
	private $where = array();
	/**
	 * @var array Key/value pairs for the on clause
	 */
	private $on = array();
	/**
	 * @var array Key/value pairs for the on duplicate key clause
	 */
	private $duplicate = array();
	/**
	 * @var array Key/value pairs for the order clause
	 */
	private $order = array();
	/**
	 * @var array Key/value pairs for the group clause
	 */
	private $group = array();
	/**
	 * @var array Key/value pairs for the having clause
	 */
	private $having = array();
	/**
	 * @var array An array, 'start', 'records' to hold limit values
	 */
	private $limit = array();
	/**
	 * @var string The character to use to quote identifiers
	 */
	protected $ident_quote_chr = "`";
	/**
	 * @var array All values, in the order added appended to the PDO::query() method
	 */
	public $values = array();
	
	
	/**
	 * Returns a stdClass object used to identify keyword values (i.e. DEFAULT)
	 * that can be set in Record and that should never be bound or escaped
	 *
	 * @param string $keyword The name of the keyword to set
	 * @return stdClass An object used to identify the keyword value
	 */
	public static function keywordValue($keyword="DEFAULT") {
		$type = new stdClass();
		$type->keyword = $keyword;
		return $type;
	}
	
	/**
	 * Sets a field with various options
	 *
	 * @param string $name The name of the field
	 * @param array $attributes An array of attributes that may contain the following:
	 * 	- type The type of field
	 * 	- size The size of the field. If type is "varchar" size could be "64" to produce "varchar(64)" (optional)
	 * 	- unsigned Set to true to set this field as unsigned (optional)
	 * 	- auto_increment Set to true to set this field to auto_increment (optional)
	 * 	- default Used to define a default value for the field (optional)
	 * 	- is_null Set to true if this field
	 * @param boolean $add True to add the field, false to drop the field
	 * @return reference to this
	 */
	public function setField($name, array $attributes=null, $add=true) {
		$this->fields[$name][$add ? "add" : "drop"] = $attributes;
		
		return $this;
	}
	
	/**
	 * Sets a key to be added to the table being created or altered
	 *
	 * @param array $fields A numerical array of fields to set as a key
	 * @param string $type The type of key ("index", "primary", "unique")
	 * @param string $name The name of the key, will default to the first value in $fields if null
	 * @param boolean $add True to add the key, false to drop the key
	 * @return reference to this
	 */
	public function setKey(array $fields, $type, $name=null, $add=true) {
		if (count($fields) < 1)
			return;
		
		if ($name == null)
			$name = $fields[0];
		$this->keys[$type][$name][$add ? "add" : "drop"] = $fields;
		
		return $this;
	}
	
	/**
	 * Creates a table with the given name
	 *
	 * @param string $table The name of the table to create
	 * @param boolean $if_not_exists If true will create the table IFF the table does not exist
	 * @return PDOStatement
	 */
	public function create($table, $if_not_exists=false) {
		$this->type = "create" . ($if_not_exists ? "_if_not_exists" : "");
		$this->tables[] = $table;
		
		$statement = $this->query($this->buildQuery(), $this->values);
		$this->reset();
		return $statement;
	}
	
	/**
	 * Alters a table with the given name
	 *
	 * @param string $table The name of the table to alter
	 * @return PDOStatement
	 */
	public function alter($table) {
		$this->type = "alter";
		$this->tables[] = $table;
		
		$statement = $this->query($this->buildQuery(), $this->values);
		$this->reset();
		return $statement;
	}
	
	/**
	 * Truncates a table with the given name
	 * 
	 * @param string $table The name of the table to truncate
	 * @return PDOStatement
	 */
	public function truncate($table) {
		$this->type = "truncate";
		$this->tables[] = $table;
		
		$statement = $this->query($this->buildQuery());
		$this->reset();
		return $statement;
	}
	
	/**
	 * Drops a table with the given name
	 *
	 * @param string $table The name of the table to create
	 * @param boolean $if_exists If true will drop the table only if it exists
	 * @return PDOStatement
	 */
	public function drop($table, $if_exists=false) {
		$this->type = "drop" . ($if_exists ? "_if_exists" : "");
		$this->tables[] = $table;
		
		$statement = $this->query($this->buildQuery());
		$this->reset();
		return $statement;
	}
	
	/**
	 * Set fields for inserting or updating
	 *
	 * @param string $field The field name, or table.field name
	 * @param string $value The value to set or insert into this field
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 */
	public function set($field, $value, $bind_value=true, $escape=true) {
		// If this is a keyword value, don't bind or escape it
		if ($value instanceof stdClass && isset($value->keyword)) {
			$value = $value->keyword;
			$bind_value = false;
			$escape = false;
		}
		$this->fields[$field] = array('value'=>$value, 'bind_value'=>$bind_value, 'escape'=>$escape);
		
		return $this;
	}
	
	/**
	 * Inserts values into a table
	 *
	 * @param string $table The table name to insert
	 * @param array $values The field/value pairs to insert into this table
	 * @param array $value_keys An array of keys reperesenting fields to accept for insertion
	 * @see Record::set()
	 * @return PDOStatement
	 */
	public function insert($table, $values=null, array $value_keys=null) {
		$this->type = "insert";
		$this->tables[] = $table;
		
		$this->setFields($values, $value_keys);
		
		$statement = $this->query($this->buildQuery(), $this->values);
		$this->reset();
		return $statement;
	}
	
	/**
	 * Updates values in a table
	 *
	 * @param string $table The table to update
	 * @param array $values The field/value pairs to update in this table
	 * @see Record::set()
	 * @return PDOStatement
	 */
	public function update($table, $values=null, array $value_keys=null) {
		$this->type = "update";
		$this->tables[] = $table;
		
		$this->setFields($values, $value_keys);
		
		$statement = $this->query($this->buildQuery(), $this->values);
		$this->reset();
		return $statement;
	}
	
	/**
	 * Deletes columns from the currently-set tables
	 *
	 * @param array $columns The tables to delete from, null to delete from all
	 * @param boolean $escape True to escape $columns, false otherwise
	 * @return PDOStatement
	 */
	public function delete(array $columns=null, $escape=true) {
		$this->type = "delete";
		$this->columns[] = array("fields"=>(array)$columns, "escape"=>$escape);
		
		$statement = $this->query($this->buildQuery(), $this->values);
		$this->reset();
		return $statement;
	}
	
	/**
	 * Sets the columns to select from
	 *
	 * @param mixed $columns The table columns to select, or a string containing a single column
	 * @param boolean $escape True to escape $columns, false otherwise
	 * @return reference to this
	 */
	public function select($columns="*", $escape=true) {
		$this->type = "select";
		$this->columns[] = array("fields"=>(array)$columns, "escape"=>$escape);
		return $this;
	}
	
	/**
	 * Sets the tuples to query from
	 *
	 * @param mixed $table The table (string) or subqueries (array) to query from
	 * @return reference to this
	 */
	public function from($table) {
		$this->tables[] = $table;
		
		return $this;
	}
	
	/**
	 * Sets the tables to join on into a sql statement
	 *
	 * @param string $table The table to join on
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */
	public function join($table, $field=null, $op=null, $value=null, $bind_value=true, $escape=true) {
		$this->buildJoin(null, $table, $field, $op, $value, $bind_value, $escape);
		return $this;
	}

	/**
	 * Sets the tables to join on into a sql statement
	 *
	 * @param string $table The table to join on
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */	
	public function leftJoin($table, $field=null, $op=null, $value=null, $bind_value=true, $escape=true) {
		$this->buildJoin("LEFT", $table, $field, $op, $value, $bind_value, $escape);
		return $this;
	}

	/**
	 * Sets the tables to join on into a sql statement
	 *
	 * @param string $table The table to join on
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */	
	public function rightJoin($table, $field=null, $op=null, $value=null, $bind_value=true, $escape=true) {
		$this->buildJoin("RIGHT", $table, $field, $op, $value, $bind_value, $escape);
		return $this;
	}
	
	/**
	 * Sets the tables to join on into a sql statement
	 *
	 * @param string $table The table to join on
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */	
	public function innerJoin($table, $field=null, $op=null, $value=null, $bind_value=true, $escape=true) {
		$this->buildJoin("INNER", $table, $field, $op, $value, $bind_value, $escape);
		return $this;
	}
	
	/**
	 * Sets the "on" conditional for the next join statement
	 *
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */
	public function on($field, $op, $value, $bind_value=true, $escape=true) {
		$on = array(
			'type'=>"and",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>$op,
			'value'=>$value
		);
		$this->setConditional("on", $on);
		
		return $this;
	}
	
	/**
	 * Sets the "on" conditional as an "or" option for the next join statement
	 *
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */
	public function orOn($field, $op, $value, $bind_value=true, $escape=true) {
		$on = array(
			'type'=>"or",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>$op,
			'value'=>$value
		);
		$this->setConditional("on", $on);
		
		return $this;
	}
	
	/**
	 * Sets the where condition of a query with an AND statement
	 *
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this 
	 */
	public function where($field, $op, $value, $bind_value=true, $escape=true) {
		$where = array(
			'type'=>"and",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>$op,
			'value'=>$value
		);
		$this->setConditional("where", $where);
		
		return $this;
	}
	
	/**
	 * Sets the where condition of a query with an OR statement
	 *
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */
	public function orWhere($field, $op, $value, $bind_value=true, $escape=true) {
		$where = array(
			'type'=>"or",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>$op,
			'value'=>$value
		);
		$this->setConditional("where", $where);
		
		return $this;
	}
	
	/**
	 * Sets the on duplicate key condition of a query
	 *
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */
	public function duplicate($field, $op, $value, $bind_value=true, $escape=true) {
		$duplicate = array(
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>$op,
			'value'=>$value
		);
		$this->setConditional("duplicate", $duplicate);
		
		return $this;
	}

	/**
	 * Sets the where condition of a query with a LIKE statement on AND
	 *
	 * @param string $field The field for comparison
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */	
	public function like($field, $value, $bind_value=true, $escape=true) {
		$where = array(
			'type'=>"and",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>"like",
			'value'=>$value
		);
		$this->setConditional("where", $where);
		
		return $this;
	}
	
	/**
	 * Sets the where condition of a query with a NOT LIKE statement on AND
	 *
	 * @param string $field The field for comparison
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */	
	public function notLike($field, $value, $bind_value=true, $escape=true) {
		$where = array(
			'type'=>"and",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>"notlike",
			'value'=>$value
		);
		$this->setConditional("where", $where);
		
		return $this;
	}
	
	/**
	 * Sets the where condition of a query with a LIKE statement on OR
	 *
	 * @param string $field The field for comparison
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */		
	public function orLike($field, $value, $bind_value=true, $escape=true) {
		$where = array(
			'type'=>"or",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>"like",
			'value'=>$value
		);
		$this->setConditional("where", $where);
		
		return $this;
	}
	
	/**
	 * Sets the where condition of a query with a NOT LIKE statement on OR
	 *
	 * @param string $field The field for comparison
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */		
	public function orNotLike($field, $value, $bind_value=true, $escape=true) {
		$where = array(
			'type'=>"or",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>"notlike",
			'value'=>$value
		);
		$this->setConditional("where", $where);
		
		return $this;
	}
	
	/**
	 * Sets the having condition of a query with an AND statement
	 *
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */	
	public function having($field, $op, $value, $bind_value=true, $escape=true) {
		$having = array(
			'type'=>"and",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>$op,
			'value'=>$value
		);
		$this->setConditional("having", $having);
		
		return $this;
	}
	
	/**
	 * Sets the having condition of a query with an OR statement
	 *
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @return reference to this
	 */	
	public function orHaving($field, $op, $value, $bind_value=true, $escape=true) {
		$having = array(
			'type'=>"or",
			'bind_value'=>$bind_value,
			'escape'=>$escape,
			'field'=>$field,
			'op'=>$op,
			'value'=>$value
		);
		$this->setConditional("having", $having);
		
		return $this;
	}	
	
	/**
	 * Sets the columns to group by
	 *
	 * @param mixed $columns The column (string) or columns (array) to group by
	 * @return reference to this
	 */
	public function group($columns) {
		if (is_array($columns)) {
			for ($i=0; $i<count($columns); $i++)
				$this->group[] = $columns[$i];
		}
		else
			$this->group[] = $columns;
			
		return $this;
	}
	
	/**
	 * Sets the fields to order by
	 *
	 * @param array $fields The fields to order by
	 * @return reference to this
	 */
	public function order(array $fields, $escape=true) {
		
		foreach ($fields as $field => $order) {
			if (is_numeric($field))
				$this->order[] = array('field'=>$order, 'order'=>null, 'escape'=>$escape);
			else
				$this->order[] = array('field'=>$field, 'order'=>$order, 'escape'=>$escape);
		}
		return $this;
	}
	
	/**
	 * Sets limits on the records of a query
	 *
	 * @param unsigned integer $records The number of records to retrieve
	 * @param unsigned integer $start The record to start on (optional, default 0)
	 * @return reference to this
	 */
	public function limit($records, $start=0) {
		$this->limit['start'] = $start;
		$this->limit['records'] = $records;
		return $this;
	}
	
	/**
	 * Fetch a single row from the query
	 *
	 * @return mixed The return value on success depends on the fetch type. In all cases, false is returned on failure.
	 * @see PDOStatement::fetch()
	 * @see PDOStatement::setFetchMode()
	 */
	public function fetch() {
		$args = func_get_args();
		$statement = $this->query($this->buildQuery(), $this->values);
		
		if (!empty($args))
			call_user_func_array(array($statement, "setFetchMode"), $args);
		
		$result = $statement->fetch();
		$statement->closeCursor();
		$this->reset();
		return $result;
	}
	
	/**
	 * Fetch all rows from the query
	 * 
	 * @return mixed An array containing all of hte remaining rows in the result set, false on failure.
	 * @see PDOStatement::fetchAll()
	 * @see PDOStatement::setFetchMode()
	 */
	public function fetchAll() {
		$args = func_get_args();
		$statement = $this->query($this->buildQuery(), $this->values);
		
		if (!empty($args))
			call_user_func_array(array($statement, "setFetchMode"), $args);
		
		$result = $statement->fetchAll();
		$statement->closeCursor();
		$this->reset();
		return $result;
	}
	
	/**
	 * Executes the query and returns the PDOStatment object
	 *
	 * @return PDOStatement The executed statement object that may be iterated over
	 */
	public function getStatement() {
		$statement = $this->query($this->buildQuery(), $this->values);
		$this->reset();
		return $statement;
	}
	
	/**
	 * Converts the Record object to a query string. This differs from Record::__toString()
	 * in that member variables are not restored to their state prior to the method call,
	 * therefore this method may prove more useful when constructing subqueries.
	 * 
	 * @return string The SQL query
	 * @see Record::__toString()
	 */
	public function get() {
		return $this->buildQuery();
	}
	
	/**
	 * Converts the Record object to a query string. This differs from Record::get()
	 * in that member variables are restored to their state prior to the method call.
	 *
	 * @return string The SQL query
	 * @see Record::get()
	 */
	public function __toString() {
		// Fetch all vars before building the query
		$vars = get_object_vars($this);
		
		// Build the query (which will overwrite member variables)
		$sql = $this->buildQuery();
		
		// Restore all vars prior to building the query
		foreach ($vars as $var => $value)
			$this->$var = $value;

		return $sql;
	}
	
	/**
	 * Returns the number of results from the given query. This essentially wraps
	 * the query into a new (sub) query, and returns a COUNT on that result
	 *
	 * @return int The number of results from the given query
	 */
	public function numResults() {
		// Fetch the given query and its values, then reset
		$sql = $this->get();
		$values = $this->values;
		$this->reset();
		
		// Add the values back in, this serves as our subquery values
		$this->values = $values;
		// Wrap the original query and COUNT those values
		$result = (array)$this->select(array("COUNT(*)" => "total"), false)->
			from(array("(" . $sql . ")"=>"t_" . mt_rand()))->fetch();
		return (int)$result['total'];
	}
	
	/**
	 * Modifies the next where statement so that it beings with an open parenthese
	 *
	 * @return reference to this
	 */
	public function open() {
		$this->open++;
		return $this;
	}
	
	/**
	 * Modifies the given coditional statement to end with a close parenthese
	 *
	 * @param string $conditional The conditional to close ("where", "on", "having", "duplicate")
	 * @return reference to this
	 */
	public function close($conditional="where") {
		$i=count($this->$conditional)-1;
		if (isset($this->{$conditional}[$i])) {
			if (!isset($this->{$conditional}[$i]['close']))
				$this->{$conditional}[$i]['close'] = 0;
			$this->{$conditional}[$i]['close']++;
		}
		return $this;
	}
	
	/**
	 * Appends the given values to the values array so that they may be applied in
	 * the given order.
	 *
	 * @param array $values An array of values to append to the existing array of values
	 * @return refe	 to this
	 */
	public function appendValues(array $values) {
		// Append the given values to our values array
		$this->values = array_merge($this->values, $values);
		return $this;
	}
	
	/**
	 * Set an array of values into this object to be used as paremeters in the query
	 *
	 * @param array $values An array of values
	 * @param array $value_keys An array of key values to accept as valid fields
	 */
	private function setFields($values, array $value_keys=null) {
		if (is_array($values)) {
			foreach ($values as $field => $value) {
				// if $value_keys given and field is not set, then skip this value
				if (is_array($value_keys) && !in_array($field, $value_keys, true))
					continue;
				
				$val = $value;
				$bind_value = true;
				$escape = true;
				if (is_array($value)) {
					$val = $value['value'];
					
					if (isset($value['bind_value']) && $value['bind_value'] === false)
						$bind_value = false;
					if (isset($value['escape']) && $value['escape'] === false)
						$escape = false;
				}
				// If this field value is a Record object, substitute the query
				elseif ($value instanceof self) {
					$bind_value = false; // can't bind queries
					$escape = false; // can't escape queries
					$val = $this->buildSubquery(array($value->get()));
				}
				$this->set($field, $val, $bind_value, $escape);
			}
		}
	}
	
	/**
	 * Sets the conditional type and any necessary parentheses
	 *
	 * @param string $conditional The type:
	 * 	- where
	 * 	- on
	 * 	- having
	 * 	- duplicate
	 * @param array $statement The statement
	 */
	private function setConditional($conditional, array $statement) {
		$this->{$conditional}[] = $statement;
		
		$i = count($this->{$conditional})-1;
		if (isset($this->{$conditional}[$i])) {
			if (!isset($this->{$conditional}[$i]['open']))
				$this->{$conditional}[$i]['open'] = 0;
			$this->{$conditional}[$i]['open'] += $this->open;
			$this->open = 0;
		}
	}
	
	/**
	 * Consturctions a group of conditional statements as provided by the given
	 * array of conditionals
	 *
	 * @param array $conditionals An array of conditional statements to construct into SQL including:
	 * 	-field The field for the left hand side
	 * 	-op The operator used to join the field and value
	 * 	-value The value of the right hand side
	 * 	-type The type of conditional (optional: "or", "and", null = comma separated)
	 * 	-bind_value Whether or not to bind the right hand value
	 * 	-escape Whether or not to escape the right hand value
	 * @param boolean $convert_nulls True to automatically convert nulls to IS NULL or IS NOT NULL
	 * @return string The constructed SQL built using the given conditionals
	 */
	private function buildConditionals($conditionals, $convert_nulls=true) {
		$sql = "";
		
		for ($i=0; $i<count($conditionals); $i++) {
			$clause = $conditionals[$i];
			$bind_value = true;
			$escape = true;
			
			if (isset($clause['bind_value']) && !$clause['bind_value'])
				$bind_value = false;
			if (isset($clause['escape']) && !$clause['escape'])
				$escape = false;
			
			// Set separators
			if (isset($clause['type'])) {
				if ($clause['type'] == "and")
					$sql .= ($i > 0 ? " AND " : "");
				elseif ($clause['type'] == "or")
					$sql .= ($i > 0 ? " OR " : "");
			}
			// Default to comma separator
			else
				$sql .= ($i > 0 ? ", " : "");

			if (isset($clause['open'])) {
				while ($clause['open']-- > 0)
					$sql .= "(";
			}
			
			$sql .= $this->buildConditional($clause['field'], $clause['op'], $clause['value'], $bind_value, $escape, $convert_nulls);
				
			if (isset($clause['close'])) {
				while ($clause['close']-- > 0)
					$sql .= ")";
			}
		}
		return $sql;
	}
	
	/**
	 * Constructs a conditional statement used in join, where, having, and on duplicate clauses.
	 * Stores $value in $this->values array where possible.
	 * 
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param mixed $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 * @param boolean $convert_nulls True to automatically convert nulls to IS NULL or IS NOT NULL
	 * @return string The SQL that makes up this conditional statement
	 */
	private function buildConditional($field, $op, $value, $bind_value=true, $escape=true, $convert_nulls=true) {
		$sql = ($escape ? $this->escapeField($field) : $field) . " ";
		
		switch ($op) {
			case "like":
				$op = "LIKE";
				break;
			case "notlike":
				$op = "NOT LIKE";
				break;
			case "in":
				$op = "IN";
				break;
			case "notin":
				$op = "NOT IN";
				break;
			case "exists":
				$op = "EXISTS";
				break;
			case "notexists":
				$op = "NOT EXISTS";
				break;
		}
		
		if ($convert_nulls && $value === null && ($op == "<>" || $op == "!=" || $op == "=")) {
			if ($op == "=")
				$op = "IS NULL";
			else
				$op = "IS NOT NULL";
			$sql .= $op;
		}	
		else {
			// If value is an array it may be a subquery or a list
			if (is_array($value)) {
				$num_values = count($value);
				// If escaping it must be a list
				if ($bind_value) {
					// List values must all be bound
					$sql .= $op . " (" . implode(",", array_fill(0, $num_values, "?")) . ")";
					foreach ($value as $val)
						$this->values[] = $val;
				}
				// If not binding must be a subquery
				else
					$sql .= $op . $this->buildSubquery($value);
			}
			// Value is scalar
			else {
				$sql .= $op . " " . ($bind_value ? "?" : ($escape ? $this->escapeField($value) : $value));
			
				if ($bind_value)
					$this->values[] = $value;
			}
		}
		return $sql;
	}
	
	/**
	 * Builds a query
	 *
	 * @return string The sql query
	 */
	private function buildQuery() {
		$sql = "";
		
		switch ($this->type) {
			case "delete":
				$sql = "DELETE " . $this->buildColumns() . " FROM " . $this->buildTables() . $this->buildWhere() . $this->buildLimit();
				break;
			case "insert":
				$sql = "INSERT INTO " . $this->buildTables() . $this->buildValues() . $this->buildOnDuplicate();
				break;
			case "update":
				$sql = "UPDATE " . $this->buildTables() . " SET " . $this->buildValuePairs($this->fields) . $this->buildWhere() . $this->buildLimit();
				break;
			case "select":
				$sql = "SELECT " . $this->buildColumns() . " FROM " . $this->buildTables() . $this->buildWhere() . $this->buildGroup() . $this->buildHaving() . $this->buildOrder() . $this->buildLimit();
				break;
			case "create_if_not_exists":
			case "create":
				$sql = "CREATE TABLE " . ($this->type == "create_if_not_exists" ? "IF NOT EXISTS " : "") . $this->buildTables() . $this->buildFields() . $this->buildTableOptions();
				break;
			case "alter":
				$sql = "ALTER TABLE " . $this->buildTables() . $this->buildFields(false);
				break;
			case "truncate":
				$sql = "TRUNCATE TABLE " . $this->buildTables();
				break;
			case "drop_if_exists":
			case "drop":
				$sql = "DROP TABLE " . ($this->type == "drop_if_exists" ? "IF EXISTS " : "") . $this->buildTables();
				break;
		}
		return $sql;
	}
	
	/**
	 * Builds all fields, keys, and indexes required when creating or altering a table
	 *
	 * @param boolean $create True if creating a table, false if altering
	 * @return string A partial SQL query to be used when creating or altering a table
	 */
	private function buildFields($create=true) {
		
		if (empty($this->fields))
			return;
		
		// Build fields
		$fields = array();
		foreach ($this->fields as $name => $field) {
			$action = isset($field['add']) ? "add" : "drop";
			
			$field_str = "";
			if ($action == "add") {
				if (isset($field[$action]['default']))
					$this->values[] = $field[$action]['default'];
					
				$field_str = $this->escapeField($name) . " " . $field[$action]['type'] .
				(isset($field[$action]['size']) ? "(" . $field[$action]['size'] . ")" : "") .
				(isset($field[$action]['unsigned']) && $field[$action]['unsigned'] ? " UNSIGNED " : "") .
				(isset($field[$action]['is_null']) && $field[$action]['is_null'] ? " NULL" : " NOT NULL") .
				(isset($field[$action]['default']) ? " DEFAULT ?" : "") .
				(isset($field[$action]['auto_increment']) && $field[$action]['auto_increment'] ? " AUTO_INCREMENT" : "");
			}
			
			if ($create)
				$fields[] = $field_str;
			else {
				if ($action == "add")
					$fields[] = "ADD " . $field_str;
				else
					$fields[] = "DROP " . $this->escapeField($name);
			}
		}
		
		// Build keys/indexes
		if (!empty($this->keys)) {
			foreach ($this->keys as $type => $key) {
				foreach ($key as $name => $field) {
					$action = isset($field['add']) ? "add" : "drop";
					
					$field_str = "";
					$i=0;
					foreach ($field[$action] as $field_name) {
						$field_str .= ($i++ > 0 ? ", " : "") . $this->escapeField($field_name);
					}
					
					$key_field = strtoupper($type) . ($type != "index" ? " KEY " : " ") . ($type == "primary" ? "" : $this->escapeField($name));
					
					if ($create)
						$fields[] = $key_field . "(" . $field_str . ")";
					else {
						if ($action == "add")
							$fields[] = "ADD " . $key_field . "(" . $field_str . ")";
						else
							$fields[] = "DROP " . $key_field;
					}
				}
			}
		}
		
		if ($create)
			return " (" . implode(", ", $fields) . ")";
		return " " . implode(", ", $fields);
	}
	
	/**
	 * Builds the table options when creating a table
	 *
	 * @return string The table options string
	 */
	private function buildTableOptions() {
		return " ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
	}
	
	/**
	 * Builds the given join statement
	 *
	 * @param string $join The join type to use
	 * @param string $table The table to join on
	 * @param string $field The field for comparison
	 * @param string $op The operator to compare on
	 * @param string $value The value to compare with
	 * @param boolean $bind_value True to treat $value as a bound value (i.e. a string or integer, rather than an table or table field)
	 * @param boolean $escape True to escape the value, false otherwise
	 */
	private function buildJoin($join, $table, $field=null, $op=null, $value=null, $bind_value=true, $escape=true) {
		
		if ($field != null)
			$this->on($field, $op, $value, $bind_value, $escape);
		
		$this->join_sql .= ($this->join_sql != "" ? " " : "") . ($join ? $join . " " : "") . "JOIN " . (is_array($table) ? $this->buildSubquery($table) : $this->escapeField($table));
		
		if (!empty($this->on))
			$this->join_sql .= " ON " . $this->buildConditionals($this->on);
			
		// Reset the conditionals
		$this->on = array();
	}
	
	/**
	 * Builds the columns of a query
	 *
	 * @return string The columns to query
	 */
	private function buildColumns() {
		$sql = "";
		if (is_array($this->columns)) {
			for ($i=0, $j=0; $i<count($this->columns); $i++) {
				if (!isset($this->columns[$i]['fields']) || !is_array($this->columns[$i]['fields']))
					continue;
				
				foreach ($this->columns[$i]['fields'] as $key => $value) {
					$sql .= ($j++ > 0 ? ", " : "");
					if (!is_numeric($key))
						$sql .= ($this->columns[$i]['escape'] ? $this->escapeField($key) : $key) . " AS " . $this->escapeField($value);
					else
						$sql .= ($this->columns[$i]['escape'] ? $this->escapeField($value) : $value);
				}
			}
		}
		else
			$sql = "*";
			
		return $sql;
	}
	
	/**
	 * Builds the tables to select from of a query
	 *
	 * @return string The tables to query
	 */
	private function buildTables() {
		$sql = "";
		for ($i=0; $i<count($this->tables); $i++) {
			$sql .= ($i > 0 ? ", " : "");
			if (is_array($this->tables[$i]))
				$sql .= $this->buildSubquery($this->tables[$i]);
			else
				$sql .= $this->escapeField($this->tables[$i]);
		}
		$sql .= strlen($this->join_sql) > 0 ? " " . $this->join_sql : "";
		
		return $sql;
	}
	
	/**
	 * Builds the ON DUPLICATE KEY UPDATE clause of a query
	 *
	 * @return string The ON DUPLICATE KEY UPDATE clause of a query
	 */
	private function buildOnDuplicate() {
		$sql = "";
		
		if (!empty($this->duplicate))
			$sql .= " ON DUPLICATE KEY UPDATE " . $this->buildConditionals($this->duplicate, false);
		return $sql;
	}
	
	/**
	 * Builds the WHERE clause of a query
	 *
	 * @return string The WHERE clause of a query
	 */
	private function buildWhere() {
		$sql = "";
		
		if (!empty($this->where))
			$sql .= " WHERE " . $this->buildConditionals($this->where);
		return $sql;
	}
	

	/**
	 * Builds the ORDER BY clause of a query
	 *
	 * @return string The ORDER BY clause of a query
	 */
	private function buildOrder() {
		$sql = "";
		
		if (!empty($this->order)) {
			$sql .= " ORDER BY ";
			for ($i=0; $i<count($this->order); $i++) {
				$sql .= ($i > 0 ? ", " : "") .
					($this->order[$i]['escape'] ? $this->quoteIdentifier($this->order[$i]['field']) : $this->order[$i]['field']) .
					(strtolower($this->order[$i]['order']) == "desc" ? " DESC" : " ASC");
			}
		}
		
		return $sql;
	}
	
	/**
	 * Builds the GROUP BY clause of a query
	 *
	 * @return string The GROUP BY clause of a query
	 */
	private function buildGroup() {
		$sql = "";
		
		if (!empty($this->group)) {
			$sql .= " GROUP BY ";
			for ($i=0; $i<count($this->group); $i++)
				$sql .= ($i > 0 ? ", " : "") . $this->escapeField($this->group[$i]);
		}
		
		return $sql;
	}
	
	/**
	 * Builds the HAVING clause of a query
	 *
	 * @return string The HAVING clause of a query
	 */
	private function buildHaving() {
		$sql = "";
		
		if (!empty($this->having))
			$sql .= " HAVING " . $this->buildConditionals($this->having);
		
		return $sql;
	}
	
	/**
	 * Builds the LIMIT clause of a query
	 *
	 * @return string The LIMIT clause of a query
	 */
	private function buildLimit() {
		$sql = "";
		
		if (isset($this->limit['start']) && isset($this->limit['records']))
			$sql .= " LIMIT " . (int)$this->limit['start'] . ", " . (int)$this->limit['records'];
		
		return $sql;
	}
	
	/**
	 * Builds the subquery as a given value, automatically wrapping with parentheses
	 *
	 * @param array $subquery An array containing either the subquery string or a key/value pair of subquery=>alias
	 * @return string The subquery as a string (with optional AS alaising)
	 */
	private function buildSubquery(array $subquery) {
		$sql = "";
		foreach ($subquery as $key => $value) {
			// Subquery non-aliasing
			if (is_numeric($key))
				$sql .= "(" . $value . ")";
			// Handle subquery aliasing (multiple words = subquery)
			elseif (substr_count($key, " ") > 0)
				$sql .= "(" . $key . ") AS " . $this->escapeField($value);
			// Handle table aliasing
			else
				$sql .= $this->escapeField($key) . " AS " . $this->escapeField($value);
		}
		return $sql;
	}
	
	/**
	 * Builds the field/value pairs of a query
	 *
	 * @return string The field/value section of a query
	 */
	private function buildValues() {
		$sql = "";
		if (!empty($this->fields)) {
			$i=0;
			$fields = "";
			$values = "";
			foreach ($this->fields as $field => $value) {
				$fields .= ($i > 0 ? ", " : "") . $this->escapeField($field);
				$values .= ($i > 0 ? ", " : "");
				
				if (is_array($value)) {
					if (isset($value['bind_value']) && $value['bind_value'] === false)
						$values .= (isset($value['escape']) && $value['escape'] ? $this->escapeField($value['value']) : $value['value']);
					else {
						$values .= "?";
						$this->values[] = $value['value'];
					}
				}
				else {
					$values .= "?";
					$this->values[] = $value;
				}
				$i++;
			}
			
			$sql .= " (" . $fields . ") VALUES (" . $values . ")";
		}
		
		return $sql;
	}
	
	/**
	 * Creates key = value pairs, comma separated
	 *
	 * @return string The value pairs of a query
	 */
	private function buildValuePairs($pairs) {
		$sql = "";
		if (!empty($pairs)) {
			$sql .= " ";
			$i=0;
			foreach ($pairs as $key => $value) {
				$sql .= ($i > 0 ? ", " : "") . $this->escapeField($key) . "=";
				// If value was an array, then check if we are to bind this value
				if (is_array($value)) {
					if (isset($value['bind_value']) && $value['bind_value'] === false)
						$sql .= (isset($value['escape']) && $value['escape'] ? $this->escapeField($value['value']) : $value['value']);
					else {
						$sql .= "?";
						$this->values[] = $value['value'];
					}
				}
				else {
					$sql .= ($i > 0 ? ", " : "") . $this->escapeField($key) . "=?";
					$this->values[] = $value;
				}
				$i++;
			}
		}
		return $sql;
	}
	
	/**
	 * Reset the object, making ready for the next query
	 */
	public function reset() {
		$this->type = null;
		$this->join_sql = null;
		$this->tables = array();
		$this->columns = array();
		$this->fields = array();
		$this->keys = array();
		$this->where = array();
		$this->on = array();
		$this->order = array();
		$this->group = array();
		$this->limit = array();
		$this->values = array();
		$this->duplicate = array();
		$this->having = array();
		$this->open = 0;
	}
	
	/**
	 * Escapes a field or SQL function wrapped field. Supports name as well as table.name as field formats
	 * 
	 * @param string $field The field to escape
	 * @return string The escaped field
	 */
	private function escapeField($field) {
		$find = "/((\w+)\((.*)\))|(.*)/i";
		return preg_replace_callback($find, array($this, "escapeFieldMatches"), $field);
	}
	
	/**
	 * Escape an array of matched elements from Record::escapeField(). 
	 *
	 * @param array $matches An array of matches from Record::escapeField()
	 * @return string The escaped value
	 * @see Record::escapeField()
	 */
	private function escapeFieldMatches($matches) {
		$elements = count($matches);
		switch ($elements) {
			case 4:
				return $matches[2] . "(" . $this->escapeField($matches[3]) . ")";
			case 5:
				return $this->escapeTableField($matches[4]);
		}
		
		return $matches[0];
	}
	
	/**
	 * Escape field or table.field elements.
	 * @param string $field The field to escape in "field" or "table.field" format
	 * @return string The escaped value
	 * @see Record::escapeField()
	 */
	private function escapeTableField($field) {
		$q = $this->ident_quote_chr;
		return preg_replace("/(\w+)/", $q . "$1" . $q, $field);
	}
	
	/**
	 * Escapes identifiers in the format of table.field or field, or an array of
	 * the form array('table', 'field')
	 *
	 * @param mixed A string identifier or an array of identifier parts
	 * @return string A string representing the quoted identifier
	 */
	public function quoteIdentifier($identifier) {
		$q = $this->ident_quote_chr;
		
		if (is_string($identifier))
			$identifier = explode(".", $identifier);
		
		$parts = array();
		if (is_array($identifier)) {
			foreach ($identifier as $part) {
				$parts[] = $q . str_replace($q, $q . $q, $part) . $q;
			}
		}
		
		return implode(".", $parts);
	}
}
?>