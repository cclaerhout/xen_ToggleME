**********************************
* ToggleME v.2.1                 *
* by Cédric CLAERHOUT            *
**********************************

**********************************
*      Addon Presentation        *
**********************************
This addon will allow your users to collapse/expand forum categories on forum page AND/OR on postbit... and much more

**********************************
*        Version History         *
**********************************
2013/01/23  v.2.1 released
	> Cleaner coding for Javascript 
	> Add global easing option with duration
	> Template modified (was still using the old code from 1 year ago)
	> Add an option to control postbit toggle state in XenForo Options (I will not add it like a user option)

	=> To upgrade use the Auto installer addon or upload files & import xml

	P.S: for those who want to have a very clean files structure, please delete this file:
	yourforum/js/sedo/toggleme/toggleME.mini.js
	Reason: it isn't used anymore
	

2013/01/20  v.2.02 released
	> Javascript fix for the postbit toggle
	
	=> To update : use Chris addon or upload files. 
2012/12/22 v.2.01 released
	> New Toggle Widget implementation
	> New IE JS fix
	> Correct callback links in options

	=> To upgrade use the Auto installer addon or upload files & import xml

2012/12/22 v.2.0 released
	> Templates & hooks Listeners have been rewritten
	> Permissions bugs have been fixed
	> Sidebar auto-off widget option has been fixed
	> Sidebar widget regex(s) have been fixed & modified 
	> Sidebar widget regex will check if the widget is using some html code with the css class "avatarHeap" (will create a width visual problem)
	> New Appearance configuration option to set the right margin when the css class "avatarHeap" is used (default value: 26px)
	> JS code for the postbit toggle has been a little modified (remains close after post)
	> First compatiblity with the Widget Framework addon (http://xenforo.com/community/threads/bd-widget-framework.28014/)
		=> might still have some problems see here: http://xenforo.com/community/threads/toggleme.27029/page-4#post-457189
	> Compatiblity with Chris Auto Installer Addon (directory path has been modified => Js & Libraries files are now in a sedo sub-directory
	
	=> To upgrade use the Auto installer addon or upload files & import xml

2012/11/08 v.1.9.2 released
	>Set a delay (90 days) for the cookie to prevent its deletion when broswer is closed

	=> To upgrade, just upload files
	

2012/10/19  v.1.9.1 released
	> JS bug fixed

	=>To upgrade from 1.9 version, just upload files

2012/10/17  v.1.9 released
	> Php & JS codes have been rewritten
	> 1 bug fixed with permissions
	> New option to keep the same display for collapsed categories
	> New option to close by default Wrapped Sub-forums

	=> To upgrade, upload files and import addon xml file

2012/08/20: v.1.8 released
	> ToggleMe now works with Widgets
	> Widgets can be closed by default
	> Widgets can be excluded (from toggle function)
	> Widget collasped visual options available in style properties

	=> To upgrade, upload files and import addon xml file

2012/02/26: v.1.7 released
	> NEW OPTIONS: 'Default Closed XenForo Categories' & 'Default Closed EXTRA Categories' (ie: Chatbox)
		What is it? You can now select which categories that have to be closed once the page loaded.
		If users select to open one of those 'default closed categories', their choice will be saved in the cookie.

	> The subcategories can now be toggled. For example: on the forum home page, the category A have inside it the subcategories X,Y,Z
	  If you click on that Category A, a new page will open. On this new page, the subcategories (not forums !) can now be toggled.

	> To allow these two options, the php and JS code have been deeply modified.
		
	=> To upgrade, upload files and import addon xml file


2012/02/22: v.1.6.2 released
	> Public phrases had been globally cached so that they don't use any DB query
	=> To upgrade just import addon xml file

2011/12/10: v.1.6.1
	- Small update to phrase three admin selection options. No need to update

2011/12/09: v.1.6
	- ToggleME now works with "wrapped subforums inside forum" with cookie memory
	- Previous Javascript code has been simplified
	- New CSS management: 1 css template for automatic update when using the xenForo Style properties options;
	  1 css to manually edit picture buttons (if needed)
	- ToggleME style configuration options are now in its own group: Appearance->Styles Properties->ToggleME
	- "CollaSpe" mistake has been corrected ^^
	- Update special information: if you see a graphic problem with the new button, just edit the template
	  "toggleme_page_container_js"... without editing it, but still saving it.

2011/12/08: v.1.5
	- ToggleME shoud now be compatible with every themes and addons
	- Fix IE no white space bug when category collapsed

2011/12/07: v.1.4
	- ToggleME now works in Conversations (extension of the postbit)

	>>Upload files AND import xml to update AND configure this addon in admin->home->options->ToggleME
2011/12/07: v.1.3
	- ToggleME options: per usergroups, per styles, per area (forumhome, postbit)
	>>Upload files AND import xml to update AND configure this addon in admin->home->options->ToggleME

2011/12/04: v.1.2
	- Toggle user extra information in postbit
	>>Upload files AND import xml to update

2011/11/30: v.1.1 
	-Just one cookie to manage toggle memory (in version 1.0 : one cookie per category)
	-The code will now be compatible with addons using 
	 'forum categories' (providing they respect a certain coding pattern)
	
	>>Upload files to update

2011/11/29: v.1.0 release

**********************************
*         Installation           *
**********************************
1) Upload the files on your forum directory
2) Import xml file


**********************************
*        Configuration           *
**********************************
>To configure this addon in admin->home->options->ToggleME

>To configure the Appearance of the Collasped category, go to:
admincp=>Appearance=>Style Properties=>Forum / Node List

You will find three new settings:
-Category Strip Collasped 
-Category Strip Collasped Title
-Category Strip Collasped Description

**********************************
*      Button customization      *
**********************************
If you want ot customize buttons and use of your owns,
just go and edit the bottom of toggleme.css template.
You will find more information here.

**********************************
* Coding pattern for developers  *
**********************************
>FIRST PARENT TAG : <div class="categoryStrip"> 
	=> FIRST CHILD TAG : <div class="categoryText">
>SECOND PARENT TAG : any TAG (ol, div...)

P.S:
-The first parent tag is the Toggle Head (Toggle control)
-The second parent tag is the element to collapse or expand

**********************************
*          References            *
**********************************
=> Jquery inspiration
http://www.tobypitman.com/multiple-collapsable-panels-with-cookies/
=> xenForo cookie Integration
http://xenforo.com/community/threads/collapsible-sidebar.14475/
Thanks to TFH
