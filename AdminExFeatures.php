<?php

/**
 * @author Andriy
 * @copyright 2010
 * This module use core AdminFeatures functional 
 * because we must set admin folder of our site as value of $adminFolder variable  
 */

require PS_ADMIN_DIR.'/tabs/AdminFeatures.php';
require_once 'exfeatures.php';

class AdminExFeatures extends AdminFeatures
{
   	public $adminFeaturesGroups;
    public $id_group;
    public $id_feature;
    public $className;
    public $identifier;
    
    private $_moduleDir;
    private $_templatesDir;
    
    public function __construct()
    {
        require_once 'AdminFeaturesGroups.php';
        require_once "ExFeature.php";
        require_once "FeatureGroup.php";
        $this->adminFeaturesGroups = new AdminFeaturesGroups();
        
        parent::__construct();
        
        $this->adminFeaturesGroups->token  = $this->token;
        $this->fieldsDisplay["group_name"] = array('title' => $this->l('Group', __CLASS__), 'width' => 128);
        $this->fieldsDisplay["f_position"] = array('title' => $this->l('Position', __CLASS__), 'width' => 40);
        $this->className = "ExFeature";
        $this->identifier = 'id_feature';
        
        $this->_moduleDir = _PS_ROOT_DIR_.'/modules/'.exfeatures::getInstance()->name;
        $this->_templatesDir = $this->_moduleDir."/templates/";
    }
    
    protected function l($string, $class = 'AdminTab', $addslashes = FALSE, $htmlentities = TRUE)
    {
        if ($class != __CLASS__) {
            $parentTranslation = parent::l($string, 'AdminFeatures', $addslashes, $htmlentities, false); 
            if ($string != $parentTranslation)
                return $parentTranslation;
            return parent::l($string, $class, $addslashes, $htmlentities);     
        } 
        return exfeatures::getInstance()->l($string, strtolower($class));
    }
    
    public function display()
    {
        if (sizeof($this->adminFeaturesGroups->_errors) OR Tools::getValue("add".$this->adminFeaturesGroups->table) OR isset($_GET['update'.$this->adminFeaturesGroups->table])) {
            $this->adminFeaturesGroups->displayForm($this->token);
        }
        elseif (isset($_GET['add'.$this->table]) OR isset($_GET['update'.$this->table]) OR sizeof($this->_errors)) {
            $this->displayForm();
        }
        elseif ($id_group = intval(Tools::getValue('id_group'))) {
            $this->id_group = $id_group;
            $this->id_feature = Tools::getValue('id_feature', null);
            parent::display();
        }
        elseif ($id_feature_value = Tools::getValue($this->adminFeaturesValues->identifier)) {
            $e_object = $this->loadObject(true);
            $this->id_group = $e_object->getFeatureGroupByValue($id_feature_value);
            if ($this->id_group)
                $_GET["id_group"] = $this->id_group;
            if (!$id_feature = Tools::getValue('id_feature')) {
                $this->id_feature = $e_object->getFeatureByValue($id_feature_value);
                if ($this->id_feature)
                    $_GET["id_feature"] = $this->id_feature;
            }
            parent::display();
        }
        elseif (isset($_GET['add'.$this->adminFeaturesValues->table])) {
            $this->adminFeaturesValues->displayForm($this->token);
        } 
        else {
            $this->adminFeaturesGroups->display();
        }
    }
    
