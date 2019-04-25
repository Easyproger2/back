<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 28.03.15
 * Time: 23:31
 * To change this template use File | Settings | File Templates.
 */

class Utils {
    public static function generateUserSessionID() {
        return ".".$_SERVER["REMOTE_ADDR"];
    }


    public static $namesInfo = null;
    public static $propertiesObjects = null;


    public static function getAllObjectProperties($server) {
        if (Utils::$propertiesObjects != null) {
            return Utils::$propertiesObjects;
        }
        $result  = $server->select("SELECT * FROM ".Config::$prefix."objects");
        if (!$result["result"]) return $result;

        $data = $result["data"];

        foreach ($data as $key=>$value) {
            if (!isset(Utils::$propertiesObjects[$value["sourceType"]])) Utils::$propertiesObjects[$value["sourceType"]] = [];
            if (!isset(Utils::$propertiesObjects[$value["sourceType"]][$value["sourceID"]])) Utils::$propertiesObjects[$value["sourceType"]][$value["sourceID"]] = [];

            Utils::$propertiesObjects[$value["sourceType"]][$value["sourceID"]][$value["sourceParam"]] = [
                "type"=>$value["objectType"]
            ];
        }

        return Utils::$propertiesObjects;
    }

    public static function getAllObjectsNames($server) {

        if (Utils::$namesInfo != null) {
            return Utils::$namesInfo;
        }

        Utils::$namesInfo = ["0"=>[]];

        /* @var Server $server */
        $result = $server->select("SELECT * FROM base_sys_new_prp");
        if (!$result["result"]) return $result;

        $data = $result["data"];

        foreach ($data as $key=>$value) {
            $fields = json_decode($value["fields"],true);
            $idbase = $value["ID"];
            foreach ($fields as $fKey=>$fValue) {
                if (!isset(Utils::$namesInfo["0"][$idbase])) Utils::$namesInfo["0"][$idbase] = [];

                Utils::$namesInfo["0"][$idbase][$fKey] = ["nameObject"=>$fValue["localeName"]];
            }
        }

        $result  = $server->select("SELECT * FROM ".Config::$prefix."objects");
        if (!$result["result"]) return $result;

        $data = $result["data"];

        foreach ($data as $key=>$value) {

            if (!isset(Utils::$propertiesObjects[$value["sourceType"]])) Utils::$propertiesObjects[$value["sourceType"]] = [];
            if (!isset(Utils::$propertiesObjects[$value["sourceType"]][$value["sourceID"]])) Utils::$propertiesObjects[$value["sourceType"]][$value["sourceID"]] = [];

            Utils::$propertiesObjects[$value["sourceType"]][$value["sourceID"]][$value["sourceParam"]] = [
                "type"=>$value["objectType"]
            ];

            if (!isset(Utils::$namesInfo[$value["sourceType"]])) Utils::$namesInfo[$value["sourceType"]] = [];
            if (!isset(Utils::$namesInfo[$value["sourceType"]][$value["sourceID"]])) Utils::$namesInfo[$value["sourceType"]][$value["sourceID"]] = [];


            if ($value["sourceType"] == 1) {
                Utils::$namesInfo[$value["sourceType"]][$value["sourceID"]][$value["objectType"]] = ["nameObject"=>$value["nameObject"],"subGroupName"=>$value["subGroupName"]];
            }else {
                Utils::$namesInfo[$value["sourceType"]][$value["sourceID"]][$value["sourceParam"]] = ["nameObject"=>$value["nameObject"],"subGroupName"=>$value["subGroupName"]];
            }
        }

        return Utils::$namesInfo;
    }



    public static function doubleRow_local($server,$tableName,$idRow,$excludeFieldsNames) {
        /* @var Server $server */
        $result = $server->select("SELECT * FROM $tableName WHERE ID=? LIMIT 0,1",$idRow);
        if (!$result["result"]) return $result;
        if (!count($result["data"])) return ["result"=>false,"error"=>"not have row"];
        $dataToDouble = $result["data"][0];

        $fields = [];
        $values = [];
        $req = [];
        foreach ($dataToDouble as $field=>$value) {
            if (in_array($field,$excludeFieldsNames)) continue;
            $req[] = "?";
            $fields[] = $field;
            $values[] = $value;
        }
        $sqlInsert = "INSERT INTO $tableName (".implode(",",$fields).") values(".implode(",",$req).")";
        $result = $server->insert($sqlInsert,$values);
        if (!$result["result"]) return $result;
        return ["result"=>true,"data"=>["insertID"=>$result["data"],"dataRow"=>$dataToDouble]];
    }

