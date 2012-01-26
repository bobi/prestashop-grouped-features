<?php

/** 
 * Prestashop 1.4
 * This class for module, which grouped features
 * It's override core Tab class for adding mapper, which allow replace default tabs names on our own
 * 
 * @package modules
 * @category exfeatures
 */ 
class Tab extends TabCore
{
	/**
     * Simply replace AdminCatalog on AdminCatalogExFeatures
     * 
     * @param string $tab name of tab
     * @return string 'AdminCatalogExFeatures' if $tab id 'AdminCatalog', $tab otherwise 
     */ 
    static public function exfeaturesMap($tab)
    {
        if (!$tab) 
            return $tab;
        $_mapper = array('AdminCatalog' => 'AdminCatalogExFeatures');
        if (array_key_exists($tab, $_mapper))
            return $_mapper[$tab];
        return $tab;
    }
    
    /**
	 * Get tab id from name
	 *
	 * @param string class_name
	 * @return int id_tab
	 */
	static public function getIdFromClassName($class_name)
	{
		$class_name = self::exfeaturesMap($class_name);
        return parent::getIdFromClassName($class_name);
	}
    
    /**
	 * Get tab id
     * Using tab mapper for getting current features tab
     * @remark There is no in prestashop one point, where tab fetched from query_string
     * Here, probably, better to use global variable $tab instead of repeat fetched this query_string 
	 *
	 * @return integer tab id
	 */
	static public function getCurrentTabId()
	{
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
            SELECT `id_tab` FROM `'._DB_PREFIX_.'tab` WHERE LOWER(class_name)=\''.pSQL(Tools::strtolower(self::exfeaturesMap(Tools::getValue('tab')))).'\'
        ');
        if ($result)
		 	return $result['id_tab'];
 		return -1;
	}
}