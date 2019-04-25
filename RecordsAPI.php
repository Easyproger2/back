<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 */

require_once("m.php");

class RecordsAPI
{

    public static $GET_RECORDS_PER_PAGE_NUM = 10;

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
        $this->resourceID = Consts::$RESOURCE_RECORDS_ID;

        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData  = $this->pCache->getCachedClass("StyleData");
    }


    public function updateRecordTagsGroups($getter) {
        $baseID  = $getter['baseID'];
        $recordID  = $getter['recordID'];
        $tagsGroups = $getter['tagsGroups'];

        return $this->updateRecordTagsGroups_local($baseID,$recordID,$tagsGroups);
    }

    public function updateRecordTagsGroups_local($baseID,$recordID,$tagsGroups) {
        $result = $this->pServer->select("SELECT * FROM system_fields_bases WHERE baseID=? AND recordID=?",$baseID,$recordID);
        if (!$result["result"]) return $result;
        if (!count($result["data"])) return ["result"=>false,"error"=>"not have $recordID record in $baseID base"];

        $sfRecordData = $result["data"][0];
        $sfRecordID = $sfRecordData["ID"];

        foreach ($tagsGroups as $groupID=>$tags) {
            $this->updateTags($tags); // add non exist tags

            $result = $this->pServer->query("DELETE FROM sys_tags_groups WHERE ID=? AND ID_group=?",$groupID,$sfRecordID);//clear tagsGroup
            if (!$result["result"]) return $result;

            foreach ($tags as $tagIndex=>$tagInfo) {
                $result = $this->pServer->insert("INSERT INTO sys_tags_groups (ID,ID_group,id_tag,type,data) VALUES(?,?,?,?,?)",$groupID,$sfRecordID,$tagInfo["ID"],$tagInfo["type"],$tagInfo["gpData"]);
                if (!$result["result"]) die(json_encode($result)) ;
            }
        }

        return ["result"=>true,"data"=>"success"];
    }

    public function updateTags(&$tags) {

        for ($i = 0; $i < count($tags); $i++) {
            $tag = &$tags[$i];

            $type      = $tag["type"];
            $dateStart = $tag["dateStart"];
            $dateEnd   = $tag["dateEnd"];
            $data      = $tag["data"];
            $isRare    = $tag["isRare"];

            if ($type == 1) {
                $result = $this->pServer->select("SELECT * FROM sys_tags WHERE data=? AND type=? AND dateStart=? AND dateEnd=? AND isRare=?",$data,$type,$dateStart,$dateEnd,$isRare);
            }else {
                $result = $this->pServer->select("SELECT * FROM sys_tags WHERE data=? AND type=?",$data,$type);
            }

            if (!$result["result"]) return $result;

            if (!count($result["data"])) {
                $result = $this->pServer->insert("INSERT INTO sys_tags (type,dateStart,dateEnd,data,isRare) VALUES(?,?,?,?,?)",$type,$dateStart,$dateEnd,$data,$isRare);
                if (!$result["result"]) return $result;
                $tagID = $result["data"];
            }else {
                $tagID = $result["data"][0]["ID"];
            }

            $tag["ID"] = $tagID;

        }
        return $tags;
    }

    public function getRecordsFromBase($getter) {
        $dateMin        = $getter['dateMin'];
        $dateMax        = $getter['dateMax'];

        $per_page = RecordsAPI::$GET_RECORDS_PER_PAGE_NUM;

        $start = -1;
        $end = -1;
        if (isset($getter['page'])) {
            $page= $getter['page']-1;
            $start = abs($page*$per_page);
            $end   = $per_page;
        }

        $baseID         = $getter['baseID'];
        $filterPublish  = $getter['filterPublish'];

        return $this->getRecordsFromBase_local($dateMin,$dateMax,$start,$end,$baseID,$filterPublish,$getter["-1"]);
    }

    public function getRecordsFromBase_local($dateMin,$dateMax,$start,$end,$baseID,$filterPublish,$recordFilter) {

        $year = 1;

        $result  = $this->pServer->select("SELECT * FROM base_sys_new_prp WHERE ID=?",$baseID);
        if (!$result["result"]) return $result;

        if (isset($propertiesBase["year"])) $year = $propertiesBase["year"];

        $answer = [
            "records"=>[]
        ];

        $whereRecords = [];
        $valueRecords = [];

        $whereObjects = [];
        $valueObjects = [];


        if (isset($dateMin) && isset($dateMax))
            $whereRecords[] =  getFilterDateRequest($year,$dateMin,$dateMax);

        if (isset($filterPublish)){
            $whereRecords[] = 'PUBLISHED_ID=?';
            $valueRecords[] = $filterPublish;
        }

        if (isset($recordFilter)) {
            $whereRecords[] = 'recordID IN('.$recordFilter.')';
        }

        $provider = $this->pCache->getCachedClass("providerForType0");

        $result = [];
        $maxRecords = 0;
        $recordParam = ["fields"=>$whereRecords,"values"=>$valueRecords];
        $objectsParam = ["fields"=>$whereObjects,"values"=>$valueObjects,"orderString"=>""];
        $provider->getRecords($result,$recordParam,$maxRecords,0,$baseID,false,$start,$end,$objectsParam);

        $answer["COUNT"] = $result["COUNT"];
        $answer["records"] = $result["records"];

        return ["result"=>true,"data"=>$answer];
    }

    public function addRecordToBase($getter) {
        $baseID = $getter["baseID"];
        return $this->addRecordToBase_local($baseID);

    }
    public function addRecordToBase_local($baseID) {

        $result  = $this->pServer->select("SELECT * FROM base_sys_new_prp WHERE ID=?",$baseID);
        if (!$result["result"] || !count($result["data"])) return $result;

        $fields = json_decode($result["data"][0]["fields"],true);


        $result  = $this->pServer->select("SELECT MAX(recordID) FROM system_fields_bases WHERE baseID=?",$baseID);
        if (!$result["result"]) return $result;

        $recordID = $result["data"][0]["MAX(recordID)"];
        $recordID++;

        /* @var ContentAPI $contentAPI */
        $contentAPI = $this->pCache->getCachedClass("ContentAPI");

        /* @var DataInfo $dataInfo */
        $dataInfo = $this->pCache->getCachedClass("DataInfo");

        $systemFields = $dataInfo->getSystemFields();

        $systemFields["baseID"]   = "baseID";
        $systemFields["recordID"] = "recordID";

        $valuesDefault = $dataInfo->getDefaultSystemValues();

        $fieldsInsert = [];
        foreach ($systemFields as $id=>$field) {
            $req[] = "?";
            $fieldsInsert[] = $field;
            if ($field == "baseID") {
                $values[] = $baseID;
            }else if ($field == "recordID") {
                $values[] = $recordID;
            }else {
                $value = $valuesDefault[$field];
                if ($value == null) $value = 0;
                $values[] = $value;
            }
        }

        $sqlInsert = "INSERT INTO system_fields_bases (".implode(",",$fieldsInsert).") values(".implode(",",$req).")";
        $result = $this->pServer->insert($sqlInsert,$values);
        if (!$result["result"]) return $result;


        $iP = "images/".$baseID."/".$recordID;
        mkdir("images/".$baseID, 0777);
        mkdir($iP, 0777);


        foreach ($fields as $key=>$field) {
            if ($field["type"] == "PIC") {
                mkdir($iP."/".$key, 0777);
                $destination = $iP."/".$key."/";
                mkdir($destination."/files");
                mkdir($destination."/thumbnails");
                Utils::recursiveChmod($destination,null, 0777, $dirPerm=0777);
            }

            $value = ""; // value
            $result = $contentAPI->addContent_local($value);
            if (!$result["result"]) return $result;
            $valueID = $result["data"]["ID"];
            $result = $this->pServer->insert("INSERT INTO data_sys (sourceID,sourceParam,valueID,groupID) VALUES(?,?,?,?)",$baseID,$key,$valueID,$recordID);
            if (!$result["result"]) return $result;
        }




        $roles = array();
        $roles[] = ApiInfo::$ROLES_READ;
        $roles[] = ApiInfo::$ROLES_WRITE;
        $roles[] = ApiInfo::$ROLES_ADD;
        $roles[] = ApiInfo::$ROLES_DEL;
        $this->pRolesValid->addOwnerRoles($this->resourceID,$baseID,$recordID,$roles);


        return ["result"=>true,"data"=>"success"];
    }


    public function updateRecord($getter) {
        $idRecord  = $getter['recordID'];
        $idField   = $getter['fieldID'];
        $baseID    = $getter['baseID'];
        $content   = $getter['content'];
        Utils::clearCacheByBaseID($this->pServer,$baseID);

        return $this->updateRecord_local($idRecord,$idField,$baseID,$content);
    }


    public function updateRecord_local($idRecord,$idField,$baseID,$content) {

        if (!is_numeric($idField)) {

            $result  = $this->pServer->select("SHOW COLUMNS FROM system_fields_bases");
            if (!$result["result"]) die();

            if (is_array($idField) && is_array($content)) {
                if (count($idField) != count($content)) return ["result"=>false,"error"=>"wrong content fields"];
            }else {
                $idField = [$idField];
                $content = [$content];
            }


            $fieldsArray = $result["data"];
            $fieldsInBase = [];
            foreach ($fieldsArray as $rowID => $dataValue) {
                $fieldName = $dataValue["Field"];
                if ($fieldName != "ID")
                    $fieldsInBase[$fieldName] = $fieldName;
            }

            $where = [];
            $values = [];
            $values[] = "";
            $havePublish = false;
            foreach ($idField as $indexField=>$valueField) {
                if (!isset($fieldsInBase[$valueField])) return ["result"=>false,"error"=>"not have field:".$valueField];
                $where[]  = $valueField."=?";
                if (strcmp("PUBLISHED_ID",$valueField) === 0) $havePublish = true;
            }
            foreach ($content as $indexContent=>$valueContent) {
                $values[] = $valueContent;
            }

            if (!$havePublish) {
                $where[]  = "PUBLISHED_ID=?";
                $values[] = 0;
            }

            $values[] = $baseID;
            $values[] = $idRecord;

            $values[0] = "UPDATE system_fields_bases SET ".implode(', ',$where)." WHERE baseID=? AND recordID=?";
            return call_user_func_array(array($this->pServer, 'query'), $values);
        }


        /* @var ContentAPI $contentAPI */
        $contentAPI = $this->pCache->getCachedClass("ContentAPI");

        $result = $this->pServer->select("SELECT *,ds.ID as dataID FROM system_fields_bases 
                                              LEFT JOIN data_sys as ds ON ds.sourceID=baseID AND ds.groupID=recordID AND ds.sourceParam=?
                                              LEFT JOIN ".config::$prefix."content as cont ON cont.ID=ds.valueID
                                           WHERE baseID=? AND recordID=?",$idField,$baseID,$idRecord);
        if (!$result["result"] || !count($result["data"])) return $result;

        $record = $result["data"][0];

        $oldValue = $record["value"];
        $dataID = $record["dataID"];

        $oldValueData = $contentAPI->getLenAndHashForContent($oldValue);

        $result = $contentAPI->editContent_local($content,$oldValueData["hash"],$oldValueData["len"]);
        if (!$result["result"]) return $result;
        $newValueID = $result["data"]["ID"];

        $result = $this->pServer->query("UPDATE data_sys SET valueID=? WHERE ID=?",$newValueID,$dataID);
        if (!$result["result"]) return $result;

        $result = $this->pServer->query("UPDATE system_fields_bases SET PUBLISHED_ID=0 WHERE baseID=? AND recordID=?",$baseID,$idRecord);

        return $result;
    }



    public function removeRecordFromBase($getter) {
        $idRecord = $getter["recordID"];
        $baseID   = $getter["baseID"];
        Utils::clearCacheByBaseID($this->pServer,$baseID);
        rmdir("images/".$baseID."/".$idRecord);
        return $this->removeRecordFromBase_local($baseID,$idRecord);
    }


    public function removeRecordFromBase_local($baseID,$idRecord) {


        $result = $this->pServer->query("DELETE FROM system_fields_bases 
                                    WHERE baseID=? AND recordID=?",$baseID,$idRecord);
        if (!$result["result"]) return $result;

        $result = $this->pServer->select("SELECT * from data_sys 
                                       WHERE sourceID=? AND groupID=?",$baseID,$idRecord);
        if (!$result["result"]) return $result;

        $dataDataSYS = $result["data"];

        foreach ($dataDataSYS as $key=>$object) {
            $valueID = $object["valueID"];
            $result  = $this->pServer->query("UPDATE ".config::$prefix."content SET refCount=refCount-1 WHERE ID=?",$valueID);
            if (!$result["result"]) return $result;
        }

        $result = $this->pServer->query("DELETE FROM data_sys 
                                    WHERE sourceID=? AND groupID=?",$baseID,$idRecord);
        if (!$result["result"]) return $result;

        $result = $this->pServer->query("DELETE FROM ".config::$prefix."content 
                                    WHERE refCount<=0");
        if (!$result["result"]) return $result;

        $roles = array();
        $roles[] = -1;
        $roles[] = ApiInfo::$ROLES_READ;
        $roles[] = ApiInfo::$ROLES_WRITE;
        $roles[] = ApiInfo::$ROLES_ADD;
        $roles[] = ApiInfo::$ROLES_DEL;
        $this->pRolesValid->removeOwnerRoles($this->resourceID,$baseID,$idRecord,$roles);

        return ["result"=>true,"data"=>"success"];
    }



}