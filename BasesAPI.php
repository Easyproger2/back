<?php

/**
 * Created by PhpStorm.
 * User: easyproger

 */

require_once("m.php");

class BasesAPI
{
    private $resourceID;

    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;
    /* @var StyleData $pStyleData*/
    private $pStyleData;
    /* @var RolesValidator $pRolesValid*/
    private $pRolesValid;


    public $some;

    function __construct(Server $server,Cache $cache) {
        $this->resourceID = Consts::$RESOURCE_BASES_ID;

        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData  = $this->pCache->getCachedClass("StyleData");
    }

    public function getBasesInfo($getter) {
        $fieldsAsObjects = $getter["fieldsAsObjects"];
        return $this->getBasesInfo_local($getter["-1"],$fieldsAsObjects);
    }

    public function getBasesInfo_local($filtered,$fieldsAsObjects) {


        if (isset($filtered)) {
            $result  = $this->pServer->select("SELECT * FROM base_sys_new_prp WHERE ID IN(".$filtered.")") ;
            if (!$result["result"]) return $result;
        }else {
            $result  = $this->pServer->select("SELECT * FROM base_sys_new_prp") ;
            if (!$result["result"]) return $result;
        }



        /* @var ObjectsAPI $objectsAPI*/

        $objectsAPI = $this->pCache->getCachedClass("ObjectsAPI");




        $data = $result["data"];
        $answer = [];
        for ($indexData= 0; $indexData < count($data);$indexData++) {
            $row = $data[$indexData];

            $properties = json_decode($row["properties"],true);
            $fields = json_decode($row["fields"],true);

            $objects = [];
            $fieldsFormated = [];
            foreach ($fields as $key=>$value) {
                if (isset($fieldsAsObjects) && $fieldsAsObjects) {

                    $defaultFormatObject = $objectsAPI->getFormatObject_local($value["type"])["data"];

                    $objectForEditor = array_merge($defaultFormatObject,[]);
                    $objectForEditor["objectType"] = $value["type"];
                    $objectForEditor["sourceID"] = $row["ID"];
                    $objectForEditor["sourceType"] = 0;
                    $objectForEditor["sourceParam"] = count($fieldsFormated);
                    unset($objectForEditor["value"]);
                    $objects[$value["localeName"]] = $objectForEditor;
                }
                $field = [
                    "id"=>$key,
                    "name"=>$value["localeName"],
                    "type"=>$value["type"],
                    "sourceParam"=>count($fieldsFormated),
                    "sourceType"=>0,
                    "sourceID"=>$row["ID"]
                ];
                $fieldsFormated[$key] = $field;
            }


            $dataObject = [
                "id"=>$row["ID"],
                "baseID"=>$row["ID"],
                "baseName"=>$properties["localeName"],
                "properties"=>$properties,
                "fields"=>$fieldsFormated
            ];



            if (isset($fieldsAsObjects) && $fieldsAsObjects) {
                $dataObject["objects"] = $objects;
            }

            $answer[] =$dataObject;
        }

        return array("result"=>true,"data"=>$answer);
    }

    public function saveBase($getter) {
        $fields     = $getter["fields"];
        $properties = $getter["properties"];
        $baseID     = $getter["baseID"];
        return $this->saveBase_local($fields,$properties,$baseID);
    }

    public function saveBase_local($fields,$properties,$baseID) {
        $result = $this->pServer->query("UPDATE base_sys_new_prp SET properties=?, fields=? WHERE ID=?",json_encode($properties,true),json_encode($fields,true),$baseID);
        return $result;
    }

    public function deleteBase($getter) {
        $baseID = $getter["baseID"];
        $result = Utils::clearCacheByBaseID($this->pServer,$baseID);
        if (!$result["result"]) return $result;

        return $this->deleteBase_local($baseID);
    }

