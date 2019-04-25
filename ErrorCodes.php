<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 27.03.15
 * Time: 19:14
 * To change this template use File | Settings | File Templates.
 */


class ErrorInfo {

    public $message;
    public $key;
    public $flags;
    public $code;

    function __construct($errorKey,$errorMsg,$errorCode,$flags) {
        $this->key     = $errorKey;
        $this->flags   = $flags;
        $this->message = $errorMsg;
        $this->code    = $errorCode;
    }

}


class ErrorCodes {

    private $errors = [];

    /* @var Server $pServer*/
    private $pServer = null;


    public static $LOG_MYSQL = 1;
    public static $LOG_EMAIL = 2;
    public static $LOG_FILE  = 4;



    public static $DENIED_REQUEST_DATA       = 0;
    public static $SERVER_REQUEST_ERROR      = 1;
    public static $PARSE_ERROR               = 2;
    public static $AUTH_EXPIRED              = 3;
    public static $NOT_LOGGED                = 4;
    public static $GET_TOKEN                 = 5;
    public static $GET_USER_INFO             = 6;
    public static $CANT_CONNECT_TO_SERVER_DB = 7;
    public static $ERROR_REQUEST             = 8;
    public static $NOT_HAVE_THIS_API         = 9;
    public static $USER_NOT_FOUND            = 10;

    public static $errorsOAUTH2IDS = array(
        "invalid_request"                    =>11,
        "invalid_client"                     =>12,
        "invalid_grant"                      =>13,
        "unauthorized_client"                =>14,
        "unsupported_grant_type"             =>15,
        "invalid_token"                      =>16,
        "expired_token"                      =>17,
        "insufficient_scope"                 =>18,
        "malformed_token"                    =>19
    );

    public static $CLIENT_NOT_FOUND          = 20;





    private static $instance = null;

    private $userInfo = null;
    private $request = null;

    private $logger = null;


    public $errorMessages = [];


    public function setRequest($r) {
        $this->errorMessages = [];
        $this->request = $r;
    }
    public function getRequest() {
        return $this->request;
    }

    public function setuserInfo($userInfo) {
        $this->userInfo = $userInfo;
    }

    public function getUserInfo() {
        return $this->userInfo;
    }

    public function setServer(Server $server) {
        $this->pServer = $server;
    }

    public function executeShort($errorFlags,$errorMessage,$errorCode,$inputArray = null) {
        $client_id    = ErrorCodes::gi()->userInfo["client_id"];
        $user_id      = ErrorCodes::gi()->userInfo["user_id"];
        $request      = ErrorCodes::gi()->getRequest();
        return $this->execute($client_id,$user_id,$request,$errorMessage,$errorFlags,$errorCode,$inputArray);
    }

    public function execute($client_id,$user_id,$request,$errorMessage,$errorFlags,$errorCode,$inputArray = null) {
        $errorMessageRaw = $errorMessage;
        $errorMessage = json_encode($errorMessage) ?json_encode($errorMessage) : $errorMessage;

        $msg = [];
        $msg[] = "client_id:".$client_id;
        $msg[] = "userID:"   .$user_id;
        $msg[] = "request:"  .$request;
        $msg[] = "info:"     .$errorMessage;

        if (($errorFlags & ErrorCodes::$LOG_MYSQL) == ErrorCodes::$LOG_MYSQL) {
            if ($this->pServer) {
                $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix().Config::$error_table." (code,message,request,user_id,client_id) VALUES(?,?,?,?,?)",$errorCode,$errorMessage,$request,$user_id,$client_id);
            }
        }


        if (($errorFlags & ErrorCodes::$LOG_EMAIL) == ErrorCodes::$LOG_EMAIL) {
            //$this->mail_utf8(Config::$error_email,'media_brain_error','media@brain.ru','errorID:'.$errorCode,implode(" ",$msg));
        }

        if (($errorFlags & ErrorCodes::$LOG_FILE) == ErrorCodes::$LOG_FILE) {
            //ErrorCodes::gi()->getLogger()->error(implode(" ",$msg));
        }

        if ($inputArray) {
            $inputArray["result"]    = false;
            $inputArray["error"]     = $errorMessage;
            $inputArray["errorCode"] = $errorCode;
        }


        $this->errorMessages[] = array("error"=>$errorMessageRaw,"errorCode"=>$errorCode);

        return array("result"=>false,"error"=>$errorMessageRaw,"errorCode"=>$errorCode);
    }


    public function executeErrorByKey($key,$inputArray = null) {
        if ($this->checkErrorByKey($key)) {
            /* @var ErrorInfo $error*/
            $error = $this->errors[$key];
            unset($this->errors[$key]);

            $client_id    = ErrorCodes::gi()->userInfo["client_id"];
            $user_id      = ErrorCodes::gi()->userInfo["user_id"];
            $request      = ErrorCodes::gi()->getRequest();

            $errorMessage = $error->message;
            $errorCode    = $error->code;
            $errorFlags   = $error->flags;

            return $this->execute($client_id,$user_id,$request,$errorMessage,$errorFlags,$errorCode,$inputArray);
        }else {
            return array("result"=>false,"error"=>"unknown error","errorCode"=>-1);
        }
    }


    public function mail_utf8($to, $from_user, $from_email,
                       $subject = '(No subject)', $message = '')
    {
        $from_user = "=?UTF-8?B?".base64_encode($from_user)."?=";
        $subject = "=?UTF-8?B?".base64_encode($subject)."?=";

        $headers = "From: $from_user <$from_email>\r\n".
            "MIME-Version: 1.0" . "\r\n" .
            "Content-type: text/html; charset=UTF-8" . "\r\n";

        return mail($to, $subject, $message, $headers);
    }

    public function clear() {
        $this->errors = [];
        $this->errorMessages = [];
    }

    public function addError(ErrorInfo $error) {
        $this->errors[$error->key] = $error;
    }

    public function checkErrorByKey($key) {
        return isset($this->errors[$key]);
    }

    public function getLogger() {
        if (!$this->logger) {
            require_once("../Logger.php");
            $config = array(
                'use'       => array('log_error'),
                'common'    => array('log_format' => '%d - %m','add_var' => false),
                'add_var'   => false,
                'appenders' => array(
                    'log_error' => array( 'type' => 'file', 'filepath' => Config::$error_file, 'min_log_level' => 'error')
                )
            );
            date_default_timezone_set("Europe/Moscow");
            $this->logger = Logger::get_logger();
            $this->logger->set_config($config);
        }
        return $this->logger;
    }


    static public function gi() {
        return ErrorCodes::get_instance();
    }

    static public function get_instance() {
        if( ! self::$instance ) self::$instance = new self;
        return self::$instance;
    }


}