<?php

if ($step = (int)($_POST['ajaxProductTab'])) {
    if ($step == 5) {
        define('PS_ADMIN_DIR', getcwd());
        include(PS_ADMIN_DIR.'/../config/config.inc.php');
        
        /* Getting cookie or logout */
        require_once(PS_ADMIN_DIR.'/init.php');
        
       	require_once(dirname(__FILE__).'/tabs/AdminCatalog.php');
       	$catalog = new AdminCatalog();
            
        require_once(_PS_ROOT_DIR_.'/modules/exfeatures/AdminProductsExFeatures.php');
        $admin = new AdminProductsExFeatures();
        
        $languages = Language::getLanguages(false);
        $defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
        $lang = $cookie->id_lang ? $cookie->id_lang : $defaultLanguage;
        $product = new Product((int)(Tools::getValue('id_product')));
        if (!Validate::isLoadedObject($product))
        	die (Tools::displayError('Product cannot be loaded'));
       	$admin->displayFormFeatures($product, $languages, $lang);
        exit();
    }
} 

require "ajax.php";
