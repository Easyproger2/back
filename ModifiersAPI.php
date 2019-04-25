<?php
/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 26/10/2018
 * Time: 10:07
 */

require_once("m.php");
class ModifiersAPI
{
    private $resourceID;

    /* @var Server $pServer */
    private $pServer;
    /* @var Cache $pCache */
    private $pCache;

    function __construct(Server $server, Cache $cache)
    {
        $this->resourceID = Consts::$RESOURCE_MODIFIERS_ID;
        $this->pServer = $server;
        $this->pCache = $cache;
    }

    //getModifiersList


    public function getModifiersList($getter) {

        return $this->getModifiersList_local($getter["-1"]);
    }

    public function getModifiersList_local($filterModifiers) {

        if (isset($filterModifiers)) {
            $result  = $this->pServer->select('SELECT * FROM '.Config::$prefix.'modifiers WHERE ID IN('.$filterModifiers.')') ;
            if (!$result["result"]) return $result;
        }else {
            $result = $this->pServer->select('SELECT * FROM '.Config::$prefix.'modifiers');
            if (!$result["result"]) return $result;
        }

        if ($result["result"]) {
            $data = $result["data"];
            $dataOut = [];
            for ($i = 0;$i<count($data);$i++) {
                $attr = $data[$i];
                $dataOut[$attr["ID"]] = $attr;
            }
            return array("result"=>true,"data"=>$dataOut);
        }

        return $result;
    }

    public function getModifiersForObject($getter) {
        $id = $getter['id'];
        return $this->getModifiersForObject_local($id);
    }

    public function getModifiersForObject_local($id)
    {
        $result = $this->pServer->select('SELECT s.*, t.* from '.Config::$prefix.'styles_modifiers s
                                                JOIN '.Config::$prefix.'modifiers t ON (s.modifier_id = t.ID)
                                                where s.ID=?',$id);
        if ($result["result"]) {
            $data = $result["data"];
            $dataOut = [];
            for ($i = 0;$i<count($data);$i++) {
                $attr = $data[$i];
                $dataOut[$attr["ID"]] = $attr;
            }
            return array("result"=>true,"data"=>$dataOut);
        }
        return $result;
    }


}