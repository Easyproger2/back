<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 28.01.15
 * Time: 21:50
 * To change this template use File | Settings | File Templates.
 */


require_once("m.php");


class Style {
    private $arrayAttributes = array();

    /* @var Cache $pCache*/
    private $pCache;
    /* @var StyleData $pStyleData*/
    private $pStyleData;
    /* @var ModifiersAPI pModifiersData*/
    private $pModifiersData;


    function __construct(Cache $cache) {
        $this->pCache = $cache;
        $this->pStyleData = $this->pCache->getCachedClass("StyleData");
        $this->pModifiersData = $this->pCache->getCachedClass("ModifiersAPI");
    }

    public function parseAttributes($attribute,$short,$id_style) {

        $idAttr = $attribute["id_attr"];
        $attrProp = $this->pStyleData->get_names()[$idAttr];
        if (!isset($this->arrayAttributes[$attrProp["name"]])) {

            $type = $attrProp["type_value"];

            $obj = array();
            $obj["name"] = $attrProp["name"];
            if ($type == StyleData::$TYPE_STROKE) {
                $obj["value"] = $this->pStyleData->get_values()[$attribute["value"]]["value"];
                $obj["space"] = $this->pStyleData->get_spaces()[$attribute["id_space"]];
            }else if ($type == StyleData::$TYPE_REAL) {
                $obj["value"] = isJsonString($attribute["value"]) ? json_decode($attribute["value"],true) : $attribute["value"];
                $obj["space"] = $this->pStyleData->get_spaces()[$attribute["id_space"]];
            }else {
                $obj["value"] = isJsonString($attribute["value"]) ? json_decode($attribute["value"],true) : $attribute["value"];
            }

            $modifiers = [];
            if ($attrProp["modifiers_id"] !== 0) {
                $modifiersRaw = $this->pModifiersData->getModifiersForObject_local($attribute["modifiers_id"]);
                if ($modifiersRaw["result"])
                    $modifiers = $modifiersRaw["data"];
            }
            $obj["modifiers"] = $modifiers;


            $obj["id_attr"] = $idAttr;

            $obj["hidden"]     = $this->pStyleData->get_names()[$attribute["id_attr"]]["hidden"];
            $obj["depthIndex"] = $this->pStyleData->get_names()[$attribute["id_attr"]]["depthIndex"];
            $obj["id_row"] = $attribute["ID_row"];
            $obj["id_style"] = $id_style;
            $obj["rate"] = $attribute["rate"];
            $obj["scaled_flag"] = $attribute["scaled_flag"];

            $idType = $this->pStyleData->get_names()[$attribute["id_attr"]]["type"];
            $obj["form"] = array();

            $obj["form"]["label"]   = $this->pStyleData->get_names()[$attribute["id_attr"]]["label"];
            $obj["form"]["tooltip"] = $this->pStyleData->get_names()[$attribute["id_attr"]]["tooltip"];
            $obj["form"]["type"]    = $this->pStyleData->get_types()[$idType]["name"];

            $numOptions = count($this->pStyleData->get_names()[$attribute["id_attr"]]["options"]);
            if($numOptions) {
                $obj["form"]["variables"]  = $this->pStyleData->get_names()[$attribute["id_attr"]]["options"];
            }
            $this->arrayAttributes[$attrProp["name"]] = $obj;

        }
    }


    private function sortAttributes($a,$b) {
        if ($a == $b) {
            return 0;
        }
        return ($a["depthIndex"] < $b["depthIndex"]) ? -1 : 1;
    }



    public function parse($stylesData,$needleId,$parseFull,$short) {
        for ($i = 0;$i<count($stylesData);$i++) {
            $attr = $stylesData[$i];
            $id = $attr["ID"];
            if (!$parseFull && $id != $needleId) continue;
            $this->parseAttributes($attr,$short,$id);
        }

        // usort( $this->arrayAttributes, array( $this, 'sortAttributes' ) );

        //sort($this->arrayAttributes);
    }

    public function getStyle($short = false) {
        return $this->arrayAttributes;

//
//        if (!$short) {
//            return $a;
//        }
//        $countA = count($a );
//        if ($countA== 0) return "";
//
//        $s = $a[0];
//        for ($i = 1;$i < $countA;$i++) {
//            $s.= ";".$a[$i];
//        }
//        return $s;

    }
}