<?php
class Sedo_ToggleME_Option_Factory
{
	public static function render_usergroups(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = XenForo_Model::create('Sedo_ToggleME_Model_GetUsergroups')->getUserGroupOptions($preparedOption['option_value']);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

	public static function render_styles(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = XenForo_Model::create('Sedo_ToggleME_Model_GetStyles')->getStylesOptions($preparedOption['option_value']);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

	public static function render_nodes(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = XenForo_Model::create('Sedo_ToggleME_Model_GetNodes')->getNodesOptions($preparedOption['option_value']);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

      	public static function render_lang_nodes(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
      	{
      		$optionValue = array();

		if(is_array($preparedOption['option_value']))
		{
	     		$i = 1;
      			foreach($preparedOption['option_value'] as $langCode => $categories)
      			{
      				$categoriesMultiArray = array();
      				
      				foreach($categories as $category)
      				{
      					array_push($categoriesMultiArray, array($category));
      				}
      				
      				
      				$optionValue[$i] = array(
      					'lang' => $langCode,
      					'nodes' => $categories
      				);
      				
	      			$i++;
      			}
      		}

      		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
      			'preparedOption' => $preparedOption,
      			'canEditOptionDefinition' => $canEdit
      		));

		$configs = XenForo_Model::create('Sedo_ToggleME_Model_GetNodes')->manageLanguageNodesOptions($optionValue);

      		return $view->createTemplateObject('option_toggleme_language_categories', array(
      			'fieldPrefix' => $fieldPrefix,
      			'listedFieldName' => $fieldPrefix . '_listed[]',
      			'preparedOption' => $preparedOption,
      			'formatParams' => $preparedOption['formatParams'],
      			'editLink' => $editLink,
      			'configs' => $configs,
      			'nodes' => XenForo_Model::create('Sedo_ToggleME_Model_GetNodes')->getNodesOptions(),
      			'nextCounter' => count($configs) + 1
      		));
      	}
      	
      	public static function verify_lang_nodes(array &$configs, XenForo_DataWriter $dw, $fieldName)
      	{
		$data = array();
		
		foreach($configs as $key => $config)
		{
			if( empty($config['lang']) )
			{
				unset($configs[$key]);
				continue;
			}
			
			if(!preg_match('#^[a-z-]+$#i', $config['lang']))
			{
				unset($configs[$key]);
				continue;
			}
			
			if(!isset($config['nodes']))
			{
				$config['nodes'] = array();
			}
			
			$langCode = strtolower($config['lang']);
			$data[$langCode] = $config['nodes'];
		}

		$configs = $data;

		//Zend_Debug::dump($configs);break;
		return true;
      	}				
}
//Zend_Debug::dump($abc);