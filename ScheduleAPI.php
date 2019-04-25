<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 */

require_once ("Cron/FieldInterface.php");
require_once ("Cron/AbstractField.php");

require_once ("Cron/CronExpression.php");
require_once ("Cron/DayOfMonthField.php");
require_once ("Cron/DayOfWeekField.php");
require_once ("Cron/FieldFactory.php");

require_once ("Cron/HoursField.php");
require_once ("Cron/MinutesField.php");
require_once ("Cron/MonthField.php");
require_once ("Cron/YearField.php");

require_once("m.php");

class ScheduleAPI
{

    public static $GET_RECORDS_PER_PAGE_NUM = 10;

    private $resourceID;

    /* @var Server $pServer */
    private $pServer;
    /* @var Cache $pCache */
    private $pCache;
    /* @var StyleData $pStyleData */
    private $pStyleData;
    /* @var RolesValidator $pRolesValid */
    private $pRolesValid;

    function __construct(Server $server, Cache $cache)
    {
        date_default_timezone_set('Europe/Moscow');
        $this->resourceID = Consts::$RESOURCE_RECORDS_ID;

        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData = $this->pCache->getCachedClass("StyleData");
    }


    function getScheduleForTagsAndDate($getter) {
        $startDate = isset($getter['startDate']) ? $getter['startDate'] : date("Y-m-d 00:00:00");
        $endDate   = isset($getter['endDate']) ? $getter['endDate'] : date("Y-m-d 23:59:59");
        $tags  = $getter['tags'];

        return $this->getScheduleForTagsAndDate_local($startDate,$endDate,$tags);
    }

    function getFilterDateRequest($year,$dateMin,$dateMax) {
        $dateFormat = $year == 1?"%Y-%m-%d %H:%i":"%m-%d %H:%i";
        $dateFormatQ = $year == 1?"Y-m-d H:i":"m-d H:i";

        $dateMinN = date($dateFormatQ, strtotime($dateMin));
        $dateMaxN = date($dateFormatQ, strtotime($dateMax));

        return " ((DATE_FORMAT(`dateStart`, '$dateFormat') <= '$dateMinN' AND  DATE_FORMAT(`dateEnd`, '$dateFormat') >= '$dateMinN') OR  (  DATE_FORMAT(`dateStart`, '$dateFormat')  <= '$dateMaxN' AND DATE_FORMAT(`dateStart`, '$dateFormat') >= '$dateMinN')) ";
    }

    private static function sortingfixTimeBlocks($a, $b) {
        return $a[0] > $b[0];
    }
    private static function sortingTimeBlocks($a, $b) {
        return $a[0] > $b[0];
    }
    function recurseInsertFixBlocks(&$fixTimeBlocks,&$minTimeStartTemplate,&$timeBlock,$isFixTime) {
        $blockstart = $minTimeStartTemplate;
        $blockEnd   = $minTimeStartTemplate+$timeBlock;



        for ($i = 0;$i < count($fixTimeBlocks);$i++){
            $block = $fixTimeBlocks[$i];
            $writedBlockStart = $block[0];
            $writedBlockEnded = $block[1];

            if ($blockstart < $writedBlockStart){
                $sss = $blockEnd > $writedBlockStart;

                if ($sss && !$isFixTime) {
                    $timeBlock-=$blockEnd - $writedBlockStart;

                    return $minTimeStartTemplate;
                }

            } else {
                $sss = $blockstart < $writedBlockEnded;
            };


            if ($sss) {
                $minTimeStartTemplate = $this->recurseInsertFixBlocks($fixTimeBlocks,$writedBlockEnded,$timeBlock,$isFixTime);
            }
        }



        return $minTimeStartTemplate;
    }

    function getScheduleForTagsAndDate_local($startDate,$endDate,$tags) {

        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);

        $tagsWHERE = [];
        for ($i = 0; $i < count($tags);$i++) {
            $tagsWHERE[] = "id_tag=".intval($tags[$i]);
        }

        $dateRequest = $this->getFilterDateRequest(1,$startDate,$endDate);


        $result = $this->pServer->select("SELECT * FROM sys_tags WHERE type=1 AND $dateRequest");
        if (!$result["result"]) return $result;

        $tagsMap = [];
        $crons = [];

