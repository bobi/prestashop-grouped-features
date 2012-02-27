<?php

/**
 * @author dreamsoft
 * @package grouped features
 * 
 * This class extends FeatureCore and override method for fetching features fo compare
 */  
 
 class Feature extends FeatureCore
 {
    public static function getFeaturesForComparison($list_ids_product, $id_lang)
	{
		$features = parent::getFeaturesForComparison($list_ids_product, $id_lang);
        $fids = array();
        foreach ($features as $feature) {
            $fids[$feature['id_feature']] = 1; 
        }
        
        $records = Db::getInstance()->ExecuteS('
            SELECT 
				f.id_feature, 
				fg.id_group, 
				fgl.name,
				f.position AS feature_position,
				fg.position AS group_position
            FROM
            '._DB_PREFIX_.'feature f
            JOIN '._DB_PREFIX_.'feature_group fg ON f.id_group=fg.id_group
            JOIN '._DB_PREFIX_.'feature_group_lang fgl ON (fg.id_group = fgl.id_group AND fgl.id_lang='.intval($id_lang).')
            WHERE f.id_feature IN ('.implode(',', array_keys($fids)).')'
        );
        
        if (!$records) {
			return null;
		}
		$result = array();
        foreach ($records as $rec) {
            if (!array_key_exists($rec["id_group"], $result))
                $result[$rec["id_group"]] = array(
					"name" => $rec["name"], 
					"position" => $rec["group_position"],
					'features' => array()
				);
            foreach ($features as $feature) {
                if ($feature["id_feature"] == $rec["id_feature"]) {
					$feature["position"] = $rec["feature_position"];
                    $result[$rec["id_group"]]["features"][$feature["id_feature"]] = $feature;
				}
            }    
        }
        
		uasort($result, array("Feature", 'sortFeaturesGroupsByPositionCallback'));
        foreach ($result as $group_id=>&$group_data) {
			uasort($group_data["features"], array("Feature", 'sortFeaturesByPositionCallback'));
		}
		
        return $result;
	}
	
	static public function sortFeaturesByPositionCallback($a, $b)
	{
		return intval($a["position"]) < intval($b["position"]) ? -1 : 1;
	}
	
	/**
	 * Use for sorting of features by positions as callback
	 * @TODO: Features can be sorted over sql 'ORDER BY'
	 * @see http://ua.php.net/manual/en/function.uasort.php
	 *  
	 * @param array $a
	 * @param array $b
	 */
	static public function sortFeaturesGroupsByPositionCallback($a, $b)
	{
		return intval($a["position"]) < intval($b["position"]) ? -1 : 1;
	}
	
 }
