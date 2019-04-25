<?php
/**
 * Created by JetBrains PhpStorm.
 * User: easyproger
 * Date: 27.02.15
 * Time: 16:10
 * To change this template use File | Settings | File Templates.
 */


require_once("m.php");

class ApiInfo {
    public static $ROLES_READ  = 1;
    public static $ROLES_WRITE = 2;
    public static $ROLES_ADD   = 3;
    public static $ROLES_DEL   = 4;


    private $api;

    function __construct()
    {
        $this->api = array(


// =================================================================================================================
// ================================================= OBJECTS API ===================================================
// =================================================================================================================

            "getObjectsList" => array("class" => "ObjectsAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_OBJECTS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "updateObjectInGroup" => array("class" => "ObjectsAPI",
                "require" => array(),
                "optional" => array("nameObject"=>"string","objectType"=>"string","sourceID"=>"string","sourceParam"=>"string","sourceType"=>"string","properties"=>"notValidate","value"=>"notValidate","groupName"=>"string"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_OBJECTS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),

            "addObject" => array("class" => "ObjectsAPI",
                "require" => array("groupName"=>"string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_OBJECTS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "getFormatObject" => array("class" => "ObjectsAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_OBJECTS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

// =================================================================================================================
// ================================================= BASES API =====================================================
// =================================================================================================================


            "getBasesInfo" => array("class" => "BasesAPI",
                "require" => array(),
                "optional" => array("fieldsAsObjects"=>"numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_BASES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "saveBase" => array("class" => "BasesAPI",
                "require" => array("baseID"=>"numbers",
                    "fields" => ["localeName"=>"string","type"=>"string","__@optional__properties"=>["__@optional__length"=>"string"]] ,
                    "properties"=>["localeName"=>"string","dateType"=>"numbers","year"=>"numbers","rangeDayMinus"=>"numbers","rangeDayPlus"=>"numbers"]),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_BASES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),

            "deleteBase" => array("class" => "BasesAPI",
                "require" => array("baseID"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_BASES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_DEL)),

            "addNewBase" => array("class" => "BasesAPI",
                "require" => array("nameBase"=>"string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_BASES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),


// =================================================================================================================
// ================================================= RECORDS API ===================================================
// =================================================================================================================
//

            "getRecordsFromBase" => array("class" => "RecordsAPI",
                "require" => array("baseID"=>"numbers"),
                "optional" => array("filterPublish"=>"numbers","dateMin"=>"date","dateMax"=>"date","start"=>"numbers","page"=>"numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_RECORDS_ID, "param1" => "baseID", "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "addRecordToBase" => array("class" => "RecordsAPI",
                "require" => array("baseID"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_RECORDS_ID, "param1" => "baseID", "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "updateRecord" => array("class" => "RecordsAPI",
                "require" => array("baseID"=>"numbers","recordID"=>"numbers","fieldID"=>"notValidate","content"=>"notValidate"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_RECORDS_ID, "param1" => "baseID", "param2" => "recordID", "roleID" => ApiInfo::$ROLES_WRITE)),

            "removeRecordFromBase" => array("class" => "RecordsAPI",
                "require" => array("baseID"=>"numbers","recordID"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_RECORDS_ID, "param1" => "baseID", "param2" => "recordID", "roleID" => ApiInfo::$ROLES_DEL)),

            "updateRecordTagsGroups" => array("class" => "RecordsAPI",
                "require" => array("baseID"=>"numbers","recordID"=>"numbers","tagsGroups"=>"notValidate"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_RECORDS_ID, "param1" => "baseID", "param2" => "recordID", "roleID" => ApiInfo::$ROLES_WRITE)),


//

// =================================================================================================================
// ================================================= SCHEDULE API ==================================================
// =================================================================================================================

            "getScheduleForTagsAndDate" => array("class" => "RecordsAPI",
                "require" => array("tags"=>"notValidate"),
                "optional" => array("startDate"=>"string","endDate"=>"string")),


// =================================================================================================================
// ================================================= TEMPLATES API =================================================
// =================================================================================================================


            "getListTemplates" => array("class" => "TemplatesAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),


            "getTemplateRecords" => array("class" => "TemplatesAPI",
                "require" => array("start"=>"numbers","end"=>"numbers","today"=>"numbers"),
                "optional" => array("objects"=>
                    [
                        "ID"=>"numbers",
                        "properties"=>"notValidate",
                        "objectType"=>"string",
                        "sourceID"=>"string",
                        "sourceParam"=>"string",
                        "sourceType"=>"numbers",
                        "IDGROUP"=>"numbers",
                        "styleID"=>"numbers",
                        "__@optional__value"=>"string",
                        "__@optional__styles"=>"notValidate"
                    ]),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "getTemplateInfo" => array("class" => "TemplatesAPI",
                "require" => array("templateID"=>"numbers"),
                "optional" => array("parseObjectNames"=>"numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => "templateID", "roleID" => ApiInfo::$ROLES_READ)),

            "doubleTemplate" => array("class" => "TemplatesAPI",
                "require" => array("templateID"=>"numbers","doubleName"=>"string"),
                "optional" => array("isDoubleBases"=>"numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "addTemplate" => array("class" => "TemplatesAPI",
                "require" => array("newnameTemplete"=>"string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "removeTemplate" => array("class" => "TemplatesAPI",
                "require" => array("templateID"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => "templateID", "roleID" => ApiInfo::$ROLES_DEL)),

            "updateTemplate" => array("class" => "TemplatesAPI",
                "require" => array("id"=>"numbers"),
                "optional" => array("objects"=>
                    [
                        "ID"=>"numbers",
                        "properties"=>"notValidate",
                        "objectType"=>"string",
                        "sourceID"=>"string",
                        "sourceParam"=>"string",
                        "sourceType"=>"numbers",
                        "IDGROUP"=>"numbers",
                        "styleID"=>"numbers",
                        "__@optional__value"=>"string",
                        "__@optional__styles"=>[
                            "__@PL__styleID"=>[
                                "name"=>"string",
                                "value"=>"notValidate",
                                "space"=>"string",
                                "scaled_flag"=>"numbers"
                            ]
                        ]
                    ],
                    "onlyUpdate"=>"numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => "id", "roleID" => ApiInfo::$ROLES_WRITE)),


            "changeTemplateSize" => array("class" => "TemplatesAPI",
                "require" => array("tmpID"=>"numbers","width"=>"numbers","height"=>"numbers","delta"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_TEMPLATES_ID, "param1" => -1, "param2" => "tmpID", "roleID" => ApiInfo::$ROLES_WRITE)),

// =================================================================================================================
// ================================================= DESIGNS API ===================================================
// =================================================================================================================


            "doubleDesign" => array("class" => "DesignsAPI",
                "require" => array("templateID"=>"numbers","doubleName"=>"string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DESIGNS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "getDesignInfo" => array("class" => "DesignsAPI",
                "require" => array("templateID"=>"numbers","designWindowNums"=>"[]"),
                "optional" => array("parseObjectNames"=>"numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DESIGNS_ID, "param1" => -1, "param2" => "templateID", "roleID" => ApiInfo::$ROLES_READ)),

            "getListDesign" => array("class" => "DesignsAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DESIGNS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "updateDesign" => array("class" => "DesignsAPI",
                "require" => array("id"=>"numbers","designWindowNum"=>"numbers"),
                "optional" => array("objects"=>
                    [
                        "ID"=>"numbers",
                        "properties"=>"notValidate",
                        "objectType"=>"string",
                        "sourceID"=>"string",
                        "sourceParam"=>"string",
                        "sourceType"=>"numbers",
                        "IDGROUP"=>"numbers",
                        "styleID"=>"numbers",
                        "__@optional__value"=>"string",
                        "__@optional__styles"=>[
                            "__@PL__styleID"=>[
                                "name"=>"string",
                                "value"=>"notValidate",
                                "space"=>"string",
                                "scaled_flag"=>"numbers"
                            ]
                        ]
                    ],
                    "onlyUpdate"=>"numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DESIGNS_ID, "param1" => -1, "param2" => "id", "roleID" => ApiInfo::$ROLES_WRITE)),

            "removeDesign" => array("class" => "DesignsAPI",
                "require" => array("templateID"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DESIGNS_ID, "param1" => -1, "param2" => "templateID", "roleID" => ApiInfo::$ROLES_DEL)),

            "addDesign" => array("class" => "DesignsAPI",
                "require" => array("newnameTemplete"=>"string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DESIGNS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "changeDesignSize" => array("class" => "DesignsAPI",
                "require" => array("tmpID"=>"numbers","width"=>"numbers","height"=>"numbers","delta"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DESIGNS_ID, "param1" => -1, "param2" => "tmpID", "roleID" => ApiInfo::$ROLES_WRITE)),

// =================================================================================================================
// ================================================= ROLES API =====================================================
// =================================================================================================================

            "getRoles" => array("class" => "RolesAPI",
                "require" => array("role_id" => "numbers","resourseID" => "numbers"),
                "optional" => array("param1"=>"string","param2"=>"string"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ROLES_ID, "param1" => "role_id", "param2" => "resourseID", "roleID" => ApiInfo::$ROLES_READ)),

            "removeRoles" => array("class" => "RolesAPI",
                "require" => array("role_id" => "numbers","resourseID" => "numbers","roles"=>"arrayNumbers"),
                "optional" => array("param1"=>"string","param2"=>"string"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ROLES_ID, "param1" => "role_id", "param2" => "resourseID", "roleID" => ApiInfo::$ROLES_DEL)),

            "addRoles" => array("class" => "RolesAPI",
                "require" => array("role_id" => "numbers","resourseID" => "numbers","param1"=>"string","param2"=>"string","roles"=>"arrayNumbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ROLES_ID, "param1" => "role_id", "param2" => "resourseID", "roleID" => ApiInfo::$ROLES_ADD)),

            "updateRoles" => array("class" => "RolesAPI",
                "require" => array("role_id" => "numbers","resourseID" => "numbers","param1"=>"string","param2"=>"string","roles"=>"arrayNumbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ROLES_ID, "param1" => "role_id", "param2" => "resourseID", "roleID" => ApiInfo::$ROLES_WRITE)),

// =================================================================================================================
// ================================================= DATES API =====================================================
// =================================================================================================================

            "addDate" => array("class" => "DatesAPI",
                "require" => array("hour" => "string", "minutes" => "string", "dom" => "string", "month" => "string", "dow" => "string", "actual_start" => "numbers", "actual_end" => "numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_DATE_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),


// =================================================================================================================
// ================================================= USER API ======================================================
// =================================================================================================================

            "getCurrentUser" => array("class" => "UserAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_USER_ID, "param1" => -1, "param2" => "userID", "roleID" => ApiInfo::$ROLES_READ)),

            "getUserInfoByID" => array("class" => "UserAPI",
                "require" => array("userID"=>"numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_USER_ID, "param1" => -1, "param2" => "userID", "roleID" => ApiInfo::$ROLES_READ)),

            "getCurrentUserInfo" => array("class" => "UserAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_USER_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "getListUsers" => array("class" => "UserAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_USER_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "setUser" => array("class" => "UserAPI",
                "require" => array("login" => "string","pass" => "string"),
                "optional" => array("firstName" => "string", "lastName" => "string", "roleID" => "numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_USER_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "refreshToken" => array("class" => "UserAPI",
                "require" => array("token" => "string"),
                "optional" => array("client_id" => "string", "client_secret" => "string", "redirect_uri" => "string")),

            "auth" => array("class" => "UserAPI",
                "require" => array("login" => "string", "pass" => "string"),
                "optional" => array("client_id" => "string", "client_secret" => "string", "redirect_uri" => "string")),

// =================================================================================================================
// ============================================== ATTRIBUTES API ===================================================
// =================================================================================================================

            "getAttributes" => array("class" => "AttributesAPI",
                "require" => array(),
                "optional" => array("short" => "bool"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ATTRS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "updateAttribute" => array("class" => "AttributesAPI",
                "require" => array("name_attribute" => "string"),
                "optional" => array("options" => ["label"=>"string","value"=>"string"], "label" => "string", "tooltip" => "string", "options_id" => "numbers", "type" => "string", "defValue" => "string", "rate" => "numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ATTRS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),

            "updateOptions" => array("class" => "AttributesAPI",
                "require" => array("options" => '{"label":"string","value":"string"}'),
                "optional" => array("options_id" => "numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ATTRS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),

            "updateValue" => array("class" => "AttributesAPI",
                "require" => array("value" => "string", "label" => "string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_ATTRS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),
// =================================================================================================================
// ================================================= MODIFICATORS API ==============================================
// =================================================================================================================
            "getModifiersList" => array("class" => "ModifiersAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_MODIFIERS_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

// =================================================================================================================
// ================================================= STYLE API =====================================================
// =================================================================================================================

            "setParentIDToStyleDefault" => array("class" => "StyleAPI",
                "require" => array("name_style" => "string", "name_parent_style" => "string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_STYLE_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),

            "removeAttributeDefault" => array("class" => "StyleAPI",
                "require" => array("name_style" => "string", "attr" => "string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_STYLE_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_DEL)),

            "updateStyleDefault" => array("class" => "StyleAPI",
                "require" => array("name_style" => "string", "parsed_style" => ["name"=>"string","value"=>"notValidate","space"=>"string","scaled_flag"=>"numbers"]),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_STYLE_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),

            "addAttributeDefault" => array("class" => "StyleAPI",
                "require" => array("name_style" => "string", "attr" => "string", "value" => "string"),
                "optional" => array("space" => "string", "scaled_flag" => "numbers"),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_STYLE_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "addDefaultStyle" => array("class" => "StyleAPI",
                "require" => array("name_style" => "string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_STYLE_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "getDefaultStyleByName" => array("class" => "StyleAPI",
                "require" => array("name_style" => "string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_STYLE_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),



            "old API closed current"


            /*

            "getStylesWithAttr" => array("class" => "StyleAPI",
                "require" => array("id_style" => "numbers", "attr" => "string"),
                "optional" => array()),

            "updateStyle" => array("class" => "StyleAPI",
                "require" => array("id_style" => "numbers", "parsed_style" => ["name"=>"string","value"=>"notValidate","space"=>"string","scaled_flag"=>"numbers"]),
                "optional" => array()),

            "getStylesByIDS" => array("class" => "StyleAPI",
                "require" => array("ids" => "numbers"),
                "optional" => array("full_path" => "bool", "short" => "bool")),

            "getStylesByIDSUp" => array("class" => "StyleAPI",
                "require" => array("ids" => "numbers"),
                "optional" => array("full_path" => "bool", "short" => "bool")),

            "getStyleIDS" => array("class" => "StyleAPI",
                "require" => array(),
                "optional" => array()),

            "getParseData" => array("class" => "StyleAPI",
                "require" => array("tableID" => "string"),
                "optional" => array()),

            "setParentIDToStyle" => array("class" => "StyleAPI",
                "require" => array("id_style" => "numbers", "id_parent" => "numbers"),
                "optional" => array()),

            "addStyleBaseRoot" => array("class" => "StyleAPI",
                "require" => array(),
                "optional" => array()),

            "addStyle" => array("class" => "StyleAPI",
                "require" => array(),
                "optional" => array()),

            "addAttribute" => array("class" => "StyleAPI",
                "require" => array("id_style" => "numbers", "attr" => "string", "value" => "string"),
                "optional" => array("space" => "string", "scaled_flag" => "numbers")),

            "removeAttribute" => array("class" => "StyleAPI",
                "require" => array("id_style" => "numbers", "attr" => "string"),
                "optional" => array()),

            "removeStyle" => array("class" => "StyleAPI",
                "require" => array("id_style" => "numbers"),
                "optional" => array()),

// =================================================================================================================
// ================================================ CONTENT API ====================================================
// =================================================================================================================

            "addContent" => array("class" => "ContentAPI",
                "require" => array("value" => "string", "type" => "string"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_CONTENT_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_ADD)),

            "changeContent" => array("class" => "ContentAPI",
                "require" => array("new_value" => "string", "type" => "string", "old_md5" => "string", "old_len" => "numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_CONTENT_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_WRITE)),

            "deleteContent" => array("class" => "ContentAPI",
                "require" => array("md5" => "string", "len" => "numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_CONTENT_ID, "param1" => -1, "param2" => -1, "roleID" => ApiInfo::$ROLES_DEL)),

            "deleteContentByID" => array("class" => "ContentAPI",
                "require" => array("id" => "numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_CONTENT_ID, "param1" => "ID", "param2" => "id", "roleID" => ApiInfo::$ROLES_DEL)),

            "getContentByIDS" => array("class" => "ContentAPI",
                "require" => array("ids" => "numbers"),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_CONTENT_ID, "param1" => "ID", "param2" => "ids", "roleID" => ApiInfo::$ROLES_READ)),

            "getContentIDS" => array("class" => "ContentAPI",
                "require" => array(),
                "optional" => array(),
                "roleInfo" => array("resourceID" => Consts::$RESOURCE_CONTENT_ID, "param1" => "ID", "param2" => -1, "roleID" => ApiInfo::$ROLES_READ)),

            "getContentTypeIDS" => array("class" => "ContentAPI",
                "require" => array(),
                "optional" => array())

            */

        );
    }


    public function getApiInfo() {
        return $this->api;
    }

}