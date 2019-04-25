<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 26.03.15
 * Time: 22:33
 * To change this template use File | Settings | File Templates.
 */
require_once("m.php");
class UserAPI {
    private $resourceID;

    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;

    function __construct(Server $server,Cache $cache) {
        $this->resourceID = Consts::$RESOURCE_USER_ID;
        $this->pServer = $server;
        $this->pCache = $cache;
    }


    // need create
    public function getClient($getter) {

    }
    public function getClient_local($client) {
        $result = $this->pServer->select('SELECT * from '.Config::$dbclients.' where client_id=?',$client);
        if ($result["result"]) {
            $data = $result["data"];
            if (count($data)) {
                return array("result"=>true,"data"=>array("client_id"=>$data[0]["client_id"]));
            }
        }
        return ErrorCodes::gi()->executeShort(0,"client not found",ErrorCodes::$CLIENT_NOT_FOUND);
    }
    // need create
    public function setClient($getter) {

    }
    public function setClient_local($client, $secret, $redirect_uri, $grant_types = null,$scope = null,$user_id = null)
    {
        $user = $this->getClient_local($client);
        if ($user["result"]) {
            $this->pServer->query('UPDATE '.Config::$dbclients.' SET client_secret=?, redirect_uri=?, grant_types=?, scope=?,user_id=? where client_id=?',$secret,$redirect_uri,$grant_types,$scope,$user_id,$client);
        } else {
            $this->pServer->insert('INSERT INTO '.Config::$dbclients.' (client_id, client_secret, redirect_uri, grant_types,scope,user_id) VALUES (?, ?, ?, ?, ?, ?)',$client,$secret,$redirect_uri,$grant_types,$scope,$user_id);
        }
    }


    public function getCurrentUser($getter) {


        return $this->getCurrentUser_local(ErrorCodes::gi()->getUserInfo()["user_id"]);
    }

    public function getCurrentUser_local($user_id) {



        $result  = $this->pServer->select("SELECT first_name,last_name,role_id FROM ".Config::$dbusers." WHERE username=?",$user_id) ;
        return $result;
    }

    public function getUserInfoByID($getter) {
        $user_id = $getter["userID"];
        return $this->getListUsers_local([$user_id]);
    }

    public function getCurrentUserInfo($getter) {
        /* @var RolesValidator $rolesValidator*/
        $rolesValidator = $this->pCache->getCachedClass("RolesValidator");
        $user_id = $rolesValidator->userInfo["user_id"];
        return $this->getListUsers_local([$user_id]);
    }

    public function getListUsers($getter) {
        return $this->getListUsers_local($getter["-1"]);
    }

    public function getListUsers_local($filteredUsers) {
        if (isset($filteredUsers)) {
            $result  = $this->pServer->select("SELECT firstName,lastName,roleID FROM ".Config::$dbusers." WHERE username IN(".implode(",", $filteredUsers).")") ;
            return $result;
        }else {
            $result = $this->pServer->select("SELECT firstName,lastName,roleID FROM ".Config::$dbusers);
            return $result;
        }
    }

    public function getUser($getter) {
        $username        = $getter['login'];
        $password        = $getter['pass'];
        $password = sha1($password);
        return $this->getUser_local($username,$password);
    }

    public function getUser_local($username,$password)
    {

        $result = $this->pServer->select('SELECT * from '.Config::$dbusers.' where username=? AND password=?',$username,$password);
        if ($result["result"]) {
            $data = $result["data"];
            if (count($data)) {
                return array("result"=>true,"data"=>$data[0]);
            }
        }
        return ErrorCodes::gi()->executeShort(0,"user not found",ErrorCodes::$USER_NOT_FOUND);
    }


    public function setUser($getter) {
        $username        = $getter['login'];
        $password        = $getter['pass'];
        $firstName       = isset($getter['firstName']) ? $getter['firstName'] : null;
        $lastName        = isset($getter['lastName']) ? $getter['lastName'] : null;
        $roleID          = isset($getter['roleID']) ? $getter['roleID'] : 0;

        return $this->setUser_local($username, $password, $firstName, $lastName,$roleID);

    }

