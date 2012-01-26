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
            SELECT f.id_feature, fg.id_group, fgl.name  
            FROM
            '._DB_PREFIX_.'feature f
            JOIN '._DB_PREFIX_.'feature_group fg ON f.id_group=fg.id_group 
            JOIN '._DB_PREFIX_.'feature_group_lang fgl ON (fg.id_group = fgl.id_group AND fgl.id_lang='.intval($id_lang).')
            WHERE f.id_feature IN ('.implode(',', array_keys($fids)).')'
        );
        
        $result = array();
        foreach ($records as $rec) {
            if (!array_key_exists($rec["id_group"], $result))
                $result[$rec["id_group"]] = array("name" => $rec["name"], 'features' => array());
            foreach ($features as $feature) {
                if ($feature["id_feature"] == $rec["id_feature"])
                    $result[$rec["id_group"]]["features"][] = $feature;
            }    
        }
        return $result;
	}
 }