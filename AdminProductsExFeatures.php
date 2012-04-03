<?php

/**
 * @author Andrew
 * @copyright 2010
 */

require_once PS_ADMIN_DIR.'/tabs/AdminProducts.php';

class AdminProductsExFeatures extends AdminProducts 
{
    private $_adminCatalogInstance;
    
    public function __construct($catalogInstance)
    {
		global $cookie;
		$this->_adminCatalogInstance = $catalogInstance;
		parent::__construct();
		$this->token = Tools::getAdminToken("AdminCatalogExFeatures".(int)$this->id.(int)$cookie->id_employee);
	}
	
    public function isAllCustom($values)
    {
        if (!is_array($values))
            return true;
        foreach ($values as $item)
            if (!intval($item["custom"]))
                return false;
        return true;
    }
    
    public function isCustomForMe($product_features, $id_feature_value)
    {
        foreach ($product_features as $pvs)
            foreach ($pvs as $item)
                if ($id_feature_value==$item)
                    return true;
        return false;
    }
    
    public function displayFormFeatures($obj, $languages, $defaultLanguage)
	{
		global $cookie, $currentIndex;
        
		//duplicate AdminTab::displayForm for display flag near traanslateable input fields 
        $allowEmployeeFormLang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        echo '
			<script type="text/javascript">
				$(document).ready(function() {
					id_language = '.$defaultLanguage.';
					languages = new Array();';
			foreach ($languages AS $k => $language)
				echo '
					languages['.$k.'] = {
						id_lang: '.(int)$language['id_lang'].',
						iso_code: \''.$language['iso_code'].'\',
						name: \''.htmlentities($language['name'], ENT_COMPAT, 'UTF-8').'\'
					};';
			echo '
					displayFlags(languages, id_language, '.$allowEmployeeFormLang.');
				});
			</script>';

         
        if ($obj->id) {
//  		$feature = Feature::getFeatures(intval($cookie->id_lang));
            include_once 'ExFeature.php';
            $feature =  ExFeature::getFeatures($defaultLanguage);
            
			$ctab = '';
			foreach ($feature AS $group)
                foreach ($group["features"] as $f)
          			$ctab .= 'ccustom_'.$f['id'].'¤';
    		$ctab = rtrim($ctab, '¤');

            ?>
			<table cellpadding="5">
				<tr>
					<td colspan="2"><b><?php echo $this->l('Assign features to this product'); ?></b></td>
				</tr>
			</table>
			<hr style="width:730px;" />
            <a href="#" id="all_close" class="module_toggle_all"><?php echo $this->l('Collapse all'); ?></a> 
            <a id="all_open" class="module_toggle_all" href="#"><?php echo $this->l('Expand all'); ?></a><br /> 
			
            <?php // Header 
			$nb_feature = Feature::nbFeatures(intval($cookie->id_lang));
            ?>
			<table border="0" cellpadding="0" cellspacing="0" class="table" style="width:906px;">
				<tr>
					<th style="width:30%; padding-left:10px;"><?php echo $this->l('Features'); ?></td>
					<th style="width:25%"><?php echo $this->l('Value'); ?></td>
					<th style="width:45%"><?php echo $this->l('Customized'); ?></td>
				</tr>
            <?php
			if (!$nb_feature) : ?>
					<tr><td colspan="3" style="text-align:center;">'.$this->l('No features defined').'</td></tr>
            <?php endif; ?>
			</table>
            
            <?php
			// Listing
			if ($nb_feature):
				$pfeatures = Product::getFeaturesStatic($obj->id);
                $product_features = array();
                foreach ($pfeatures as $pf) {
                    if (!array_key_exists($pf["id_feature"], $product_features))
                        $product_features[$pf["id_feature"]] = array(); 
                    $product_features[$pf["id_feature"]][] = $pf["id_feature_value"];
                }
                unset($pfeatures);
                
                $odd = 0;
                ?>
				
                <?php // <table cellpadding="5" style="width:743px; margin-top:10px"> ?>
                
                <?php 
                //Main loop
                foreach ($feature as $gid=>$group): ?>
                    <div id="cgroup<?php echo $gid ?>" class="header_module">
				        <span class="nbr_module" style="width:100px;text-align:right; margin-right: 125px;"><?php echo count($group["features"]) ?> <?php echo $this->l('features'); ?></span>
	                    <a class="header_module_toggle" id="group_<?php echo $gid; ?>" href="modgo_search_filter" style="margin-left: 5px;">
                            <span style="padding-right:0.5em">
       					        <img class="header_module_img" id="search_filter_img" src="../img/admin/more.png" alt="" />
                            </span><?php echo $group["name"]; ?>
                        </a> 
				    </div>
                    <div id="group_<?php echo $gid; ?>_content" class="tab_module_content" style="display:none;border:solid 1px #CCC">
                        <div id="modgo_blocksearch">
                            <table style="width:100%" cellpadding="0" cellspacing="0">
                <?php 
				foreach ($group["features"] AS $tab_features)
				{
					$current_item = false;
					$custom = true;
					foreach ($obj->getFeatures() as $tab_products)
						if ($tab_products['id_feature'] == $tab_features['id_feature'])
							$current_item = $tab_products['id_feature_value'];

					$featureValues = FeatureValue::getFeatureValuesWithLang((int)$cookie->id_lang, (int)$tab_features['id_feature']);

					echo '
					<tr>
						<td style="padding-left: 10px; width: 30%">'.$tab_features['name'].'</td>
						<td style="width: 25%">';

					if (sizeof($featureValues))
					{
						echo '
							<select id="feature_'.$tab_features['id_feature'].'_value" name="feature_'.$tab_features['id_feature'].'_value"
								onchange="$(\'.custom_'.$tab_features['id_feature'].'_\').val(\'\');">
								<option value="0">---&nbsp;</option>';

						foreach ($featureValues AS $value)
						{
							if ($current_item == $value['id_feature_value'])
								$custom = false;
							echo '<option value="'.$value['id_feature_value'].'"'.(($current_item == $value['id_feature_value']) ? ' selected="selected"' : '').'>'.substr($value['value'], 0, 40).(Tools::strlen($value['value']) > 40 ? '...' : '').'&nbsp;</option>';
						}

						echo '</select>';
					}
					else
						echo '<input type="hidden" name="feature_'.$tab_features['id_feature'].'_value" value="0" /><span style="font-size: 10px; color: #666;">'.$this->l('N/A').' - <a href="index.php?tab=AdminFeatures&addfeature_value&id_feature='.(int)$tab_features['id_feature'].'&token='.Tools::getAdminToken('AdminFeatures'.(int)(Tab::getIdFromClassName('AdminFeatures')).(int)($cookie->id_employee)).'" style="color: #666; text-decoration: underline;">'.$this->l('Add pre-defined values first').'</a></span>';

					echo '
						</td>
						<td style="width:45%" class="translatable">';
					$tab_customs = ($custom ? FeatureValue::getFeatureValueLang($current_item) : array());
					foreach ($languages as $language)
						echo '
							<div class="lang_'.$language['id_lang'].'" style="display: '.($language['id_lang'] == $defaultLanguage ? 'block' : 'none').'; float: left; margin-top: 5px; margin-bottom: 5px;">
								<textarea class="custom_'.$tab_features['id_feature'].'_" name="custom_'.$tab_features['id_feature'].'_'.$language['id_lang'].'" cols="40" rows="1"
									onkeyup="$(\'#feature_'.$tab_features['id_feature'].'_value\').val(0);" >'.htmlentities(Tools::getValue('custom_'.$tab_features['id_feature'].'_'.$language['id_lang'], FeatureValue::selectLang($tab_customs, $language['id_lang'])), ENT_COMPAT, 'UTF-8').'</textarea>
							</div>';
                    
/*                    echo '<div style="
                                    float: left;
                                    margin-top: 10px;
                                    margin-left: 5px">'
                            .$this->displayFlags($languages, $defaultLanguage, $ctab, 'ccustom_'.$tab_features['id_feature'], true).
                            '</div>'; */
					echo '
						</td>
					</tr>';
				} ?>		                        

                            </table>
				        </div>
				    </div> 
                <?php endforeach; ?>
                <div id="updateFeatureAdminContainer" style="text-align: center; margin-top: 10px;">    
                    <input type="submit" name="submitProductFeature" id="submitProductFeature" value="<?php echo $this->l('Update features'); ?>" class="button" />
                </div>
			<?php endif; ?>
            
            
			<hr style="width:730px;" />
			<div style="text-align:center;">
				<a href="index.php?tab=AdminExFeatures&addfeature&token=<?php echo Tools::getAdminToken('AdminExFeatures'.intval(Tab::getIdFromClassName('AdminExFeatures')).intval($cookie->id_employee)); ?>" onclick="return confirm('<?php echo $this->l('Are you sure you want to delete entered product information?', __CLASS__, true, false); ?>');">
                    <img src="../img/admin/add.gif" alt="new_features" title="<?php echo $this->l('Create new features'); ?>" />&nbsp;<?php echo $this->l('Create new features'); ?>
                </a>
			</div>
		<?php }
		else
			echo '<b>'.$this->l('You must save this product before adding features').'.</b>';
	}
    
