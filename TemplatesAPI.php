<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 * Date: 22.02.17
 * Time: 14:51
 */
require_once("m.php");
class TemplatesAPI
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
        $this->resourceID = Consts::$RESOURCE_TEMPLATES_ID;

        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
        $this->pStyleData  = $this->pCache->getCachedClass("StyleData");
    }

    public function getTemplateInfo($getter) {
        $templateID       = $getter["templateID"];
        $parseObjectNames = isset($getter["parseObjectNames"]) ? $getter["parseObjectNames"] : false;
        return $this->getTemplateInfo_local($templateID,$parseObjectNames);
    }

    public function getTemplateInfo_local($templateID,$parseObjectNames = false) {

        $query  = $this->pServer->select("SELECT * FROM template_1_sys WHERE ID=?",$templateID);
        if (!$query["result"]) return $query;
        if (!count($query["data"])) return array("result"=>false,"error"=>"bad id");

        $row = $query["data"][0];

        /* @var StyleAPI $stylesAPI*/
        $stylesAPI = $this->pCache->getCachedClass("StyleAPI");


        $result = Utils::getObjectsForTemplate($this->pServer,$row["objectsID"],$stylesAPI,$parseObjectNames);
        if (!$result["result"]) return $result;



        $answer = [
            "name"=>$row["name"],
            "id"=>$row["ID"],
            "objects"=>$result["data"],
            "typelayer"=>$row["typelayer"],
            "objectsID"=>$row["objectsID"],
            "smallWidth"=>$row["smallWidth"],
            "smallHeight"=>$row["smallHeight"],
            "fullWidth"=>$row["fullWidth"],
            "fullHeight"=>$row["fullHeight"],
            "showSplash"=>$row["showSplash"]
        ];

        return array("result"=>true,"data"=>$answer);
    }


    public function getListTemplates($getter) {

        return $this->getListTemplates_local($getter["-1"]);
    }

    public function getListTemplates_local($filterTemplates) {

        if (isset($filterTemplates)) {
            $result  = $this->pServer->select("SELECT * FROM template_1_sys WHERE ID IN(".$filterTemplates.")") ;
            if (!$result["result"]) return $result;
        }else {
            $result = $this->pServer->select("SELECT * FROM template_1_sys");
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

    public function getTemplateRecords($getter) {
        $start   = $getter["start"];
        $end     = $getter["end"];
        $objects = $getter["objects"];
        $today   = $getter["today"];

        return $this->getTemplateRecords_local($start,$end,$objects,$today);
    }

    public function getTemplateRecords_local($start,$end,$objects,$today) {
        $formatedSource = Utils::formatSource($objects);
        $result         = Utils::getDataForFormatedSources($this->pServer,$this->pCache,$formatedSource,$start,$end,$today);
        return $result;
    }


    public function doubleTemplate($getter) {
        $templateID    = $getter["templateID"];
        $doubleName    = $getter["doubleName"];
        $isDoubleBases = isset($getter["isDoubleBases"]) ?$getter["isDoubleBases"] : 0;
        return $this->doubleTemplate_local($templateID,$doubleName,$isDoubleBases);
    }

    public function doubleTemplate_local($templateID,$doubleName,$isDoubleBases) {

        $result = $this->getTemplateInfo_local($templateID);
        if (!$result["result"]) return $result;

        $templateInfo = $result["data"];
        $templateIDToDouble = $result["data"]["id"];


        $result = $this->pServer->select("show tables");
        if (!$result["result"]) return $result;

        /* @var ObjectsAPI $objectsAPI*/
        $objectsAPI = $this->pCache->getCachedClass("ObjectsAPI");

        $result = $objectsAPI->doubleObjects_local($this->pCache,$this->pServer,$isDoubleBases,$doubleName,$templateInfo["objects"]);
        if (!$result["result"]) return $result;

        $newObjectsID = $result["data"];

        $result = Utils::doubleRow_local($this->pServer,"template_1_sys",$templateIDToDouble,["ID"]);
        if (!$result["result"]) return $result;
        $newTemplateID   = $result["data"]["insertID"];


        $roles = array();
        $roles[] = ApiInfo::$ROLES_READ;
        $roles[] = ApiInfo::$ROLES_WRITE;
        $roles[] = ApiInfo::$ROLES_ADD;
        $roles[] = ApiInfo::$ROLES_DEL;
        $this->pRolesValid->addOwnerRoles($this->resourceID,-1,$newTemplateID,$roles);

        $result = $this->pServer->query("UPDATE template_1_sys SET objectsID = ?,name=? WHERE ID = ?",$newObjectsID,$doubleName,$newTemplateID);
        if (!$result["result"]) return $result;

        return ["result"=>true,"data"=>"success"];
    }

    public function addTemplate($getter) {
        $name  = $getter['newnameTemplete'];
        return $this->addTemplate_local($name);
    }

    public function addTemplate_local($name,$typeLayer=0) {

        $objectsID = -1;
        $userOwnerID = 0;
        $query = $this->pServer->insert("insert into template_1_sys (name,userOwnerID,objectsID,typelayer) values (?,?,?,?)",$name,$userOwnerID,$objectsID,$typeLayer);
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



    public function removeTemplate($getter) {
        $templateID   = $getter['templateID'];
        return $this->removeTemplate_local($templateID);
    }

    public function removeTemplate_local($templateID) {

        // clear template

        $result = $this->updateTemplate_local($templateID,[]);
        if (!$result["result"]) return $result;

        $result = $this->pServer->query("DELETE FROM template_1_sys WHERE ID=?",$templateID);
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
    public function updateTemplate($getter) {
        $id        = $getter['id'];
        $objects   = $getter['objects'];
        $name      = isset($getter['name']) ? $getter['name'] : null;
        $onlyUpdate = isset($getter['onlyUpdate']) ? $getter['onlyUpdate'] : 0;
        return $this->updateTemplate_local($id,$objects,$name,$onlyUpdate);
    }

    function updateTemplate_local($id,$objects,$name=null,$onlyUpdate = false) {

        Utils::clearCacheByTemplateID($this->pServer,$id);

        $result = $this->pServer->select("SELECT * FROM template_1_sys WHERE ID=?",$id);
        if (!$result["result"]) { return $result;}
        $nameUpdate = $result["data"][0]['name'];

        if ($name != null) $nameUpdate = $name;

        /* @var StyleAPI $baseAPI*/
        $stylesAPI = $this->pCache->getCachedClass("StyleAPI");

        /* @var ContentAPI $contentAPI*/
        $contentAPI = $this->pCache->getCachedClass("ContentAPI");

        /* @var ObjectsAPI $objectsAPI*/
        $objectsAPI = $this->pCache->getCachedClass("ObjectsAPI");

        $resultUpdate = $objectsAPI->updateObjects_local($this->pServer,$stylesAPI,$contentAPI,$objects,$result["data"][0]['objectsID'],$onlyUpdate);
        if (!$resultUpdate["result"])  return $resultUpdate;

        $result = $this->pServer->query("UPDATE template_1_sys SET objectsID=?,name=? WHERE ID=? ",$resultUpdate["data"]['objectsID'],$nameUpdate,$id);
        if (!$result["result"]) { return $result;}

        return $resultUpdate;
    }




    public function changeTemplateSize($getter) {
        $tmpID  = intval($getter['tmpID']);
        $width  = floatval($getter['width']);
        $height = floatval($getter['height']);
        $delta  = floatval($getter['delta']);
        return $this->changeTemplateSize_local($tmpID,$width,$height,$delta);
    }



    public function changeTemplateSize_local($tmpID,$width,$height,$delta) {

        $result = $this->pServer->select('SELECT * FROM template_1_sys WHERE ID = ?',$tmpID);
        if (!$result["result"]) return $result;
        $rows = $result["data"][0];
        $persentOld = (floatval($rows["smallWidth"])/floatval($rows["fullWidth"]))*100;
        $objectsID = $rows["objectsID"];

        /* @var StyleAPI $stylesAPI*/
        $stylesAPI = $this->pCache->getCachedClass("StyleAPI");

        $result = Utils::changeObjectsSize_local($this->pServer,$stylesAPI,$objectsID,$delta,$persentOld);
        if (!$result["result"]) return $result;


        $deltaF = ($delta/100.0)*1.0;
        $smallWidth  = intval($width*$deltaF);
        $smallHeight = intval($height*$deltaF);

        $result = $this->pServer->query('UPDATE template_1_sys SET smallWidth =?, smallHeight=?, fullWidth=?, fullHeight=? WHERE ID = ?',$smallWidth, $smallHeight, $width,$height,$tmpID);
        return $result;
    }



}