    public function postProcess()   
    {
        global $currentIndex;
        
        $ci = $currentIndex;
        $currentIndexParams = array();
        foreach (array($this->identifier, $this->adminFeaturesGroups->identifier, $this->adminFeaturesValues->identifier) as $param) {
            if ($vparam = Tools::getValue($param))
                $currentIndexParams[$param] = $vparam;                
        }
        
        if (!array_key_exists($this->adminFeaturesGroups->identifier, $currentIndexParams)) {
            $e_object = $this->loadObject(true);
            if ($id_feature_value = Tools::getValue($this->adminFeaturesValues->identifier)) {
                $this->id_group = $e_object->getFeatureGroupByValue($id_feature_value);
                if ($this->id_group)
                    $currentIndexParams[$this->adminFeaturesGroups->identifier] = $this->id_group;
                $this->id_feature = $e_object->getFeatureByValue($id_feature_value);
                if ($this->id_feature)
                    $currentIndexParams[$this->identifier] = $this->id_feature;
            }
            
            if (!array_key_exists($this->identifier, $currentIndexParams)) {
                if ($id_feature = Tools::getValue($this->identifier)) {
                    $currentIndexParams[$this->identifier] = $e_object->getFeatureGroup($id_feature);
                }
            }    
        }
        
        foreach ($currentIndexParams as $param=>$value) {
            $currentIndex .= '&'.$param.'='.$value;
        }
        
        $this->adminFeaturesGroups->tabAccess =$this->tabAccess;
        if (Tools::isSubmit('submitAdd'.$this->adminFeaturesGroups->table)) {
            if (!$this->adminFeaturesGroups->add()) {
                $this->_errors = $this->adminFeaturesGroups->_errors;
            } 
        }
        elseif (Tools::getValue('upposition') OR Tools::getValue('downposition')) {
            $this->setupPosition();
        }
        elseif (isset($_GET['delete'.$this->adminFeaturesGroups->table]) OR Tools::getValue('submitDel'.$this->adminFeaturesGroups->table)) {
            $this->adminFeaturesGroups->postProcess();
            unset($_GET[$this->adminFeaturesGroups->identifier]);
        }
        elseif (Tools::getValue('up_group_position') OR Tools::getValue('down_group_position')) {
            $this->adminFeaturesGroups->setupPosition();
            unset($_GET[$this->adminFeaturesGroups->identifier]);
        }
        elseif (Tools::getValue("reorderpositions")) {
            $this->reorderPositions();
        }
            
        parent::postProcess();
        
        $currentIndex = $ci;
    }
    
    public function getList($id_lang, $orderBy = 'f.position', $orderWay = 'ASC', $start = 0, $limit = NULL)
    {
        global $cookie;
        $sql = "SELECT 
                    f.id_feature AS id_feature,
                    fl.name AS name,
                    fgl.name AS group_name,
                    f.position as f_position,
                    '---' AS value
                FROM 
                    "._DB_PREFIX_."feature f 
                    JOIN "._DB_PREFIX_."feature_group fg ON f.id_group = fg.id_group
                    JOIN "._DB_PREFIX_."feature_lang fl ON f.id_feature = fl.id_feature
                    JOIN "._DB_PREFIX_."feature_group_lang fgl ON fg.id_group = fgl.id_group
                WHERE 
                ".($this->id_group?(" f.id_group = ".$this->id_group." AND "): "").
                " fl.id_lang = ".intval($id_lang)." AND fgl.id_lang = ".intval($id_lang).
                " ORDER BY ".$orderBy." ".$orderWay
                ." ".($limit?' LIMIT '.intval($start).', '.intval($limit):' ');
                
        $this->_list = Db::getInstance()->ExecuteS($sql);
        if (mysql_errno())
            throw new Exception("SQL error to get feature list ".mysql_error());
    }  
     
