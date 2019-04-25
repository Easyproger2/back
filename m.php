<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 28.01.15
 * Time: 22:52
 * To change this template use File | Settings | File Templates.
 */

if (!defined("serverApi")) die();

function __autoload($className) {
    if (file_exists($className . '.php')) {
        require $className . '.php';
        return true;
    }
    return false;
}


function curPageURL() {
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

function isJsonObject($string) {
    return is_array($string);
}

function isJsonString($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}