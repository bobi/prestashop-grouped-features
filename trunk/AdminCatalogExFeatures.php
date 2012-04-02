<?php

/**
 * @author Andrew
 * @copyright 2010
 */

require_once PS_ADMIN_DIR.'/tabs/AdminCatalog.php';
require_once 'AdminProductsExFeatures.php';
require_once 'AdminCategoriesExFeatures.php';

class AdminCatalogExFeatures extends AdminCatalog
{
    public function __construct()
    {
        global $cookie;
        parent::__construct();
        $this->adminProducts = new AdminProductsExFeatures();
        $this->adminCategories = new AdminCategoriesExFeatures();
        $this->token = Tools::getAdminToken("AdminCatalogExFeatures".(int)$this->id.(int)$cookie->id_employee);
    }
    
    public function checkToken()
    {
		global $cookie;
		if ($parentCheck = parent::checkToken()) {
			return $parentCheck;
		} else {
			$token = Tools::getValue('token');
			return (!empty($token) AND $token === Tools::getAdminToken('AdminCatalog'.(int)(Tab::getIdFromClassName('AdminCatalog')).(int)($cookie->id_employee)));
		}
	}
    
    protected function l($string, $class = 'AdminTab', $addslashes = FALSE, $htmlentities = TRUE)
    {
        if ($class != __CLASS__) {
            $parentTranslation = parent::l($string, 'AdminCatalog', $addslashes, $htmlentities, false);
            if ($string != $parentTranslation)
                return $parentTranslation;
            return parent::l($string, $class, $addslashes, $htmlentities);     
        } 
        return exfeatures::getInstance()->l($string, strtolower($class));
    }
}

?>