    public function displayList()
	{
		global $currentIndex, $cookie;
        ?>
<h2><?php echo $this->adminFeaturesGroups->loadObject()->name[$cookie->id_lang]; ?></h2>
<br />
<a href="<?php echo $currentIndex; ?>&add<?php echo $this->table.($this->id_group?'&id_group='.$this->id_group:''); ?>&token=<?php echo $this->token; ?>">
    <img src="../img/admin/add.gif" border="0" /> <b><?php echo $this->l('Add feature', __CLASS__); ?></b>
</a><br />
<a href="<?php echo $currentIndex; ?>&reorderpositions=1<?php echo ($this->id_group?'&id_group='.$this->id_group:''); ?>&token=<?php echo $this->token; ?>">
    <img src="../img/admin/add.gif" border="0" /> <b><?php echo $this->l('Reorder position', __CLASS__); ?></b>
</a><br />

<a href="<?php echo $currentIndex; ?>&addfeature_value&token=<?php echo $this->token; ?>">
    <img src="../img/admin/add.gif" border="0" /> <?php echo $this->l('Add feature value', __CLASS__); ?>
</a><br /><br />
<?php echo $this->l('Click on the feature name to view its values. Click again to hide them', __CLASS__); ?><br /><br />

<script type="text/javascript" src="../js/jquery/jquery.tablednd_0_5.js"></script>
<script type="text/javascript">
	var token = '<?php echo $this->token; ?>';
	var come_from = '<?php echo $this->table; ?>';
	var alternate = '<?php echo ($this->_orderWay == 'DESC' ? '1' : '0' ); ?>';
</script>
<script type="text/javascript" src="../js/admin-dnd.js"></script>

<?php echo $this->displayListHeader(); ?>
<input type="hidden" name="id_group" value="<?php echo intval(Tools::getValue("id_group")); ?>" />
<input type="hidden" name="groupid" value="0" />

<?php  if (!sizeof($this->_list)): ?> 
	<tr><td class="center" colspan="<?php echo sizeof($this->_list); ?>"><?php echo $this->l('No features found', __CLASS__); ?></td></tr>
<?php endif; ?>

<?php 
$irow = 0;
foreach ($this->_list AS $tr):
	$id = intval($tr['id_'.$this->table]); ?> 
	<tr<?php echo ($irow++ % 2 ? ' class="alt_row"' : ''); ?>>
		<td style="vertical-align: top; padding: 4px 0 4px 0" class="center">
            <input type="checkbox" name="<?php echo $this->table; ?>Box[]" value="<?php echo $id; ?>" class="noborder" />
        </td>
		<td style="width: 140px; vertical-align: top; padding: 4px 0 4px 0; cursor: pointer" onclick="$('#features_values_<?php echo $id; ?>').slideToggle();">
            <?php echo $tr['name']; ?>
        </td>
		<td style="vertical-align: top; padding: 4px 0 4px 0; width: 340px">
			<div 
                id="features_values_<?php echo $id; ?>" 
                style="display: <?php echo ($this->id_feature==$id && !Tools::getValue('upposition') && !Tools::getValue('downposition') ? "block":"none"); ?>">
			<table class="table" cellpadding="0" cellspacing="0">
				<tr>
					<th>
                        <input type="checkbox" name="checkme" class="noborder" onclick="checkDelBoxes(this.form, 'feature_value<?php echo $id; ?>Box[]', this.checked)" />
                    </th>
					<th width="100%"><?php echo $this->l('Value'); ?></th>
					<th><?php echo $this->l('Actions'); ?></th>
				</tr>
        <?php $features = FeatureValue::getFeatureValuesWithLang(intval(Configuration::get('PS_LANG_DEFAULT')), $id);
	      foreach ($features AS $feature): ?>
				<tr>
					<td class="center">
                        <input type="checkbox" name="feature_value<?php echo $id; ?>Box[]" value="<?php echo $feature['id_feature_value']; ?>" class="noborder" />
                    </td>
					<td><?php echo $feature['value']; ?></td>
					<td class="center">
						<a href="<?php echo $currentIndex; ?>&id_feature_value=<?php echo $feature['id_feature_value']; ?>&updatefeature_value&token=<?php echo $this->token; ?>">
                            <img src="../img/admin/edit.gif" border="0" alt="<?php echo $this->l('Edit'); ?>" title="<?php echo $this->l('Edit'); ?>" />
                        </a>&nbsp;
						<a href="<?php echo $currentIndex; ?>&id_feature_value=<?php echo $feature['id_feature_value']; ?>&deletefeature_value&token=<?php echo $this->token; ?>"
						  onclick="return confirm(\'<?php echo $this->l('Delete value', __CLASS__, true, false); ?> #<?php echo $feature['id_feature_value']; ?>?\');">
						      <img src="../img/admin/delete.gif" border="0" alt="<?php echo $this->l('Delete'); ?>" title="<?php echo $this->l('Delete'); ?>" />
                        </a>
					</td>
				</tr>
	<?php endforeach; ?>
	<?php if (!sizeof($features)): ?>
        <tr><td colspan="3" style="text-align:center"><?php echo $this->l('No values defined'); ?></td></tr>
    <?php endif; ?>
	          </table>
				<p>
					<a href="<?php echo $currentIndex; ?>&<?php echo $this->identifier; ?>=<?php echo $id; ?>&addfeature_value&token=<?php echo $this->token; ?>" class="button"><?php echo $this->l('Add new value', __CLASS__); ?></a> 
					<input type="Submit" class="button" name="submitDelfeature_value" value="<?php echo $this->l('Delete selection'); ?>"
					   onclick="changeFormParam(this.form, '?tab=AdminExFeatures', <?php echo $id; ?>); return confirm('<?php echo $this->l('Delete selected items?', __CLASS__, true, false); ?>');" />
				</p>
			</div>
			</td>
    		<td style="width: 40px; vertical-align: top; padding: 4px 0 4px 0; cursor: pointer" onclick="$('#features_values_<?php echo $id; ?>').slideToggle();">
    			<?php $tr['group_name']; ?>
    		</td>
    	    <td class="pointer dragHandle center" id="td_<?php echo $tr["id_feature"]; ?>">
    	<?php if ($irow<count($this->_list)): ?> 
    		<a href="<?php echo $currentIndex; ?>&id_feature=<?php echo $tr["id_feature"]; ?>&downposition=<?php echo $tr["f_position"]; ?>&id_group=<?php echo intval(Tools::getValue('id_group')); ?>&token=<?php echo $this->token; ?>">
                <img title="Down" alt="Down" src="../img/admin/down.gif" />
    		</a>
        <?php endif; ?>
      
    	<?php if ($irow>1): ?>
    		<a href="<?php echo $currentIndex; ?>&id_feature=<?php echo $tr["id_feature"]; ?>&upposition=<?php echo $tr["f_position"]; ?>&id_group=<?php echo intval(Tools::getValue('id_group')); ?>&token=<?php echo $this->token; ?>" style="">
                <img title="Up" alt="Up" src="../img/admin/up.gif" />
    		</a>
        <?php endif; ?>
    	   </td>
        	<td style="vertical-align: top; padding: 4px 0 4px 0" class="center">
        		<a href="<?php echo $currentIndex; ?>&id_<?php echo $this->table; ?>=<?php echo $id; ?>&update<?php echo $this->table; ?>&token=<?php echo $this->token; ?>">
        		<img src="../img/admin/edit.gif" border="0" alt="<?php echo $this->l('Edit'); ?>" title="<?php echo $this->l('Edit'); ?>" /></a>&nbsp;
        		<a href="<?php echo $currentIndex; ?>&id_<?php echo $this->table; ?>=<?php echo $id; ?>&delete<?php echo $this->table.($this->id_group?'&id_group='.$this->id_group:''); ?>&token=<?php echo $this->token; ?>" 
                    onclick="return confirm('<?php echo $this->l('Delete item', __CLASS__, true, false); ?> #<?php echo $id; ?>?\');">
        		     <img src="../img/admin/delete.gif" border="0" alt="<?php echo $this->l('Delete'); ?>" title="<?php echo $this->l('Delete'); ?>" />
                </a>&nbsp;
        	</td>
	</tr>
<?php endforeach; ?>

<?php $this->displayListFooter(); ?>

<br /><br />
<a href="<?php echo $currentIndex; ?>&token=<?php echo $this->token; ?>">
    <img src="../img/admin/arrow2.gif" /> <?php echo $this->l('Back to groups list', __CLASS__); ?>
</a>
<br />
<?
	}
    
