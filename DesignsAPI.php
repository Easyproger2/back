<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 22.02.17
 * Time: 18:04
 */

require_once("m.php");
class DesignsAPI
{


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
        $this->resourceID = Consts::$RESOURCE_DESIGNS_ID;

        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData  = $this->pCache->getCachedClass("StyleData");
    }



    public function doubleDesign($getter) {
        $id            = $getter["templateID"];
        $doubleName    = $getter["doubleName"];
        $isDoubleBases = $getter["isDoubleBases"];
        return $this->doubleDesign_local($id,$doubleName,$isDoubleBases);
    }

    public function doubleDesign_local($id,$doubleName,$isDoubleBases) {

        $tablesName = [];

        $result = $this->pServer->select("show tables");
        if (!$result["result"]) return $result;
        foreach ($result["data"] as $key=>$value) $tablesName[array_pop( $value)] = true;

        // first window
        $result = $this->getDesignInfo_local($id,1);
        if (!$result["result"]) return $result;

        $designInfo       = $result["data"];
        $designIDToDouble = $result["data"]["id"];

        $objects1 = $designInfo["objects"];

        /* @var ObjectsAPI $objectsAPI*/
        $objectsAPI = $this->pCache->getCachedClass("ObjectsAPI");

        // double first window
        $result = $objectsAPI->doubleObjects_local($this->pCache,$this->pServer,$isDoubleBases,$doubleName,$objects1);
        if (!$result["result"]) return $result;

        $newObjects1ID = $result["data"];

        // second window
        $result = $this->getDesignInfo_local($id,2);
        if (!$result["result"]) return $result;

        $designInfo = $result["data"];
        $objects2   = $designInfo["objects"];

        // double second window
        $result = $objectsAPI->doubleObjects_local($this->pCache,$this->pServer,$isDoubleBases,$doubleName,$objects2);
        if (!$result["result"]) return $result;

        $newObjects2ID = $result["data"];

        // double record design
        $result = Utils::doubleRow_local($this->pServer,"tmpl_win_sys",$designIDToDouble,["ID"]);
        if (!$result["result"]) return $result;
        $newDesignID   = $result["data"]["insertID"];

        $roles = array();
        $roles[] = ApiInfo::$ROLES_READ;
        $roles[] = ApiInfo::$ROLES_WRITE;
        $roles[] = ApiInfo::$ROLES_ADD;
        $roles[] = ApiInfo::$ROLES_DEL;
        $this->pRolesValid->addOwnerRoles($this->resourceID,-1,$newDesignID,$roles);

        // update doubled design
        $result = $this->pServer->query("UPDATE tmpl_win_sys SET w1 = ?,w2 = ?,name=? WHERE ID = ?",$newObjects1ID,$newObjects2ID,$doubleName,$newDesignID);
        if (!$result["result"]) return $result;

        return ["result"=>true,"data"=>"success"];
    }



    public function getDesignInfo($getter) {
        $id              = $getter['templateID'];
        $designWindowNums = $getter['designWindowNums'];
        $parseObjectNames = isset($getter["parseObjectNames"]) ? $getter["parseObjectNames"] : false;
        return $this->getDesignInfo_local($id,$designWindowNums,$parseObjectNames);
    }


    public function getDesignInfo_local($id,$designWindowNums,$parseObjectNames = false) {

        $result = $this->pServer->select('SELECT * FROM tmpl_win_sys WHERE ID = ?',$id);

        if (!$result["result"]) return $result;
        if (!count($result["data"])) return array("result"=>false,"error"=>"bad id");

        $dataDesign = $result["data"][0];


        $objects = [];
        for ($i= 0 ; $i< count($designWindowNums);$i++) {
            if (!is_numeric($designWindowNums[$i])) return array("result"=>false,"error"=>"bad num window");

            $designWindowNum = $designWindowNums[$i]+1;
            if (!isset($dataDesign["w".$designWindowNum])) return array("result"=>false,"error"=>"bad num window");
            $objectsID = $dataDesign["w".$designWindowNum];

            /* @var StyleAPI $stylesAPI*/
            $stylesAPI = $this->pCache->getCachedClass("StyleAPI");

            $resultObjects = Utils::getObjectsForTemplate($this->pServer,$objectsID,$stylesAPI,$parseObjectNames);
            if (!$resultObjects["result"]) return $resultObjects;

            $objects[] = [
                "objects"=>$resultObjects["data"],
                "objectsID"=>$objectsID
            ];
        }
        $desgin = array(
            "windows"       => $objects,
            "name"          => $dataDesign['name'],
            "id"            => $dataDesign['ID'],
            "smallWidth"    => $dataDesign["smallWidth"],
            "smallHeight"   => $dataDesign["smallHeight"],
            "fullWidth"     => $dataDesign["fullWidth"],
            "fullHeight"    => $dataDesign["fullHeight"]
        );


        return array("result"=>true, 'data' => $desgin);
    }


    public function getListDesign($getter) {
        return $this->getListDesign_local($getter["-1"]);
    }

    public function getListDesign_local($filterDesigns) {

        if (isset($filterDesigns)) {
            $result  = $this->pServer->select("SELECT * FROM tmpl_win_sys WHERE ID IN(".$filterDesigns.")") ;
            if (!$result["result"]) return $result;
        }else {
            $result = $this->pServer->select("SELECT * FROM tmpl_win_sys");
            if (!$result["result"]) return $result;
        }

        $answer = [];
        $data = $result["data"];
        for ($indexData = 0;$indexData<count($data);$indexData++) {
            $rows = $data[$indexData];
            $answer[] =array(
                "name"          => trim($rows['name'],'"'),
                "properties"    => $rows['properties'],
                "typelayer"     => $rows['typelayer'],
                "id"            => $rows['ID']
            );
        }

        return array("result"=>true,'data' => $answer);
    }



    public function updateDesign($getter) {
        $id              = $getter['id'];
        $objects         = $getter['objects'];
        $designWindowNum = $getter['designWindowNum'];
        $onlyUpdate      = isset($getter['onlyUpdate']) ? $getter['onlyUpdate'] : 0;
        return $this->updateDesign_local($id,$objects,$designWindowNum,$onlyUpdate);
    }

    public function updateDesign_local($id,$objects,$designWindowNum,$onlyUpdate = 0) {

        $result = $this->pServer->select("SELECT * FROM tmpl_win_sys WHERE ID=?",$id);
        if (!$result["result"]) return $result;
        $windowInfo = $result["data"][0];
        if (!isset($windowInfo['w'.$designWindowNum])) return array("result"=>false,"error"=>"bad id window");

        /* @var StyleAPI $baseAPI*/
        $stylesAPI = $this->pCache->getCachedClass("StyleAPI");

        /* @var ContentAPI $contentAPI*/
        $contentAPI = $this->pCache->getCachedClass("ContentAPI");

        /* @var ObjectsAPI $objectsAPI*/
        $objectsAPI = $this->pCache->getCachedClass("ObjectsAPI");



        $resultUpdate =  $objectsAPI->updateObjects_local($this->pServer,$stylesAPI,$contentAPI,$objects,$windowInfo['w'.$designWindowNum],$onlyUpdate);
        if (!$resultUpdate["result"]) return $resultUpdate;

        $result = $this->pServer->query("UPDATE tmpl_win_sys SET w".$designWindowNum."=? WHERE ID=? ",$resultUpdate["data"]['objectsID'],$id);
        if (!$result["result"]) return $result;

        return $resultUpdate;
    }

    public function removeDesign($getter) {
        $templateID      = $getter['templateID'];
        return $this->removeDesign_local($templateID);
    }
    public function removeDesign_local($templateID) {

        $result = $this->updateDesign_local($templateID,[],1);
        if (!$result["result"]) return $result;

        $result = $this->updateDesign_local($templateID,[],2);
        if (!$result["result"]) return $result;

        $result = $this->pServer->query("DELETE FROM tmpl_win_sys WHERE ID=?",$templateID);
        if (!$result["result"]) return $result;

        if ($result["result"] === true) {
            $roles = array();
            $roles[] = -1;
            $roles[] = ApiInfo::$ROLES_READ;
            $roles[] = ApiInfo::$ROLES_WRITE;
            $roles[] = ApiInfo::$ROLES_ADD;
            $roles[] = ApiInfo::$ROLES_DEL;
            $this->pRolesValid->removeOwnerRoles($this->resourceID,-1,$templateID,$roles);
        }

        return array("result"=>true,"data"=>"success");
    }



    public function addDesign($getter) {
        $name  = $getter['newnameTemplete'];
        return $this->addDesign_local($name);
    }


    public function addDesign_local($name) {

        $query = $this->pServer->insert("insert into tmpl_win_sys (name,userOwnerID) values (?,?)",$name,0);
        if (!$query["result"]) return $query;
        $insertID = $query["data"];
        if ($insertID > 0){
            $roles = array();
            $roles[] = ApiInfo::$ROLES_READ;
            $roles[] = ApiInfo::$ROLES_WRITE;
            $roles[] = ApiInfo::$ROLES_ADD;
            $roles[] = ApiInfo::$ROLES_DEL;
            $this->pRolesValid->addOwnerRoles($this->resourceID,-1,$insertID,$roles);
        }
        $answer = [
            "id"=>$insertID,
            "name"=>$name
        ];
        return array("result"=>true,"data"=>$answer);
    }


    public function changeDesignSize($getter) {
        $tmpID  = intval($getter['tmpID']);
        $width  = floatval($getter['width']);
        $height = floatval($getter['height']);
        $delta  = floatval($getter['delta']);
        return $this->changeDesignSize_local($tmpID,$width,$height,$delta);
    }

    public function changeDesignSize_local($tmpID,$width,$height,$delta) {

        $result = $this->pServer->select('SELECT * FROM tmpl_win_sys WHERE ID = ?',$tmpID);
        if (!$result["result"]) return $result;
        $rows = $result["data"][0];
        $persentOld = (floatval($rows["smallWidth"])/floatval($rows["fullWidth"]))*100;
        $objectsID1 = $rows["w1"];
        $objectsID2 = $rows["w2"];

        /* @var StyleAPI $stylesAPI*/
        $stylesAPI = $this->pCache->getCachedClass("StyleAPI");

        /* @var ObjectsAPI $objectsAPI*/
        $objectsAPI = $this->pCache->getCachedClass("ObjectsAPI");

        $result = $objectsAPI->changeObjectsSize_local($this->pServer,$stylesAPI,$objectsID1,$delta,$persentOld);
        if (!$result["result"]) return $result;

        $result = $objectsAPI->changeObjectsSize_local($this->pServer,$stylesAPI,$objectsID2,$delta,$persentOld);
        if (!$result["result"]) return $result;

        $deltaF = ($delta/100.0)*1.0;
        $smallWidth  = intval($width*$deltaF);
        $smallHeight = intval($height*$deltaF);

        $result = $this->pServer->query('UPDATE tmpl_win_sys SET smallWidth =?, smallHeight=?, fullWidth=?, fullHeight=? WHERE ID = ?',$smallWidth, $smallHeight, $width,$height,$tmpID);
        return $result;
    }
}