<?php

/**
 * @author Andrew
 * @copyright 2010
 */

require_once PS_ADMIN_DIR.'/tabs/AdminCatalog.php';
require_once 'AdminProductsExFeatures.php';

class AdminCatalogExFeatures extends AdminCatalog
{
    public function __construct()
    {
        parent::__construct();
        $this->adminProducts = new AdminProductsExFeatures();
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