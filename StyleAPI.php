<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 29.01.15
 * Time: 1:07
 * To change this template use File | Settings | File Templates.
 */




require_once("m.php");


class StyleAPI {

    private $resourceID;

    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;
    /* @var StyleData $pStyleData*/
    private $pStyleData;
    /* @var RolesValidator $pRolesValid*/
    private $pRolesValid;

    function __construct(Server $server,Cache $cache) {
        $this->resourceID = Consts::$RESOURCE_STYLE_ID;


        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData = $this->pCache->getCachedClass("StyleData");
    }

    private static function usortGetStylesByIDS($a, $b) {
        return $a["ID"] < $b["ID"];
    }


    /* getParseData
        in => tableID id for static data
              names,  // attributes names
              values, // attribute string values
              spaces, // spaces

        out => for names result [["name","type"],....] other [,,...]
    */

    public function getParseData($getter) {
        $tableID = $getter["tableID"];
        return $this->getParseData_local($tableID);
    }

    public function getParseData_local($tableID) {
        return array("result"=>true,"data"=>$this->pStyleData->{"get_".$tableID}());
    }


    /* getStylesByIDS
        in => ids       // id for needle style example 1,2,3 or 1
        in => full_path // if 0 return local attributes for style if 1 return full
        in => short     // if 0 return full data with names if 1 return short data only indexes and values
        out => array styles
        if short = 0 {"<id>":[{"name":..,"value":...,"space":...},...],"<id>": ... etc for all ids
        if short = 1 {"<id>":"...","<id>":...} id_row:id_attr:value or id_row:id_attr:value:id_space

        error => result = false and in error key set for every bad id error
    */



    public function getStylesWithAttr($getter) {
        $ids       = $getter["id_style"];
        $attr      = $getter["attr"];

        return $this->getStylesWithAttr_local($ids,$attr);
    }

