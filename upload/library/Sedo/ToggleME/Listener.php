<?php
// Last modified: version 2.1.2
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

				$viewParams = array(
					'toggleme_js_version' => filemtime("js/sedo/toggleme/toggleME.js")
				);

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
				$search[] = '#((?<!"></div>)<div class="categoryText">)#i';
				$replace[] = '<div id="_crc_' . $CRC_ID . '-" class="toggle_me tglWchild' . $tglOffClass. '"></div>$1';
				$search[] = '#(<li class="(?:.+?)?groupNoChildren(?:.+?)?">\n\s+?<div class="(?:.+?)?categoryStrip(?:.+?)?>)#i';
				$replace[] = '$1<div class="toggle_me tglNOchild' . $tglOffClass. '"></div>';
			
				$contents = preg_replace($search, $replace, $contents);
						
				//Let's now finalize the IDs of main categories adding to them their 'replacement order' number 
						
				$contents = preg_replace_callback('#_crc_\d{1,9}-#', array('Sedo_ToggleME_Listener', 'nodeCategoriesRegex'), $contents);
						
			
				// Default Closed EXTRA Categories
				if ($options->toggleME_DefaultOff_ExtraCat)
				{
					$closed_cats = explode(',', $options->toggleME_DefaultOff_ExtraCat);
			
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
				
				if(empty($perms['toggle_postbit_usr']) || !$options->toggleME_selected_areas['postbit_extra'])
				{
					break;
				}

				$search = '#(<h3 class="userText">)#i';
				$replace = '$1<div class="tglPosbit"></div>';
			
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

				$hasFramework = preg_match('#WidgetFramework#i', $contents);

				if($hasFramework)
				{
					preg_match_all('#class="(?:section|[^"]*(?<!id=")(WidgetFramework_WidgetRenderer_\w+)[^"]*)?"#i', $contents, $matches_sb);
					//preg_match_all('#class="[^"]*widget[^"]*(WidgetFramework_WidgetRenderer_\w+)[^"]*"#i', $contents, $matches_sb);
				}
				else
				{
					preg_match_all('#<div class="\bsection\b(.*?)">#i', $contents, $matches_sb);
				}
					
				$count = 1;
					
				$widgets_options = self::BakeWidgetsOptions($options->toggleME_Widgets_Exclude, $options->toggleME_Widgets_DefaultOff);
				
				//must be a string
				$excludes_withclass = $widgets_options['excludes_withclass'];
				$OFF_withclass  =  $widgets_options['OFF_withclass'];
		
				//must be an array AND only have numeric value (not full class name)
				$excludes_noclass = $widgets_options['excludes_noclass'];
				$OFF_noclass = $widgets_options['OFF_noclass'];
					
				foreach ($matches_sb[1] as $match_sb)
				{
					//Blocks with several class names
					if(!empty($match_sb) AND !preg_match('#\b' . $excludes_withclass . '\b#i', $match_sb) AND !$hasFramework)
					{
						$search = '#<div class="section' . $match_sb . '">[\r\n\t ]*.+$#mi';
						preg_match('#(\S+).*+#i', $match_sb, $block_id);
						
						if(!empty($OFF_withclass) AND preg_match('#\b' . $OFF_withclass . '\b#i', $match_sb))
						{
							$replace = '$0<div id="tglblock_'. $block_id[1] .'" class="tglSidebar tglSbOFF"></div>';
						}
						else
						{
							$replace = '$0<div id="tglblock_'. $block_id[1] .'" class="tglSidebar"></div>';
						}
			
						$contents = preg_replace($search, $replace, $contents);
					}

					/*Blocks with several class names - with Framework*/
					if(!empty($match_sb) AND !preg_match('#\b' . $excludes_withclass . '\b#i', $match_sb) AND $hasFramework)
					{
						//look forward regex to exclude if the replace has already occurred => try to fix want two widgets have the same classname
						$tempFix_hasSameClassName = '(?!<div id="tglblock_)';
						
						$search = '#<div[^>]+?class="[^>]+?widget[^>]+?' . $match_sb . '[^>]+?>'. $tempFix_hasSameClassName . '#i';
						preg_match('#(\S+).*+#i', $match_sb, $block_id);

						if(!empty($OFF_withclass) AND preg_match('#\b' . $OFF_withclass . '\b#i', $match_sb))
						{
							$replace = '$0<div id="tglblock_'. $block_id[1] .'" class="tglSidebar tglSbOFF"></div>';
						}
						else
						{
							$replace = '$0<div id="tglblock_'. $block_id[1] .'" class="tglSidebar"></div>';
						}
			
						$contents = preg_replace($search, $replace, $contents, 1);
					}						
					/*Blocks with no class name except "section"*/
					elseif(empty($match_sb) AND !in_array($count, $excludes_noclass))
					{
						$search = '#<div class="section">([\r\n\t ]*.+)$#mi';							
							
						if(in_array($count, $OFF_noclass))
						{
							$replace = '<div class="section tglblock_' . $count . '">$1<div id="_tglblock_'. $count .'" class="tglSidebar tglSbOFF"></div>';			
						}
						else
						{
							$replace = '<div class="section tglblock_' . $count . '">$1<div id="_tglblock_'. $count .'" class="tglSidebar"></div>';
						}
		
						$contents = preg_replace($search, $replace, $contents, 1);
						$count++;					
					}
				}
				break;
		}			
	}

    	protected static $_counter = 0;
    
    	public static function nodeCategoriesRegex($matches)
	{
		self::$_counter++;
		return $matches[0] . self::$_counter;
	}
	
	public static function BakeWidgetsOptions($excludes, $off)
	{
		//Init
		$output['excludes_withclass'] = '';
		$output['OFF_withclass'] = '';		
		$output['excludes_noclass'] = array();
		$output['OFF_noclass'] = array();

		//Bake excludes
		$wip_excludes = explode(',', $excludes);
		$i = 1;
		foreach ($wip_excludes as $exclude)
		{
			if(!empty($exclude))
			{
				if(preg_match('#tglblock_(\d+)#ui', $exclude ,$capture))
				{
					$output['excludes_noclass'][] = $capture[1];
				}
				else
				{
					if($i == 1)
					{
						$output['excludes_withclass'] .= $exclude;
					}
					else
					{
						$output['excludes_withclass'] .= '|' . $exclude;
					}
				}
			}
			$i++;
		}
		
		//Bake Off by default
		$wip_off = explode(',', $off);
		$i = 1;
		
		foreach($wip_off as $item)
		{
			if(empty($item))
			{
				continue;
			}

			if(preg_match('#tglblock_(\d+)#ui', $item ,$capture))
			{
				$output['OFF_noclass'][] = $capture[1];
			}
			else
			{
				if($i == 1)
				{
					$output['OFF_withclass'] .= $item;
				}
				else
				{
					$output['OFF_withclass'] .= '|' . $item;
				}
				$i++;
			}
		}

		return $output;	
	}
    
	public static function template_postrender($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		switch ($templateName) 
		{
			case 'thread_view':
			case 'conversation_view':
				$style_session = $template->getParam('visitorStyle');
				$perms = self::bakePerms($style_session);
				$options = XenForo_Application::get('options');	
				$state = ($options->toggleME_Usergroups_Postbit_State == 'opened') ? '' : 'toggleHidden';
			
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
				preg_match_all('#<li.+?class=".+?node_(?P<id>\d{1,9}).+?(?P<search><div class="categoryText">)#si', $content, $matches, PREG_SET_ORDER);

				if(is_array($matches))
				{
					foreach ($matches as $match)
					{
						if($options->toggleME_DefaultOff_XenCat && in_array($match['id'], $options->toggleME_DefaultOff_XenCat))
						{
							$withChildClasses .= ' tglWOFF';
						}

						$content = preg_replace('#<div class="categoryText">#i', '<div id="_node_' . $match['id'] . '" class="' . $withChildClasses . '"></div>$0', $content);
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
			'quickCheck' => false
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