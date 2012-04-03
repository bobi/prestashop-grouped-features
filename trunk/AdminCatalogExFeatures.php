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
        $this->adminProducts = new AdminProductsExFeatures($this);
        $this->adminCategories = new AdminCategoriesExFeatures($this);
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
	
	public function exf_l($string, $class = 'AdminTab', $addslashes = FALSE, $htmlentities = TRUE)
	{
		return $this->l($string, $class, $addslashes, $htmlentities);
	}
    
    protected function l($string, $class = 'AdminTab', $addslashes = FALSE, $htmlentities = TRUE)
    {
        if ($class != __CLASS__) {
			if (!in_array($class, array("AdminCategories", "AdminProducts"))) {
				$class = "AdminCatalog";
			}
            $parentTranslation = parent::l($string, $class, $addslashes, $htmlentities);
            if ($string != $parentTranslation) {
                return $parentTranslation;
			}
			global $_LANGADM;
			$key = md5(str_replace('\'', '\\\'', $string));
			$str = ((key_exists($class.$key, $_LANGADM)) ? $_LANGADM[$class.$key] : $string);
			$str = $htmlentities ? htmlentities($str, ENT_QUOTES, 'utf-8') : $str;
			return str_replace('"', '&quot;', ($addslashes ? addslashes($str) : stripslashes($str)));
        } 
        return exfeatures::getInstance()->l($string, strtolower($class));
    }
}

?>
