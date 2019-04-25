<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 26.03.15
 * Time: 20:07
 * To change this template use File | Settings | File Templates.
 */

// include our OAuth2 Server object
require_once ('../oauth2server.php');

// Handle a request for an OAuth2.0 Access Token and send the response to the client

$serverOAuth2->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();