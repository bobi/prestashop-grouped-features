<?php
/**
 * This is file for override core prestashop Product class
 * Should be copied to /override/classes directory of prestashop installation location
 */
 
 class Product extends ProductCore
 {
	/**
     * Get all features, which has values for product
     * 
     * @param int $is_lang Language on which we should return labels and values of features
     * @param bool $withGroups whether we should return features with groups or not
     * @return array of features. @see parent::getFrontFeatures()
     */ 
    public function getFrontFeatures($id_lang, $withGroups=true)
	{
        if (!$withGroups)
            return parent::getFrontFeatures($id_lang);
        $id_product = $this->id;
        if (!array_key_exists($this->id.'-'.$id_lang, self::$_frontFeaturesCache))
		{
			self::$_frontFeaturesCache[$this->id.'-'.$id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT 
                fl.name AS feature_name, 
                value, 
                pf.id_feature, 
                fgl.id_group, 
                fgl.name AS feature_group_name
			FROM '._DB_PREFIX_.'feature_product pf
            LEFT JOIN '._DB_PREFIX_.'feature f ON pf.id_feature=f.id_feature
            LEFT JOIN '._DB_PREFIX_.'feature_group fg ON f.id_group=fg.id_group
            LEFT JOIN '._DB_PREFIX_.'feature_group_lang fgl ON (fg.id_group=fgl.id_group AND fgl.id_lang = '.(int)$id_lang.')
			LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = '.(int)$id_lang.')
			LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = '.(int)$id_lang.')
			WHERE pf.id_product = '.(int)$this->id);

            /* for debug
			if (Db::getInstance()->getNumberError()) {
                echo Db::getInstance()->getMsgError();
                exit();
            } */
		}
        
        $result = array();
        foreach (self::$_frontFeaturesCache[$id_product.'-'.$id_lang] as $frecord) {
            if (!$frecord['id_group'] || !$frecord['id_feature'])
                continue;
            if (!array_key_exists($frecord["id_group"], $result))
                $result[$frecord["id_group"]] = array("name" => $frecord['feature_group_name'], 'features' => array());
            $result[$frecord['id_group']]['features'][$frecord['id_feature']] = array("name" => $frecord['feature_name'], 'value'=>$frecord["value"]);
        }
        return $result;
	}
 } 