<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 26.03.15
 * Time: 20:14
 * To change this template use File | Settings | File Templates.
 */

// include our OAuth2 Server object
require_once ('../oauth2server.php');

// Handle a request to a resource and authenticate the access token


if (!$serverOAuth2->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $serverOAuth2->getResponse()->send();
    die;
}

$token = $serverOAuth2->getAccessTokenData(OAuth2\Request::createFromGlobals());

echo json_encode($token);