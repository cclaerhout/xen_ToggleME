<?php
// Last modified: version 3.0.0 Beta 2
class Sedo_ToggleME_Listener
{
	protected static $_zendMethod = false; // for test purpose - do not change this.
	
	public static function template_create(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch($templateName)
		{
			case "PAGE_CONTAINER":
				$visitorStyle = (isset($params['visitorStyle'])) ? $params['visitorStyle'] : false;
			
				$config = array(
					'state' => (self::forcePostbitExtraInfoDisplay()) ? 1 : (XenForo_Application::get('options')->get('toggleME_Usergroups_Postbit_State') == 'opened') ? 1 : 0,
					'perms' => self::getPerms($visitorStyle),
					'pureCss' => self::isPureCssMode()
				);
		
				$params['toggleME'] = $config;
			break;
		}
	}

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName) 
		{
			case 'forum_list_nodes':
		
				//For categories using by addons or styles
				$style_session = $template->getParam('visitorStyle');
				$viewName = $template->getParam('viewName');				
				$perms = self::getPerms($style_session);
				$options = XenForo_Application::get('options');
				$isAdmin = self::isAdmin();
				$pureCssMode = self::isPureCssMode();
			
				if(empty($perms['toggle_forumhome_usr']) || !$options->toggleME_selected_areas['node_categories'])
				{
					break;
				}
				
				/*Settings*/
				$closed_xenCats = $options->toggleME_DefaultOff_XenCat;
				$closed_extraCats = array_map('trim', explode(',', $options->toggleME_DefaultOff_ExtraCat));

				/*Custom Language*/
				$langCheck = Sedo_ToggleME_Helper_CustomLanguage::isEnabled();
				if($langCheck)
				{
					$userBrowserLanguage = self::getClientPreferedLanguage();
					$languageCategories = Sedo_ToggleME_Helper_CustomLanguage::getLanguageConfig();

					if(!isset($languageCategories[$userBrowserLanguage]))
					{
						$langCheck = !$options->toggleME_lang_cat_fallback_open;
						$languageCategoriesToKeepOpen = array();
					}
					else
					{
						$languageCategoriesToKeepOpen = $languageCategories[$userBrowserLanguage];
						unset($languageCategories[$userBrowserLanguage]);
					}

					$languageCategoriesToClose = array();

					foreach($languageCategories as $tempCats)
					{
						$languageCategoriesToClose = array_merge($languageCategoriesToClose, $tempCats);
					}
						
					$languageCategoriesToClose = array_diff($languageCategoriesToClose, $languageCategoriesToKeepOpen); 
						
					$langCloseAllCategories = Sedo_ToggleME_Helper_CustomLanguage::closeAllCategories();

					if(is_array($langCloseAllCategories))
					{
						if(!empty($langCloseAllCategories))
						{
							$languageCategoriesToKeepOpen = array_merge($languageCategoriesToKeepOpen, $langCloseAllCategories);
							$langCloseAllCategories = true;
						}
						else
						{
							$langCloseAllCategories = false;							
						}
					}
				}

				/*Dom management*/
				$zendMethod = self::$_zendMethod;
				
				if(!$zendMethod)
				{
					$doc = new DOMDocument();
					libxml_use_internal_errors(true);
					$doc->loadHTML('<?xml encoding="utf-8"?>' . "<wip>{$contents}</wip>");
					self::_fixNpTags($doc);					
					libxml_clear_errors();
					$doc->encoding = 'utf-8';

					$finder = new DomXPath($doc);
					$categoryStripNodes = $finder->query("//*[contains(@class, 'categoryStrip')]");
				}
				else
				{

					$readyContent = mb_convert_encoding($contents, 'HTML-ENTITIES', 'UTF-8');
					$dom = new Zend_Dom_Query("<wip>{$readyContent}</wip>", 'utf-8');
					$categoryStripNodes = $dom->query('.categoryStrip');
					$doc = $categoryStripNodes->getDocument();
				}
				
				$doc->removeChild($doc->firstChild); //remove html tag

				if(!$zendMethod)
				{
					$doc->removeChild($doc->firstChild); //remove xml fix
				}
				
				$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild); //make wip tag content as first child

				foreach($categoryStripNodes as $categoryStripNode)
				{
					/*Try to cach the root node*/
					$rootNode = null;
					$maxDepth = 20;

					for($parentNode = $categoryStripNode->parentNode; ; $parentNode = $parentNode->parentNode)
					{
						$maxDepth--;
						if(in_array($parentNode->parentNode->tagName, array('wip', 'ol')))
						{
							$rootNode = $parentNode;
							break;
						}
						
						if($maxDepth == 0)
						{
							break;
						}
					}
				
					if($rootNode == null)
					{
						continue;
					}
					
					/*Settings*/
					$rootId = $rootNode->getAttribute('id');
					$rootClass = $rootNode->getAttribute('class');

					$hasChildren = (strpos($rootClass, 'groupNoChildren') === false);
					list($nodeId, $isXenId) = self::_getNodeId($rootClass, $rootId, $viewName);
					$idName = self::_uniqNodeId($nodeId);

					$classTarget = ($hasChildren) ? 'toggle_me tglWchild' : 'toggle_me tglNOchild';
					
					if($pureCssMode)
					{
						$classTarget .= ' pcss';
					}
					
					if($options->toggleME_Categories_CloseClass_Off)
					{
						$classTarget .= ' tglDnt';
					}
					
					if($isXenId)
					{
						$idName = "_node_{$idName}";
						
						if($closed_xenCats && in_array($nodeId, $closed_xenCats))
						{
							$classTarget .= ' tglWOFF';
						}

						if($langCheck && 
							(
								in_array($nodeId, $languageCategoriesToClose) ||
								($langCloseAllCategories && !in_array($nodeId, $languageCategoriesToKeepOpen))
							)
						)
						{
							$classTarget .= ' tglWOFF';
						}
					}
					else
					{
						if($closed_extraCats && in_array($idName, $closed_extraCats))
						{
							$classTarget .= ' tglWOFF';
						}
						
						if($langCheck && 
							(
								in_array($idName, $languageCategoriesToClose) ||
								($langCloseAllCategories && !in_array($idName, $languageCategoriesToKeepOpen))
							)
						)
						{
							$classTarget .= ' tglWOFF';
						}						
					}

					/*New node creation*/
					$newNode =  $categoryStripNode->ownerDocument->createElement('div');
					$newNode->setAttribute('id', $idName);
					$newNode->setAttribute('class', $classTarget);					

					if($pureCssMode)
					{
						$pureCssSpanNode = $categoryStripNode->ownerDocument->createElement('span');
						$newNode->appendChild($pureCssSpanNode); 
					}

					/*New node insertion*/
					if($hasChildren)
					{
						$categoryStripNode->insertBefore($newNode, $categoryStripNode->firstChild);
					}
					else
					{
						$categoryStripNode->appendChild($newNode);
					}
					
					if($isAdmin && $options->toggleME_debug_displayCategoryId)
					{
						$textInfo = "ID: {$idName} ";
						$textInfo .= ($isXenId) ? "(Xen Node)" : "(Manual node)";
					
						$infoNode =  $categoryStripNode->ownerDocument->createElement('div');
						$infoNodeText = $categoryStripNode->ownerDocument->createTextNode($textInfo);
						$infoNode->appendChild($infoNodeText); 
						$infoNode->setAttribute('class', "debug_tglm_info");
						$categoryStripNode->insertBefore($infoNode, $categoryStripNode->firstChild);
					}
				}

				$html = $doc->saveHTML($dom->documentElement);
				
				/*Get rid of the body tag: too difficult to do it with the dom...*/
				$html = preg_replace('#^<wip>(.*)</wip>$#si', '$1', $html);
				//$html = substr($html, 5, -7);

				$contents = $html;
			break;
				
			case 'message_user_info_text':		 
				//For postbit area
				$style_session = $template->getParam('visitorStyle');
				$perms = self::getPerms($style_session);
				$options = XenForo_Application::get('options');
				$position = $options->toggleME_Usergroups_Postbit_Position;
				$pureCssMode = self::isPureCssMode();
				
				if(empty($perms['toggle_postbit_usr']) || !$options->toggleME_selected_areas['postbit_extra'])
				{
					break;
				}

				$search = '#<h3 class="userText">#i';
				
				if($position == 'abvextra')
				{
					$search = '#<a[^>]+?class="username"[^>]+?>.*?</a>#i';
				}

				if($pureCssMode)
				{
					$replace = "$0<div class='tglPosbit pos_$position pcss'><span></span></div>";
				}
				else
				{
					$replace = "$0<div class='tglPosbit pos_$position'></div>";				
				}
				
				$contents = preg_replace($search, $replace, $contents);	
				break;				

			case 'page_container_sidebar':	
				//For sidebar blocks
				$style_session = $template->getParam('visitorStyle');
				$perms = self::getPerms($style_session);
				$options = XenForo_Application::get('options');
				$isAdmin = self::isAdmin();
				$debugOn = $options->toggleME_debug_displayWidgetId;

				if(empty($perms['toggle_widgets_usr']) || !$options->toggleME_selected_areas['widgets'])
				{
					break;
				}

				$excludedWidgetIds = array_map('trim', explode(',', $options->toggleME_Widgets_Excluded));
				$disabledWidgetIds = array_map('trim', explode(',', $options->toggleME_Widgets_Disabled));
				$pureCssMode = self::isPureCssMode();
				$widgetFrameworkEnabled = (strpos($contents, 'WidgetFramework') !== false);				

				//Dom management
				$zendMethod = self::$_zendMethod;
				
				if(!$zendMethod)
				{
					$doc = new DOMDocument();
					libxml_use_internal_errors(true);
					$doc->loadHTML('<?xml encoding="utf-8"?>' . "<wip>{$contents}</wip>");
					self::_fixNpTags($doc);
					libxml_clear_errors();
					$doc->encoding = 'utf-8';

					$finder = new DomXPath($doc);
					$widgetNodes = $finder->query("//wip/div");
				}
				else
				{
					$readyContent = htmlspecialchars_decode(utf8_decode(htmlentities($contents, ENT_COMPAT, 'UTF-8')));
					$dom = new Zend_Dom_Query("<wip>{$readyContent}</wip>");
					$widgetNodes = $dom->query('wip > div');
					$doc = $widgetNodes->getDocument();			
				}
				
				$doc->removeChild($doc->firstChild); //remove html tag

				if(!$zendMethod)
				{
					$doc->removeChild($doc->firstChild); //remove xml fix
				}
				
				$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild); //make wip tag content as first child
			
				foreach($widgetNodes as $widgetNode)
				{
					if(empty($widgetNode->attributes))
					{
						continue;
					}

					/*ROOT NODE*/
						$rootId = $widgetNode->getAttribute('id');
						$rootClass = $widgetNode->getAttribute('class');

						if(self::hasNoToggleClass($rootClass))
						{
							continue;
						}

						/*WFMR + SECTION*/
						$isWfmr = null;
						$sectionNode = null;
					
						if($widgetFrameworkEnabled)
						{
							list($isWfmr, $wfmrId, $wfmrClass, $wfmrChild) = self::isWfmrWidget($rootId, $rootClass);
						}
					
						$sectionClass = self::isSectionClass($rootClass);
						if($sectionClass)
						{
							$sectionNode = $widgetNode;
						}

					/*CHILD NODE*/			
						$childId = null;
						$childClass = null;
		
						if($widgetNode->hasChildnodes() && !$isWfmr)
						{
							//$childNodes = $widgetNode->getElementsByTagName('div');
							$childNode = $widgetNode->childNodes->item(1);
	
							if($childNode->tagName == 'div')
							{
								$childId = $childNode->getAttribute('id');
								$childClass = $childNode->getAttribute('class');

								if(self::hasNoToggleClass($childClass))
								{
									continue;
								}
	
								/*WFMR + SECTION*/
								if($widgetFrameworkEnabled)
								{
									list($isWfmr, $wfmrId, $wfmrClass, $wfmrChild) = self::isWfmrWidget($childId, $childClass, true);
								}
	
								if(!$sectionNode && !$isWfmr)
								{
									$sectionClass = self::isSectionClass($childClass);
									if($sectionClass)
									{
										$sectionNode = $childNode;
									}
								}
							}
						}

					/*Manage node settings*/
					$node = null;

					if($isWfmr)
					{
						$idNum = "$wfmrId";
						$idName = $wfmrClass;
						$className = $wfmrClass;

						if(!$wfmrChild)
						{
							$h3 = $widgetNode->getElementsByTagName('h3')->item(0);
							$node = ($h3) ? $h3->parentNode : null;
						}
						else
						{
							$node = $childNode;
						}
					}
					elseif($sectionNode)
					{
						$h3 = $sectionNode->getElementsByTagName('h3')->item(0);
						$node = ($h3) ? $h3->parentNode : null;
						$idName = $sectionClass;
						$className = $sectionClass;
					}

					if(!$node)
					{
						continue;
					}

					$idName = self::_uniqWidgetId($idName);//To do: viewName for empty
					$className = strtolower($className);

					if(in_array($idName, $excludedWidgetIds))
					{
						continue;
					}

					$newNode =  $node->ownerDocument->createElement('div');
					$newNode->setAttribute('id', $idName);

					$classToAdd = "tglSidebar {$className}";
					
					if(in_array($idName, $disabledWidgetIds))
					{
						$classToAdd .= " tglSbOFF";
					}

					if($pureCssMode)
					{
						$classToAdd .= ' pcss';
						$pureCssSpanNode = $node->ownerDocument->createElement('span');
						$newNode->appendChild($pureCssSpanNode); 
					}
					
					$newNode->setAttribute('class', $classToAdd);
					$node->insertBefore($newNode, $node->firstChild);
					
					if($isAdmin && $debugOn)
					{
						$infoNode =  $node->ownerDocument->createElement('div');
						$infoNodeText = $node->ownerDocument->createTextNode("ID: {$idName}");
						$infoNode->appendChild($infoNodeText); 
						$infoNode->setAttribute('class', "debug_tglm_info");
						$node->insertBefore($infoNode, $node->parent);
					}
				}
				
				$html = $doc->saveHTML();

				/*Get rid of the body tag: too difficult to do it with the dom...*/
				$html = preg_replace('#^<wip>(.*)</wip>$#si', '$1', $html);
				//$html = substr($html, 5, -7);

				$contents = $html;

				/***
				 *	http://fr2.php.net/manual/en/domnode.c14n.php
				 *	http://stackoverflow.com/questions/5914643/writing-changes-back-to-a-zend-dom-query-object
				 ***/
			break;
		}			
	}

	public static function isWfmrWidget($id, $class, $isChild = false)
	{
		$falseFallback = array(false, false, false, $isChild);

		if(empty($id) || empty($class))
		{
			return $falseFallback;
		}
		
		$wdgPos = strpos($id, 'widget-');
		
		if($wdgPos === false || $wdgPos != 0)
		{
			return $falseFallback;
		}

		$id = substr($id, 7);

		if(preg_match('#.*WidgetRenderer_(\w+).*#i', $class, $match))
		{
			$className = "wf_$match[1]";
		}
		else
		{
			$className = "wf_$id";
		}
		
		return array(true, $id, $className, $isChild);
	}

	public static function isSectionClass($class)
	{
		if(preg_match('#.*\bsection\b[ ]?([\S]*).*#i', $class, $match))
		{
			$remainingClass = trim($match[1]);
			return "nwf_{$remainingClass}";
		}
		
		return false;
	}

	public static function hasNoToggleClass($string)
	{
		$class = explode(' ', $string);

		return in_array('noToggle', $class);
	}
	
	protected static $_widgetIds = array();
	protected static function _uniqWidgetId($widgetId)
	{
		$widgetId = strtolower($widgetId);
		$modifyId = false;
		
		if( isset(self::$_widgetIds[$widgetId]) )
		{
			self::$_widgetIds[$widgetId] = self::$_widgetIds[$widgetId]+1;
			$modifyId = true;
		}
		else
		{
			self::$_widgetIds[$widgetId] = 0;
		}

		if($modifyId)
		{
			$widgetId = "{$widgetId}-n-" . self::$_widgetIds[$widgetId];
		}
		
		return $widgetId;
	}

	protected static function _getNodeId($nodeClass, $rootId, $viewName = null)
	{
		if(preg_match('#node_(?P<id>\d{1,9})#', $nodeClass, $match))
		{
			return array($match['id'], true);
		}
		
		if($rootId)
		{
			return  array($rootId, false);
		}

		$fallbackName = str_replace('XenForo_ViewPublic_', '', $viewName);

		return array($fallbackName, false);
	}

	protected static $_nodeIds = array();
	protected static function _uniqNodeId($nodeId)
	{
		$nodeId = strtolower($nodeId);
		$modifyId = false;
		
		if( isset(self::$_nodeIds[$nodeId]) )
		{
			self::$_nodeIds[$nodeId] = self::$_nodeIds[$nodeId]+1;
			$modifyId = true;
		}
		else
		{
			self::$_nodeIds[$nodeId] = 0;
		}

		if($modifyId)
		{
			$nodeId = "{$nodeId}-n-" . self::$_nodeIds[$nodeId];
		}
		
		return $nodeId;
	}
	
	protected static $_permsCache;
	public static function getPerms($style_session)
	{
		if(self::$_permsCache)
		{
			return self::$_permsCache; 
		}
		
		$options = XenForo_Application::get('options');

		//Init perms
		$perms = array(
			'toggle_forumhome_usr' => false,
			'toggle_postbit_usr' => false,
			'toggle_widgets_usr' => false,
			'toggle_wrappednoded_usr' => false,
			'quickCheck' => false,
			'visitorUserGroupIds' => false
		);

		if (empty($options->toggleME_enabled))
	  	{
	  		return $perms;
	  	}

		//Style permissions: don't use the styleid extrated from "XenForo_Visitor::getInstance". Reason: the styleid of the Unregistered user is "0".
		if($options->toggleME_styles)
		{
			$toggle_styles = in_array($style_session['style_id'], $options->toggleME_styles);			
		}

		if (!$toggle_styles)
		{
	  		return $perms;
		}

		//Users permissions (not empty if access granted)
		$visitor = XenForo_Visitor::getInstance();
		$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));
		$perms['visitorUserGroupIds'] = $visitorUserGroupIds;

		if($options->toggleME_selected_areas['node_categories'])
		{
			$chkusr = array_intersect($visitorUserGroupIds, $options->toggleME_Usergroups_Forumhome);
			$perms['toggle_forumhome_usr'] = (empty($chkusr)) ? false : true;
			$perms['quickCheck'] = true;
		}
		
		if($options->toggleME_selected_areas['postbit_extra'])
		{
			$chkusr = array_intersect($visitorUserGroupIds, $options->toggleME_Usergroups_Postbit);
			$perms['toggle_postbit_usr'] = (empty($chkusr)) ? false : true;
			$perms['quickCheck'] = true;
		}
		
		if($options->toggleME_selected_areas['widgets'])
		{
			$chkusr = array_intersect($visitorUserGroupIds, $options->toggleME_Usergroups_Widgets);
			$perms['toggle_widgets_usr'] = (empty($chkusr)) ? false : true;
			$perms['quickCheck'] = true;
		}		
		
		if($options->toggleME_selected_areas['node_subforums'])
		{
			/*2014-08-11: done in templates*/
			//$chkusr = array_intersect($visitorUserGroupIds, $options->toggleME_Usergroups_Wrapped_Nodes);
			//$perms['toggle_wrappednoded_usr'] = (empty($chkusr)) ? false : true;
			$perms['quickCheck'] = true;		
		}
		
		self::$_permsCache = $perms;
		return $perms;
	}
	

	protected static $_postbitForcedDisplay;
	public static function forcePostbitExtraInfoDisplay($perms = false)
	{
		if(self::$_postbitForcedDisplay)
		{
			return self::$_postbitForcedDisplay;
		}

		if(!empty($perms['visitorUserGroupIds']))
		{
			$visitorUserGroupIds = $perms['visitorUserGroupIds'];
		}
		else
		{
			$visitor = XenForo_Visitor::getInstance();
			$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));
		}
		
		$validUserGroups =  XenForo_Application::get('options')->get('toggleME_Usergroups_Postbit_ForceOpenState');
		
		if(!$validUserGroups || !is_array($validUserGroups) || !is_array($visitorUserGroupIds))
		{
			return false;
		}
		
		self::$_postbitForcedDisplay = $postbitForcedDisplay = (array_intersect($visitorUserGroupIds, $validUserGroups)) ? true : false;
		return $postbitForcedDisplay;
	}
	

	protected static $_clientPreferedLanguage = 'init';

	public static function getClientPreferedLanguage()
	{
		if(self::$_clientPreferedLanguage == 'init')
		{
			$clientPreferedLanguage = strtolower(Sedo_ToggleME_Helper_Misc::getClientPreferedLanguage(!Sedo_ToggleME_Helper_CustomLanguage::useFullLanguageCode()));
			self::$_clientPreferedLanguage = $clientPreferedLanguage;
		}
		else
		{
			$clientPreferedLanguage = self::$_clientPreferedLanguage;
		}

		return $clientPreferedLanguage;
	}

	protected static $_isAdmin;	
	public static function isAdmin()
	{
		if(!self::$_isAdmin)
		{
			$visitor = XenForo_Visitor::getInstance();
			self::$_isAdmin = $visitor->is_admin;
		}

		return self::$_isAdmin;
	}
	

	protected static $_isPureCssMode;
	public static function isPureCssMode()
	{
		if(!self::$_isPureCssMode)
		{
			self::$_isPureCssMode = XenForo_Template_Helper_Core::styleProperty('toggleMe_pureCssMode');
		}

		return self::$_isPureCssMode;
	}
	
	protected static function _fixNpTags(&$doc)
	{
		$tagStack = array();
		$fbTag = 'fb:like';
		$fbTagFixed = false;
		
		/* Automatic method*/
		foreach (libxml_get_errors() as $error)
		{
			if(!$error->message)
			{
				continue;
			}

			$message = $error->message;

			if(strpos($message, 'Tag') === 0 && strpos($message, 'invalid') === (strlen($message) - 8))
			{
				$tag = substr($message, 4, -9);

				if(strpos($tag, ':') === false || isset($tagStack[$tag]))
				{
					continue;
				}

				$tagStack[$tag] = true;
				$tagInfo = explode(':', $tag);
				$tagSuffix = $tagInfo[1];

				$targetNodes = $doc->getElementsByTagName($tagSuffix);

				if($targetNodes->length == 0)
				{
					continue;
				}

				foreach($targetNodes as $node)
				{
					$patchedNode = $doc->createElement($tag, $node->nodeValue);
					$node->parentNode->replaceChild($patchedNode, $node);
				}
				
				if($tag == $fbTag)
				{
					$fbTagFixed = true;
				}				
			}
			else
			{
				continue;
			}
		}
		
		/* Manual method*/
		if(!$fbTagFixed)
		{
      			$targetNodes = $doc->getElementsByTagName('like');

      			if($targetNodes->length == 0)
      			{
      				return;
      			}

      			foreach($targetNodes as $node)
      			{
      				$patchedNode = $doc->createElement($fbTag, $node->nodeValue);
      				$node->parentNode->replaceChild($patchedNode, $node);
      			}			
		}
	}
	
}
/*
	DEV TOOLS:
	$mergedParams = array_merge($template->getParams(), $hookParams);
	Zend_Debug::dump($mergedParams["nodeList"]["nodesGrouped"][0]);
	if ($hookName == $hookName) { $contents .= '<span style="diplay:inline;color:red;">' . $hookName . '</span><br />'; }
	if ($templateName == $templateName) { $content .= '<span style="diplay:inline;color:red;">' . $templateName . '</span><br />'; }
	in templates: {xen:helper dump, $category.node_id}
*/
//Zend_Debug::dump($contents);