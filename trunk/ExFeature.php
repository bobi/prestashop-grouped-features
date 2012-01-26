<?php

/**
 * @author Andriy
 * @copyright 2010
 */

class ExFeature extends Feature 
{
    protected $fieldsRequired = array('id_group');
	protected $fieldsValidate = array(
		'id_group' => 'isUnsignedId',
	);
	protected $fieldsRequiredLang = array('name');
	/* Description short is limited to 400 chars, but without html, so it can't be generic */
	protected $fieldsSizeLang = array('name' => 128);
	protected $fieldsValidateLang = array('name' => 'isGenericName');
    
    public static $stable = 'feature'; 
    public $position = NULL;
    public $id_group = NULL;
    
    public function add()
    {
        //setup max position if not provide
        //get max position 
        if (!$this->position) {
            $sql = "SELECT max(position) AS mp FROM "._DB_PREFIX_."feature WHERE id_group=".intval($this->id_group);
            $maxP = Db::getInstance()->getRow($sql);
            $this->position = intval($maxP["mp"])+1;
        };
        return parent::add(); 
   } 
    
    public function getFields()
    {
   		parent::validateFields();
        $fields = array();
		if (isset($this->id))
			$fields['id_feature'] = intval($this->id);
        $fields["position"] = $this->position;
        $fields["id_group"] = $this->id_group;
        return $fields;
    }
    
    public function getTranslationsFieldsChild()
	{
		self::validateFieldsLang();
        return parent::getTranslationsFields($this->fieldsRequiredLang);
    }
    
    public function update()
    {
        Db::getInstance()->AutoExecute(_DB_PREFIX_.$this->table, $this->getFields(), 'UPDATE', '`'
            .pSQL($this->identifier).'` = '.intval($this->id));
        return parent::update();   
    }
    
