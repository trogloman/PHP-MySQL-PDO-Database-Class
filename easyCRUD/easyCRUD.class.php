<?php 
/**
* Easy Crud  -  This class kinda works like ORM. Just created for fun :) 
*
* @author		Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
* @version      0.1a
*/
require_once(__DIR__ . '/../DB.class.php');
class Crud {

	private $db;

	public $variables;

	public function __construct($data = array()) {
		$this->db =  new DB();	
		$this->variables  = $data;
	}

	public function __set($name,$value){
		if(strtolower($name) === $this->pk) {
			$this->variables[$this->pk] = $value;
		}
		else {
			$this->variables[$name] = $value;
		}
	}

	public function __get($name)
	{	
		if(is_array($this->variables)) {
			if(array_key_exists($name,$this->variables)) {
				return $this->variables[$name];
			}
		}

		$trace = debug_backtrace();
		trigger_error(
		'Undefined property via __get(): ' . $name .
		' in ' . $trace[0]['file'] .
		' on line ' . $trace[0]['line'],
		E_USER_NOTICE);
		return null;
	}

	public function varDump(){
		return	$this->variables;
	}

	public function save($id = "0") {
		$this->variables[$this->pk] = (empty($this->variables[$this->pk])) ? $id : $this->variables[$this->pk];

		$fieldsvals = '';
		$columns = array_keys($this->variables);

		foreach($columns as $column)
		{
			if($column !== $this->pk)
			$fieldsvals .= $column . " = :". $column . ",";
		}

		$fieldsvals = substr_replace($fieldsvals , '', -1);

		if(count($columns) > 1 ) {
			$sql = "UPDATE " . $this->table .  " SET " . $fieldsvals . " WHERE " . $this->pk . "= :" . $this->pk;
//echo $sql."\n";
			return $this->db->query($sql,$this->variables);
		}
	}

	public function create() { 
		$bindings   	= $this->variables;

		if(!empty($bindings)) {
			$fields     =  array_keys($bindings);
			$fieldsvals =  array(implode(",",$fields),":" . implode(",:",$fields));
			$sql 		= "INSERT INTO ".$this->table." (".$fieldsvals[0].") VALUES (".$fieldsvals[1].")";
		}
		else {
			$sql 		= "INSERT INTO ".$this->table." () VALUES ()";
		}

		return $this->db->query($sql,$bindings);
	}

     /**
       *  Returns the last inserted id.
       *  @return string
       */	
		public function lastInsertId() {
			return $this->db->lastInsertId();
		}	

	public function delete($id = "") {
		$id = (empty($this->variables[$this->pk])) ? $id : $this->variables[$this->pk];

		if(!empty($id)) {
			$sql = "DELETE FROM " . $this->table . " WHERE " . $this->pk . "= :" . $this->pk. " LIMIT 1" ;
			return $this->db->query($sql,array($this->pk=>$id));
		}
	}

	public function find($id = "") {
		$id = (empty($this->variables[$this->pk])) ? $id : $this->variables[$this->pk];

		if(!empty($id)) {
			$sql = "SELECT * FROM " . $this->table ." WHERE " . $this->pk . "= :" . $this->pk . " LIMIT 1";	
			$r=$this->db->row($sql,array($this->pk=>$id));
			$this->variables = $r;
		}
		if(is_array($r)){
			return true;
		}else{
			return false;
		}

	}
	//public function search($param=false,$limitParam=false,$order=false) {
	public function search($param=false,$limitParam=false) {
		// check param and construct where close
		if($param==false){
			return $this->all();
		}elseif(is_array($param)){
			$where='';
			foreach($param as $k=>$v){
				$where.=(empty($where)?'':' AND ');
				$this->db->bind($k,$v);
				if(is_numeric($v)){
				  $where.=$k."= :".$k."";
				}elseif(is_string($v)){
				  $where.=$k." LIKE :".$k;
//				  $where.=$k." LIKE ':".$k."'";
				}
			}
		}elseif(is_string($param)){
			$where=$param;
		}
		if(!empty($where)) {
			$sql = "SELECT * FROM " . $this->table ." WHERE " .$where;	
		}
		if(!empty($sql)){
			// construct limit
			if($limitParam==false){
				$limit='';
			    return $this->db->query($sql);
			} elseif($limitParam!='1'){
				$sql .= ' LIMIT '.$limitParam;	
			    return $this->db->query($sql);
			}else{
				$sql .= ' LIMIT 1';	
				//echo $sql;
				//$this->variables = $this->db->row($sql,array($this->pk=>$id));
				$r= $this->db->row($sql);
				return $r;
			}
		}
	}

	public function all(){
		return $this->db->query("SELECT * FROM " . $this->table);
	}
	
	public function min($field)  {
		if($field)
		return $this->db->single("SELECT min(" . $field . ")" . " FROM " . $this->table);
	}

	public function max($field)  {
		if($field)
		return $this->db->single("SELECT max(" . $field . ")" . " FROM " . $this->table);
	}

	public function avg($field)  {
		if($field)
		return $this->db->single("SELECT avg(" . $field . ")" . " FROM " . $this->table);
	}

	public function sum($field)  {
		if($field)
		return $this->db->single("SELECT sum(" . $field . ")" . " FROM " . $this->table);
	}

	public function count($field)  {
		if($field)
		return $this->db->single("SELECT count(" . $field . ")" . " FROM " . $this->table);
	}	
	
}
?>
