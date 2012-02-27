<?php

/**
 * Main file with class for grouped fatures modules
 * Prestashop version 1.4
 * 
 * @category Features
 * @package Prestashop
 */ 

/**
 * Besides traditional functions of implements presta module,
 * this class also implements singleton pattern for another class functional
 */ 
class exfeatures extends Module
{
    private static $_instance;
    private $_installationErrors;
    private $_installationSuccesses;
    private $_overriddenClasses;
    private $_overridenTemplates;
    
    public function __construct()
    {
		$this->name = 'exfeatures';
        $this->tab = 'front_office_features';
		$this->version = '1.1';
        $this->author = "Gon";
        
		parent::__construct();
        
		$this->displayName = $this->l('Extensible Grouped Features');
		$this->description = $this->l('Adding groups for your features');
        $this->_overriddenClasses = array('Product.php', 'Tab.php', 'Tools.php', 'Feature.php');
        $this->_overridenTemplates = array('product.tpl', 'products-comparison.tpl');
        if (!$this->checkInstallationCorrection())
            $this->warning = $this->l("This module is not installed properly. Please click 'Configure' to see what is wrong");
        $this->_installationSuccesses  = array();
        $this->_installationErrors = array();
        
	}
    
    /**
     * This class implements singleton pattern
     */ 
    static public function getInstance()
    {
        if (!self::$_instance)
            self::$_instance = new self();
        return self::$_instance;
    }
   
    public function install()
    {
        if (!parent::install() || !$this->registerHook('backOfficeHeader')) {
            return false;
        }
        $this->installDatabase();
        $this->createDefaultGroup();
        $this->installTabsOverride();
        $this->copyAdditionalScripts();
        $this->patchTheme();
        $this->patchCore();
        return true;
    } 
    
    /**
     * Make necessary modifications in database
     * 
     * @see base.sql
     * @see README install (3)
     * @return bool true on success, false when errors occur
     */ 
    private function installDatabase()
    {
        return $this->executeQueries(array(
            
            'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'feature_group(
              id_group INT (11) NOT NULL AUTO_INCREMENT,
              position INT (11) DEFAULT NULL,
              PRIMARY KEY (id_group)
            )
            ENGINE = INNODB
            AUTO_INCREMENT = 27
            AVG_ROW_LENGTH = 5461
            CHARACTER SET utf8
            COLLATE utf8_general_ci',
            
            'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'feature_group_lang(
              id_group INT (11) DEFAULT NULL,
              id_lang INT (11) DEFAULT NULL,
              name VARCHAR (50) DEFAULT NULL
            )
            ENGINE = INNODB
            AVG_ROW_LENGTH = 2730
            CHARACTER SET utf8
            COLLATE utf8_general_ci',
            
            'ALTER TABLE '._DB_PREFIX_.'feature ADD COLUMN position int(10)',
            
            'ALTER TABLE '._DB_PREFIX_.'feature ADD COLUMN id_group int(10)'
        ), false);   
    }
    
    /**
     * When on moment of installation module some features already exists (most cases), 
     * they can not be displayed, because they are not in one of groups
     * That's why we create one group, where we put all features. 
     * During using module user can rename or delete this group. 
     * Also user can move features from this group to another, which can be created by user
     * 
     * @return bool true on success, false otherwise
     */  
    public function createDefaultGroup()
    {
        global $cookie;
        $this->executeQueries(array('INSERT INTO '._DB_PREFIX_.'feature_group(position) VALUES(1)'));
        $insertId = Db::getInstance()->Insert_ID();
        
        //insert name of group as "Common" on all langauges, installed on shop
        foreach (Language::getLanguages(false) as $k=>$lrec)   
            $this->executeQueries(array('INSERT INTO '._DB_PREFIX_.'feature_group_lang(id_lang, id_group, name) VALUES(\''.$lrec['id_lang'].'\', \''.$insertId.'\', \'Common\')'));
        
        //Move all features exists at that time to this group 
        $this->executeQueries(array('UPDATE '._DB_PREFIX_.'feature SET id_group='.$insertId));
        return true;
    }
    
