<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 10.02.15
 * Time: 19:41
 * To change this template use File | Settings | File Templates.
 */
require_once("m.php");
class ContentAPI {
    private $resourceID = 2;
    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;
    /* @var RolesValidator $pRolesValid*/
    private $pRolesValid;

    function __construct(Server $server,Cache $cache) {
        $this->resourceID = Consts::$RESOURCE_CONTENT_ID;
        $this->pServer = $server;
        $this->pCache = $cache;

        $this->pRolesValid = $this->pCache->getCachedClass("RolesValidator");
    }


    private function checkExistContent($md5,$len) {


        $result = $this->pServer->select("SELECT ID,refCount FROM ".$this->pServer->getPrefix()."content WHERE md5hash=? AND length=?",$md5,$len);
        if ($result["result"]) {
            $data = $result["data"];
            return $data[0];
        }else {
            return -1;
        }
    }


    public function getContentTypeIDS() {
        return $this->getContentTypeIDS_local();
    }
    public function getContentTypeIDS_local() {
        $result = $this->pServer->select("SELECT ID,label FROM ".$this->pServer->getPrefix()."content_type_names ");

        if ($result["result"]) {
            $data = $result["data"];
            $ids = array();
            for ($i = 0; $i < count($data);$i++) {
                $obj = $data[$i];
                $ids[] = array("ID"=>$obj["ID"],"name"=>$obj["label"]);
            }
            return array("result"=>true,"data"=>$ids);
        }else {
            return ErrorCodes::gi()->executeShort(0,$result["error"],ErrorCodes::$SERVER_REQUEST_ERROR);
        }
    }

    /* getContentTypeByIDS
       in => ids
    */



    /* getContentIDS
        out => [,,,....]
    */
    public function getContentIDS() {
        return $this->getContentIDS_local();
    }
    public function getContentIDS_local() {
        $result = $this->pServer->select("SELECT ID FROM ".$this->pServer->getPrefix()."content ");

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

    /* getContentByID
        in => IDs content
        out => {"<id>":...,"<id>":...}
    */
    public function getContentByIDS($getter) {
        $id = $getter["ids"];
        return $this->getContentByIDS_local($id);
    }

    public function getContentByIDS_local($id) {
        $id = explode(",",$id);
        $result = $this->pServer->select("SELECT ID,value FROM ".$this->pServer->getPrefix()."content WHERE ID IN(%s)",$id);

        if ($result["result"]) {
            $data = $result["data"];
            $ids = array();
            for ($i = 0; $i < count($data);$i++) {
                $obj = $data[$i];
                $ids[$obj["ID"]] = array(

                    "value"=>$obj["value"]);
            }
            return array("result"=>true,"data"=>$ids);
        }else {
            return ErrorCodes::gi()->executeShort(0,$result["error"],ErrorCodes::$SERVER_REQUEST_ERROR);
        }
    }

    /* addContent
        in => value
        out => ID resource
    */


    public function getLenAndHashForContent($value) {
        $md5 = md5($value);
        $len = strlen($value);
        return ["hash"=>$md5,"len"=>$len];
    }

    public function addContent($getter) {
        $value = $getter["value"];
        return $this->addContent_local($value);
    }
    public function addContent_local($value) {

        $dataValue = $this->getLenAndHashForContent($value);
        $md5 = $dataValue["hash"];
        $len = $dataValue["len"];

        $exist = $this->checkExistContent($md5,$len);



        if ($exist["ID"]) {
            $result = $this->pServer->query("UPDATE ".$this->pServer->getPrefix()."content SET refCount=refCount+1 WHERE ID=?",$exist["ID"]);
            if ($result["result"]) {
                return array("result"=>true,"data"=>array("ID"=>$exist["ID"]));
            }else {
                return $result;
            }
        }else {

            $dataValue = $this->getLenAndHashForContent($value);
            $md5 = $dataValue["hash"];
            $len = $dataValue["len"];

            $result = $this->pServer->insert("INSERT INTO ".$this->pServer->getPrefix()."content (value,refCount,md5hash,length) VALUES (?,?,?,?)",$value,1,$md5,$len);



            if ($result["result"]) {
                $insertedID = $result["data"];
                $result["data"] = array();
                $result["data"]["ID"] = $insertedID;

            }

            return $result;
        }
    }


    /* deleteContentByID
            in => id
    */

    public function deleteContentByID($getter) {
        $id_row = $getter["id"];
        return $this->deleteContentByID_local($id_row);
    }

    public function deleteContentByID_local($id_row) {
        $result = $this->pServer->select("SELECT refCount FROM ".$this->pServer->getPrefix()."content WHERE ID=? ",$id_row);
        if ($result["result"]) {
            $data = $result["data"];
            $refCount = $data[0]["refCount"];
            if ($refCount - 1 <= 0) {
                $this->pServer->query("DELETE FROM ".$this->pServer->getPrefix()."content WHERE ID=?",$id_row);
            }else {
                $this->pServer->query("UPDATE ".$this->pServer->getPrefix()."content SET refCount=refCount-1 WHERE ID=?",$id_row);
            }
            return array("result"=>true,"data"=>"success");
        }else {
            return ErrorCodes::gi()->executeShort(0,"cant get data byID".$id_row,ErrorCodes::$SERVER_REQUEST_ERROR);
        }
    }

    /* deleteContent
        in => value
    */

    public function deleteContent($getter) {
        $md5 = $getter["md5"];
        $len = $getter["len"];


        return $this->deleteContent_local($md5,$len);
    }
    public function deleteContent_local($md5,$len) {
        $exist = $this->checkExistContent($md5,$len);
        if ($exist["ID"]) {
            if ($exist["refCount"] - 1 <= 0) {
                // need delete resource
                $this->pServer->query("DELETE FROM ".$this->pServer->getPrefix()."content WHERE ID=?",$exist["ID"]);
            }else {
                // need update refCount
                $this->pServer->query("UPDATE ".$this->pServer->getPrefix()."content SET refCount=refCount-1 WHERE ID=?",$exist["ID"]);
            }
        }
        return array("result"=>true,"data"=>"success");
    }


    /* changeContent
        in => new_value
        in => old_value
        out => ID resource
    */

    public function changeContent($getter) {
        $new_value = $getter["new_value"];
        $old_md5   = $getter["old_md5"];
        $old_len   = $getter["old_len"];
        return $this->editContent_local($new_value,$old_md5,$old_len);
    }
    public function editContent_local($new_value,$old_md5,$old_len) {
        $this->deleteContent_local($old_md5,$old_len);
        return $this->addContent_local($new_value);
    }

}