    public static function getObjectsForTemplate($server,$objectsID,$stylesAPI,$parseNames=false) {
        /* @var Server $server */
        /* @var StyleAPI $stylesAPI */
        $result = $server->select("SELECT * FROM templates_obj_sys WHERE IDGROUP=?",$objectsID);
        if (!$result["result"]) return $result;
        $dataObjects = $result["data"];

        $namesObjects = null;

        if ($parseNames)
        $namesObjects = Utils::getAllObjectsNames($server);

        for ($indexObject = count($dataObjects)-1; $indexObject >= 0; $indexObject--) {
            $object = &$dataObjects[$indexObject];

            if ($parseNames && $namesObjects != null) {
                try{
                    $obj = $object["sourceType"] == 1 ? $namesObjects[$object["sourceType"]][$object["sourceID"]][$object["objectType"]] : $namesObjects[$object["sourceType"]][$object["sourceID"]][$object["sourceParam"]];
                    $object = array_merge($obj,$object);
                }catch (Exception $e){};
            }


            $object["properties"] = isJsonString($object["properties"]) ? json_decode($object["properties"],true) : $object["properties"];
            if ($stylesAPI != null) {
                $result = $stylesAPI->getStylesByIDS_local($object["styleID"],true,false);
                if (!$result["result"]) {
                    unset($dataObjects[$indexObject]);
                    return $result;
                }
                $styles = $result["data"];
                $object["styles"] = $styles;

                $result = $stylesAPI->getStylesByIDSDOWN_local($object["styleID"]);
                if (!$result["result"]) {
                    continue;
                }

                $styles = $result["data"];
                if (count($styles)) {
                    $parentStyleID = $styles[0];
                    $result = $stylesAPI->getStylesByIDS_local($styles[0],true,false);
                    if (!$result["result"]) {
                        continue;
                    }

                    $styles = $result["data"];
                    $object["stylesParent"] = $styles[$parentStyleID];
                }
            }
        }

        return array("result"=>true,"data"=>$dataObjects);
    }

    public static function formatSource($dataObjects) {
        $sourceData = [];

        for ($i = 0; $i < count($dataObjects); $i++) {
            $objData      = $dataObjects[$i];
            $sourceID     = $objData["sourceID"];
            $sourceParam  = $objData["sourceParam"];
            if (!$sourceData[$sourceID]) {
                $sourceData[$sourceID] = array();
                $sourceData[$sourceID]["sourceType"] = $objData["sourceType"];
                $sourceData[$sourceID]["fields"] = array();
            }
            $sourceData[$sourceID]["fields"][$sourceParam] = $objData["objectType"];
        }
        return $sourceData;
    }

    public static function getDataForFormatedSources($server,$cache,$formatedSource,$start,$end,$today,$params=null) {
        /* @var Server $server */
        /* @var Cache $cache */


        $sourceKeys = array_keys($formatedSource);
        $maxRecords = 0;

        for ($i = 0 ; $i < count($sourceKeys); $i++) {
            $sourceKey     = $sourceKeys[$i];
            $source        = &$formatedSource[$sourceKey];
            $sourceType    = $source["sourceType"];
            $providerClass = $cache->getCachedClass("providerForType".$sourceType);
            $providerClass->getRecords($source,$params,$maxRecords,$sourceType,$sourceKey,$today,$start,$end);
        }

        $records = [];
        for ($i = 0; $i < $maxRecords; $i++) {
            $record = [];
            for ($j = 0 ; $j < count($sourceKeys); $j++) {
                $sourceKey = $sourceKeys[$j];
                $source = &$formatedSource[$sourceKey];
                if (!isset($source["maxRecords"]) || !intval($source["maxRecords"])) continue;
                $index = $i % $source["maxRecords"];
                $record = array_merge($record,$source["records"][$index]);
            }
            $records[] = $record;
        }
        return array("result"=>true,"data"=>$records);
    }
    public static function recursiveChmod($path,$ftpConn, $filePerm=0644, $dirPerm=0755)
    {
        // Check if the path exists
        if(!file_exists($path))
        {
            return(FALSE);
        }
        // See whether this is a file
        if(is_file($path))
        {
            // Chmod the file with our given filepermissions
            $ftpConn?ftp_chmod($ftpConn, $filePerm, $path) :chmod($path, $filePerm);
            // If this is a directory...
        } elseif(is_dir($path)) {
            // Then get an array of the contents
            $foldersAndFiles = scandir($path);
            // Remove "." and ".." from the list
            $entries = array_slice($foldersAndFiles, 2);
            // Parse every result...
            foreach($entries as $entry)
            {
                // And call this function again recursively, with the same permissions
                Utils::recursiveChmod($path."/".$entry, $filePerm, $dirPerm);
            }
            // When we are done with the contents of the directory, we chmod the directory itself
            $ftpConn?ftp_chmod($ftpConn, $dirPerm, $path) :chmod($path, $dirPerm);

        }
        // Everything seemed to work out well, return TRUE
        return(TRUE);
    }

