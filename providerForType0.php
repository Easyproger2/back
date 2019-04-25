<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 18.12.16
 * Time: 6:34
 */
class providerForType0
{

    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;


    function __construct(Server $server,Cache $cache) {
        $this->pServer = $server;
        $this->pCache = $cache;
    }

    function getRecords(&$source,$paramsRecords,&$maxRecords,$sourceType,$sourceKey,$today,$start,$end,$paramsObjects=null) {

        // forming select data format for table

        /* @var DataInfo $dataInfo*/
        $dataInfo = $this->pCache->getCachedClass("DataInfo");

        $fieldsInBase      = $dataInfo->getSystemFields();
        $propertiesRecords = $dataInfo->getPropertiesRecords();
        $tagsMap           = $dataInfo->getTagsInfo();


        $where  = [];
        $values = [];

        if ($paramsRecords != null ) {
            $where  = $paramsRecords["fields"];
            $values = $paramsRecords["values"];
        }

        $where[] = "baseID=?";
        $values[] = $sourceKey;

        $requestString = count($where) ? " WHERE ".implode(" AND ",$where) : "";

        $limit = "";

        $result = $this->pServer->select("SELECT COUNT(*) FROM system_fields_bases $requestString ",$values);
        if (!$result["result"]) return $result;
        $count = $result["data"][0]["COUNT(*)"];

        if ($start != -1 && $end != -1) {
            $limit = "LIMIT ?,?";
            $values[] = $start;
            $values[] = $end;
        }

        $result = $this->pServer->select("SELECT * FROM system_fields_bases $requestString $limit",$values);
        if (!$result["result"]) return $result;

        $recordsIDS = $result["data"];
        $records = [];
        for ($j = 0; $j < count($recordsIDS);$j++) {

            $idRow    = $recordsIDS[$j]["ID"];
            $recordID = $recordsIDS[$j]["recordID"];
            $baseID   = $recordsIDS[$j]["baseID"];

            $whereObjects = [];
            $valueObjects = [];
            $orderString = "";

            if ($paramsObjects != null) {
                $whereObjects = $paramsObjects["fields"];
                $valueObjects = $paramsObjects["values"];
                $orderString  = $paramsObjects["orderString"];
            }

            $whereObjects[] = "sourceID=?";
            $valueObjects[] = $baseID;
            $whereObjects[] = "groupID=?";
            $valueObjects[] = $recordID;

            $requestObjectsString = count($whereObjects) ? " WHERE ".implode(" AND ",$whereObjects) : "";


            $result = $this->pServer->select("SELECT * FROM sys_tags_groups WHERE ID_group=? ",$idRow);
            if (!$result["result"]) return $result;


            $tagsGroups = $result["data"];

            $result = $this->pServer->select("SELECT * FROM data_sys as ds
                                                      LEFT JOIN ".config::$prefix."content as cont ON cont.ID=valueID
                                                          $requestObjectsString $orderString",$valueObjects);
            if (!$result["result"]) return $result;

            $tagsGroupsGrouped = [];

            foreach ($tagsGroups as $keyTG=>$valueTG) {
                $tagsInfo = $tagsMap[$valueTG["id_tag"]];
                if (isset($tagsInfo)) {
                    $tagsInfo["gpData"] = $valueTG["data"];
                    $tagsGroupsGrouped[$valueTG["ID"]][] = $tagsInfo;
                }
            }


            $record = ["tagsGroups"=>$tagsGroupsGrouped];





            foreach ($result["data"] as $indexData=>$valueData) {
                $key        = $valueData["sourceParam"];
                $value      = $valueData["value"];

                $properties = "";
                if ($propertiesRecords != null) $properties = $propertiesRecords[$valueData["properties"]];

                $record["$baseID:$key"] = ["value"=>$value,"properties"=>$properties];
                $record["IDRECORD"] = $recordID;

                foreach ($fieldsInBase as $key=>$keyCopy) {
                    $record[$keyCopy] = $recordsIDS[$j][$keyCopy];
                }
            }
            $records[] = $record;
        }

        if ($maxRecords < count($records)) $maxRecords = count($records);

        $source["COUNT"]      = $count;
        $source["records"]    = $records;
        $source["maxRecords"] = count($records);
    }

}