    /**
     * Display form for edit list of features of particular group
     */      
    public function displayForm()
	{
		global $currentIndex, $cookie;

		$defaultLanguage = intval(Configuration::get('PS_LANG_DEFAULT'));
		$languages = Language::getLanguages();
		$obj = $this->loadObject(true);
        if (!$obj->id_group)
            $obj->id_group = (isset($_GET["id_group"])?intval($_GET["id_group"]):NULL);
        ?>

<script type="text/javascript">
	id_language = Number(<?php echo $defaultLanguage; ?>);
</script>

<form action="<?php echo $currentIndex; ?>&token=<?php echo $this->token; ?>" method="post">
<?php echo ($obj->id ? '<input type="hidden" name="id_'.$this->table.'" value="'.$obj->id.'" />' : ''); ?>
	<fieldset class="width3">
        <legend>
            <img src="../img/t/AdminFeatures.gif" /><?php echo $this->l('Feature'); ?>
        </legend>
		<label><?php echo $this->l('Name:'); ?></label>
		<div class="margin-form">
            <?php foreach ($languages as $language): ?>
            	<div id="name_<?php echo $language['id_lang']; ?>" style="display: <?php echo ($language['id_lang'] == $defaultLanguage ? 'block' : 'none'); ?>; float: left;">
            		<input size="33" type="text" name="name_<?php echo $language['id_lang']; ?>" value="<?php echo htmlentities($this->getFieldValue($obj, 'name', intval($language['id_lang'])), ENT_COMPAT, 'UTF-8'); ?>" /><sup> *</sup>
            		<span class="hint" name="help_box"><?php echo $this->l('Invalid characters:') . "<>;=#{}"; ?><span class="hint-pointer">&nbsp;</span></span>
            	</div>
            <?php endforeach; ?>            
            <?php echo $this->displayFlags($languages, $defaultLanguage, 'name', 'name'); ?>
            <div style="clear: both;"></div>
		</div>
        <label><?php echo $this->l('Group: '); ?></label>
        <div class="margin-form">
            <select name="id_group">
                <option value=''>---</option>
              <?php echo $featureGroups = FeatureGroup::getFeatureGroups(intval($cookie->id_lang));
              foreach ($featureGroups as $fg): ?> 
                <option value='<?php echo $fg["id_group"]; ?>'
                <?php if (intval($fg["id_group"]) == intval($obj->id_group)): ?> 
                    selected="selected"
                <?php endif; ?>><?php echo $fg["name"]; ?></option>
              <?php endforeach; ?> 
			</select>
        </div>
        <label><?php echo $this->l('Position: '); ?></label>
        <div class="margin-form">
            <input size=5 maxlength=5 type="text" name="position" value="<?php echo htmlentities($this->getFieldValue($obj, 'position', NULL), ENT_COMPAT, 'UTF-8'); ?>" />
            <span class="hint" name="help_box"><?php echo $this->l('Only digital allowed!'); ?><span class="hint-pointer">&nbsp;</span></span>
        </div>
		<div class="margin-form">
			<input type="submit" value="<?php echo $this->l('   Save   '); ?>" name="submitAdd<?php echo $this->table; ?>" class="button" />
		</div>
		<div class="small"><sup>*</sup> <?php echo $this->l('Required field'); ?></div>
	</fieldset>
</form> 
<?php 
	}
    
    public function setupPosition()
    {
        $id_feature = intval(Tools::getValue('id_feature'));
        $id_group = intval(Tools::getValue('id_group'));
        if (!$id_feature || !$id_group)
            return false;
        $obj = $this->loadObject(true);
        $order = '';
        
        if (Tools::getValue('upposition'))
            $order = "up";
        elseif (Tools::getValue('downposition'))
            $order = "down"; 
        
        $obj->setupPosition($order, $id_group);
    }
    
    
    public function reorderPositions()
    {
        if ($id_group = intval(Tools::getValue('id_group'))) {
            $sql = "SELECT id_feature FROM "._DB_PREFIX_.$this->table." WHERE id_group = $id_group ORDER BY position";
            $result = Db::getInstance()->ExecuteS($sql);
            
            $position = 1;
            foreach ($result as $record) {
                $sql = "UPDATE "._DB_PREFIX_.$this->table." SET position= $position WHERE id_feature = ".$record["id_feature"]; 
                Db::getInstance()->Execute($sql);
                if (mysql_errno())
                    throw new Exception(mysql_error());
                ++$position;
            }
        }
        else 
            $this->adminFeaturesGroups->reorderPositions();
    }
 
}

?>