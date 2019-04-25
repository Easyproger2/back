<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 18.12.16
 * Time: 6:37
 */
class providerForType1
{
    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;


    function __construct(Server $server,Cache $cache) {
        $this->pServer = $server;
        $this->pCache = $cache;
    }

    function getRecords(&$source,$params,&$maxRecords,$sourceType,$sourceKey,$today,$start,$end) {
        if ($maxRecords < 1) $maxRecords = 1;

        $record = [];

        $fieldsKeys = array_keys($source["fields"]);

        $filteredFields = [];
        $inReq = [];
        foreach ($fieldsKeys as $key) {
            $filteredFields[] = $key;
            $inReq[] = '?';
        }


        $result = $this->pServer->select("SELECT * FROM data_sys 
LEFT JOIN ".config::$prefix."content as cont ON cont.ID=valueID
WHERE sourceID=? AND sourceParam in(".implode(",",$inReq).")",$sourceKey,$filteredFields);
        if (!$result["result"]) return $result;

        for ($j = 0; $j < count($result["data"]);$j++) {
            $row = $result["data"][$j];
            $key = $row["sourceParam"];
            $value = $row["value"];
            $properties = json_decode($row["properties"]);

            $record["$sourceKey:$key"] = ["value"=>$value,"properties"=>$properties];
        }

        $source["records"] = [$record];
        $source["maxRecords"] = 1;
    }
}