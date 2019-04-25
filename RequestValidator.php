<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 27.02.15
 * Time: 3:41
 * To change this template use File | Settings | File Templates.
 */

class RequestValidator {
    function __construct() {
    }

    private function validate_notValidate($param) {
        return true;
    }

    private function validate_string($param) {
        return is_string($param) || is_numeric($param);
    }

    private function validate_bool($param) {
        return is_bool($param);
    }

    private function validate_numbers($param) {

        $array = explode(",",$param);
        $res = true;
        for ($i = 0;$i < count($array);$i++) {
            $res = is_numeric($array[$i]) && $res;
        }
        return $res;
    }

    private function validate_arrayNumbers($param) {
        $array = $param;
        $res = true;
        for ($i = 0;$i < count($array);$i++) {
            $res = is_numeric($array[$i]) && $res;
        }
        return $res;
    }

    private function validate_json(&$errorData,$json,$param,$prevLvl) {

        if (count($param) && !$param[0]) $param = [$param];

        for ($i = 0;$i<count($param);$i++) {
            $obj = $param[$i];


            foreach ($json as $key=>$value) {

                $isOptional = strpos($key,"__@optional__") === 0;
                $jsonKey = $key;
                if ($isOptional) {
                    $key = substr($key,strlen("__@optional__"));
                }
                $isPrevLvl = strpos($key,"__@PL__") === 0;
                if ($isPrevLvl) {
                    $key = substr($key,strlen("__@PL__"));
                    $key = isset($prevLvl[$key]) ? $prevLvl[$key] : $key;
                }


                if (isset($obj[$key])) {



                    $result = is_array($json[$jsonKey]) ? $this->validate_json($errorData,$json[$jsonKey],$obj[$key],$obj) : $this->{'validate_'.$json[$jsonKey]}($obj[$key]);

                    if ($result) {

                    }else {
                        $errorData[$key] = "error data:".$obj[$key]." need:".$json[$jsonKey];
                        return false;
                    }
                }else if ($isOptional){
                    // optional not present skip
                    return true;
                }else {
                    $errorData[$key] = "not exist:".$jsonKey;
                    return false;
                }
            }

        }
        return true;
    }

    private function validateParams(&$parsedData,$keys,$data,$dataValidate,$isRequire) {
        for ($i = 0; $i < count($keys); $i++) {
            $paramID = $keys[$i];
            $type    = $data[$paramID];



            $isSetData    = isset($dataValidate[$paramID]);

            if (isJsonString($type)) {
                $parsedResult = $isSetData ? $this->validate_json($errorData,json_decode($type,true),$dataValidate[$paramID],$dataValidate[$paramID]) : true;
            }else if (is_array($type)) {
                $parsedResult = $isSetData ? $this->validate_json($errorData,$type,$dataValidate[$paramID],$dataValidate[$paramID]) : true;
            }else {
                $parsedResult = $isSetData ? $this->{'validate_'.$type}($dataValidate[$paramID]) : true;
            }

            if (($isSetData) && $parsedResult) {
                $parsedData[$paramID] = $dataValidate[$paramID];
            }else {
                // require filed !
                if (!$isSetData && $isRequire) {
                    ErrorCodes::gi()->addError(new ErrorInfo("REQUEST_VALIDATE","require param not have in request ".$paramID,ErrorCodes::$PARSE_ERROR,ErrorCodes::$LOG_FILE));
                }else if (!$parsedResult) {
                    ErrorCodes::gi()->addError(new ErrorInfo("REQUEST_VALIDATE", $errorData,ErrorCodes::$PARSE_ERROR,ErrorCodes::$LOG_FILE));
                }
            }
        }
    }

    public function validate($dataValidate,$validateInfo) {

        $parsedData = array();

        $requires = $validateInfo["require"];
        $requireKeys = array_keys($requires);
        $this->validateParams($parsedData,$requireKeys,$requires,$dataValidate,true);

        $optional = $validateInfo["optional"];
        $optionalKeys = array_keys($optional);
        $this->validateParams($parsedData,$optionalKeys,$optional,$dataValidate,false);

        if (!ErrorCodes::gi()->checkErrorByKey("REQUEST_VALIDATE")) {
            return array("result"=>true,"data"=>$parsedData);
        }
        return null;
    }
}