    public function setupPosition($order, $id_group)
    {
        $order = strtolower($order) ;
        switch ($order) {
            case "up":
                //get near highest position
                $sql = "SELECT id_feature, position FROM "._DB_PREFIX_.$this->table." WHERE position < ".intval($this->position)." AND id_group=".$id_group." ORDER BY position DESC ";
                $result = Db::getInstance()->getRow($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = ".intval($result["position"])." WHERE id_feature = ".$this->id;
                Db::getInstance()->Execute($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = position+1 WHERE id_feature = ".intval($result["id_feature"]);
                Db::getInstance()->Execute($sql);
                break;
            case "down":
                //get near lowest position
                $sql = "SELECT id_feature, position FROM "._DB_PREFIX_.$this->table." WHERE position > ".intval($this->position)." AND id_group=".$id_group." ORDER BY position ";
                $result = Db::getInstance()->getRow($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = ".intval($result["position"])." WHERE id_feature = ".$this->id;
                Db::getInstance()->Execute($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = position-1 WHERE id_feature = ".intval($result["id_feature"]);
                Db::getInstance()->Execute($sql);
                break;
            default: return false;
        }
    }
    
    public function getProductsFeatures($id_product, $id_lang=NULL)
    {
        global $cookie;
        if (!$id_lang)
            $id_lang = $cookie->id_lang;
        if (!$id_product)
            return false;
        $sql = 'SELECT 
                    f.id_feature AS id_feature,
                    fl.name AS feature_name,
                    fg.id_group AS id_group,
                    fgl.name AS group_name,
                    fvl.value AS value,
                    fv.id_feature_value AS id_feature_value
                FROM
                    '._DB_PREFIX_.$this->table.' f 
                    JOIN '._DB_PREFIX_.'feature_group fg ON f.id_group = fg.id_group
                    JOIN '._DB_PREFIX_.'feature_lang fl ON f.id_feature = fl.id_feature
                    JOIN '._DB_PREFIX_.'feature_group_lang fgl ON fg.id_group = fgl.id_group 
                    JOIN '._DB_PREFIX_.'feature_product fp ON f.id_feature = fp.id_feature
                    JOIN '._DB_PREFIX_.'feature_value fv ON (f.id_feature = fv.id_feature AND fp.id_feature_value=fv.id_feature_value)
                    JOIN '._DB_PREFIX_.'feature_value_lang fvl ON fv.id_feature_value = fvl.id_feature_value  
                WHERE 
                    fgl.id_lang = '.$id_lang.' AND fl.id_lang = '.$id_lang.' AND fvl.id_lang='.$id_lang.' AND fp.id_product='.$id_product.'
                     ORDER BY fg.position, f.position';
        $records = Db::getInstance()->ExecuteS($sql);
        $result = array();
        foreach ($records as $record) {
            if (!array_key_exists($record["id_group"], $result))
                $result[$record["id_group"]] = array("name" => $record["group_name"], "features" => array());
            if (!array_key_exists($record["id_feature"], $result[$record["id_group"]]["features"]))
                $result[$record["id_group"]]["features"][$record["id_feature"]] = array("id" => $record["id_feature"], "name" => $record["feature_name"], "value" => array());
            $result[$record["id_group"]]["features"][$record["id_feature"]]["values"][$record["id_feature_value"]] = $record["value"];
        }
        return $result;
    }
    
    static public function getFeatures($id_lang=NULL)
    {
        global $cookie;
        if (!$id_lang)
            $id_lang = $cookie->id_lang;
        $sql = 'SELECT 
                    f.id_feature AS id_feature,
                    fl.name AS feature_name,
                    fg.id_group AS id_group,
                    fgl.name AS group_name
                FROM
                    '._DB_PREFIX_.self::$stable.' f 
                    LEFT JOIN '._DB_PREFIX_.'feature_group fg ON f.id_group = fg.id_group
                    LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON f.id_feature = fl.id_feature
                    LEFT JOIN '._DB_PREFIX_.'feature_group_lang fgl ON fg.id_group = fgl.id_group 
                WHERE 
                    (fgl.id_lang = '.$id_lang.' OR fgl.id_lang IS NULL) 
                    AND (fl.id_lang = '.$id_lang.' OR fl.id_lang IS NULL) 
                     ORDER BY fg.position, f.position';
         
        $records = Db::getInstance()->ExecuteS($sql);
        $result = array();
        foreach ($records as $record) {
            if (!array_key_exists($record["id_group"], $result))
                $result[$record["id_group"]] = array("name" => $record["group_name"], "features" => array());
            if (!array_key_exists($record["id_feature"], $result[$record["id_group"]]["features"]))
                $result[$record["id_group"]]["features"][$record["id_feature"]] = array (
                        "id_feature" => $record["id_feature"], 
                        "id" => $record["id_feature"], 
                        "name" => $record["feature_name"] 
                );
        }
        return $result;
    }
    
    public function getFeatureGroup($id_feature)
    {
        if (!intval($id_feature))
            return null;
        $sql = 'SELECT id_group FROM '._DB_PREFIX_.$this->table .' WHERE id_feature = '.intval($id_feature);
        $result = Db::getInstance()->getRow($sql);
        return intval($result["id_group"]);       
    }
    
    public function getFeatureGroupByValue($id_feature_value)
    {
        if (!intval($id_feature_value))
            return null;
        $sql = 'SELECT f.id_group AS id_group 
                FROM '._DB_PREFIX_.$this->table.' f JOIN '._DB_PREFIX_.$this->table.'_value fv ON f.id_feature = fv.id_feature 
                WHERE id_feature_value = '.intval($id_feature_value);
        $result = Db::getInstance()->getRow($sql);
        return intval($result["id_group"]);
    }
    
    public function getFeatureByValue($id_feature_value)
    {
        if (!intval($id_feature_value))
            return null;
        $sql = 'SELECT id_feature FROM '._DB_PREFIX_.$this->table.'_value WHERE id_feature_value = '.intval($id_feature_value);
        $result = Db::getInstance()->getRow($sql);
        return intval($result["id_feature"]);
    }
}

?>