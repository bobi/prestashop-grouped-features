<?php

/**
 * @author Andriy
 * @copyright 2010
 */

require_once 'exfeatures.php';

class AdminFeaturesGroups extends AdminTab
{
    public $table = 'feature_group';
    public $identifier = 'id_group';
    
    private $_moduleDir;
    private $_templatesDir;
    
    protected function l($string, $class = 'AdminTab', $addslashes = FALSE, $htmlentities = TRUE)
    {
        if ($class == "AdminTab")
            return parent::l($string, $class, $addslashes, $htmlentities);
            
        return exfeatures::getInstance()->l($string, strtolower(get_class($this)));
    }
    
    public function __construct()
    {
 	 	$this->className = 'FeatureGroup';
        $this->fieldsDisplay  = array(
            'name' => array('title' => $this->l('Name', __CLASS__), 'width' => 200),
            'position' => array('title' => $this->l('Position', __CLASS__), 'width' => 40)
        );
        $this->lang = true;
	 	$this->edit = true;
	 	$this->delete = true;
        $this->identifier = 'id_group';
        $this->table = 'feature_group';
        
        $this->_moduleDir = _PS_ROOT_DIR_.'/modules/'.exfeatures::getInstance()->name;
        $this->_templatesDir = $this->_moduleDir."/templates/";

        parent::__construct();
    }
    
    public function displayForm($token)
    {
		global $currentIndex;

		$defaultLanguage = intval(Configuration::get('PS_LANG_DEFAULT'));
		$languages = Language::getLanguages();
		$obj = $this->loadObject(true);
        
		echo '
		<script type="text/javascript">
			id_language = Number('.$defaultLanguage.');
		</script>';

		echo '
		<form action="'.$currentIndex.'&token='.$token.'"" method="post">
		'.($obj->id ? '<input type="hidden" name="id_group" value="'.$obj->id.'" />' : '').'
			<fieldset class="width3"><legend><img src="../img/t/AdminFeatures.gif" />'.$this->l('Feature Group', __CLASS__).'</legend>
				<label>'.$this->l('Name', __CLASS__).': </label>
				<div class="margin-form">';
		foreach ($languages as $language)
			echo '
					<div id="name_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left;">
						<input size="33" type="text" name="name_'.$language['id_lang'].'" value="'.htmlentities($this->getFieldValue($obj, 'name', intval($language['id_lang'])), ENT_COMPAT, 'UTF-8').'" /><sup> *</sup>
						<span class="hint" name="help_box">'.$this->l('Invalid characters', __CLASS__).': <>;=#{}<span class="hint-pointer">&nbsp;</span></span>
					</div>';
		$this->displayFlags($languages, $defaultLanguage, 'name', 'name');
		echo '
					<div style="clear: both;"></div>
				</div>
                <label>'.$this->l('Position', __CLASS__).': </label>
                <div class="margin-form">
                    <input size=5 maxlength=5 type="text" name="position" value="'.htmlentities($this->getFieldValue($obj, 'position', NULL), ENT_COMPAT, 'UTF-8').'">
                    <span class="hint" name="help_box">'.$this->l('Only digital allowed!', __CLASS__).'<span class="hint-pointer">&nbsp;</span></span>
                </div>
				<div class="margin-form">
					<input type="submit" value="'.$this->l('   Save   ', __CLASS__).'" name="submitAdd'.$this->table.'" class="button" />
				</div>
				<div class="small"><sup>*</sup> '.$this->l('Required field', __CLASS__).'</div>
			</fieldset>
		</form>';
        
        echo '<br /><br /><a href="'.$currentIndex.'&token='.$this->token.'"><img src="../img/admin/arrow2.gif" /> '.$this->l('Back to list').'</a><br />';
    }
    
    public function displayList()
	{
		global $currentIndex;

		$this->_orderBy = "position";
        echo '<br />
            <a href="'.$currentIndex.'&add'.$this->table.'=1&token='.$this->token.'">
                <img src="../img/admin/add.gif" border="0" /> <b>'.$this->l('Add group', __CLASS__).'</b>
            </a><br />
            <a href="'.$currentIndex.'&reorderpositions=1&token='.$this->token.'">
                <img src="../img/admin/add.gif" border="0" /> <b>'.$this->l('Reorder positions', __CLASS__).'</b>
            </a><br /><br />

		'.$this->l('Click on the group name to view its features. ', __CLASS__).'<br /><br />
        <h2>'.$this->l('Feature Groups', __CLASS__).'</h2>';

		$this->displayListHeader();
		echo '<input type="hidden" name="groupid" value="0">';

		if (!sizeof($this->_list))
			echo '<tr><td class="center" colspan="'.sizeof($this->_list).'">'.$this->l('No features found.', __CLASS__).'</td></tr>';
        
        $this->displayListContent();

		$this->displayListFooter();
    }
    