    public function getStylesWithAttr_local($ids,$attr) {

        $this->pStyleData->get_names();
        if (!is_numeric($attr)) {
            $attr = $this->pStyleData->namesS[$attr];
        }


        $result = $this->pServer->select("SELECT * FROM ".$this->pServer->getPrefix()."styles_tree_paths d
                                                   join ".$this->pServer->getPrefix()."styles_tree_paths a on (a.descendant = d.descendant)
                                               where d.ancestor = ? and d.descendant != d.ancestor group by d.descendant order by d.depth",$ids);






        if (!$result["result"]) return $result;

        $data = $result["data"];





        for ($i = 0; $i < count($data); $i++) {




        }


        return $result;
    }



    public function getStylesByIDSUp($getter) {
        $ids       = $getter["ids"];
        return $this->getStylesByIDSUP_local($ids);

    }

    public function getStylesByIDSUP_local($ids)
    {
        $ids = explode(",", $ids);
        for ($i = 0; $i < count($ids); $i++) {
            $idNeedle = $ids[$i]; // here i have id needle !
            $result = $this->pServer->select("SELECT s.* FROM " . $this->pServer->getPrefix() . "styles_tree_paths s
                                                JOIN " . $this->pServer->getPrefix() . "styles_tree_paths t
                                                  ON (s.ancestor = t.descendant)
                                               WHERE t.ancestor=? AND t.depth = 1 order by t.depth", $idNeedle);

            if ($result["result"]) {
                $data = $result["data"];
                $filteredData = [];
                for ($i = 0; $i < count($data); $i++ ) {
                    $obj = $data[$i];
                    if ($obj["depth"] == 0) {
                        $filteredData[] = $obj["descendant"];
                    }
                }
                $result["data"] = $filteredData;
            }
            return $result;
        }
    }

    public function getStylesByIDSDOWN($getter) {
        $ids       = $getter["ids"];
        return $this->getStylesByIDSUP_local($ids);

    }

    public function getStylesByIDSDOWN_local($ids)
    {
        $ids = explode(",", $ids);
        for ($i = 0; $i < count($ids); $i++) {
            $idNeedle = $ids[$i]; // here i have id needle !
            $result = $this->pServer->select("SELECT s.* FROM ".$this->pServer->getPrefix()."styles_tree_paths s
                                                JOIN ".$this->pServer->getPrefix()."styles_tree_paths t
                                                  ON (s.descendant = t.ancestor)
                                               WHERE t.descendant=? AND t.depth = 1 order by t.depth",$idNeedle);
            if ($result["result"]) {
                $data = $result["data"];
                $filteredData = [];
                for ($i = 0; $i < count($data); $i++ ) {
                    $obj = $data[$i];
                    if ($obj["depth"] == 0) {
                        $filteredData[] = $obj["descendant"];
                    }
                }
                $result["data"] = $filteredData;
            }
            return $result;
        }
    }

    public function getStylesByIDS($getter) {
        $full_path = $getter["full_path"];
        $short     = $getter["short"];
        $ids       = $getter["ids"];

        return $this->getStylesByIDS_local($ids,$full_path,$short);
    }

    public function getStylesByIDS_local($ids,$full_path,$short,$clear_result=false) {

        $ids = explode(",",$ids);
        $resultData = array();
        $success = false;
        for ($i = 0; $i < count($ids);$i++) {
            $idNeedle = $ids[$i]; // here i have id needle !
            $result = $this->pServer->select("SELECT s.* FROM ".$this->pServer->getPrefix()."styles s
                                                JOIN ".$this->pServer->getPrefix()."styles_tree_paths t
                                                  ON (s.ID = t.ancestor)
                                               WHERE t.descendant=? order by t.depth",$idNeedle);

            if ($result["result"]) {
                $data = $result["data"];
                usort($data, array('StyleAPI','usortGetStylesByIDS'));

                $objStyle = new Style($this->pCache);
                $objStyle->parse($data,$idNeedle,$full_path,$short);



                if(!$clear_result) {
                    $resultData[$idNeedle] = $objStyle->getStyle($short);
                }else {
                    $resultData = $objStyle->getStyle($short);
                }


                $success = true;
            }else {
                $resultData[$idNeedle] = $result["error"];
            }
        }
        if ($success) {

            $resultData[Config::$magicRemove] = Config::$magicRemove;

            if (!$clear_result) {
                return array("result"=>true,"data"=>$resultData);
            }else {
                return $resultData;
            }
        }else {
            return ErrorCodes::gi()->executeShort(0,$resultData,ErrorCodes::$SERVER_REQUEST_ERROR);
        }
    }

    /* getStyleIDS
        out => ids 1,2,3....
    */
    public function getStyleIDS() {
        return $this->getStyleIDS_local();
    }

    public function getStyleIDS_local($type=0) {
        $result = $this->pServer->select("SELECT DISTINCT(ID) FROM ".$this->pServer->getPrefix()."styles WHERE typeRecord=?",$type);

        if ($result["result"]) {
            $data = $result["data"];
            $ids = array();
            for ($i = 0; $i < count($data);$i++) {
                $obj = $data[$i];
                $ids[] = $obj["ID"];
            }

            return array("result"=>true,"data"=>$ids);
        }else {
            return ErrorCodes::gi()->executeShort(0,$result["error"],ErrorCodes::$SERVER_REQUEST_ERROR);
        }
    }


    public function changeAttribute_local($id_row,$new_id_attr="false",$new_value="false",$new_id_space="false",$new_scaled_flag="false"){
        $sets   = [];
        $values = [];

        $values[] = "";
        if ($new_id_attr     !== "false") {$sets[] = "id_attr=?";    $values[] = $new_id_attr;};
        if ($new_value       !== "false") {$sets[] = "value=?";      $values[] = $new_value;};
        if ($new_id_space    !== "false") {$sets[] = "id_space=?";   $values[] = $new_id_space;};
        if ($new_scaled_flag !== "false") {$sets[] = "scaled_flag=?";$values[] = $new_scaled_flag;};

        $values[] = $id_row;

        $values[0] = "UPDATE ".$this->pServer->getPrefix()."styles SET ".implode(', ',$sets)." WHERE ID_row=?";
        return call_user_func_array(array($this->pServer, 'query'), $values);
    }

    /* addAttribute
        require  in => id_style // id style
        require  in => id_attr  // id attribute
        require  in => value    // value
        optional in => id_space // space
        out => id_row   // unique id for attribute
    */
    public function addAttribute($getter) {
        $id_style    = $getter["id_style"];
        $id_attr     = $getter["attr"];
        $value       = $getter["value"];
        $id_space    = isset($getter["space"])?$getter["space"]:null;
        $scaled_flag = isset($getter["scaled_flag"])?$getter["scaled_flag"]:null;

        return $this->addAttribute_local($id_style,$id_attr,$value,$id_space,0,$scaled_flag);
    }

    public function addAttribute_local($groupID,$id_attr,$value,$id_space,$type=0,$scaled_flag = null) {
        $this->pStyleData->get_names();
        $this->pStyleData->get_spaces();
        $this->pStyleData->get_values();


        if ($id_space == NULL) $id_space = 2;
        if (!is_numeric($id_attr)) {
            $id_attr = $this->pStyleData->namesS[$id_attr];
        }

        if (!is_numeric($id_space)) {
            $id_space   = $this->pStyleData->spacesS[$id_space];
        }

        if (StyleData::$TYPE_STROKE==$this->pStyleData->get_names()[$id_attr]["type_value"]) {
            $value = $this->pStyleData->valuesS[$value];
        }
        if (!is_numeric($scaled_flag)) {
            $scaled_flag = $this->pStyleData->get_names()[$id_attr]["scaled_flag"];
        }

        $result = $this->pServer->select("SELECT ID_row FROM ".$this->pServer->getPrefix()."styles WHERE ID=? AND id_attr=?",$groupID,$id_attr);
        if ($result["result"]) {
            if (count($result["data"])) {
                $groupID = $result["data"][0]["ID_row"];


                $result = $this->changeAttribute_local($groupID,$id_attr,$value,$id_space?$id_space:"false",$scaled_flag);
                if ($result["result"]) {
                    return array("result"=>true,"data"=>array("id_row"=>$groupID));
                }else {
                    return $result;
                }
            }
        }

        $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."styles(ID,id_attr,value,id_space,typeRecord,scaled_flag) VALUES(?,?,?,?,?,?)",$groupID,$id_attr,$value,$id_space,$type,$scaled_flag);
        if ($result["result"]) {


            return array("result"=>true,"data"=>array("id_row"=>$result["data"]));
        }else {
            return $result;
        }
    }

    /* setStyleAttributes
        require  in => id   // id  style
        require  in => attributes    // array attributes [{"id_attr":"numbers","value":"string","id_space":"numbers"},...]
        out => array result // :[{"result":true,"data":{"id_row":id row attribute}},....] or error nodes
    */



    public function updateStyle($getter) {
        $id_style = $getter["id_style"];
        $attrs    = $getter["parsed_style"];
        return $this->updateStyle_local($id_style,$attrs);
    }

    public function updateStyle_local($id_style,$attrs,$type=0) {
        $answer = [];
        $answer["data"]   = array();
        $answer["result"] = true;

        $this->pStyleData->get_names();
        $this->pStyleData->get_spaces();
        $this->pStyleData->get_values();
        // here need take all attributes for needle style

        $result = $this->getStylesByIDS_local($id_style,true,false,false);
        if (!$result["result"]) return $result;


        $styles = $result["data"][$id_style];


        for ($i = 0; $i < count($attrs);$i++) {
            $obj = $attrs[$i];

            $id_attr = $this->pStyleData->namesS[$obj["name"]];
            $id_space = $this->pStyleData->spacesS[$obj["space"]];

            if (is_array($obj["value"])) $obj["value"] = json_encode($obj["value"],true);

            $can_write = true;

            for ($j = 0; $j < count($styles); $j++) {
                $style = $styles[$j];

                if (is_array($style["value"])) $style["value"] = json_encode($style["value"],true);

                if (strpos("".$style["name"],"".$obj["name"])   === 0  &&
                    strpos("".$style["value"],"".$obj["value"]) === 0 &&
                    strpos("".$style["space"],"".$obj["space"]) === 0 &&
                    strpos("".$style["scaled_flag"],"".$obj["scaled_flag"]) === 0) {
                    $can_write = false;
                }
            }


            if ($can_write ) {
                $result = $this->addAttribute_local($id_style,$id_attr,$obj["value"],$id_space,$type,$obj["scaled_flag"]);

                if (!isset($answer["result"])) $answer["result"] = $result["result"];

                $answer["result"] = $answer["result"] && $result["result"];
                $answer["data"][] = $result;
            }else {
                if (!isset($answer["result"])) $answer["result"] = true;
                $answer["result"] = $answer["result"] && true;
            }
        }

        return $answer;
    }



    public function addStyleBaseRoot($getter) {

        return $this->addStyleBaseRoot_local();
    }

    public function addStyleBaseRoot_local() {


        $resultAdd = $this->addStyle_local();

        if(!$resultAdd["result"]) return $resultAdd;

        $result = $this->setParentIDToStyle_local($resultAdd["data"]["id_style"],0);
        if(!$result["result"]) return $result;
        return $resultAdd;
    }

    /* addStyle
            require  in => id_attr  // id  attribute
            require  in => value    // value
            optional in => id_space // space
            out => id_row   // unique id for attribute
                   id_style // id for new style
        */
    public function addStyle($getter) {

        return $this->addStyle_local();
    }

    public function addStyle_local($id_attr="position",$value="absolute",$id_space=2,$scaled_flag=null) {
        $result = $this->pServer->select("SELECT MAX(ancestor) FROM ".$this->pServer->getPrefix()."styles_tree_paths");
        if (!$result["result"]) {
            return $result;
        }

        $groupID = $result["data"][0]["MAX(ancestor)"];
        $groupID+=1;
        $result = $this->setParentIDToStyle_local($groupID,$groupID);
        if (!$result["result"]) {
            return $result;
        }

        return $this->addStyleToGroupIDClear($groupID,$id_attr,$value,$id_space,$scaled_flag);
    }

    private function addStyleToGroupIDClear($groupID,$id_attr,$value,$id_space,$scaled_flag=null) {
        $result = $this->addAttribute_local($groupID,$id_attr,$value,$id_space,0,$scaled_flag);
        if ($result["result"]) {
            $id_row = $result["data"]["id_row"];

            $roles = array();
            $roles[] = ApiInfo::$ROLES_READ;
            $roles[] = ApiInfo::$ROLES_WRITE;
            $roles[] = ApiInfo::$ROLES_ADD;
            $roles[] = ApiInfo::$ROLES_DEL;
            //$this->pRolesValid->addOwnerRoles($this->resourceID,"ID",$groupID,$roles);

            $this->pStyleData->get_names();
            $id_attr = $this->pStyleData->namesS[$id_attr];

            $resultRemove = $this->removeAttribute_local($groupID,$id_attr);
            if (!$resultRemove["result"]) {
                return $resultRemove;
            }
            return array("result"=>true,"data"=>array("id_row"=>$id_row["id_row"],"id_style"=>$groupID));
        }
        return $result;
    }

    /* setParentIDToStyle
        require  in => id_style  // id style
        require  in => id_parent // id parent style
    */
    public function setParentIDToStyle($getter) {
        $id_style  = $getter["id_style"];
        $id_parent = $getter["id_parent"];
        return $this->setParentIDToStyle_local($id_style,$id_parent);
    }
    public function setParentIDToStyle_local($id_style,$id_parent) {
        $messagesError = ErrorCodes::gi()->errorMessages;
        $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."styles_tree_paths(ancestor,descendant, depth)
                                          SELECT ancestor,depth+1,? FROM ".$this->pServer->getPrefix()."styles_tree_paths
                                           WHERE descendant =?
                                UNION ALL SELECT ?,?,0",$id_style,$id_parent,$id_style,$id_style);
        if ($result["result"]) {
            return array("result"=>true,"data"=>"success");
        }else {

            $result = $this->pServer->query("DELETE a FROM ".$this->pServer->getPrefix()."styles_tree_paths AS a
                                                      JOIN ".$this->pServer->getPrefix()."styles_tree_paths AS d ON a.descendant = d.descendant
                                                 LEFT JOIN ".$this->pServer->getPrefix()."styles_tree_paths AS x
                                                        ON x.ancestor = d.ancestor AND x.descendant = a.ancestor
                                                     WHERE d.ancestor = ? AND x.ancestor IS NULL;",$id_style);
            if ($result["result"]) {
                $result = $this->pServer->query("INSERT INTO ".$this->pServer->getPrefix()."styles_tree_paths (ancestor, descendant, depth)
                                                      SELECT supertree.ancestor, subtree.descendant, supertree.depth+1
                                                        FROM ".$this->pServer->getPrefix()."styles_tree_paths AS supertree
                                                        JOIN ".$this->pServer->getPrefix()."styles_tree_paths AS subtree
                                                       WHERE subtree.ancestor = ?
                                                         AND supertree.descendant = ?",$id_style,$id_parent);
            }

        }

        if ($result["result"]) {
            ErrorCodes::gi()->errorMessages = $messagesError;
        }

        return $result;
    }

    /* removeAttribute
        in => id_attr  // id attribute
    */
    public function removeAttribute($getter) {
        $id_style  = $getter["id_style"];
        $attr  = $getter["attr"];
        return $this->removeAttribute_local($id_style,$attr);
    }

    public function removeAttribute_local($id_style,$id_attr) {

        if (!is_numeric($id_attr)) {
            $this->pStyleData->get_names();
            $id_attr = $this->pStyleData->namesS[$id_attr];
        }


        return $this->pServer->query("DELETE FROM ".$this->pServer->getPrefix()."styles WHERE ID=? AND id_attr=?",$id_style,$id_attr);
    }

    public function removeStyle($getter) {
        $id_style  = $getter["id_style"];
        return $this->removeStyle_local($id_style);
    }

    public function removeStyle_local($id_style) {
        // получаем всех кто сверху
        $result = $this->getStylesByIDSUP_local($id_style);
        if (!$result["result"]) return $result;
        $dataUP = $result["data"];
        // получаем всех кто снизу
        $result = $this->getStylesByIDSDOWN_local($id_style);
        if (!$result["result"]) return $result;
        $dataDown = $result["data"];

        if (count($dataDown) && count($dataDown) == 1) {
            // have parent
            $idDOWN = $dataDown[0];
            for ($i = 0; $i < count($dataUP);$i++) {
                $idUP = $dataUP[$i];
                // сцепляем тех кто сверху с теми кто снизу
                // то есть вырезаем текущий стиль из дерева
                $this->setParentIDToStyle_local($idUP,$idDOWN);
            }
        }

        // удаляем связи
        $result = $this->pServer->query("DELETE a FROM ".$this->pServer->getPrefix()."styles_tree_paths AS a
                                                      JOIN ".$this->pServer->getPrefix()."styles_tree_paths AS d ON a.descendant = d.descendant
                                                 LEFT JOIN ".$this->pServer->getPrefix()."styles_tree_paths AS x
                                                        ON x.ancestor = d.ancestor AND x.descendant = a.ancestor
                                                     WHERE d.ancestor = ? AND x.ancestor IS NULL;",$id_style);
        if (!$result["result"]) return $result;
        // удалем елемент из дерева
        $result = $this->pServer->query("DELETE FROM ".$this->pServer->getPrefix()."styles_tree_paths WHERE ancestor=? AND descendant=?",$id_style,$id_style);
        if (!$result["result"]) return $result;
        // удаляем из стилей
        $result = $this->pServer->query("DELETE FROM ".$this->pServer->getPrefix()."styles WHERE ID=?",$id_style);
        return $result;
    }









    // ==================== DEFAULT SECTION ===========================




    public function setParentIDToStyleDefault($getter) {
        $name_style  = $getter["name_style"];
        $name_parent_style = $getter["name_parent_style"];
        return $this->setParentIDToStyleDefault_local($name_style,$name_parent_style);
    }


    public function setParentIDToStyleDefault_local($name_style,$name_parent_style) {

        $this->pStyleData->get_default_names();


        if (!isset($this->pStyleData->default_namesS[$name_style])) {
            return array("result"=>false,"error"=>"name:".$name_style." not exist");
        }

        if (!isset($this->pStyleData->default_namesS[$name_parent_style])) {
            return array("result"=>false,"error"=>"parent name:".$name_parent_style." not exist");
        }


        $id_style  = $this->pStyleData->default_namesS[$name_style];
        $id_parent = $this->pStyleData->default_namesS[$name_parent_style];

        return $this->setParentIDToStyle_local($id_style,$id_parent);
    }


    public function updateStyleDefault($getter) {
        $name_style = $getter["name_style"];
        $attrs      = $getter["parsed_style"];
        return $this->updateStyleDefault_local($name_style,$attrs);
    }

    public function updateStyleDefault_local($name_style,$attrs) {

        $this->pStyleData->get_default_names();


        if (!isset($this->pStyleData->default_namesS[$name_style])) {
            return array("result"=>false,"error"=>"name:".$name_style." not exist");
        }

        $groupID = $this->pStyleData->default_namesS[$name_style];

        return $this->updateStyle_local($groupID,$attrs);
    }



    public function removeAttributeDefault($getter) {
        $name_style  = $getter["name_style"];
        $attr  = $getter["attr"];
        return $this->removeAttributeDefault_local($name_style,$attr);
    }

    public function removeAttributeDefault_local($name_style,$id_attr) {
        $this->pStyleData->get_default_names();


        if (!isset($this->pStyleData->default_namesS[$name_style])) {
            return array("result"=>false,"error"=>"name:".$name_style." not exist");
        }

        $groupID = $this->pStyleData->default_namesS[$name_style];
        return $this->removeAttribute_local($groupID,$id_attr);
    }


    public function addAttributeDefault($getter) {
        $name_style  = $getter["name_style"];
        $id_attr     = $getter["attr"];
        $value       = $getter["value"];
        $id_space    = isset($getter["space"])?$getter["space"]:null;
        $scaled_flag = isset($getter["scaled_flag"])?$getter["scaled_flag"]:null;
        return $this->addAttributeToDefaultStyle_local($name_style,$id_attr,$value,$id_space,$scaled_flag);
    }

    public function addAttributeToDefaultStyle_local($name_style,$id_attr,$value,$id_space,$scaled_flag=null) {
        $this->pStyleData->get_default_names();


        if (!isset($this->pStyleData->default_namesS[$name_style])) {
            return array("result"=>false,"error"=>"name:".$name_style." not exist");
        }

        $groupID = $this->pStyleData->default_namesS[$name_style];
        return $this->addAttribute_local($groupID,$id_attr,$value,$id_space,1,$scaled_flag);
    }


    public function addDefaultStyle($getter) {
        $name = $getter["name_style"];
        return $this->addDefaultStyle_local($name);
    }

    public function addDefaultStyle_local($name) {


        $this->pStyleData->get_default_names();


        if(isset($this->pStyleData->default_namesS[$name])) {
            // exist
            return array("result"=>true,"data"=>array("id_row"=>$this->pStyleData->default_namesS[$name]));
        }

        $result = $this->addStyle_local("position","absolute",2);
        if (!$result["result"]) return $result;

        $id_style = $result["data"]["id_style"];

        $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."default_styles_names (label,id_style) VALUES(?,?)",$name,$id_style);
        if ($result["result"]) {

            return array("result"=>true,"data"=>array("id_row"=>$id_style));
        }else {
            return $result;
        }

    }

    public function getDefaultStyleByName($getter) {
        $name = $getter["name_style"];
        return $this->getDefaultStyleByName_local($name);
    }

    public function getDefaultStyleByName_local($name) {


        $this->pStyleData->get_default_names();

        $id = $this->pStyleData->default_namesS[$name];


        if (!isset($this->pStyleData->default_namesS[$name])) {
            return array("result"=>false,"error"=>"name:".$name." not exist");
        }


        $result = $this->getStylesByIDS_local($id,true,false);
        if (!$result["result"]) return $result;

        return array("result"=>true,"data"=>$result["data"][$id]);
    }

}

