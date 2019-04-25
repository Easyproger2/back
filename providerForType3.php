<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 18.12.16
 * Time: 6:39
 */
class providerForType3
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
        // WEATHER GISMETEO

        // $sourceKey have url
        $xml_contents = file_get_contents($sourceKey);
        $xml = new SimpleXMLElement($xml_contents);
        $tmp = $xml->xpath("/MMWEATHER/REPORT/TOWN/FORECAST");

        if($tmp === false) return;

        $fieldsKeys = array_keys($source["fields"]);
        $forecast = [];

        foreach($tmp as $frcst) {
//
            $TEMPERATURE = $frcst->{"TEMPERATURE"};
            $PHENOMENA = $frcst->{"PHENOMENA"};

            if (!isset($forecast['precipitationIMG'])) $forecast['precipitationIMG'] = [];
            if (!isset($forecast['TEMPERATUREMIN'])) $forecast['TEMPERATUREMIN'] = [];
            if (!isset($forecast['TEMPERATUREMAX'])) $forecast['TEMPERATUREMAX'] = [];
            if (!isset($forecast['hour']))           $forecast['hour'] = [];
            if (!isset($forecast['day']))            $forecast['day'] = [];
            if (!isset($forecast['tod']))            $forecast['tod'] = [];

            $forecast['precipitationIMG'][(int)$frcst["tod"]] = "images/weathergis/precipitation_".$PHENOMENA["precipitation"].".png";
            $forecast['TEMPERATUREMIN'][(int)$frcst["tod"]] = (int)$TEMPERATURE["min"] . "°";
            $forecast['TEMPERATUREMAX'][(int)$frcst["tod"]] = (int)$TEMPERATURE["max"] . "°";
            $forecast['hour'][(int)$frcst["tod"]] = (int)$frcst["hour"] . ":00";
            $forecast['day'][(int)$frcst["tod"]]  = (int)$frcst["year"] . "-" . (int)$frcst["month"] . "-" . (int)$frcst["day"];
            $forecast['tod'][(int)$frcst["tod"]]  = (int)$frcst["tod"];

            foreach ($frcst as $key=>$value) {
                foreach ($value->attributes() as $keyAttr=>$valAttr) {
                    if (!isset($forecast[$key."_".$keyAttr])) $forecast[$key."_".$keyAttr] = [];
                    $forecast[$key."_".$keyAttr][(int)$frcst["tod"]] = (int)$value[$keyAttr]."";
                }
            }
        }

        $record = [];

        $objectParams = Utils::getAllObjectProperties($this->pServer);

        foreach ($fieldsKeys as $key) {
            $keyForData = substr($key, 0, -1);
            $value = $forecast[$keyForData];
            $properties = $objectParams[$sourceType][$sourceKey][$key];
            $record["$sourceKey:$key"] = ["value"=>$value,"properties"=>$properties];
        }

        $source["records"] = [$record];
        $source["maxRecords"] = 1;
        if ($maxRecords < 1) $maxRecords = 1;
    }
}