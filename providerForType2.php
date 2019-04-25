<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 18.12.16
 * Time: 6:38
 */
class providerForType2
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

        $xml_contents = file_get_contents($sourceKey);
        if($xml_contents === false) return;
        $xml = new SimpleXMLElement($xml_contents);
        $tmp = $xml->xpath("/ValCurs/Valute[ @ID='R01235' or @ID='R01239']");

        if($tmp === false) return;

        $inc = 0;
        $rss = [];
        $tmpDate = $xml->xpath("/ValCurs");
        if($tmpDate === false) return;
        $rss["date"] = $tmpDate[0]->attributes()["Date"]."";


        $objectParams = Utils::getAllObjectProperties($this->pServer);


        foreach($tmp as $item) {
            foreach ($item as $attr=>$value) {
                $key = $sourceKey.":".$attr.$inc;
                $properties = $objectParams[$sourceType][$sourceKey][$attr.$inc];
                if ($attr == "Value") {
                    $value = substr($value."",0,strpos($value."",",")+3);
                }
                $rss[$key] = ["value"=>$value."","properties"=>$properties];
            }
            $inc++;
        }

        $source["records"] = [$rss];
        $source["maxRecords"] = 1;
        if ($maxRecords < 1) $maxRecords = 1;
    }
}