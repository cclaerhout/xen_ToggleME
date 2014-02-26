<?php
class Sedo_ToggleME_Helper_CustomLanguage
{
	/**
	 * Enable language categories check (and below options)
	 *
	 * Return: bolean
	 **/
	 	
	public static function isEnabled()
	{
		return XenForo_Application::get('options')->get('toggleME_lang_cat_enable');
	}
	
	/**
	 * Let false to only get the language subtag (recommended)
	 * Subtag registry: http://www.iana.org/assignments/language-subtag-registry/language-subtag-registry
	 *
	 * Return: bolean
	 **/
	 
	public static function useFullLanguageCode()
	{
		return XenForo_Application::get('options')->get('toggleME_lang_cat_fullcode');
	}

	/**
	 * By default, the only categories that will be closed will be the one of other languages (the ones you set up in the getLanguageConfig function)
	 * - If you want to close ALL categories that don't match the current user language codes, set the below setting to true.
	 * - If you want to close ALL categories except a few ones, set the below setting with an array containing Categories ids you want to remain open
	 *
	 * Setting this setting to true is NOT RECOMMENDED - language codes can't be fully trusted
	 *
	 * Return: false or array
	 **/
	 
	public static function closeAllCategories()
	{
		return false;
	}
	
	/**
	 * Your custom language categories configuration
	 * Array keys are the languageCode, values are the language Categories Identifiers to keep open
	 * The identifiers are:
	 * - The traditional ones of XenForo categories (check the XenForo Node Tree urls, the ids is there) 
	 * - The ones of the ToggleME "EXTRA categories" (see explanation in ToggleME options)
	 *
	 * Example: 	
	 *		return array(
	 * 			'fr' => array(1,2,3), 	//french
	 *			'en' => array(4,5,6), 	//english
	 *			'zh' => array(7,8,9), 	//chinese
	 *			'ru' => array(10,11,12) //russian
	 *		);
	 *
	 * Return: array	 	 
	 **/
	 
	public static function getLanguageConfig()
	{
		//Get the XenForo LangConfig option (will not work "Extra categories")
		$xenLangConfig = XenForo_Application::get('options')->get('toggleME_lang_cat_config');
		
		//this option should already be an array but better check it
		if(!is_array($xenLangConfig))
		{
			return array();
		}

		return $xenLangConfig;
	}
}
//Zend_Debug::dump($abc);