    public static function constructDateFormatRequestString2($tableID,$server,$addPublish=true) {
        /* @var Server $server */

        $clearTableID = substr($tableID, 1);
        $clearTableID = $tableID;//substr($clearTableID, 0,strrpos($clearTableID, "_b"));


        $result = $server->select("SELECT * FROM base_sys_new_prp WHERE ID = ?",$clearTableID);
        if (!$result["result"]) return $result;
        $data = $result["data"][0];
        $p = $data["properties"];
        $prop = json_decode($p);

        $dateMin = date("Y-m-d");
        $dateMax = date("Y-m-d");

        $request = Utils::getFilterDateRequest($prop->{'year'},$dateMin,$dateMax);

        if ($addPublish) {
            $request.= " AND PUBLISHED_ID = 1 ";
        }

        return array("result"=>true,"data"=>[$request,$prop]);
    }
    public static function getFilterDateRequest($year,$dateMin,$dateMax) {
        $dateFormat = $year == 1?"%Y-%m-%d %H:%i":"%m-%d %H:%i";
        $dateFormatQ = $year == 1?"Y-m-d H:i":"m-d H:i";

        $dateMinN = date($dateFormatQ, strtotime($dateMin));
        $dateMaxN = date($dateFormatQ, strtotime($dateMax));

        return " ((DATE_FORMAT(`DATESTART_ID_DF`, '$dateFormat') <= '$dateMinN' AND  DATE_FORMAT(`DATEEND_ID_DF`, '$dateFormat') >= '$dateMinN') OR  (  DATE_FORMAT(`DATESTART_ID_DF`, '$dateFormat')  <= '$dateMaxN' AND DATE_FORMAT(`DATESTART_ID_DF`, '$dateFormat') >= '$dateMinN')) ";
    }

    public static function clearCacheByBaseID($server,$baseID) {
        /* @var Server $server */
        $result = Utils::getTemplatesByBaseID($server,$baseID);
        if (!$result["result"]) return $result;
        $templates = $result["data"];
        $result = Utils::getFoldersByTemplatesIDs($server,$templates);
        if (!$result["result"]) return $result;
        $folders = $result["data"];
        $result = Utils::getShedulesIDsByFoldersOrTemplates($server,$folders,$templates);
        if (!$result["result"]) return $result;
        $stations = $result["data"];

        foreach ($stations as $key=>$stationID) {
            $result = Utils::clearCacheByStationID($server,$stationID);
            if (!$result["result"]) return $result;
        }
        return ["result"=>true,"data"=>"success"];
    }
    public static function clearCacheByTemplateID($server,$templateID) {
        /* @var Server $server */
        $result = Utils::getFoldersByTemplatesIDs($server,[$templateID]);
        if (!$result["result"]) return $result;
        $folders = $result["data"];
        $result = Utils::getShedulesIDsByFoldersOrTemplates($server,$folders,[$templateID]);
        if (!$result["result"]) return $result;
        $stations = $result["data"];
        foreach ($stations as $key=>$stationID) {
            $result = Utils::clearCacheByStationID($server,$stationID);
            if (!$result["result"]) return $result;
        }
        return ["result"=>true,"data"=>"success"];
    }
    public static function clearCacheByFolderID($server,$folderID) {
        /* @var Server $server */
        $folders = [$folderID];
        $result = Utils::getShedulesIDsByFoldersOrTemplates($server,$folders,[]);
        if (!$result["result"]) return $result;
        $stations = $result["data"];
        foreach ($stations as $key=>$stationID) {
            $result = Utils::clearCacheByStationID($server,$stationID);
            if (!$result["result"]) return $result;
        }
        return ["result"=>true,"data"=>"success"];
    }
    public static function clearCacheBySheduleID($server,$sheduleID) {
        /* @var Server $server */
        $result = $server->query("UPDATE `stations_sys` SET cached_blocks_date=NULL WHERE sheduleID=?",$sheduleID);
        return $result;
    }
    public static function clearCacheByStationID($server,$stationID) {
        /* @var Server $server */
        $result = $server->query("UPDATE `stations_sys` SET cached_blocks_date=NULL WHERE stationID=?",$stationID);
        return $result;
    }
    public static function clearCacheByStationRowID($server,$stationRowID) {
        /* @var Server $server */
        $result = $server->query("UPDATE `stations_sys` SET cached_blocks_date=NULL WHERE id=?",$stationRowID);
        return $result;
    }





