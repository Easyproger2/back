<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 10.03.15
 * Time: 16:29
 * To change this template use File | Settings | File Templates.
 */

class RolesValidator {


    /* @var Server $pServer */
    private $pServer;
    private $role_id;

    public $userInfo = NULL;

    private $token = null;
    function __construct(Server $server) {
        $this->pServer = $server;
        $this->role_id = NULL;
        $this->userInfo = NULL;
    }

    public function setToken($token){
        $this->token = $token;
    }

    public function getUserInfo($httpError = true) {

        if ($this->userInfo !== NULL) {
            return $this->userInfo;
        }


        if ($this->token === NULL || $this->token == '') {
            if ($httpError) http_response_code(401);
            return ErrorCodes::gi()->executeShort(0,"user not logged",ErrorCodes::$NOT_LOGGED);
        }

        // need ask resource.php for user info
        $token_url = pathinfo(curPageURL())['dirname']."/resource.php";

        $post_string = "access_token=".$this->token;

        $ch = curl_init( $token_url );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $content = curl_exec( $ch );
        curl_close( $ch );

        $userInfo = json_decode($content,true);

        if (!$userInfo || $userInfo === NULL) {
            // create error
            if ($httpError) http_response_code(401);
            return ErrorCodes::gi()->executeShort(0,$content,ErrorCodes::$GET_TOKEN);
        }

        if ($userInfo["error"]) {
            if ($httpError) http_response_code(401);
            return ErrorCodes::gi()->executeShort(0,$userInfo["error_description"],ErrorCodes::$errorsOAUTH2IDS[$userInfo["error"]]);
        }

        $result = $this->pServer->select("SELECT users.role_id as role_id FROM ".Config::$dbusers." as users
                                                                         WHERE users.username = ?", $userInfo["user_id"]);

        if ($result["result"]) {
            $data = $result["data"][0];
            if (!count($result["data"])) return ErrorCodes::gi()->executeShort(0,"can't get info about user",ErrorCodes::$SERVER_REQUEST_ERROR);

            $this->role_id = $data["role_id"];
        }else {
            if ($httpError) http_response_code(401);
            return ErrorCodes::gi()->executeShort(0,"can't get info about user",ErrorCodes::$SERVER_REQUEST_ERROR);
        }
        $this->userInfo = array("result"=>true,"token_time"=>$userInfo["expires"],"user_id"=>$userInfo["user_id"],"client_id"=>$userInfo["client_id"]);
        return $this->userInfo;
    }



    private function getstring($param) {
    }

    private function getbool($param) {
    }

    private function getnumbers($param) {

        $array = explode(",",$param);
        $res = true;
        for ($i = 0;$i < count($array);$i++) {
            $res = is_numeric($array[$i]) && $res;
        }
        return $array;
    }


    public function getRoles_local($role_id,$resourseID,$param1,$param2) {

        $values = [];
        $values[] = 0;
        $where = [];
        $where[] = "ID=?"; $values[] = $role_id;
        $where[] = "id_resource=?"; $values[] = $resourseID;

        if ($param1 != null) { $where[] = "param1=?"; $values[] = $param1;}
        if ($param1 != null) { $where[] = "param2=?"; $values[] = $param2;}


        $values[0] = "SELECT * FROM ".$this->pServer->getPrefix()."roles WHERE ".implode(" AND ",$where);
        $result = call_user_func_array(array($this->pServer, 'query'), $values);
        return $result;
    }

    public function removeOwnerRoles($resourseID,$param1,$param2,$roles) {
        $role_id = $this->role_id;
        $this->removeRoles_local($role_id,$resourseID,$param1,$param2,$roles);
    }

    public function removeRoles_local($role_id,$resourseID,$param1,$param2,$roles) {
        $values = [];
        $values[] = 0;
        $where = [];
        $where[] = "ID=?"; $values[] = $role_id;
        $where[] = "id_resource=?"; $values[] = $resourseID;

        if ($param1 != null) { $where[] = "param1=?"; $values[] = $param1;}
        if ($param1 != null) { $where[] = "param2=?"; $values[] = $param2;}

        $where[] = "role IN(%s)";
        $values[] = $roles;



        $values[0] = "DELETE FROM ".$this->pServer->getPrefix()."roles WHERE ".implode(" AND ",$where);
        $result = call_user_func_array(array($this->pServer, 'query'), $values);

        if ($result["result"]) {

        }else {
            ErrorCodes::gi()->addError(new ErrorInfo("ROLE_VALIDATE","error remove roles",ErrorCodes::$SERVER_REQUEST_ERROR,ErrorCodes::$LOG_FILE));
        }
        return $result;
    }


    public function addOwnerRoles($resourseID,$param1,$param2,$roles) {
        $roleID = $this->role_id;
        $this->addRoles_local($roleID,$resourseID,$param1,$param2,$roles);
    }

    public function addRoles_local($role_id,$resourseID,$param1,$param2,$roles) {
        $result = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."roles
                                                  WHERE ID=? AND id_resource = ? AND param1 = ? AND param2 = ? AND role IN(%s)",$role_id,$resourseID,$param1,$param2,$roles);
        $rolesNeedle = array();
        if ($result["result"]) {
            $data = $result["data"];
            for ($j = 0;$j < count($roles);$j++) {
                $roleID = $roles[$j];
                $founded = false;
                for ($i = 0; $i < count($data);$i++) {
                    if ($roleID == $data[$i]["role"])
                        $founded = true;
                }
                if (!$founded)$rolesNeedle[] = $roleID;
            }
            for ($i = 0;$i < count($rolesNeedle); $i++) {
                $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."roles (ID,id_resource,param1,param2,role) VALUES(?,?,?,?,?)",$role_id,$resourseID,$param1,$param2,$rolesNeedle[$i]);
            }
        }else {
            ErrorCodes::gi()->addError(new ErrorInfo("ROLE_VALIDATE","error add owner roles",ErrorCodes::$SERVER_REQUEST_ERROR,ErrorCodes::$LOG_FILE));
            return $result;
        }
    }


