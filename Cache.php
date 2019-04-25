<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 12.02.15
 * Time: 0:31
 * To change this template use File | Settings | File Templates.
 */
require_once("m.php");

class Cache {
    /* @var Server $pServer*/
    private $pServer;
    private $cacheClasses = array();
    function __construct(Server $server) {
        $this->pServer = $server;
    }

    public function getCachedClass($name) {
        if (!$this->cacheClasses[$name]) {
            $this->cacheClasses[$name] = new $name($this->pServer,$this);
        }
        return $this->cacheClasses[$name];
    }

}