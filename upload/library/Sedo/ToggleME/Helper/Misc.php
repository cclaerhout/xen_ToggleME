<?php
class Sedo_ToggleME_Helper_Misc
{
	public static function getClientPreferedLanguage($subTagOnly = true, $getSortedList = false, $acceptedLanguages = false)
	{
		if(!isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			return '';
		}

		if (empty($acceptedLanguages))
		{
			$acceptedLanguages = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		}
	
		// regex borrowed from Gabriel Anderson on http://stackoverflow.com/questions/6038236/http-accept-language
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptedLanguages, $lang_parse);
		$langs = $lang_parse[1];
		$ranks = $lang_parse[4];
	
		// (recursive anonymous function)
		$getRank = function ($j)use(&$getRank, &$ranks)
		{
			while (isset($ranks[$j]))
				if (!$ranks[$j])
					return $getRank($j + 1);
				else
					return $ranks[$j];
		};
	
		// (create an associative array 'language' => 'preference')
		$lang2pref = array();
		for($i=0; $i<count($langs); $i++)
		{
			$lang2pref[$langs[$i]] = (float) $getRank($i);
		}
	
		// (comparison function for uksort)
		$cmpLangs = function ($a, $b) use ($lang2pref) 
		{
			if ($lang2pref[$a] > $lang2pref[$b])
				return -1;
			elseif ($lang2pref[$a] < $lang2pref[$b])
				return 1;
			elseif (strlen($a) > strlen($b))
				return -1;
			elseif (strlen($a) < strlen($b))
				return 1;
			else
				return 0;
		};
	
		// sort the languages by prefered language and by the most specific region
		uksort($lang2pref, $cmpLangs);
	
	
		if ($getSortedList)
		{
			if($subTagOnly)
				return self::getOnlyLangSubStag($lang2pref);
			else
				return $lang2pref;
		}
	
		// return the first value's key
		reset($lang2pref);
		$lang2pref = key($lang2pref);
		
		if($subTagOnly)
			return self::getOnlyLangSubStag($lang2pref);
		else
			return $lang2pref;
	}
	
	protected static function getOnlyLangSubStag($langCode)
	{
		//Subtag registry: http://www.iana.org/assignments/language-subtag-registry/language-subtag-registry
		$tiretPos = strpos($langCode, '-');
		
		if($tiretPos !== false)
		{
			return substr($langCode, 0, $tiretPos);
		}
		
		return $langCode;
	}
	
	public static function flattenArray(array $array)
	{
		$newArray = array();

		foreach($array as $key => $value)
		{
			if (is_array($value)) 
			{ 
				$newArray = array_merge($newArray, self::flattenArray($value)); 
			} 
			else
			{ 
			     $newArray[$key] = $value;
			}
		} 

		return $newArray; 
	}
}