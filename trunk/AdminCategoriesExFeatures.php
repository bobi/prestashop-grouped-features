<?php

/**
 * @author Andrew
 * @copyright 2010
 */

require_once PS_ADMIN_DIR.'/tabs/AdminCategories.php';

class AdminCategoriesExFeatures extends AdminCategories 
{
    public function __construct()
    {
		global $cookie;
		parent::__construct();
		$this->token = Tools::getAdminToken("AdminCatalogExFeatures".(int)$this->id.(int)$cookie->id_employee);
	}
}
