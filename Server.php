<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 28.01.15
 * Time: 22:56
 * To change this template use File | Settings | File Templates.
 */



require_once("m.php");

class Server {
    private $isConnected = false;
    /* @var mysqli $mysqli*/
    public $mysqli = null;
    /* @var mysqli_stmt $prepare_tmp*/
    private $prepare_tmp = null;
    private $queryInfo = null;
    private $prefix = "";

    function __construct() {
        require_once("../p/config.php");
        $this->prefix = config::$prefix;
    }

    public function getPrefix() {
        return $this->prefix;
    }


    public function connect() {
        if ($this->isConnected) return;
        // connect now


        try {
            $this->mysqli = new mysqli(config::$dbhost, config::$dbuser, config::$dbpassword, config::$dbname);
            mysqli_set_charset($this->mysqli, "utf8");
        }catch (Exception $error) {
            $this->isConnected = false;
            printf("mysqli error: %s\n", $error->getMessage());
            exit();
        }

        // check connect
        if (mysqli_connect_errno()) {
            printf("error connect: %s\n", mysqli_connect_error());
            exit();
        }

        $this->isConnected = true;
    }


    protected function prepareQuery(&$arguments)
    {



        $sprintfArg = array();
        $sprintfArg[] = $arguments[0];
        foreach ($arguments as $pos => $var) {
            if (is_array($var)) {
                $insertAfterPosition = $pos;
                $replaceWith = array();
                unset($arguments[$pos]);
                foreach ($var as $arrayVar) {
                    array_splice($arguments, $insertAfterPosition, 0, $arrayVar);
                    $insertAfterPosition++;
                    $replaceWith[] = '?';
                }
                $sprintfArg[] = implode(',', $replaceWith);
            }
        }


        $arguments[0] = call_user_func_array('sprintf', $sprintfArg);
        if ($arguments[0] === false) {
            $arguments[0] = $sprintfArg[0];
        }
    }


    private function bindResult(&$data)
    {
        $this->prepare_tmp->store_result();
        $variables = array();
        $meta = $this->prepare_tmp->result_metadata();
        while ($field = $meta->fetch_field()) {
            $variables[] = &$data[$field->name]; // pass by reference, not value
        }
        return call_user_func_array(array($this->prepare_tmp, 'bind_result'), $variables);
    }

    protected function getTypeByVal($variable)
    {
        switch (gettype($variable)) {
            case 'integer':
                $type = 'i';
                break;
            case 'double':
                $type = 'd';
                break;
            default:
                $type = 's';
        }
        return $type;
    }

    private function getParamTypes($arguments)
    {
        unset($arguments[0]);
        $retval = '';
        foreach ($arguments as $arg) {
            $retval .= $this->getTypeByVal($arg);
        }
        return $retval;
    }

    private function bindParams($bindVars, &$params)
    {
        $params[] = $this->getParamTypes($bindVars);
        foreach ($bindVars as $key => $param) {
            $params[] = &$bindVars[$key]; // pass by reference, not value
        }

        return call_user_func_array(array($this->prepare_tmp, 'bind_param'), $params);
    }

    protected function _query($arguments)
    {
        $this->prepareQuery($arguments);


        $query = $arguments[0];

        $this->prepare_tmp = $this->mysqli->prepare($query);
        if (!$this->prepare_tmp) return false;

        if (count($arguments) > 1) {
            $bindVars = $arguments;
            unset($bindVars[0]);
            $params = array();
            $binding = $this->bindParams($bindVars, $params);
            if (!$binding) return false;
        }
        return true;
    }

    private function mysqliFetchAssoc(&$data)
    {
        $i = 0;
        $array = array();
        while ($this->prepare_tmp->fetch())
        {
            $array[$i] = array();
            foreach ($data as $k => $v) {
                $array[$i][$k] = $v;
            }
            $i++;
        }
        return $array;
    }

