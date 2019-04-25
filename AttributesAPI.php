<?php
/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 19.08.15
 * Time: 16:46
 */



require_once("m.php");


class AttributesAPI
{

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
        $this->resourceID = Consts::$RESOURCE_ATTRS_ID;


        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData = $this->pCache->getCachedClass("StyleData");
    }



    public function getAttributes($getter) {
        $short = isset($getter["short"]) ? $getter["short"] : false;

        return $this->getAttributes_local($short);
    }
    public function getAttributes_local($short) {

        $attributes = $this->pStyleData->get_names();

        $style = new Style($this->pCache);

        foreach($attributes as $attr ) {
            $style->parseAttributes(["id_attr"=>$attr["ID"],"value"=>$attr["anValue"],"rate"=>$attr["anRate"]],$short,0);
        }
        return array("result"=>true,"data"=>$style->getStyle($short));
    }

    public function updateAttribute($getter) {
        $name        = $getter["name_attribute"];
        $label       = isset($getter["label"])?$getter["label"]:false;
        $tooltip     = isset($getter["tooltip"])?$getter["tooltip"]:false;
        $options_id  = isset($getter["options_id"])?$getter["options_id"]:-1;
        $type        = isset($getter["type"])?$getter["type"]:false;
        $defValue    = isset($getter["defValue"])?$getter["defValue"]:false;
        $options     = isset($getter["options"])?$getter["options"]:false;
        $rate        = isset($getter["rate"])?$getter["rate"]:false;


        return $this->updateAttribute_local($name,$label,$tooltip,$options,$type,$options_id,$defValue,$rate);
    }

    public function updateAttribute_local($name,$label = false,$tooltip = false,$options = false,$type = false,$options_id = -1,$defValue = false,$rate = false) {

        $this->pStyleData->get_names(); // fill data
        $this->pStyleData->get_types();

        $nameID = $this->pStyleData->namesS[$name];
        $type_value = false; // default real value

        if ($options_id != -1) {
            $type_value = 0;
        }

        if ($this->pStyleData->typesS[$type ? $type : "input"] == $this->pStyleData->typesS["select"]) {
            $type_value = 0;
        }

        if (!isset($this->pStyleData->namesS[$name])) {

            $type_value = !$type_value ? 1 : $type_value;

            if (!$type) {
                $type_id = $options? $this->pStyleData->typesS["select"] : $this->pStyleData->typesS["input"];
            }else {
                $type_id =  isset($this->pStyleData->typesS[$type]) ? $this->pStyleData->typesS[$type] : ($options ? $this->pStyleData->typesS["select"] : $this->pStyleData->typesS["input"]);
            }

            if ($options) {
                $type_value = 0;
                $result = $this->updateOptions_local($options,$options_id);
                if (!$result["result"]) return $result;
                $options_id = $result["data"]["options_id"];
            }

            $label = $label?$label:"";
            $tooltip = $tooltip?$tooltip:"";

            if (!$defValue) $defValue = "";

            $options_id = $options_id == -1 ? 0 : $options_id;
            // no attribute need add

            $this->pStyleData->get_values();

            if ($type_value == 0) { // collection
                $defValue = $this->pStyleData->valuesS[$defValue];
            }

            if ($rate === false) {
                $rate = 0;
            }

            $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."attribute_names (name,label,tooltip,type_value,id_options,type,value) VALUES(?,?,?,?,?,?,?,?)",$name,$label,$tooltip,$type_value,$options_id,$type_id,$defValue,$rate);
            if (!$result["result"]) return $result; // bad request
            $nameID = $result["data"];
        }else {


            if (!$type) {
                $type_id = $options? $this->pStyleData->typesS["select"] : false;
            }else {

                $type_id =  isset($this->pStyleData->typesS[$type]) ? $this->pStyleData->typesS[$type] : ($options ? $this->pStyleData->typesS["select"] : $this->pStyleData->typesS["input"]);

            }

            if ($options) {
                $type_value = 0;

                $result = $this->pServer->select("SELECT id_options FROM ".$this->pServer->getPrefix()."attribute_names WHERE ID=?",$nameID);
                if (!$result["result"]) return $result;

                $options_id = $result["data"][0]["options_id"];

                $result = $this->updateOptions_local($options,$options_id);
                if (!$result["result"]) return $result;
                $options_id = $result["data"]["options_id"];
            }

            $sets = [];
            $vals = [];
            $vals[] = "";

            if ($label)            { $sets[] = "label=?";      $vals[] = $label;}
            if ($tooltip)          { $sets[] = "tooltip=?";    $vals[] = $tooltip;}

            if ($type_id)          { $sets[] = "type=?";       $vals[] = $type_id;}
            if ($options_id != -1) { $sets[] = "id_options=?"; $vals[] = $options_id;}
            if ($type_value)       { $sets[] = "type_value=?"; $vals[] = $type_value;}
            if ($defValue !== false)         {
                $this->pStyleData->get_values();

                if (StyleData::$TYPE_STROKE == $this->pStyleData->get_names()[$nameID]["type_value"]) {
                    $defValue = $this->pStyleData->valuesS[$defValue];
                }

                $sets[] = "value=?";      $vals[] = $defValue;
            }

            if ($rate !== false) {
                $sets[] = "rate=?";      $vals[] = $rate;
            }


            if (count($sets)) {
                $vals[] = $nameID; // get id name
                $vals[0] = "UPDATE ".$this->pServer->getPrefix()."attribute_names SET ".implode(', ',$sets)." WHERE ID=?";
                $result =  call_user_func_array(array($this->pServer, 'query'), $vals);
                if (!$result["result"]) return $result; // bad request
            }
        }



        return array("result"=>true,"data"=>array("id_attribute"=>$nameID));
    }



    public function updateOptions($getter) {
        $options    = $getter["options"];
        $options_id = isset($getter["options_id"])?$getter["options_id"]:false;
        return $this->updateOptions_local($options,$options_id);
    }

    public function updateOptions_local($options,$options_id = false) {
        if (!$options) return ErrorCodes::gi()->executeShort(0,"not set options!",ErrorCodes::$SERVER_REQUEST_ERROR);


        if (!$options_id || $options_id == -1 || $options_id == 0) {
            // need add new group
            $result = $this->pServer->select("SELECT MAX(ID) FROM ".$this->pServer->getPrefix()."attribute_options");
            if (!$result["result"]) return $result;
            $options_id = $result["data"][0]["MAX(ID)"];
            $options_id++;
        }

        $this->pStyleData->get_values();

        for ($i = 0; $i < count($options);$i++) {
            $option = $options[$i];
            $value = $option["value"];
            $label = $option["label"];

             // need fill values

            $result = $this->updateValue_local($value,$label,false);
            if (!$result["result"]) return $result;

            $value_id = $result["data"]["value_id"];

            // here i have value_id ! can add option
            $result = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."attribute_options WHERE ID=? AND id_name=?",$options_id,$value_id);
            if (!$result["result"]) return $result;

            if (count($result["data"]) == 0) {
                $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."attribute_options (ID,id_name) VALUES(?,?)",$options_id,$value_id);
                if (!$result["result"]) return $result;
            }else {
                // have this option ! ignore
            }
        }

        return array("result"=>true,"data"=>array("options_id"=>$options_id));
    }


    public function updateValue($getter) {
        $value = $getter["value"];
        $label = $getter["label"];
        return $this->updateValue_local($value,$label);
    }

    public function updateValue_local($value,$label,$get_values = true) {
        if ($get_values) $this->pStyleData->get_values();


        $value_id = $this->pStyleData->valuesS[$value];
        if (!isset($this->pStyleData->valuesS[$value])) {
            // need add value

            $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."attribute_values (value,label) VALUES(?,?)",$value,$label);
            if (!$result["result"]) return $result;

            $this->pStyleData->valuesS[$value] = $result["data"]; // update

            return array("result"=>true,"data"=>array("value_id"=>$result["data"]));
        } else {
            if (strcmp($this->pStyleData->values[$value_id]["label"],$label) !== 0 ) {
                $result = $this->pServer->query( "UPDATE ".$this->pServer->getPrefix()."attribute_values SET label=? WHERE ID=?",$label,$value_id);
                if (!$result["result"]) return $result;
                $this->pStyleData->values[$value_id]["label"] = $label;
            }
        }




        return array("result"=>true,"data"=>array("value_id"=>$value_id));
    }



}