    /** 
     * Body of this method almost fully duplicated the parent::displayForm()
     */  
    public function displayForm($token = NULL)
    {   
		global $currentIndex;
        parent::displayForm();
        if (!($obj = $this->loadObject(true)))
			return; 
        ?>
        
        <script type="text/javascript">
            function loadTab(id) { 
                <?php if ($obj->id): ?>
                if (toload[id]) {
		          toload[id] = false;
		              $.post(
						"<?php echo dirname($currentIndex); ?>/ajax_ex_features.php", 
                        {
                            ajaxProductTab: id, 
                            id_product: <?php echo $obj->id; ?>,
							token: '<?php echo Tools::getValue('token'); ?>',
							id_category: <?php echo (int)(Tools::getValue('id_category')); ?>
                        },
						function(rep) {
						  $("#step" + id).html(rep);var languages = new Array();
						  if (id == 3)
						      populate_attrs();
						  if (id == 7) {
						      $('#addAttachment').click(function() {
								    return !$('#selectAttachment1 option:selected').remove().appendTo('#selectAttachment2');
								});
                              $('#removeAttachment').click(function() {
								    return !$('#selectAttachment2 option:selected').remove().appendTo('#selectAttachment1');
								});
						      $('#product').submit(function() {
								$('#selectAttachment1 option').each(function(i) {
								    $(this).attr("selected", "selected");
								});
						      });
		                  }
                          
                          //functional for slide of feature groups
      		              $('.header_module_toggle, .module_toggle_all').unbind('click').click(function(){
                            var id = $(this).attr('id');
                            if (id == 'all_open')
                            $('.tab_module_content').each(function(){
                            	$(this).slideDown();
                            	$('#all_open').hide();
                            	$('#all_close').show();
                            	$('.header_module_img').each(function(){
                            		$(this).attr('src', '../img/admin/less.png');
                            	});
                            });
                            else if (id == 'all_close')
                            $('.tab_module_content').each(function(){
                            	$('#all_open').show();
                            	$('#all_close').hide();
                            	$(this).slideUp();
                            	$('.header_module_img').each(function(){
                            		$(this).attr('src', '../img/admin/more.png');
                            	});
                            });
                            else {
                            if ($('#'+id+'_content').css('display') == 'none')
                            	$('#'+id+'_img').attr('src', '../img/admin/less.png');
                            else
                            	$('#'+id+'_img').attr('src', '../img/admin/more.png');
                            
                            $('#'+$(this).attr('id')+'_content').slideToggle();
                            }
                            return false;
                            });
						}
			           )
			         }
                <?php endif; ?>
		      }
        </script>
        <?php
    }
    
    public function displayList($id_lang=NULL)
    {
        global $currentIndex;
        $tmp_currentIndex = $currentIndex;
        if ($id_category = intval(Tools::getValue('id_category')))
            $currentIndex .= '&id_category='.$id_category;
        parent::displayList($id_lang);
        $currentIndex = $tmp_currentIndex;
    }
    
    protected function l($string, $class = 'AdminTab', $addslashes = FALSE, $htmlentities = TRUE)
	{
		return $this->_adminCatalogInstance->exf_l($string, $class, $addslashes, $htmlentities);
	}
} 
    
?>