    protected function setQueryInfo()
    {
        $info = array(
            'affected_rows' => $this->prepare_tmp->affected_rows,
            'insert_id'     => $this->prepare_tmp->insert_id,
            'num_rows'      => $this->prepare_tmp->num_rows,
            'field_count'   => $this->prepare_tmp->field_count,
            'sqlstate'      => $this->prepare_tmp->sqlstate,
        );
        $this->queryInfo = $info;
    }

    public function getQueryInfo()
    {
        return $this->queryInfo;
    }

    /* SQL Select
        in => query and arguments example select('SELECT * FROM test WHERE id >?',1001);
        out => array with flag result
        if result = true have data
        else error have info about error and info have arguments
    */

    public function select($query) {


        if (!$this->isConnected) {
            $this->connect();
            if (!$this->isConnected) return ErrorCodes::gi()->executeShort(0,"can't connect",ErrorCodes::$CANT_CONNECT_TO_SERVER_DB);
        }

        $arguments = func_get_args();
        // create query
        $this->_query($arguments);
        if (!$this->prepare_tmp) return ErrorCodes::gi()->executeShort(0,array("msg"=>$this->mysqli->error,"args"=>$arguments),ErrorCodes::$ERROR_REQUEST);
        // execute
        $execute = $this->prepare_tmp->execute();
        if (!$execute) return ErrorCodes::gi()->executeShort(0,array("msg"=>$this->mysqli->error,"args"=>$arguments),ErrorCodes::$ERROR_REQUEST);
        // bind result



        $result = $this->bindResult($data);
        if (!$result) return ErrorCodes::gi()->executeShort(0,array("msg"=>$this->mysqli->error,"args"=>$arguments),ErrorCodes::$ERROR_REQUEST);
        // get result





        $rows = $this->mysqliFetchAssoc($data);


        // set info
        $this->setQueryInfo();
        return array("result"=>true,"data"=>$rows);
    }

    /* SQL Insert
        in => query and arguments example insert('INSERT INTO `test` (`name`,`properties`) VALUES (?,?)','test','some');
        out => 0 or lastInsertID
    */

    public function insert($query)
    {
        if (!$this->isConnected) {
            $this->connect();
            if (!$this->isConnected) return ErrorCodes::gi()->executeShort(0,"can't connect",ErrorCodes::$CANT_CONNECT_TO_SERVER_DB);
        }
        $arguments = func_get_args();
        $this->_query($arguments);
        if (!$this->prepare_tmp) return ErrorCodes::gi()->executeShort(0,array("msg"=>$this->mysqli->error,"args"=>$arguments),ErrorCodes::$ERROR_REQUEST);

        $execute = $this->prepare_tmp->execute();
        if (!$execute) return ErrorCodes::gi()->executeShort(0,array("msg"=>$this->mysqli->error,"args"=>$arguments),ErrorCodes::$ERROR_REQUEST);
        $this->setQueryInfo();
        return array("result"=>true,"data"=>$this->mysqli->insert_id);
    }

    /* SQL query
        in => query and arguments ;
        out => bool
    */

    public function query($query)
    {
        if (!$this->isConnected) {
            $this->connect();
            if (!$this->isConnected) return ErrorCodes::gi()->executeShort(0,"can't connect",ErrorCodes::$CANT_CONNECT_TO_SERVER_DB);
        }
        $arguments = func_get_args();
        $this->_query($arguments);
        if (!$this->prepare_tmp) return ErrorCodes::gi()->executeShort(0,array("msg"=>$this->mysqli->error,"args"=>$arguments),ErrorCodes::$ERROR_REQUEST);

        $execute = $this->prepare_tmp->execute();
        if (!$execute) return ErrorCodes::gi()->executeShort(0,array("msg"=>$this->mysqli->error,"args"=>$arguments),ErrorCodes::$ERROR_REQUEST);
        $this->setQueryInfo();
        return array("result"=>true,"data"=>"success");
    }


    public function disconnect() {
        if (!$this->isConnected) return;
        // disconnect now

        if ($this->mysqli) {
            $this->mysqli->close();
        }

        $this->isConnected = false;
    }
}