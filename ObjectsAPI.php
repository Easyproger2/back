<?php

/**
 * Created by PhpStorm.
 * User: easyproger

 */

require_once("m.php");
class ObjectsAPI
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



    function __construct(Server $server,Cache $cache) {
        $this->resourceID = Consts::$RESOURCE_DESIGNS_ID;

        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData  = $this->pCache->getCachedClass("StyleData");
    }


    public function addObject($getter) {
        $name = $getter["groupName"];
        return $this->addObject_local($name);
    }

    public function addObject_local($name) {
        $result = $this->pServer->select("SELECT ID FROM ".$this->pServer->getPrefix()."objects_names WHERE name=?",$name);
        if (!$result["result"]) return $result;

        if (!count($result["data"])) {
            $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."objects_names (name) VALUES(?)",$name);
        }else {
            $result = ["result"=>true,"data"=>$result["data"][0]["ID"]];
        }
        return $result;
    }

    public function updateObjectInGroup($getter) {
        $nameObject  = $getter["nameObject"];
        $objectType  = $getter["objectType"];
        $sourceID    = $getter["sourceID"];
        $sourceParam = $getter["sourceParam"];
        $sourceType  = $getter["sourceType"];
        $properties  = $getter["properties"];
        $value       = $getter["value"];
        $groupName   = $getter["groupName"];


        return $this->updateObjectInGroup_local($nameObject,$objectType,$sourceID,$sourceParam,$sourceType,$properties,$value,$groupName);
    }

    public function updateObjectInGroup_local($nameObject,$objectType,$sourceID,$sourceParam,$sourceType,$properties,$value,$groupName) {
        $result = $this->pServer->select("SELECT ID FROM ".$this->pServer->getPrefix()."objects_names WHERE name=?",$groupName);
        if (!$result["result"]) return $result;
        if (!count($result["data"])) return ["result"=>false,"error"=>"group not exist"];

        $groupID = $result["data"][0]["ID"];


        $result = $this->pServer->select("SELECT ID FROM ".$this->pServer->getPrefix()."objects WHERE nameObject=?",$nameObject);
        if (!$result["result"]) return $result;

        $requests = [];
        $values = [];

        $isInsert = !count($result["data"]);

        if (isset($nameObject)) { $requests[] = "nameObject". ($isInsert ? "" : "=?");$values[] = $nameObject;}
        if (isset($objectType)) { $requests[] = "objectType". ($isInsert ? "" : "=?");$values[] = $objectType;};
        if (isset($sourceID)) {   $requests[] = "sourceID".   ($isInsert ? "" : "=?");$values[] = $sourceID;};
        if (isset($sourceParam)) {$requests[] = "sourceParam".($isInsert ? "" : "=?");$values[] = $sourceParam;};
        if (isset($sourceType)) { $requests[] = "sourceType". ($isInsert ? "" : "=?");$values[] = $sourceType;};
        if (isset($properties)) { $requests[] = "properties". ($isInsert ? "" : "=?");$values[] = $properties;};
        if (isset($value)) {      $requests[] = "value".      ($isInsert ? "" : "=?");$values[] = $value;};

        if ($isInsert) {
            if (count($requests)){
                $requests[] = "ID";
                $values[]   = $groupID;
                $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."objects (".implode(",",$requests).") VALUES(%s)",$values);
            }
        }else {
            $id_row = $result["data"][0]["ID"];
            $values[] = $id_row;
            if (count($requests))
            $result = $this->pServer->query( "UPDATE ".$this->pServer->getPrefix()."objects SET ".implode(",",$requests)." WHERE ID_row=?",$values);
        }

        return $result;
    }


    public function getObjectsList($getter) {
        return $this->getObjectsList_local($getter["-1"]);
    }


    public function getObjectsList_local($filtered) {
        if (isset($filtered)) {
            $result  = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."objects_names as objsNames
                                                   LEFT JOIN ".$this->pServer->getPrefix()."objects as objs ON objs.ID = objsNames.ID
                                                        WHERE ID IN(".$filtered.")") ;
            if (!$result["result"]) return $result;
        }else {
            $result  = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."objects_names as objsNames
                                                   LEFT JOIN ".$this->pServer->getPrefix()."objects as objs ON objs.ID = objsNames.ID");
            if (!$result["result"]) return $result;
        }



        $answer = [];
        foreach ($result["data"] as $key=>$value) {
            if (!isset($answer[$value["name"]])) $answer[$value["name"]] = [];


            $defaultObject = $this->getFormatObject_local($value["objectType"])["data"];

            $merged = array_merge($defaultObject,$value);

            $merged["ID"]      = -1;
            $merged["IDGROUP"] = -1;
            $merged["styleID"] = -1;

            unset($merged["ID_row"]);
            unset($merged["name"]);


            if (empty($merged["subGroupName"])) {
                $answer[$value["name"]][] = ["name"=>$merged["nameObject"],"object"=>$merged];
            }else {
                if (!isset($answer[$value["name"]][$merged["subGroupName"]])) $answer[$value["name"]][$merged["subGroupName"]] = [];
                $answer[$value["name"]][$merged["subGroupName"]][] = ["name"=>$merged["nameObject"],"object"=>$merged];
            }
        }

        return ["result"=>true,"data"=>$answer];
    }



    public function getFormatObject() {
        return $this->getFormatObject_local();
    }

    public function getFormatObject_local($name=null) {
        $object = [];


        $this->pStyleData->get_names();
        $this->pStyleData->get_spaces();
        $this->pStyleData->get_values();

        $object["styles"] = [];


        $parsedStyles = [];



        // if cant get default

        if ($name == null) {
            $style = new Style($this->pCache);

            foreach([   ["name"=>"left",    "value"=>"0px",       "space"=>2,"scaled_flag"=>1],
                        ["name"=>"top",     "value"=>"0px",       "space"=>2,"scaled_flag"=>1],
                        ["name"=>"width",   "value"=>"200px",     "space"=>2,"scaled_flag"=>1],
                        ["name"=>"height",  "value"=>"200px",     "space"=>2,"scaled_flag"=>1]
                    ] as $attr ) {

                $id_attr = $this->pStyleData->namesS[$attr["name"]];
                if (is_array($attr["value"])) $attr["value"] = json_encode($attr["value"],true);

                $style->parseAttributes(["id_attr"=>$id_attr,"value"=>$attr["value"],"id_space"=>$attr["space"],"scaled_flag"=>$attr["scaled_flag"]],false,0);
            }
            $parsedStyles = $style->getStyle(false);
        }else {
            /* @var StyleAPI $styleAPI*/
            $styleAPI = $this->pCache->getCachedClass("StyleAPI");
            $result = $styleAPI->getDefaultStyleByName_local($name);
            if ($result["result"]) {
                $parsedStyles = $result["data"];
            }
        }

        $object["styles"]["-1"] = $parsedStyles;

        $object["ID"] = -1;
        $object["properties"] = -1;
        $object["objectType"] = -1;
        $object["sourceID"] = -1;
        $object["sourceParam"] = -1;
        $object["sourceType"] = -1;
        $object["IDGROUP"] = -1;
        $object["styleID"] = -1;
        $object["value"] = -1;

        return array("result"=>true,"data"=>$object);
    }



    public function changeObjectsSize_local($server,$stylesAPI,$objectsID,$delta,$persentOld) {

        $result = Utils::getObjectsForTemplate($server,$objectsID,$stylesAPI);
        if (!$result["result"]) return $result;
        $objects = $result["data"];

        for ($i = 0; $i < count($objects);$i++) {
            $obj    = $objects[$i];
            $styles = $obj["styles"][$obj["styleID"]];

            for ($indexStyle = 0; $indexStyle < count($styles);$indexStyle++) {
                $style = &$styles[$indexStyle];
                $scaled_flag = $style["scaled_flag"];

                if (intval($scaled_flag) == 1) {
                    $style["value"] = floatval((floatval($style["value"])*$delta)/$persentOld);
                }
            }
            $result = $stylesAPI->updateStyle_local($obj["styleID"],$styles);
            if (!$result["result"]) return $result;
        }

        return ["result"=>true,"data"=>"success"];
    }

    public function doubleObjects_local($cache,$server,$isDoubleBases,$doubleBaseName,$objectsForDouble) {

        /* @var BasesAPI $baseAPI*/
        $baseAPI = $cache->getCachedClass("BasesAPI");

        $doubledBases = [];

        $objectsDoubled = [];
        foreach ($objectsForDouble as $keyObj=>$objectInfo) {
            $sourceType = $objectInfo["sourceType"];

            $source1 = $objectInfo["sourceID"];
            $source2 = $objectInfo["sourceParam"];
            $objectType = $objectInfo["objectType"];

            $objectInfo["ID"] = -1;
            $objectInfo["IDGROUP"] = -1;

            $styles = $objectInfo["styles"][$objectInfo["styleID"]];
            $objectInfo["styleID"] = -1;
            unset($objectInfo["styles"]);
            $objectInfo["styles"][$objectInfo["styleID"]] = $styles;

            if ($isDoubleBases) {
                if ($sourceType == 0) {
                    $baseToDouble = $objectInfo["sourceID"];
                    if (isset($doubledBases[$baseToDouble])) {
                        $objectInfo["sourceID"] = $doubledBases[$baseToDouble];
                    }else {
                        $result = $baseAPI->doublicateBase_local($baseToDouble,$doubleBaseName);
                        if (!$result["result"]) return $result;
                        $newBaseName = $result["data"];
                        $doubledBases[$baseToDouble] = $newBaseName;
                        $objectInfo["sourceID"]      = $newBaseName;
                    }
                }
            }

            if ($sourceType == 1) {
                $result = $server->select("SELECT * FROM data_sys WHERE sourceID=? AND sourceParam=?",$source1,$source2);
                if (!$result["result"]) return $result;
                $value = "";
                if (count($result["data"])) $value = $result["data"][0]["value"];

                $objectInfo["value"] = $value;
                $objectInfo["sourceParam"] = $objectType;
            }

            $objectsDoubled[] = $objectInfo;
        }

        /* @var StyleAPI $baseAPI*/
        $stylesAPI = $cache->getCachedClass("StyleAPI");

        /* @var ContentAPI $contentAPI*/
        $contentAPI = $cache->getCachedClass("ContentAPI");

        /* @var ObjectsAPI $objectsAPI*/
        $objectsAPI = $this->pCache->getCachedClass("ObjectsAPI");

        $result = $objectsAPI->updateObjects_local($server,$stylesAPI,$contentAPI,$objectsDoubled,-1);
        if (!$result["result"]) return $result;

        $newObjectsID = $result["data"]["objectsID"];

        return ["result"=>true,"data"=>$newObjectsID];
    }

    public function updateObjects_local($server,$stylesAPI,$contentAPI,$objects,$objectsID,$onlyUpdate = 0) {
        /* @var StyleAPI $stylesAPI*/
        /* @var ContentAPI $contentAPI*/
        /* @var Server $server*/

        $existObjects = [];
        if ($objectsID != -1) {
            $result = $server->select("SELECT * FROM `templates_obj_sys` WHERE IDGROUP=?",$objectsID);
            if (!$result["result"]) return $result;

            for ($indexObject = 0; $indexObject < count($result["data"]);$indexObject++) {
                $object     = $result["data"][$indexObject];
                $existObjects[$object["ID"]] = $object;
            }
            if (!count($objects)){
                $objectsID = -1;
            }
        }else {
            if (count($objects)) {
                $result = $server->select("SELECT MAX(IDGROUP) FROM templates_obj_sys");
                if (!$result["result"]) return $result;
                $objectsID = $result["data"][0]["MAX(IDGROUP)"];
                $objectsID+=1;
            }
        }

        for ($indexObject = 0; $indexObject < count($objects);$indexObject++) {
            $object     = $objects[$indexObject];

            $rowID      = $object["ID"];
            $id_style   = $object["styleID"];
            $properties = is_array($object["properties"]) ? json_encode($object["properties"],true) : $object["properties"];
            $source1    = $object["sourceID"];
            $source2    = $object["sourceParam"];
            $objectType = $object["objectType"];
            $sourceType = $object["sourceType"];
            $value      = is_array($object["value"]) ? json_encode($object["value"],true) : $object["value"];
            $styles     = $object["styles"][$object["styleID"]];

            if ($id_style == -1 && count($styles)) {
                $result = $stylesAPI->addStyleBaseRoot_local();
                if (!$result["result"]) return $result;
                $id_style = $result["data"]["id_style"];
            }

            $parsedStyle = [];
            for ($indexStyle = 0; $indexStyle < count($styles); $indexStyle++) {
                $style = $styles[$indexStyle];
                $parsedStyle[] = array("name"=>$style["name"], "value"=>$style["value"], "space"=>$style["space"],"scaled_flag"=>$style["scaled_flag"]);
            }
            $result = $stylesAPI->updateStyle_local($id_style,$parsedStyle);
            if (!$result["result"]) return $result;


            if ($sourceType == 1 && isset($object["value"])) {
                $result = $server->select("SELECT *,ds.ID as IDDS FROM data_sys as ds
                                           LEFT JOIN ".config::$prefix."content as cont ON cont.ID=valueID 
                                               WHERE sourceID=? AND sourceParam=?",$source1,$source2);

                if (!$result["result"]) return $result;
                if (count($result["data"])) {
                    $idData = $result["data"][0]["IDDS"];
                    $oldValue = $result["data"][0]["value"];
                    $oldValueData = $contentAPI->getLenAndHashForContent($oldValue);

                    $result = $contentAPI->editContent_local($value,$oldValueData["hash"],$oldValueData["len"]);
                    if (!$result["result"]) return $result;
                    $valueID = $result["data"]["ID"];

                    $result = $server->query("UPDATE data_sys SET valueID=? WHERE ID=?",$valueID,$idData);
                    if (!$result["result"]) return $result;

                }else {

                    $result = $contentAPI->addContent_local($value);
                    if (!$result["result"]) return $result;

                    $valueID = $result["data"]["ID"];

                    $result = $server->insert("INSERT INTO data_sys (sourceID,sourceParam,valueID) VALUES(?,?,?)",$source1,0,$valueID);
                    if (!$result["result"]) return $result;
                    $idData = $result["data"];
                    $source2 = $idData;
                    $result = $server->query("UPDATE data_sys SET valueID=?,sourceParam=? WHERE ID=?",$valueID,$source2,$idData);
                    if (!$result["result"]) return $result;
                }
            }

            // update or insert objects
            if (isset($existObjects[$rowID])) {
                $result = $server->query("UPDATE templates_obj_sys SET IDGROUP=?,styleID=?,properties=?,sourceID=?,objectType=?,sourceType=?,sourceParam=? WHERE ID=?",$objectsID,$id_style,$properties,$source1,$objectType,$sourceType,$source2,$rowID);
                if (!$result["result"]) return $result;
                unset($existObjects[$rowID]);
            }else {
                $result = $server->insert("INSERT INTO templates_obj_sys (IDGROUP,styleID,properties,sourceID,objectType,sourceType,sourceParam) VALUES(?,?,?,?,?,?,?)",$objectsID,$id_style,$properties,$source1,$objectType,$sourceType,$source2);
                if (!$result["result"]) return $result;
            }
        }
        // clear deleted objects
        if (count($existObjects) && !$onlyUpdate) {
            foreach ($existObjects as $rowID=>$object) {
                $id_style   = $object["styleID"];
                if ($id_style != -1) {
                    $result = $stylesAPI->removeStyle_local($id_style);
                    if (!$result["result"]) return $result;
                }
                $result = $server->query("DELETE FROM `templates_obj_sys` WHERE ID=?",$rowID);
                if (!$result["result"]) return $result;
            }
        }


        return array("result"=>true,"data"=>["objectsID"=>$objectsID]);
    }





}