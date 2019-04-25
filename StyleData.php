<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 28.01.15
 * Time: 23:19
 * To change this template use File | Settings | File Templates.
 */
require_once("m.php");



class StyleData {
    // first css name
    // second type for value 0 from other table 1 real value

    public static $TYPE_STROKE = 0;
    public static $TYPE_REAL = 1;


    public $default_namesS = null;
    public $spacesS = null;
    public $namesS  = null;
    public $valuesS = null;
    public $typesS  = null;

    public  $values = null;
    private $default_names = null;
    private $names  = null;

    private $spaces = null;
    private $types = null;

    /* @var Server $pServer*/
    private $pServer = null;

    function __construct(Server $server,Cache $cache) {
        $this->pServer = $server;
    }

    public function get_types() {
        if (!$this->types) {
            // need request
            $result = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."attribute_types");

            if ($result["result"]) {
                $data = $result["data"];
                $this->types = array();
                $this->typesS = array();
                for ($i = 0;$i < count($data);$i++) {
                    $this->types[$data[$i]["ID"]] = $data[$i];
                    $this->typesS[$data[$i]["name"]] = $data[$i]["ID"];
                }
            }
        }
        return $this->types;
    }


    public function get_names() {
        if (!$this->names) {
            // need request
            $result = $this->pServer->select("SELECT an.hidden as hidden,an.depthIndex as depthIndex,an.scaled_flag as scaled_flag, an.rate as anRate,an.value as anValue,an.ID as ID,an.name as name,an.label as label,an.tooltip as tooltip,an.type as type,an.type_value as type_value, ao.id_name as aoid_name, av.value as avValue, av.label as avLabel,av.ID as avID
                 FROM ".$this->pServer->getPrefix()."attribute_names as an
            Left join ".$this->pServer->getPrefix()."attribute_options as ao ON (an.id_options = ao.ID)
            Left join ".$this->pServer->getPrefix()."attribute_values as av ON (ao.id_name = av.ID)");

            if ($result["result"]) {
                $data = $result["data"];
                $this->names = array();
                for ($i = 0;$i < count($data);$i++) {


                    if (!isset($this->names[$data[$i]["ID"]])) {
                        $this->names[$data[$i]["ID"]] = $data[$i];
                        $this->names[$data[$i]["ID"]]["options"] = array();
                    }
                    if ($data[$i]["avID"]) {
                        $indexOptions = count($this->names[$data[$i]["ID"]]["options"]);
                        $this->names[$data[$i]["ID"]]["options"][$indexOptions]["name"]    = $data[$i]["avValue"];
                        $this->names[$data[$i]["ID"]]["options"][$indexOptions]["tooltip"] = $data[$i]["avLabel"];
                        $this->names[$data[$i]["ID"]]["options"][$indexOptions]["ID"]      = $data[$i]["avID"];
                    }


                    $this->namesS[$data[$i]["name"]] = $data[$i]["ID"];
                }
            }
        }
        return $this->names;
    }

    public function get_values() {
        if (!$this->values) {
            // need request
            $result = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."attribute_values");

            if ($result["result"]) {
                $data = $result["data"];
                $this->values = array();
                $this->valuesS = array();
                for ($i = 0;$i < count($data);$i++) {
                    $this->values[$data[$i]["ID"]] = $data[$i];
                    $this->valuesS[$data[$i]["value"]] = $data[$i]["ID"];
                }
            }
        }
        return $this->values;
    }

    public function get_spaces() {
        if (!$this->spaces) {
            // need request
            $result = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."attribute_spaces");

            if ($result["result"]) {
                $data = $result["data"];
                $this->spaces = array();
                for ($i = 0;$i < count($data);$i++) {
                    $this->spaces[$data[$i]["ID"]] = $data[$i]["value"];
                    $this->spacesS[$data[$i]["value"]] = $data[$i]["ID"];
                }
            }
        }
        return $this->spaces;
    }

    public function get_default_names() {
        if (!$this->default_names) {
            // need request
            $result = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."default_styles_names");

            if ($result["result"]) {
                $data = $result["data"];
                $this->default_names = array();
                for ($i = 0;$i < count($data);$i++) {
                    $this->default_names[$data[$i]["ID"]] = $data[$i]["label"];
                    $this->default_namesS[$data[$i]["label"]] = $data[$i]["id_style"];
                }
            }
        }
        return $this->default_names;
    }

}