    /** 
     * Simply execute queries, passed as array
     * 
     * @param array array of queries
     * @param bool $breakOnError Whether or not break execution, when one of queries finish with errors
     * @return bool true if all queries was executed successfully, false otherwise
     */ 
    private function executeQueries(array $queries, $breakOnError=true)
    {
        $dbInstance = Db::getInstance();
        $return = true;
        foreach ($queries as $query) {
            $up_tab = $dbInstance->Execute($query); 
            if (!$up_tab) {
                file_put_contents(_PS_ROOT_DIR_.'/modules/'.$this->name.'/debug.log', "Error while execute: \n'".$query."' \n".$dbInstance->getMsgError()."\n\n", FILE_APPEND);
                if ($breakOnError)
                    return false;
                else 
                    $return = false;
            }
        }
        return $return;
    }
    
    /**
     * Override class of Prestashop tabs by out classes.
     * This is necessary for replace default behaviour with feature in admin panel
     * 
     * @see README
     * @return bool true on success, false when errors occur
     */   
    private function installTabsOverride()
    {
        $queries = array(
            "UPDATE "._DB_PREFIX_."tab 
            SET module='exfeatures', class_name='AdminCatalogExFeatures' 
            WHERE class_name='AdminCatalog'",
            
            "UPDATE "._DB_PREFIX_."tab 
            SET module='exfeatures', class_name='AdminExFeatures' 
            WHERE class_name='AdminFeatures'"  
        );
        return $this->executeQueries($queries);
    }
    
    /**
     * Path default theme (prestashop)
     * Replace code for display feature list by another, which display grouped list 
     * 
     * @return bool true if patched successfully, false if errors occur
     */ 
    private function patchTheme()
    {
        //backup original scripts for restore it if module will be uninstall
        foreach ($this->_overridenTemplates as $tpl) {
            copy(_PS_ALL_THEMES_DIR_."prestashop/".$tpl, dirname(__FILE__)."/override/theme/".$tpl.".original");    
        }
        
        //replace core template
        foreach ($this->_overridenTemplates as $tpl) {
            //file_put_contents(dirname(__FILE__)."/debug.log", dirname(__FILE__)."/override/theme/".$tpl." -> "._THEMES_DIR_."prestashop/".$tpl . "\n", FILE_APPEND);
            copy(dirname(__FILE__)."/override/theme/".$tpl, _PS_ALL_THEMES_DIR_."prestashop/".$tpl);    
        }
    }
    
    public function writeCore($pattern, $area)
    {
        $AdminCatalogDump = file_get_contents(PS_ADMIN_DIR.'/tabs/AdminCatalog.php');
        $count = 0;
        $newDump = preg_replace($pattern, $area.' $2', $AdminCatalogDump, -1, $count);
        if ($count != 1) {
            $newDump = $AdminCatalogDump;
        }
        file_put_contents(PS_ADMIN_DIR.'/tabs/AdminCatalog.php', $newDump);
        return true;  
    } 
    /**
     * Everything, what this function do is change access area of AdminCatalog::adminProducts property from private to protected
     */  
    private function patchCore()
    {
        $this->writeCore('/(private[\t\s]+)(\$adminProducts)/', 'protected');
    }
    
    /**
     * Rollback action of $this->patchCore() method
     */ 
    private function restoreCore()
    {
        $this->writeCore('/(protected[\t\s]+)(\$adminProducts)/', 'private');
    } 
    
    /**
     * Rollback action of $this->patchTheme function
     */ 
    private function restoreTheme()
    {
        foreach ($this->_overridenTemplates as $tpl) {
            $fname = dirname(__FILE__)."/override/theme/".$tpl.".original"; 
            if (file_exists($fname))
                copy($fname, _PS_ALL_THEMES_DIR_.$tpl);
        }
        return true;
    }
    
