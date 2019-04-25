<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 28.03.15
 * Time: 18:32
 * To change this template use File | Settings | File Templates.
 */
// include our OAuth2 Server object
require_once ('../oauth2server.php');

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// validate the authorize request
if (!$serverOAuth2->validateAuthorizeRequest($request, $response)) {
    $response->send();
    die;
}

// print the authorization code if the user has authorized your client
$is_authorized = true;
$serverOAuth2->handleAuthorizeRequest($request, $response, $is_authorized);
if ($is_authorized) {
    // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
    $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=')+5, 40);
    echo json_encode(array("code"=>$code));
    die();
}
$response->send();