    public static function getShedulesIDsByFoldersOrTemplates($server,$folders,$templates) {
        /* @var Server $server */
        /* @var StyleAPI $stylesAPI */
        $result = $server->select("SELECT sheduleID,stationID FROM `stations_sys`");
        if (!$result["result"]) return $result;

        $data = $result["data"];

        $shedules = [];
        foreach ($data as $key=>$value) {
            if (!isset($shedules[$value["sheduleID"]])) $shedules[$value["sheduleID"]] = [];
            $shedules[$value["sheduleID"]][] = $value["stationID"];
        }

        $arrayFilter = [];
        $array = [];
        $saveErrors = ErrorCodes::gi()->errorMessages;

        foreach ($shedules as $sheduleID=>$stations) {
            $result = $server->select("SELECT * FROM $sheduleID WHERE (templateID IN (?) AND isFolder=1) OR  (templateID IN (?) AND isFolder=0) LIMIT 0,1",implode(",",$folders),implode(",",$templates));
            if (!$result["result"]) continue;
            if (count($result["data"])) {
                foreach ($stations as $indexStation=>$stationID) {
                    if (!isset($arrayFilter[$stationID])) {
                        $arrayFilter[$stationID] = $stationID;
                        $array[] = $stationID;
                    }
                }
            }
        }

        ErrorCodes::gi()->errorMessages = $saveErrors;
        return ["result"=>true,"data"=>$array];
    }

    public static function getTemplatesByBaseID($server,$baseID) {
        /* @var Server $server */
        /* @var StyleAPI $stylesAPI */
        $result = $server->select("SELECT t.ID FROM template_1_sys as t
                           JOIN templates_obj_sys AS tobjs ON tobjs.IDGROUP = t.objectsID AND tobjs.sourceType = 0 AND tobjs.sourceID = ?
                           GROUP by t.ID",$baseID);
        if (!$result["result"]) return $result;

        $templatesUsed = $result["data"];

        $array = [];
        foreach ($templatesUsed as $key=>$indexTemplate) {
            $array[] = $indexTemplate["ID"];
        }
        return ["result"=>true,"data"=>$array];
    }

    public static function getFoldersByTemplatesIDs($server,$templatesUsed) {
        /* @var Server $server */
        /* @var StyleAPI $stylesAPI */
        $result = $server->select("SELECT * FROM folderstemplates_sys");
        if (!$result["result"]) return $result;

        $data = $result["data"];

        $folders = [];
        foreach ($data as $key=>$folder){
            if (empty($folder["properties"])) continue;
            $templates = explode(",",$folder["properties"]);
            foreach ($templates as $tkey=>$tmpID) {
                $folders[$tmpID][$folder["ID"]] = true;
            }
        }

        $foldersUsed = [];
        $array = [];
        foreach ($templatesUsed as $key=>$indexTemplate) {

            if (isset($folders[$indexTemplate])) {
                foreach ($folders[$indexTemplate] as $fkey=>$fval) {
                    if (!isset($foldersUsed[$fkey])) {
                        $foldersUsed[$fkey] = $fkey;
                        $array[] = $fkey;
                    }
                }
            }
        }
        return ["result"=>true,"data"=>$array];
    }






}