  	public function uninstall()
	{
		if (!parent::uninstall())
            return false;
        $this->cleanDatabase();
        $this->restoreTabs();
        $this->removeAdditionalScripts();
        $this->restoreTheme();
        $this->restoreCore();
		return true;
	}
    
    /**
     * Remove tables from database and special fields from table _DB_PREFIX_.feature 
     * Use only for uninstall module
     * 
     * @return true on success and false on failure
     */ 
    private function cleanDatabase()
    {
        return $this->executeQueries(array(
            'DROP TABLE `'._DB_PREFIX_.'feature_group` , `'._DB_PREFIX_.'feature_group_lang`',
            'ALTER TABLE '._DB_PREFIX_.'feature DROP COLUMN id_group',
            'ALTER TABLE '._DB_PREFIX_.'feature DROP COLUMN position'
        ), false);
    }
    
    /**
     * Restore default tabs classes - AdminFeatures for 'Features' tab and AdminCatalog for 'Catalog' tab
     * Use only for unistall module
     * 
     * @return bool true on success and false on failure
     */  
    private function restoreTabs()
    {
         $queries = array(
            "UPDATE "._DB_PREFIX_."tab 
            SET module='', class_name='AdminCatalog' 
            WHERE class_name='AdminCatalogExFeatures'",
            
            "UPDATE "._DB_PREFIX_."tab 
            SET module='', class_name='AdminFeatures' 
            WHERE class_name='AdminExFeatures'"  
        );
       return $this->executeQueries($queries, false);
    }
    
    /**
     * For working module, we must have several scripts in appropriate places of prestashop installation
     * ajax_ex_features.php should be in admin catalog. It's use for load features into product editing form
     * 
     * In override/classes/ category should be next files:
     *  Product.php. For generating grouped features for display in frontOffice on product page
     *  Tabs.php. For implements tab mapping
     *  Tools.php. For the same as Tab.php. There was override method for security token
     */ 
    private function copyAdditionalScripts()
    {
       copy(dirname(__FILE__)."/ajax_ex_features.php", _PS_ADMIN_DIR_.'/ajax_ex_features.php');
       foreach ($this->_overriddenClasses as $oc) {
            copy(dirname(__FILE__).'/override/classes/'.$oc, _PS_ROOT_DIR_.'/override/classes/'.$oc);
       }
       return true;
    }
    
    /**
     * Roolback of actions of $this->copyAdditionalScripts() method
     * Remove all files, which have been need for module work
     * 
     * @return nothing
     */  
    private function removeAdditionalScripts()
    {
        foreach ($this->_overriddenClasses as $oc) {
            unlink(_PS_ROOT_DIR_.'/override/classes/'.$oc);    
        }
        unlink(_PS_ADMIN_DIR_.'/ajax_ex_features.php');
    }
    