    public function setUser_local($username, $password, $firstName = null, $lastName = null,$roleID=0)
    {
        // do not store in plaintext
        $password = sha1($password);
        // if it exists, update it.
        $user = $this->getUser_local($username,$password);
        if ($user["result"]) {
            $this->pServer->query('UPDATE '.Config::$dbusers.' SET password=?, first_name=?, last_name=?,role_id=? where username=?',$password,$firstName,$lastName,$roleID,$username);
        } else {
            $this->pServer->insert('INSERT INTO '.Config::$dbusers.' (username, password, first_name, last_name,role_id) VALUES (?, ?, ?, ?,?)',$username,$password,$firstName,$lastName,$roleID);
        }
    }

    private function requestTokenWithAuthCode($client_id,$client_secret,$redirect_uri) {

        $token_url   = pathinfo(curPageURL())['dirname']."/authorize.php?response_type=code&client_id=testclient&state=xyz";
        $contentInfo = file_get_contents($token_url);
        $authJson    = json_decode($contentInfo,true);
        if (!$authJson || $authJson === NULL) {
            return ErrorCodes::gi()->executeShort(0,$contentInfo,ErrorCodes::$GET_TOKEN);
        }

        $code = $authJson["code"];

        $token_url = pathinfo(curPageURL())['dirname']."/token.php";
        $ch = curl_init( $token_url );

        $post_array = array(
            "grant_type"    => "authorization_code",
            "code"          => $code,
            "client_id"     => $client_id,
            "client_secret" => $client_secret,
            "redirect_uri"  => $redirect_uri
        );

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_array));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $content = curl_exec( $ch );
        curl_close( $ch );

        $userInfo = json_decode($content,true);

        if (!$userInfo || $userInfo === NULL) {
            return ErrorCodes::gi()->executeShort(0,$content,ErrorCodes::$GET_TOKEN);
        }
        return $userInfo;
    }

    private function requestTokenWithUserCredentials($userLogin,$userPassword,$client_id,$client_secret) {
        $token_url = pathinfo(curPageURL())['dirname']."/token.php";
        $ch = curl_init( $token_url );


        $post_array = array(
            "grant_type"    => "password",
            "client_id"     => $client_id,
            "client_secret" => $client_secret,
            "username"      => $userLogin,
            "password"      => $userPassword
        );

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_array));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $content = curl_exec( $ch );
        curl_close( $ch );

        $userInfo = json_decode($content,true);

        if (!$userInfo || $userInfo === NULL) {
            return ErrorCodes::gi()->executeShort(0,$content,ErrorCodes::$GET_TOKEN);
        }
        return $userInfo;
    }

    private function requestTokenWithRefreshToken($refreshToken,$client_id,$client_secret) {
        $token_url = pathinfo(curPageURL())['dirname']."/token.php";
        $ch = curl_init( $token_url );

        $post_array = array(
            "grant_type"    => "refresh_token",
            "client_id"     => $client_id,
            "client_secret" => $client_secret,
            "refresh_token" => $refreshToken
        );

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_array));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $content = curl_exec( $ch );
        curl_close( $ch );

        $userInfo = json_decode($content,true);

        if (!$userInfo || $userInfo === NULL) {
            return ErrorCodes::gi()->executeShort(0,$content,ErrorCodes::$GET_TOKEN);
        }
        return $userInfo;
    }

    public function refreshToken($getter) {
        $refresh_token = $getter["token"];

        $client_id     = $getter["client_id"];
        $client_secret = $getter["client_secret"];

        return $this->refreshToken_local($refresh_token,$client_id,$client_secret);
    }
    public function refreshToken_local($refresh_token,$client_id,$client_secret,$gen_now=false) {

        if (!isset($client_id))     $client_id     = "notSet";
        if (!isset($client_secret)) $client_secret = "notSet";

        $userSessionID = Utils::generateUserSessionID();

        $client_id.=$userSessionID;


        if (!$gen_now) {
            date_default_timezone_set("UTC");
            $nowFormat = date('Y-m-d H:i:s');
            $result = $this->pServer->select("SELECT access.access_token as access_token,refresh.refresh_token as refresh_token
                                            FROM ".Config::$dbrefresh." refresh
                                       left join ".Config::$dbtokens."   access ON (refresh.client_id=access.client_id AND refresh.user_id=access.user_id AND access.expires>?)
                                            WHERE refresh.refresh_token=? AND refresh.client_id=? AND refresh.expires>?",$nowFormat,$refresh_token,$client_id,$nowFormat);

            if ($result["result"]) {
                if (count($result["data"]) && $result["data"][0]["access_token"]) {
                    $data = $result["data"][0];
                    return array("result"=>true,"data"=>$data);
                }
            }
        }

        $token = $this->requestTokenWithRefreshToken($refresh_token,$client_id,$client_secret);

        if ($token["error"]) {
            http_response_code(401);
            return ErrorCodes::gi()->executeShort(0,$token["error_description"],ErrorCodes::$errorsOAUTH2IDS[$token["error"]]);
        }

        $result = array("access_token"=>$token["access_token"],"refresh_token"=>$refresh_token);
        return array("result"=>true,"data"=>$result);
    }

    public function auth($getter) {
        $userLogin     = $getter["login"];
        $userPassword  = $getter["pass"];

        $client_id     = $getter["client_id"];
        $client_secret = $getter["client_secret"];
        $redirect_uri  = $getter["redirect_uri"];

        return $this->auth_local($userLogin,$userPassword,$client_id,$client_secret,$redirect_uri);
    }

    public function auth_local($userLogin,$userPassword,$client_id,$client_secret,$redirect_uri) {

        if (!isset($client_id))     $client_id     = "notSet";
        if (!isset($client_secret)) $client_secret = "notSet";
        if (!isset($redirect_uri))  $redirect_uri  = "notSet";

        $clearClient_ID = $client_id;
        $userSessionID = Utils::generateUserSessionID();

        $client_id.=$userSessionID;

        $password = sha1($userPassword);

        $user   = $this->getUser_local($userLogin,$password);
        $client = $this->getClient_local($client_id);


        if (!$user["result"]) return $user;

        if ($user["result"] && !$client["result"]) {
            $this->setClient_local($client_id,$client_secret,$redirect_uri);
        }

        date_default_timezone_set("UTC");
        $nowFormat = date('Y-m-d H:i:s');
        $result = $this->pServer->select("SELECT access.access_token as access_token,refresh.refresh_token as refresh_token
                                            FROM ".Config::$dbtokens."  access
                                       left join ".Config::$dbrefresh." refresh ON (access.client_id=refresh.client_id AND access.user_id=refresh.user_id AND refresh.expires>?)
                                            WHERE access.client_id=? AND access.user_id=? AND access.expires>?",$nowFormat,$client_id,$userLogin,$nowFormat);

        if ($result["result"]) {
            if (count($result["data"]) && $result["data"][0]["refresh_token"]) {
                $data = $result["data"][0];
                return array("result"=>true,"data"=>$data);
            }
        }


        $result = $this->pServer->select("SELECT refresh.refresh_token as refresh_token
                                            FROM ".Config::$dbrefresh." refresh
                                            WHERE refresh.client_id=? AND refresh.user_id=? AND refresh.expires>?",$client_id,$userLogin,$nowFormat);

        if ($result["result"]) {
            if (count($result["data"])) {
                $data = $result["data"][0];
                $userInfo = $this->refreshToken_local($data["refresh_token"],$clearClient_ID,$client_secret,true);

                if (!$userInfo["error"]) {
                    return $userInfo;
                }

            }
        }


        $userInfo = $this->requestTokenWithUserCredentials($userLogin,$userPassword,$client_id,$client_secret);

        if ($userInfo["error"]) {
            http_response_code(401);
            return ErrorCodes::gi()->executeShort(0,$userInfo["error_description"],ErrorCodes::$errorsOAUTH2IDS[$userInfo["error"]]);
        }
        $result = array("access_token"=>$userInfo["access_token"],"refresh_token"=>$userInfo["refresh_token"]);
        return array("result"=>true,"data"=>$result);
    }


}