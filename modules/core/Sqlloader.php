<?
class Sqlloader extends Module{
public static $name="Sqlloader";
public static $version=2.4;
public static $short='c';
public static $required=array();
public static $hooks=array(); //An empty array blocks hook loading.

var $mysqli;
var $o=array();
var $queries=0;
var $tableColumnCache=array();
var $tablePrimaryKeyCache=array();
var $lastQuery="";

    function __construct(){
    }

    function connect($sqluser,$sqlpass,$database){
        $this->mysqli = new mysqli('localhost',$sqluser,$sqlpass,$database);
        if(mysqli_connect_errno())die(mysqli_connect_error());
        $this->mysqli->autocommit(TRUE);
        $this->mysqli->set_charset("utf8");
        $this->loadOptions();
    }

    function close(){
        @ $this->mysqli->close();
    }

    function commit(){
        $this->mysqli->commit();
    }

    function rollback(){
        $this->mysqli->rollback();
    }

    function raw($query){
        return $this->mysqli->query($query);
    }

    function insertID(){
        return $this->mysqli->insert_id;
    }

    function query($query,$data,$secureHTML=true){
        try{
        for($i=0,$temp=count($data);$i<$temp;$i++){
            if($data[$i]==null)$data[$i]='';
        }

        $stmt = $this->mysqli->stmt_init();
        if($stmt->prepare($query)){
            $stmt=$this->bindVars($stmt,$data,$secureHTML);
            if(!$stmt->execute())throw new Exception("MySQL error: ".$this->mysqli->error."<br>Query: $query".$msqli->errno."<br>Args: ".implode(";",$data));
            $stmt->close();
            $this->queries++;
            $this->lastQuery=$query;
            return true;
        }else{
            throw new Exception("MySQL error: ".$this->mysqli->error."<br>Query: $query".$msqli->errno."<br>Args: ".implode(";",$data));
        }
        }catch(Exception $e){
            global $k;
            $k->err("Error Code: ".$e->getCode()."<br>Error Message: ".$e->getMessage()."<br>Strack Trace: <br>".$e->getTraceAsString());
        }
    }

    function getData($query,$data=array(),$secureHTML=true){
        try{
        $stmt = $this->mysqli->stmt_init();
        if($stmt->prepare($query)){
            $stmt=$this->bindVars($stmt,$data,$secureHTML);
            if(!$stmt->execute())throw new Exception("MySQL error: ".$this->mysqli->error."<br>Query: $query".$msqli->errno."<br>Args: ".implode(";",$data));
            $stmt->store_result();
            $meta = $stmt->result_metadata();

            while ($column = $meta->fetch_field()) {
                $bindVarsArray[] = &$results[$column->name];
            }
            call_user_func_array(array($stmt, 'bind_result'), $bindVarsArray);

            $i=0;$result=array();
            while($stmt->fetch()){
                foreach($results as $key => $val){
                    $result[$i][$key]=$val;
                }
                $i++;
            }
            $this->queries++;
            $this->lastQuery=$query;
            $stmt->close();
            return $result;
        }else{
            throw new Exception("MySQL error: ".$this->mysqli->error."\nQuery: $query".$msqli->errno."\nArgs: ".implode(";",$data));
        }
        }catch(Exception $e){
            global $k;
            $k->err("Error Code: ".$e->getCode()."<br />Error Message: ".$e->getMessage()."<br />Strack Trace: <br />".$e->getTraceAsString());
        }
    }

    function bindVars($stmt,$params=array(),$secureHTML=true) {
        global $p;
        if (count($params)>0) {
            $types = '';                        //initial string with types
            foreach($params as &$param) {       //for each element, determine type and add
                if(is_int($param)) {            $types .= 'i';
                } elseif (is_float($param)) {   $types .= 'd';
                } elseif (is_string($param)) {  $types .= 's';
                                                $param=$this->enparse($param,$secureHTML);
                } else {                        $types .= 'b';
                }
            }
            $bind_names[] = $types;             //first param needed is the type string
                                                // eg:  'issss'

            for ($i=0; $i<count($params);$i++) {//go through incoming params and added em to array
                $bind_name = 'bind' . $i;       //give them an arbitrary name
                $$bind_name = $params[$i];      //add the parameter to the variable variable
                $bind_names[] = &$$bind_name;   //now associate the variable as an element in an array
            }
            if(!call_user_func_array(array($stmt,'bind_param'),$bind_names)){
                throw new Exception("Failed to bind parameters properly!\nParameters: "+implode(",",$params));
            }
        }
        return $stmt;                           //return the bound statement 
    }
    
    function getTableColumns($table){
        if(!array_key_exists($table,$this->tableColumnCache)){
            $fields = $this->getData('SELECT column_name AS name FROM information_schema.columns WHERE table_name=? AND table_schema=?',array($table,SQLDB));
            foreach($fields as $field)$this->tableColumnCache[$table][] = $field['name'];
        }
        return $this->tableColumnCache[$table];
    }
    
    function getTablePrimaryKeys($table){
        if(!array_key_exists($table,$this->tablePrimaryKeyCache)){
            $fields = $this->getData('SELECT column_name AS name FROM information_schema.columns WHERE column_key = ? AND table_name=? AND table_schema=?',array('PRI',$table,SQLDB));
            foreach($fields as $field)$this->tablePrimaryKeyCache[$table][] = $field['name'];
        }
        return $this->tablePrimaryKeyCache[$table];
    }
    
    function getTableInformation($table){
        
    }
    
    function enparse($s,$secure=false){
        if(!mb_check_encoding($s, 'UTF-8')){
            mb_substitute_character('none');
            $s = mb_convert_encoding($s, 'UTF-8');
        }
        //Fix PHP bullshit
        $s = str_replace("\\'","'",$s);
        $s = str_replace('\\"','"',$s);
        $s = str_replace('\\\\','\\',$s);
        if($secure)$s=$this->secureHTML ($s);
        return $s;
    }
    
    function secureHTML($s){
        $s = str_ireplace('>','&gt;',$s);
        $s = str_ireplace('<','&lt;',$s);
        $s = str_ireplace('$','&#36;',$s);
        $s = str_ireplace('\'','&apos;',$s);
        return $s;
    }
    
    function desecureHTML($s){
        $s = str_ireplace('&gt;','>',$s);
        $s = str_ireplace('&lt;','<',$s);
        $s = str_ireplace('&#36;','$',$s);
        $s = str_ireplace('&apos;','\'',$s);
        return $s;
    }

    function loadOptions(){
        $result=$this->getData("SELECT `key`,`value` FROM ms_options");
        for ($i=0,$arc=count($result);$i<$arc;$i++) {
            $this->o[$result[$i]["key"]]=$result[$i]["value"];
        }
    }
}
?>
