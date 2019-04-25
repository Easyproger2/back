<?php

/**
 * Created by PhpStorm.
 * User: easyproger
 */

require_once("m.php");

class RolesAPI
{
    private $resourceID;

    /* @var Server $pServer*/
    private $pServer;
    /* @var Cache $pCache*/
    private $pCache;

    function __construct(Server $server,Cache $cache) {
        $this->resourceID = Consts::$RESOURCE_ROLES_ID;
        $this->pServer = $server;
        $this->pCache = $cache;
    }

    public function getRoles($getter) {
        $role_id     = $getter['role_id'];
        $resourseID  = $getter['resourseID'];
        $param1      = isset($getter['param1']) ? $getter['param1'] : null;
        $param2      = isset($getter['param2']) ? $getter['param2'] : null;

        /* @var RolesValidator $rolesValidator*/
        $rolesValidator = $this->pCache->getCachedClass("RolesValidator");
        return $rolesValidator->getRoles_local($role_id,$resourseID,$param1,$param2);
    }

    public function removeRoles($getter) {
        $role_id     = $getter['role_id'];
        $resourseID  = $getter['resourseID'];
        $param1      = isset($getter['param1']) ? $getter['param1'] : null;
        $param2      = isset($getter['param2']) ? $getter['param2'] : null;
        $roles       = $getter['roles'];

        /* @var RolesValidator $rolesValidator*/
        $rolesValidator = $this->pCache->getCachedClass("RolesValidator");
        return $rolesValidator->removeRoles_local($role_id,$resourseID,$param1,$param2,$roles);
    }

    public function addRoles($getter) {
        $role_id     = $getter['role_id'];
        $resourseID  = $getter['resourseID'];
        $param1      = $getter['param1'];
        $param2      = $getter['param2'];
        $roles       = $getter['roles'];

        /* @var RolesValidator $rolesValidator*/
        $rolesValidator = $this->pCache->getCachedClass("RolesValidator");
        return $rolesValidator->addRoles_local($role_id,$resourseID,$param1,$param2,$roles);
    }

    public function updateRoles($getter) {
        $role_id     = $getter['role_id'];
        $resourseID  = $getter['resourseID'];
        $param1      = $getter['param1'];
        $param2      = $getter['param2'];
        $roles       = $getter['roles'];

        $rolesClassic = array();
        $rolesClassic[] = -1;
        $rolesClassic[] = ApiInfo::$ROLES_READ;
        $rolesClassic[] = ApiInfo::$ROLES_WRITE;
        $rolesClassic[] = ApiInfo::$ROLES_ADD;
        $rolesClassic[] = ApiInfo::$ROLES_DEL;

        /* @var RolesValidator $rolesValidator*/
        $rolesValidator = $this->pCache->getCachedClass("RolesValidator");

        $result = $rolesValidator->removeRoles_local($role_id,$resourseID,$param1,$param2,$rolesClassic);
        if (!$result["result"]) return $result;

        return $rolesValidator->addRoles_local($role_id,$resourseID,$param1,$param2,$roles);
    }

}