    public function deleteBase_local($baseID) {

        $result = $this->pServer->select("SELECT * from data_sys 
                                       WHERE sourceID=?",$baseID);
        if (!$result["result"]) return $result;

        $dataDataSYS = $result["data"];

        foreach ($dataDataSYS as $key=>$object) {
            $valueID = $object["valueID"];
            $result  = $this->pServer->query("UPDATE ".config::$prefix."content SET refCount=refCount-1 WHERE ID=?",$valueID);
            if (!$result["result"]) return $result;
        }

        $result = $this->pServer->query("DELETE FROM data_sys 
                                    WHERE sourceID=?",$baseID);
        if (!$result["result"]) return $result;

        $result = $this->pServer->query("DELETE FROM ".config::$prefix."content 
                                    WHERE refCount<=0");
        if (!$result["result"]) return $result;

        $result = $this->pServer->query("DELETE FROM system_fields_bases WHERE baseID=?",$baseID);
        if (!$result["result"]) return $result;

        $result = $this->pServer->query("DELETE FROM base_sys_new_prp WHERE ID=?",$baseID);
        if (!$result["result"]) return $result;


        $roles = array();
        $roles[] = -1;
        $roles[] = ApiInfo::$ROLES_READ;
        $roles[] = ApiInfo::$ROLES_WRITE;
        $roles[] = ApiInfo::$ROLES_ADD;
        $roles[] = ApiInfo::$ROLES_DEL;
        $this->pRolesValid->removeOwnerRoles($this->resourceID,-1,$baseID,$roles);
        $this->pRolesValid->removeOwnerRoles(Consts::$RESOURCE_RECORDS_ID,null,null,$roles);

        return $result;
    }



    public function addNewBase($getter) {
        $name = $getter["nameBase"];
        return $this->addNewBase_local($name);
    }
    public function addNewBase_local($name) {

        $properties = [
            "localeName"   =>$name,
            "dateType"     =>0,
            "year"         =>1,
            "rangeDayMinus"=>0,
            "rangeDayPlus" =>0
        ];
        $fields = [];

        $result = $this->pServer->insert("INSERT INTO base_sys_new_prp (properties,fields) VALUES(?,?)",json_encode($properties,true),json_encode($fields,true));
        if (!$result["result"]) return $result;

        $roles = array();
        $roles[] = ApiInfo::$ROLES_READ;
        $roles[] = ApiInfo::$ROLES_WRITE;
        $roles[] = ApiInfo::$ROLES_ADD;
        $roles[] = ApiInfo::$ROLES_DEL;
        $this->pRolesValid->addOwnerRoles($this->resourceID,-1,$result["data"],$roles);
        $this->pRolesValid->addOwnerRoles(Consts::$RESOURCE_RECORDS_ID,$result["data"],-1,$roles);

        return $result;
    }


    public function doublicateBase_local($baseID,$doubleName) {

        $result = Utils::doubleRow_local($this->pServer,"base_sys_new_prp",$baseID,["ID"]);
        if (!$result["result"]) return $result;

        $oldData     = $result["data"]["dataRow"];
        $newBaseID   = $result["data"]["insertID"];
        $newBaseName = $newBaseID;


        $properties =json_decode( $oldData["properties"],true);
        $properties["localeName"] = $doubleName;

        $result = $this->pServer->query("UPDATE base_sys_new_prp SET properties = ? WHERE ID = ?",json_encode($properties),$newBaseID);
        if (!$result["result"]) return $result;

        $roles = array();
        $roles[] = ApiInfo::$ROLES_READ;
        $roles[] = ApiInfo::$ROLES_WRITE;
        $roles[] = ApiInfo::$ROLES_ADD;
        $roles[] = ApiInfo::$ROLES_DEL;
        $this->pRolesValid->addOwnerRoles($this->resourceID,-1,$newBaseID,$roles);
        $this->pRolesValid->addOwnerRoles(Consts::$RESOURCE_RECORDS_ID,$newBaseID,-1,$roles);


        return ["result"=>true,"data"=>$newBaseName];
    }

}