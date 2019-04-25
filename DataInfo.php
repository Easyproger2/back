<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 19.02.17
 * Time: 23:48
 */
class DataInfo
{

    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;

    private $system_fields;
    private $systemValuesDefault;

    private $propertiesMap;
    private $tagsInfo;

    function __construct(Server $server,Cache $cache) {
        $this->pServer = $server;
        $this->pCache = $cache;
    }

    public function getTagsInfo() {
        if (!$this->tagsInfo) {
            $result = $this->pServer->select("SELECT * FROM ".config::$prefix."tags");
            if (!$result["result"]) return null;

            $this->tagsInfo = [];
            $data = $result["data"];

            foreach ($data as $rowID => $dataValue) {
                $this->tagsInfo[$dataValue["ID"]] = $dataValue;
            }

        }
        return $this->tagsInfo;
    }

    public function getPropertiesRecords() {
        if (!$this->propertiesMap) {
            $result = $this->pServer->select("SELECT * FROM ".config::$prefix."types");
            if (!$result["result"]) return null;
            $this->propertiesMap = [];
            $data = $result["data"];

            foreach ($data as $rowID => $dataValue) {
                $this->propertiesMap[$dataValue["ID"]] = json_decode($dataValue["name"]);
            }
        }
        return $this->propertiesMap;
    }

    public function getSystemFields() {
        if (!$this->system_fields) {
            $result  = $this->pServer->select("SHOW COLUMNS FROM system_fields_bases");
            if (!$result["result"]) null;

            $fieldsArray = $result["data"];
            $this->system_fields = [];
            foreach ($fieldsArray as $rowID => $dataValue) {
                $fieldName = $dataValue["Field"];
                if ($fieldName != "ID" && $fieldName != "baseID" && $fieldName != "recordID") {
                    $this->system_fields[$fieldName] = $fieldName;
                }
            }
        }

        return $this->system_fields;
    }

    public function getDefaultSystemValues() {
        global $userid;
        if (!$this->systemValuesDefault) {
            $this->systemValuesDefault = [];
            $this->systemValuesDefault["PUBLISHED_ID"]      = 0;
            $this->systemValuesDefault["NUM_SHOW_ID"]       = 0;
            $this->systemValuesDefault["PUBLISHED_USER_ID"] = $userid;
            $this->systemValuesDefault["DATEEND_ID_DF"]     = date("Y-m-d 23:59:59");
            $this->systemValuesDefault["DATESTART_ID_DF"]   = date("Y-m-d 00:00:00");
            $this->systemValuesDefault["TIME_RECORD_ID"]    = 0;
            $this->systemValuesDefault["TIME_START_SYS_ID"] = "00:00:00";
            $this->systemValuesDefault["TIME_END_SYS_ID"]   = "23:59:59";
            $this->systemValuesDefault["DATESYSTEM_ID_DF"]  = date("Y-m-d 11:11:11");
        }
        return $this->systemValuesDefault;
    }

}