    /**
     * Check if all components of module exists in their places and ready to work
     * Fill $this->_installationSuccesses and $this->_installationErrors with appropriate messages
     * 
     * @return bool true if all of necesary components are installed and ready to work, false otherwise 
     */   
    private function checkInstallationCorrection()
    {
        $everythingOk = true;
        $dbInstance = Db::getInstance();

        //check appropriate tables
        foreach (array('feature_group', 'feature_group_lang') as $table) {
            $res = $dbInstance->ExecuteS('Show tables where Tables_in_'._DB_NAME_.'=\''._DB_PREFIX_.$table.'\'');
            if (!$res) {
                $this->_installationErrors[] = "Table ".$table." does not exists";
                $everythingOk = false;
            } else {
                $this->_installationSuccesses[] = "Table ".$table." exists";
            }
        }
        
        //ckeck appropriate fields in feature table
        $res = $dbInstance->ExecuteS('Show columns from '._DB_PREFIX_.'feature');
        $_fields = array();
        foreach ($res as $field) {
            $_fields[] = $field["Field"];
        }
        foreach (array('id_group', 'position') as $field) {
            if (array_key_exists($field, $_fields)) {
                $everythingOk = false;
                $this->_installationErrors[] = 'Field '.$field.' does not exists in feature table';
            } else {
                $this->_installationSuccesses[] = 'Field '.$field.' exists in feature table'; 
            }
        }
        
        //check if tabs overrided on our classes
        foreach (array('AdminCatalogExFeatures', 'AdminExFeatures') as $tabClass) {
            if (!$dbInstance->ExecuteS('SELECT * FROM '._DB_PREFIX_.'tab WHERE class_name=\''.$tabClass.'\'')) {
                $everythingOk = false;
                $this->_installationErrors[] = 'Tab with working class '.$tabClass.' does not exists';
            } else {
                $this->_installationSuccesses[] = 'Tab with working class '.$tabClass.' exists';
            }
        }
        
        //check if all scripts on their places
        $_scripts = array();
        foreach ($this->_overriddenClasses as $oc) {
            $_scripts[] = _PS_ROOT_DIR_.'/override/classes/'.$oc;
        }
        $_scripts[] = _PS_ADMIN_DIR_.'/ajax_ex_features.php';
        foreach ($_scripts as $_script) {
            if (!file_exists($_script)) {
                $everythingOk = false;
                $this->_installationErrors[] = 'There are no '.substr($_script, strlen(_PS_ROOT_DIR_)).' script on-site';
            } else {
                $this->_installationSuccesses[] = 'Script '.substr($_script, strlen(_PS_ROOT_DIR_)).' on-site';
            }
        }
        
        //check if patching default theme
        foreach ($this->_overridenTemplates as $tpl) {
            if (strpos(file_get_contents(_PS_ALL_THEMES_DIR_.'/prestashop/'.$tpl), 'Added by module for grouped features')) {
                $this->_installationSuccesses[] = "Default ".$tpl." successfully patched";
            } else {
                $this->_installationErrors[] = "Default ".$tpl." not patched";
            }
        }
        
        //check if AdminCatalog::adminProducts is moved to protected area
        require_once PS_ADMIN_DIR.'/tabs/AdminCatalog.php';
        $reflection = new ReflectionClass('AdminCatalog');
        $adminProductsProp = $reflection->getProperty('adminProducts');
        if ($adminProductsProp->isProtected() || $adminProductsProp->isPublic()) {
            $this->_installationSuccesses[] = 'AdminCatalog::adminProduct in correct access area';
        } else {
            $everythingOk = false;
            $this->_installationErrors[] = 'AdminCatalog:adminProduct should be in protected access area';
        }
        return $everythingOk;

    }
    
    public function getContent()
    {
        $this->checkInstallationCorrection();
        $output = '<ul style="list-style: none">';
        foreach ($this->_installationSuccesses as $item) {
            $output .= '<li style="color: green;"><img src="../img/admin/module_install.png" /> '.$item."</li>\n";
        }
        foreach ($this->_installationErrors as $item) {
            $output .= '<li style="color: red;"><img src="../img/admin/module_notinstall.png" />'.$item."</li>\n";
        } 
        $output .= '</ul>';
        $output .= "<span style=\"margin-left: 40px; font-style: italic;\">* All items should be green for correct functioning of the module</span>";
        return $output;
    }
    
    /**
     * When prestashop generate url for some links in Catalog page (Admin panel),
     * it use string constant AdminCatalog instead of $currentIndex, as I have guess early.
     * This function map $tab from AdminCatalog to AdminCatalogExFeatures
     * 
     * @param array The same array, which passed to all hooks in prestashop
     * @return string Empty string, because output of this function insert into output html of header.  
     */ 
    public function hookbackOfficeHeader($params)
    {
        global $tab;
        $tab = Tab::exfeaturesMap($tab);
        return '';
    }
}
