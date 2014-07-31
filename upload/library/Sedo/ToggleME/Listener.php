<?php
// Last modified: version 3.0.0 WIP Early release
class Sedo_ToggleME_Listener
{
	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName) 
		{
			case 'page_container_js_body':
				$options = XenForo_Application::get('options');
				$easing = $options->toggleme_effect_easing;
				$duration = $options->toggleme_effect_duration;
				$state = ($options->toggleME_Usergroups_Postbit_State == 'opened') ? 1 : 0;
				
				if(self::forcePostbitExtraInfoDisplay())
				{
					$state = 1;
				}
		
				$search = '#(\s+)(_ignoredUsers:)#i';
				$replace = "$1toogleMeConfig:{ effect: \"$easing\", duration: $duration, postbit_state: $state },$1$2";
				
				$contents = preg_replace($search, $replace, $contents);
				break;
			case 'page_container_head':
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);

				if($perms['quickCheck'] !== true)
				{
					break;
				}

				$viewParams = array();

				$contents .= $template->create('toggleme_page_container_js', $viewParams);
				break;
			case 'forum_list_nodes':
				//For categories using by addons or styles
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);
				$options = XenForo_Application::get('options');
				
				if(empty($perms['toggle_forumhome_usr']) || !$options->toggleME_selected_areas['node_categories'])
				{
					break;
				}
				
				/***
				*	Let's create the foundation of the ID... based on the addition of each number of the crc of the full_url
				*	Why ? I don't know if the hook 'forum_list_nodes' is only displayed in the forumhome page. In the javascript
				*	file, I'm using 'index' function to count the ID of the category (that doesn't belong to XenForo, ie: Chatbox). 
				*	Without this part of code, if the first category W of page A is closed, then the first category Y will also be 
				*	closed on page B.
				***/
			
				$full_url = $template->getParam('requestPaths');
				$full_url = $full_url['fullBasePath'];
				$CRC_ID = array_sum(str_split(crc32($full_url)));
			
				//Check if the collapsed categories must use another class
				$tglOffClass = '';
				if(!empty($options->toggleME_Categories_CloseClass_Off))
				{
					$tglOffClass = ' tglDnt'; //tlg Don't!
				}
					
				//The regex backreference (?<!"></div>) is to avoid redundancy with the similar replacement inside the template_postrender function
				$search[] = '#(?<!"></div>)<div class="categoryText">#i';
				$replace[] = '<div id="_crc_' . $CRC_ID . '-" class="toggle_me tglWchild' . $tglOffClass. '"></div>$0';
				$search[] = '#<li class="(?:.+?)?groupNoChildren(?:.*?)?(node_[\d]+)(?:.+?)?">\n\s+?<div class="(?:.+?)?categoryStrip(?:.+?)?>#i';
				$replace[] = '$0<div data-id ="_$1" class="toggle_me tglNOchild' . $tglOffClass. '"></div>';
			
				$contents = preg_replace($search, $replace, $contents);
						
				//Let's now finalize the IDs of main categories adding to them their 'replacement order' number 
				self::resetCounter();
				$contents = preg_replace_callback('#_crc_\d{1,9}-#', array('Sedo_ToggleME_Listener', 'makeMeUniqRegex'), $contents);

				// Default Closed EXTRA Categories
				if ($options->toggleME_DefaultOff_ExtraCat)
				{
					$closed_cats = explode(',', $options->toggleME_DefaultOff_ExtraCat);

					if(Sedo_ToggleME_Helper_CustomLanguage::isEnabled())
					{
						$userBrowserLanguage = self::getClientPreferedLanguage();
						$languageCategories = Sedo_ToggleME_Helper_CustomLanguage::getLanguageConfig();
						$proceed = true;
	
						if(!isset($languageCategories[$userBrowserLanguage]))
						{
							$languageCategoriesToKeepOpen = array();
							$proceed = !$options->toggleME_lang_cat_fallback_open;
						}
						else
						{
							$languageCategoriesToKeepOpen = $languageCategories[$userBrowserLanguage];
							unset($languageCategories[$userBrowserLanguage]);
						}	

						if($proceed)
						{
							foreach($languageCategories as $tempCats)
							{
								$closed_cats = array_merge($closed_cats , $tempCats);
							}
						}
					}

					foreach ($closed_cats as $closed_cat)
					{
						$search = '<div id="' . $closed_cat . '" class="toggle_me';
						$replace = $search . ' tglWOFF';
						$contents = str_replace($search, $replace, $contents);
					}
				}
				break;
			case 'message_user_info_text':		 
				//For postbit area
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);
				$options = XenForo_Application::get('options');
				$position = $options->toggleME_Usergroups_Postbit_Position;
				
				if(empty($perms['toggle_postbit_usr']) || !$options->toggleME_selected_areas['postbit_extra'])
				{
					break;
				}

				$search = '#<h3 class="userText">#i';
				
				if($position == 'abvextra')
				{
					$search = '#<a[^>]+?class="username"[^>]+?>.*?</a>#i';
				}

				$replace = "$0<div class='tglPosbit pos_$position'></div>";
				
				$contents = preg_replace($search, $replace, $contents);	
				break;				


			case 'page_container_sidebar':	
				//For sidebar blocks
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);
				$options = XenForo_Application::get('options');	

				if(empty($perms['toggle_widgets_usr']) || !$options->toggleME_selected_areas['widgets'])
				{
					break;
				}

				$excludedWidgetIds = array_map('trim', explode(',', $options->toggleME_Widgets_Excluded));
				$disabledWidgetIds = array_map('trim', explode(',', $options->toggleME_Widgets_Disabled));

				$widgetFrameworkEnabled = (strpos($contents, 'WidgetFramework') !== false);
				$dom = new Zend_Dom_Query("<wip>{$contents}</wip>");

				$widgetNodes = $dom->query('wip > div');
				$doc = $widgetNodes->getDocument();
				$doc->removeChild($doc->firstChild);
				$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild);
			
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
					
					$newNode->setAttribute('class', $classToAdd);
					$node->insertBefore($newNode, $node->firstChild);
				}
				
				$html = $widgetNodes->getDocument()->saveHTML();
				
				/*Get rid of the body tag: too difficult to do it with the dom...*/
				$html = preg_replace('#^<wip>(.*)</wip>$#si', '$1', $html);
				//$html = substr($html, 5, -7);
				
				$contents = $html;

			/*
				http://fr2.php.net/manual/en/domnode.c14n.php
				http://stackoverflow.com/questions/5914643/writing-changes-back-to-a-zend-dom-query-object
			*/
			//Zend_Debug::dump($html);
			break;

		}			
	}

    	protected static $_counter = 0;

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
	
    	public static function makeMeUniqRegex($matches, $separator = '')
	{
		self::$_counter++;
		return $matches[0] . $separator . self::$_counter;
	}
	
	public static function resetCounter()
	{
		self::$_counter = 0;
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
	
	public static function template_postrender($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		switch ($templateName) 
		{
			case 'thread_view':
			case 'conversation_view':
				//This part could be deleted but let's keep this if admins want to use css to customize the hidden postbit
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);
				$options = XenForo_Application::get('options');	
				$state = ($options->toggleME_Usergroups_Postbit_State == 'opened') ? '' : 'toggleHidden';

				if(self::forcePostbitExtraInfoDisplay($perms))
				{
					$state = '';
				}
			
				if($perms['toggle_postbit_usr'] || $options->toggleME_selected_areas['postbit_extra'])
				{
					$content = str_replace('<div class="extraUserInfo">', "<div class=\"extraUserInfo $state\">", $content);
				}
				break;
			case 'forum_view':
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);
				$options = XenForo_Application::get('options');	
			
				if(!$perms['toggle_wrappednoded_usr'] || !$options->toggleME_selected_areas['node_subforums'])
				{
					break;
				}
				
				$NodeId = $template->getParam('nodeList');

				if(!$NodeId) // Needed conditional to avoid a error message on other nodes who don't have the "parentNodeId" variable
				{
					break;	
				}

				//Close by default
				$tglNodeOffClass = '';
				if($options->toggleME_Usergroups_Wrapped_Nodes_OFF)
				{
					$tglNodeOffClass = ' tglNodeOff';
				}
					
				$NodeId = $NodeId['parentNodeId'];
				$search = '#(<ol.+?class="nodeList.+">)#';
				$replace = '$1
					<div id="tglnode_' . $NodeId . '" class="tglNodelist_forumview' . $tglNodeOffClass . '">
						<span class="toggleME_Expand" style="display:none">' . new XenForo_Phrase('toggleMe_Expand') . '</span>
						<span class="toggleME_Collapse" style="display:none">' . new XenForo_Phrase('toggleMe_Collapse') . '</span>
					</div>';
		
				$content = preg_replace($search, $replace, $content);
				break;
			case 'node_category_level_1':
				//For categories using by xenForo
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);
				$options = XenForo_Application::get('options');
				
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
			
				if(!$perms['toggle_forumhome_usr'] || !$options->toggleME_selected_areas['node_categories'])
				{
					break;
				}

				$withChildClasses = 'toggle_me tglWchild';
				$withoutChildClasses = 'toggle_me tglNOchild';

				if($options->toggleME_Categories_CloseClass_Off)
				{
					$withChildClasses .= ' tglDnt';
					$withoutChildClasses .= ' tglDnt';
				}
			
				//Check if the collapsed categories must use another class
				preg_match_all('#<li.+?class=".+?node_(?P<id>\d{1,9}).+?(?P<search><div class="categoryText">)#si', 
					$content, 
					$matches,
					PREG_SET_ORDER
				);

				if(is_array($matches))
				{
					foreach ($matches as $match)
					{
						if($options->toggleME_DefaultOff_XenCat && in_array($match['id'], $options->toggleME_DefaultOff_XenCat))
						{
							$withChildClasses .= ' tglWOFF';
						}

						if($langCheck && 
							(
								in_array($match['id'], $languageCategoriesToClose) ||
								($langCloseAllCategories && !in_array($match['id'], $languageCategoriesToKeepOpen))
							)
						)
						{
							$withChildClasses .= ' tglWOFF';
						}

						$content = preg_replace(
							'#<div class="categoryText">#i', 
							'<div id="_node_' . $match['id'] . '" class="' . $withChildClasses . '"></div>$0', 
							$content
						);
					}
				}
							
				$search = '#(<li class="(?:.+?)?groupNoChildren(?:.+?)?">\n\s+?<div class="(?:.+?)?categoryStrip(?:.+?)?>)#i';
				$replace = '$1<div class="' . $withoutChildClasses . '"></div>';

				$content = preg_replace($search, $replace, $content);
			break;
		}
	}

	public static function bakePerms($style_session)
	{
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
			$chkusr = array_intersect($visitorUserGroupIds, $options->toggleME_Usergroups_Wrapped_Nodes);
			$perms['toggle_wrappednoded_usr'] = (empty($chkusr)) ? false : true;
			$perms['quickCheck'] = true;
		}
		
		return $perms;
	}
	
	public static function forcePostbitExtraInfoDisplay($perms = false)
	{
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
		
		return (array_intersect($visitorUserGroupIds, $validUserGroups)) ? true : false;
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