    public function validate($roleInfo,&$dataIn,$apiInfo) {
        if (!isset($roleInfo)) return true;
        $where = [];
        $params = [];
        $params[] = 0;

        // user auth validate
        if ($this->userInfo === NULL) {
            $this->userInfo = $this->getUserInfo();
            if (!$this->userInfo["result"]){
                ErrorCodes::gi()->addError(new ErrorInfo("ROLE_VALIDATE",$this->userInfo["error"],$this->userInfo["errorCode"],ErrorCodes::$LOG_FILE));
                return false;
            }else {
                date_default_timezone_set("UTC");
                if (time() >= $this->userInfo["token_time"]) {
                    // expired !
                    ErrorCodes::gi()->addError(new ErrorInfo("ROLE_VALIDATE","auth expired",ErrorCodes::$AUTH_EXPIRED,ErrorCodes::$LOG_FILE));
                    return false;
                }
            }
        }

        $where[] = "ID = ?";
        $params[] = $this->role_id;


        // get id field validate
        $id = $roleInfo["param2"];
        if ($id != -1) {
            // get type for validated field
            if (isset($apiInfo["require"][$id])) {
                $type = $apiInfo["require"][$id];
                // get parsed data to validate
                $validArray = $this->{'get'.$type}($dataIn[$id]);
            }else {
                $validArray = null;
            }
        } else {
            $validArray = null;
        }

        // fill

        $where[]  = "(id_resource = ? OR id_resource=-1)";
        $params[] = $roleInfo["resourceID"];

        $where[]  = "(param1 = ? OR param1=-1)";
        $params[] = isset($dataIn[$roleInfo["param1"]]) ?$dataIn[$roleInfo["param1"]] : $roleInfo["param1"];

        if ($validArray) {
            $where[]  = "(param2 IN(%s) OR param2=-1)";
            $params[] = $validArray;
        }

        $where[]  = "(role = ? OR role = -1)";
        $params[] = $roleInfo["roleID"];

        $params[0] = "SELECT * FROM ".$this->pServer->getPrefix()."roles WHERE ".implode(" AND ",$where);
        $result = call_user_func_array(array($this->pServer, 'select'), $params);



        if ($result["result"]) {
            $data = $result["data"];
            if (count($data)) {
                if (!$validArray) {
                    foreach ($data as $key=>$value) {
                        $validArray[] = $value["param2"];
                    }
                }
                $dataAccepted = [];
                for ($i = 0; $i < count($validArray);$i++) {
                    $paramRequest = $validArray[$i];
                    $resultCheck = false;
                    for ($j = 0;$j < count($data);$j++) {
                        $accepted = $data[$j]["param2"];
                        if ($accepted == -1) {
                            return true;
                        }

                        if ($accepted == $paramRequest) {
                            $resultCheck = true;
                        }
                    }
                    if (!$resultCheck) {
                        ErrorCodes::gi()->addError(new ErrorInfo("ROLE_VALIDATE","have denied request data reqID:".$paramRequest,ErrorCodes::$DENIED_REQUEST_DATA,ErrorCodes::$LOG_FILE));
                    }else {
                        $dataAccepted[] = $paramRequest;
                    }

                }
                $id = $roleInfo["param2"];
                $dataIn[$id] = implode(",",$dataAccepted);
                return true;
            }else {
                ErrorCodes::gi()->addError(new ErrorInfo("ROLE_VALIDATE","have denied request data reqID:".$validArray[0],ErrorCodes::$DENIED_REQUEST_DATA,ErrorCodes::$LOG_FILE));
            }
        }else {
            ErrorCodes::gi()->addError(new ErrorInfo("ROLE_VALIDATE","request to rules error",ErrorCodes::$SERVER_REQUEST_ERROR,ErrorCodes::$LOG_FILE));
        }
        return false;
    }

}