    public function displayListContent()
    {
        global $currentIndex;
        if ($this->_list):
            $irow = 0;
            foreach ($this->_list as $i=>$tr):
                ++$irow;
                $id = $tr[$this->identifier]; ?>
                <tr style="height: 35px;" class="nodrag nodrop">
                    <?php if ($this->delete): ?> 
            			<td class="center">
                            <input type="checkbox" name="<?php echo $this->table; ?>Box[]" value="<?php echo $id; ?>" class="noborder" />
                        </td>
                    <?php endif; ?>
                    
                    <td onclick="document.location = '<?php echo $currentIndex; ?>&id_group=<?php echo $tr["id_group"]; ?>&token=<?php echo $this->token; ?>'" class="pointer">
                        <?php echo $tr["name"]; ?>
                    </td>
                    <td class="pointer dragHandle center" id="td_<?php echo $tr["id_group"]; ?>">
                        <?php if ($irow<count($this->_list)): ?>
                            <a href="<?php echo $currentIndex; ?>&id_group=<?php echo $tr["id_group"]; ?>&down_group_position=<?php echo $tr["position"]; ?>&token=<?php echo $this->token; ?>">
                                <img title="Down" alt="Down" src="../img/admin/down.gif" />
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($irow>1): ?> 
                            <a href="<?php echo $currentIndex; ?>&id_group=<?php echo $tr["id_group"]; ?>&up_group_position=<?php echo $tr["position"]; ?>&token=<?php echo $this->token; ?>">
                                <img title="Up" alt="Up" src="../img/admin/up.gif" />
                            </a>
                        <?php endif; ?>
                    </td>
                    
                    <td style="vertical-align: top; padding: 4px 0 4px 0" class="center">
            			<a href="<?php echo $currentIndex; ?>&id_group=<?php echo $id; ?>&update<?php echo $this->table; ?>&token=<?php echo $this->token; ?>">
                            <img src="../img/admin/edit.gif" border="0" alt="<?php echo $this->l('Edit'); ?>" title="<?php echo $this->l('Edit'); ?>" />
                        </a>&nbsp;
            			<a href="<?php echo $currentIndex; ?>&id_group=<?php echo $id; ?>&delete<?php echo $this->table; ?>&token=<?php echo $this->token; ?>" 
                            onclick="return confirm('<?php echo $this->l('Delete item', __CLASS__, true, false).' #'.$id; ?>'?');">
            			     <img src="../img/admin/delete.gif" border="0" alt="<?php echo $this->l('Delete'); ?>" title="<?php echo $this->l('Delete'); ?>" />
                        </a>
            		</td>
                </tr>
            <?php endforeach; ?>
        <?php endif;
    }
    
    public function add()
    {
        //check input data
        
        //get multilanguage data
        $languages = Language::getLanguages();
        $name = array();
        foreach ($languages as $lang) {
            if ($n = Tools::getValue("name_".$lang["id_lang"])) {
                if (!Validate::isGenericName($n)) {
                    $this->_errors[] = $this->l("Invalid required field - 'group name' for language ").$lang["name"];
                }
                $name[$lang["id_lang"]] = $n;
            }  
        }
        if (empty($name)) {
            echo Tools::displayError($this->l("Empty required field - 'group name'"));
            return false;
        }
        $position = intval(Tools::getValue('position'));
        if (!$position) {
            $position = $this->getMaxGroupPosition()+1; 
        }
        
        $obj = $this->loadObject(true);
        $obj->name = $name;
        $obj->position = $position;
        if (sizeof($this->_errors))
            return false;
        if ($obj->id) {
            if (!$obj->update()) {
                echo Tools::displayError($this->l("Error updating group"));
                return false;
            } 
        }
        else {
            if (!$obj->add()) {
                echo Tools::displayError($this->l("Error adding group"));
                return false; 
            }
        }
        return true;   
    }
    
    public function setupPosition()
    {
        $id_group = intval(Tools::getValue("id_group"));
        if (!$id_group)
            return false;
        $order='';
        
        if (Tools::getValue("up_group_position")) 
            $order = "up";
        elseif (Tools::getValue("down_group_position"))
            $order = "down";
        $this->loadObject()->setupPosition($id_group, $order);
    }
    
    public function getList($id_lang, $orderBy = 'fg.position', $orderWay = 'ASC', $start = 0, $limit = NULL)
    {
        $sql = 'SELECT fg.id_group AS id_group, fg.position AS position, fgl.name 
                FROM '._DB_PREFIX_.$this->table.' fg JOIN '._DB_PREFIX_.$this->table.'_lang fgl ON fg.id_group = fgl.id_group
                WHERE fgl.id_lang='.intval($id_lang)." 
                ORDER BY ".$orderBy." ".$orderWay." 
                ".($limit?(" LIMIT ".$start.", ".$limit):" ");
        
        $this->_list = Db::getInstance()->ExecuteS($sql);
        if (mysql_errno())
            throw new Exception(mysql_error());   
    }
    
    public function reorderPositions() 
    {
        //get all groups 
        $sql = "SELECT id_group FROM "._DB_PREFIX_.$this->table." ORDER BY position";
        $result = Db::getInstance()->ExecuteS($sql);
        $position = 1;
        foreach ($result as $record) {
            $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position=$position WHERE id_group = ".$record["id_group"];
            Db::getInstance()->Execute($sql);
            if (mysql_errno())
                throw new Exception(mysql_error());
            ++$position;
        }
        return true;
    }
     
    
    private function getMaxGroupPosition()
    {
        $sql = "SELECT max(position) as mp FROM "._DB_PREFIX_."feature_group";
        $result = Db::getInstance()->getRow($sql);
        return intval($result["mp"]);
    }
    
     
    
     
}

?>