<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 29.01.15
 * Time: 0:54
 * To change this template use File | Settings | File Templates.
 */







error_reporting(0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Session-Token,X-Session-Refresh-Token, Content-Type');

header('Content-Type: application/json');




if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    die(json_encode(array('answer' => 'OPTIONS request complete')));
}


define("serverApi",1);

require_once("m.php");


$apiInfoInstance = new ApiInfo();

$api = $apiInfoInstance->getApiInfo();


/* @var Server $server */
$server = new Server();

/* @var Cache $cache */
$cache  = new Cache($server);


/* @var RolesValidator $roleValidator */
$roleValidator = $cache->getCachedClass("RolesValidator");

// here read token and push to Roles

if (!function_exists('getallheaders')) {
    $headers = array();
    foreach ($_SERVER as $name => $value) {
        /* RFC2616 (HTTP/1.1) defines header fields as case-insensitive entities. */
        if (strtolower(substr($name, 0, 5)) == 'http_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    $request_headers = $headers;
} else {
    $request_headers = getallheaders();
}


$roleValidator->setToken($request_headers["X-Session-Token"]);



ErrorCodes::gi()->setUserInfo($roleValidator->getUserInfo(false));

/* @var RequestValidator $requestValidator */
$requestValidator = new RequestValidator();



$answer = array();


// test zone

if (isset($_GET["test"])) {

    $timeValidator = new DatesAPI($server,$cache);


    date_default_timezone_set(Config::$timeZoneDef);
    $startTimeDay  = strtotime(date('Y-m-d H:i:s',mktime(0, 0, 0,    date('n'), date('j'))));
    $endTimeDay    = strtotime(date('Y-m-d H:i:s',mktime(23, 59, 59, date('n'), date('j'))));

    $timeLine = $timeValidator->getTimeLine2($startTimeDay,$endTimeDay);

    //for ($i=0;$i< count($timeLine);$i++) {
    //    /* @var StoredDataTL $obj*/
    //    $obj = $timeLine[$i];
    //    echo date('Y-m-d H:i:s',$obj->startTime)." entryID ".$obj->entryID."\n";
    //}
    die();
}

if (isset($_GET["expire_1"])) {
    if (!$roleValidator->userInfo) {
        echo json_encode(array("люк я не твой отец !"));
        die();
    }
    $token = $request_headers["X-Session-Token"];
    $client_id = $roleValidator->userInfo["client_id"];
    $user_id   = $roleValidator->userInfo["user_id"];

    date_default_timezone_set("UTC");
    $expire = date('Y-m-d H:i:s');
    $server->query('UPDATE '.Config::$dbtokens.' SET expires=? where client_id=? AND user_id=?',$expire,$client_id,$user_id);

}

if (isset($_GET["expire_2"])) {

    $token = isset( $request_headers["X-Session-Token"]) ? $request_headers["X-Session-Token"] : $_GET["token"];
    date_default_timezone_set("UTC");
    $expire = date('Y-m-d H:i:s');
    $server->query('UPDATE '.Config::$dbtokens.' SET expires=? where access_token=?',$expire,$token);

}





// end test zone
if (true) {

    if (empty($_POST)) {
        $getter = json_decode(file_get_contents('php://input'),true);
    }else {
        $getter = $_POST["data"];
    }


    for ($i = 0;$i < count($getter);$i++) {

        $request = $getter[$i]["name"];
        $udid = $getter[$i]["udid"];

        ErrorCodes::get_instance()->clear();
        ErrorCodes::get_instance()->setRequest($request);
        $apiData = $api[$request];
        if (!$apiData) {
            $error         = ErrorCodes::gi()->executeShort(0,"sorry we not have this API",ErrorCodes::$NOT_HAVE_THIS_API);
            $error["name"] = $request;
            $error["udid"] = $udid;
            $answer[]      = $error;
            continue;
        }

        $resultValidateRequest = $requestValidator->validate($getter[$i],$apiData);
        if ($resultValidateRequest) {
            $dataValidated       = $resultValidateRequest["data"];

            $resultValidateRoles = $roleValidator->validate($apiData["roleInfo"],$dataValidated,$apiData);

            if ($resultValidateRoles) {
                $resultRequest = $cache->getCachedClass($apiData["class"])->{$request}($dataValidated,$i);

                if (ErrorCodes::gi()->checkErrorByKey("ROLE_VALIDATE")) {
                    ErrorCodes::gi()->executeErrorByKey("ROLE_VALIDATE",$resultRequest["data"]);
                }


                if (count(ErrorCodes::gi()->errorMessages)) {
                    $answer[] = array("udid"=>$udid,"name"=>$request,"result"=>false,"error"=>ErrorCodes::gi()->errorMessages);
                }else {
                    $answer[] = array("udid"=>$udid,"name"=>$request,"data"=>$resultRequest,"result"=>$resultRequest["result"]);
                }
            }else {
                if (ErrorCodes::gi()->checkErrorByKey("ROLE_VALIDATE")) {
                    $error = ErrorCodes::gi()->executeErrorByKey("ROLE_VALIDATE");
                }else {
                    $error = [];
                    $error["name"] = $request;
                    $error["udid"] = $udid;
                }
                $answer[]      = $error;
            }
        }else {
            $error = ErrorCodes::gi()->executeErrorByKey("REQUEST_VALIDATE");
            $error["name"] = $request;
            $error["udid"] = $udid;
            $answer[] = $error;
        }

    }
}
$server->disconnect();



$jsonData = json_encode($answer);

$jsonData = str_replace(',"'.Config::$magicRemove.'"','',$jsonData);
$jsonData = str_replace(':"'.Config::$magicRemove.'",','',$jsonData);
$jsonData = str_replace(':"'.Config::$magicRemove.'"','',$jsonData);
$jsonData = str_replace('"'.Config::$magicRemove.'"','',$jsonData);

echo $jsonData;




