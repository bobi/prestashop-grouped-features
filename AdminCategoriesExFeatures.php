<?php

/**
 * @author Andrew
 * @copyright 2010
 */

require_once PS_ADMIN_DIR.'/tabs/AdminCategories.php';

class AdminCategoriesExFeatures extends AdminCategories 
{
    private $_adminCatalogInstance;
    
    public function __construct($catalogInstance=null)
    {
		$this->_adminCatalogInstance = $catalogInstance;
		global $cookie;
		parent::__construct();
		$this->token = Tools::getAdminToken("AdminCatalogExFeatures".(int)$this->id.(int)$cookie->id_employee);
	}
	
	protected function l($string, $class = 'AdminTab', $addslashes = FALSE, $htmlentities = TRUE)
	{
		if ($this->_adminCatalogInstance) {
			return $this->_adminCatalogInstance->exf_l($string, "AdminCategories", $addslashes, $htmlentities);
		}
		return parent::l($string, $class, $addslashes, $htmlentities);
	}
}