        $rareRecords = [];
        $freeRecords = [];

        for ($i = 0; $i < count($result["data"]);$i++) {
            $obj = $result["data"][$i];
            $tagID = intval($obj["ID"]);

            $crons[$tagID] = ["isRare"=>intval($obj["isRare"]),"rule"=>$obj["data"]];

            if (intval($obj["isRare"])) {
                $rareRecords[$tagID] = [];
            }else {
                $freeRecords[$tagID] = [];
            }

            $tagsMap[$tagID] = true;
            $tagsWHERE[] = "id_tag=".$tagID;
        }

        $result = $this->pServer->select("SELECT ID_group,ID 
                                   FROM sys_tags_groups  
                                   WHERE ".implode(" OR ",$tagsWHERE)." 
                                   GROUP BY ID_group,ID 
                                   HAVING SUM(type = 1)AND SUM(type = 0)");
        if (!$result["result"]) return $result;


        $recordsIDS = [];
        $requestTemplateTagsIDS = [];

        $instances = [];
        foreach ($result["data"] as $key=>$value) {

            $groupID  = $value["ID"];
            $recordID = $value["ID_group"];

            $recordsIDS[$recordID] = $recordID;

            // get group
            $result = $this->pServer->select("SELECT id_tag,type,data
                                   FROM sys_tags_groups  
                                   WHERE ID=$groupID AND ID_group=$recordID");
            if (!$result["result"]) continue;

            $instances[$groupID] = ["time"=>0,"templateInfo"=>0];
            foreach ($result["data"] as $keyT=>$valueT) {
                $idTag = $valueT["id_tag"];
                $type = $valueT["type"];
                $data = $valueT["data"];
                if ($type === 2) {
                    $requestTemplateTagsIDS[$idTag] = "initialize";
                    $instances[$groupID]["templateInfo"] = ["info"=>&$requestTemplateTagsIDS[$idTag]] ;
                    $instances[$groupID]["time"] = $data;
                    $requestTemplateTagsIDS[$idTag] = $idTag;
                    continue;
                }
                if (!isset($tagsMap[$idTag])) continue;

                if ($crons[$idTag]["isRare"]) {
                    $rareRecords[$idTag][$recordID."_".$groupID] = ["idRecord"=>$recordID,"idTemplate"=>&$instances[$groupID]["templateInfo"],"time"=>&$instances[$groupID]["time"]];
                }else {
                    $freeRecords[$idTag][$recordID."_".$groupID] = ["idRecord"=>$recordID,"idTemplate"=>&$instances[$groupID]["templateInfo"],"time"=>&$instances[$groupID]["time"]];
                }
            }
        }

        $recordsIDS = array_keys($recordsIDS);
        $templateTagsIDS = array_keys($requestTemplateTagsIDS);

        $result = $this->pServer->select("SELECT * FROM sys_tags WHERE ID IN(".implode(",",$templateTagsIDS).")");
        if (!$result["result"]) return $result;

        foreach ($result["data"] as $key=>$value) {
            $requestTemplateTagsIDS[$value["ID"]] = $value["data"];
        }

        $result = $this->pServer->select("SELECT *,ss_base.ID as ss_baseID FROM system_fields_bases as ss_base
                                              LEFT JOIN data_sys as ds ON ds.sourceID=ss_base.baseID AND ds.groupID=ss_base.recordID 
                                              LEFT JOIN ".config::$prefix."content as cont ON cont.ID=ds.valueID
                                           WHERE ss_base.ID IN (".implode(",",$recordsIDS).") ");
        if (!$result["result"]) return $result;

        $recordsIDS = $result["data"];
        $recordsPre = [];

        for ($j = 0; $j < count($recordsIDS);$j++) {

            $objData    = $recordsIDS[$j]; // object

            $ss_baseID  = $objData["ss_baseID"];

            $sourceID   = $objData["sourceID"];
            $key        = $objData["sourceParam"];
            $recordID   = $objData["recordID"];
            $value      = $objData["value"];
            $properties = json_decode($objData["properties"]);

            $recordsPre[$ss_baseID]["$sourceID:$key"] = ["value"=>$value,"properties"=>$properties];
        }

        $currentTime = clone $startDateTime;

        $fixTimeBlocks = [];



        $startTimeCodeMS = microtime();

        foreach ($rareRecords as $key=>$value) {

            $tagInfo = $crons[$key];
            $cronRule = $tagInfo["rule"];

            try {
                $cron = Cron\CronExpression::factory($cronRule);
            }catch (Exception $e) {
                return ["result"=>false,"error"=>$e->getMessage()];
                continue;
            }

            $currentTime = clone $startDateTime;

            foreach ($value as $keyR=>$valueR) {
                $timeRecord = $valueR["time"];


                for ($i = 0; $i < 100; $i++) {
                    try {
                        $date = $cron->getNextRunDate($currentTime, 0, false);

                        if ($date->getTimestamp()  > $endDateTime->getTimestamp()) {
                            break;
                        }

                        $blockData = [];

                        $startTimeInDay = ($date->getTimestamp()-$startDateTime->getTimestamp())*1000;
                        $endTimeInDay   = $startTimeInDay+$timeRecord;
                        $minTimeStartTemplate = $this->recurseInsertFixBlocks($fixTimeBlocks,$startTimeInDay,$timeRecord,false);


                        $blockData[] = $minTimeStartTemplate;
                        $blockData[] = $minTimeStartTemplate+$valueR["time"];
                        $blockData[] = $valueR["idRecord"];
                        $blockData[] = $valueR["idTemplate"];

                        $fixTimeBlocks[] = $blockData;

                        $currentTime = $date;


                    } catch (RuntimeException $e) {

                        return ["result"=>false,"error"=>$e->getMessage()];
                        break;
                    }
                }
            }
        }



        $endTimeCodeMS = microtime()-$startTimeCodeMS ;

        usort($fixTimeBlocks, array('ScheduleAPI','sortingfixTimeBlocks'));

        $blocks = [];
        $minTimeStartTemplate = 0;

        $indexFix = 0;
        $timeToNextFixBlock = 86400000;
        $timeToNextFixBlockEnd = 86400000;

        if (count($fixTimeBlocks)) {
            $fixBlock = $fixTimeBlocks[$indexFix];
            $timeToNextFixBlock    = $fixBlock[0];
            $timeToNextFixBlockEnd = $fixBlock[1];
        }

        $startTimeCodeMS = microtime();

        for (; $minTimeStartTemplate < 86400000 ;) {
            $lastTime = $minTimeStartTemplate;

            foreach ($freeRecords as $key=>$value) {
                foreach ($value as $keyR=>$valueR) {
                    $timeRecord = $valueR["time"];
                    $blockData = [];
                    try {

                        if ($minTimeStartTemplate+$timeRecord <= $timeToNextFixBlock) {
                        }else {
                            $getNewPoint = false;
                            for (; $indexFix < count($fixTimeBlocks);$indexFix++) {
                                $fixBlock = $fixTimeBlocks[$indexFix];
                                $timeToNextFixBlock    = $fixBlock[0];
                                $timeToNextFixBlockEnd = $fixBlock[1];
                                if ($minTimeStartTemplate+$timeRecord <= $timeToNextFixBlock) {
                                    $getNewPoint = true;
                                    break;
                                }
                                $minTimeStartTemplate  = $timeToNextFixBlockEnd;
                            }
                        }

                        $blockData[] = $minTimeStartTemplate;
                        $blockData[] = $minTimeStartTemplate+$timeRecord;
                        $blockData[] = $valueR["idRecord"];
                        $blockData[] = $valueR["idTemplate"];

                        $minTimeStartTemplate=$blockData[1];
                        $blocks[] = $blockData;

                    }catch (Exception $e) {
                        return ["result"=>false,"error"=>$e->getMessage()];
                    }

                }
            }

            if ($lastTime == $minTimeStartTemplate) {
                $minTimeStartTemplate+=5000;
            }
        }

        for ($i = 0; $i < count($fixTimeBlocks);$i++) {
            $block = $fixTimeBlocks[$i];

            $block[] = "FIX";

            $blocks[] = $block;

        }

        usort($blocks, array('ScheduleAPI','sortingTimeBlocks'));

        $endTimeCodeMS = microtime()-$startTimeCodeMS ;

        return ["result"=>true,"data"=>$blocks];
    }
}