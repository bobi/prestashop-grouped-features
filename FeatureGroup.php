<?php

/**
 * @author Andrew
 * @copyright 2010
 */

class FeatureGroup extends ObjectModel
{
  	protected 	$fieldsRequiredLang = array('name');
 	protected 	$fieldsSizeLang = array('name' => 128);
 	protected 	$fieldsValidateLang = array('name' => 'isGenericName');
		
	protected 	$table = 'feature_group';
    static protected $stable = 'feature_group';
	protected 	$identifier = 'id_group';
    
    public $name;
    public $position;
        
    public function getFields()
    {
        return array(
            "id_group" => $this->id,
            "position" => $this->position
        );
    }
    
    public function getTranslationsFieldsChild()
    {
		parent::validateFieldsLang();
		return parent::getTranslationsFields(array('name'));
	}
    
    static public function getFeatureGroups($id_lang)
    {
        $sql = 'SELECT fg.id_group AS id_group, fgl.name as name 
                FROM '._DB_PREFIX_.self::$stable.' fg JOIN '._DB_PREFIX_.self::$stable.'_lang fgl ON fg.id_group = fgl.id_group
                WHERE fgl.id_lang = '.$id_lang;
        $result = Db::getInstance()->ExecuteS($sql);
        if (mysql_errno())
            throw new Exception(mysql_error());
        return $result; 
    }
    
    public function delete()
    {
        //delete first all features into this group
        $sql = "DELETE FROM "._DB_PREFIX_."feature_lang WHERE id_feature IN (SELECT id_feature  FROM "._DB_PREFIX_."feature WHERE id_group = ".$this->id.")";
        $r = Db::getInstance()->Execute($sql); 
        $sql = "DELETE FROM "._DB_PREFIX_."feature WHERE id_group=".$this->id;
        $r = Db::getInstance()->Execute($sql);
        return parent::delete();
    }
    
    public function setupPosition($id_group, $order)
    {
        $order = strtolower($order) ;
        switch ($order) {
            case "up":
                //get near highest position
                $sql = "SELECT id_group, position FROM "._DB_PREFIX_.$this->table." WHERE position < ".intval($this->position)." ORDER BY position DESC ";
                $result = Db::getInstance()->getRow($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = ".intval($result["position"])." WHERE id_group = ".$this->id;
                Db::getInstance()->Execute($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = position+1 WHERE id_group = ".intval($result["id_group"]);
                Db::getInstance()->Execute($sql);
                break;
            case "down":
                //get near lowest position
                $sql = "SELECT id_group, position FROM "._DB_PREFIX_.$this->table." WHERE position > ".intval($this->position)." ORDER BY position ";
                $result = Db::getInstance()->getRow($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = ".intval($result["position"])." WHERE id_group = ".$this->id;
                Db::getInstance()->Execute($sql);
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position = position-1 WHERE id_group = ".intval($result["id_group"]);
                Db::getInstance()->Execute($sql);
                break;
            default: return false;
        }
        return true;
    }
 
}

?>