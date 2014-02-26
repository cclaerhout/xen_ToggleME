!function($, window, document, _undefined)
{    	
	/*ToggleME 2.3*/
	XenForo.ToggleME =
	{
		bloodyIE: false,
		cookiename: 'toggleme',
		initGlobal: function($element)
		{
			var self = XenForo.ToggleME;

			//Define ToogleMe Cookie
			self.mycookie = $.getCookie(self.cookiename);
			
			//Init Effects
			self.initEffects();
			
			//Check if is bloodyIE
			if($.browser.msie && parseInt($.browser.version, 10) < 8){
				self.bloodyIE = true;
			}
			
			// NODES with children
			$('.tglWchild').ready(function() {
				var hook = '.tglWchild', cookie_data_prefix = 'main';
				self.bakeCategories(hook, cookie_data_prefix);
			});
		
			// NODES without children
			$('.tglNOchild').ready(function() {
				var hook = '.tglNOchild', cookie_data_prefix = 'mix';
				self.bakeCategories(hook, cookie_data_prefix);
			});
		
			// NODELIST in FORUMVIEW
			$('.tglNodelist_forumview').ready(function(){
				self.bakeNodeList();
			});
	
			// SIDEBAR BLOCKS
			$('.tglSidebar').ready(function(){
				self.bakeBlocks();	
			});		
		
		},
		initEffects: function()
		{
			this.d = XenForo.toogleMeConfig.duration;
			this.e = XenForo.toogleMeConfig.effect;
		},
		initPostbit: function($element)
		{
			var self = XenForo.ToggleME,
				$tglPostbit = $element.find('.tglPosbit'),
				$extraUserInfo = $element.find('.extraUserInfo');

			//Init Effects
			self.initEffects();

			//Check if toggle option is activated
			if($tglPostbit.length == 0){
				return;
			}

			//Needed for ajax
      			/*
      			 	The class "extraUserInfo" is used instead of "tglPosbit" because it's located in 
      				"message_user_info" template AFTER xenForo check content verification, so this
      				will prevent to display the toggle icon if extraUserInfo has no content ^^
      			*/
			
			var open = function(){
				var $el = $(this);
				$el.parents('.messageUserBlock').find('.extraUserInfo')
					.slideDown(self.d, self.e).removeClass('toggleHidden');
      				$el.removeClass('inactive').addClass('active');			
			};
			
			var close = function(){
				var $el = $(this);
				$el.parents('.messageUserBlock').find('.extraUserInfo')
					.slideUp(self.d, self.e).addClass('toggleHidden');
				$el.removeClass('active').addClass('inactive');			
			};

			if(XenForo.toogleMeConfig.postbit_state === 0){
				$extraUserInfo.hide();
				$tglPostbit.addClass('inactive').toggle(open, close);
			}else{
				$extraUserInfo.show();
	      			$tglPostbit.addClass('active').toggle(close, open);
			}
		},
		bakeCookie: function(cname, ccat, cval) 
		{
			var cdata = ccat + ':' + cval, self = this;	
		
			if (self.mycookie && !(self.mycookie == 'undefined')){
				//if the cookie exists and its value is defined [to check]
				var cdatas = self.mycookie.split('[]'),
					ccat_regex = new RegExp( ccat + ":[01]", "i" );
				
				if (self.mycookie.match(ccat_regex)){
					//if the category is found inside cookie change its value
					self.mycookie = self.mycookie.replace(ccat_regex, cdata);
				} 
				else{
					//if the category is not found, add it inside the cookie
					self.mycookie = self.mycookie + '[]' + cdata;
				}
			}else{
				//if the cookie hasn't been created yet
				self.mycookie = cdata;
			}
	
			//Date Expiratation
			var expires = 90; // number of days
			expires = new Date(new Date().getTime() + expires * 86400000); // milliseconds in a day
		
			return $.setCookie(cname, self.mycookie, expires);
		},
		bakeCategories: function(hook, cookie_data_prefix)
		{
			var self = this,
				$hook = $(hook),
				hook_active = hook+'.active',
				hook_inactive = hook+'.inactive',
				hook_defaultoff = hook+'.tglWOFF',
				chkClass = false,
				CategoryStripCollapsed = 'CategoryStripCollapsed', categoryStrip = 'categoryStrip',
				inactive = 'inactive', active = 'active';
	
			if(!$hook.hasClass('tglDnt')){
				chkClass = true;
			}

			$hook.addClass('active');
		
			//Multi theme and multi-addon trick
			$hook.parent().each(function(){
				$(this).nextAll().not('span').wrapAll('<div class="toggleMEtarget" />');
			});
		
			//Check inside cookie which category has to be collapsed
			if (self.mycookie && !(self.mycookie == 'undefined')){
			//The cookie exists, let's proceed
				//Let's get all the categories with ID (XenForo Categories -  template_postrender fct || XenForo Add-ons -  template_hook fct)
				$hook.each(function(index){
					var $this = $(this),
						node_id = this.id,
	      					node_id = (node_id == undefined || !node_id) ? $this.data('id') : node_id;

					var $target = $this.parent().next(), // = li.category ol ; = toggleMEtarget
						check_regex_closed = new RegExp(cookie_data_prefix + node_id + ":1", "i" ), //Look inside cookie to check if category was closed
						check_regex_opened = new RegExp(cookie_data_prefix + node_id + ":0", "i" ); //Look inside cookie to check if category was opened

					if ( (self.mycookie.match(check_regex_closed)) || 
						($this.hasClass('tglWOFF') && 
							!(self.mycookie.match(check_regex_opened)) &&
							!(self.mycookie.match(check_regex_closed))
						)
					){
						$target.hide();
						$target.prev().children(hook).removeClass(active).addClass(inactive);
						if(chkClass == true)
						{
							$target.prev().removeClass(categoryStrip).addClass(CategoryStripCollapsed);
						}
					}
				});
			}else{
			//The cookie doesn't exist, manage the defaut closed categories
				$(hook_defaultoff).parent().next().hide();
				$(hook_defaultoff).removeClass(active).addClass(inactive);
				if(chkClass == true){			
					$(hook_defaultoff).parent().removeClass(categoryStrip).addClass(CategoryStripCollapsed);
				}
			};

			var genericToggle = function($this, expand)
			{
      				var $parent = $this.parent(),
      					$elToSlide = $parent.next(),
    					classToRemove = (expand) ? inactive : active,
      					classToAdd = (expand) ? active : inactive,
      					parentClassToRemove = (expand) ? CategoryStripCollapsed : categoryStrip,
      					parentClassToAdd = (expand) ? categoryStrip : CategoryStripCollapsed;

      				if(self.bloodyIE) {
      					if(expand){
	      					$elToSlide.show();
	      				}else{
	      					$elToSlide.hide();
	      				}
      				} else {
    					if(expand){
	      					$elToSlide.slideDown(self.d, self.e);
	      				}else{
      						$elToSlide.slideUp(self.d, self.e);
	      				}
      				}

      				$this.removeClass(classToRemove).addClass(classToAdd);

      				if(chkClass == true){
      					$parent.removeClass(parentClassToRemove).addClass(parentClassToAdd);
      				}

      				var num = $this.attr('id'),
     					num = (num == undefined) ? $this.data('id') : num;
     				
				var cookieCategory = cookie_data_prefix + num,
      					cookieCategoryValue = (expand) ? '0' : '1';

      				self.bakeCookie(self.cookiename, cookieCategory, cookieCategoryValue);			
			}

			var collapseME = function(){
				genericToggle($(this), false);
			};

			var expandME = function(){
				genericToggle($(this), true);
			};

			$(hook_active).toggle(collapseME, expandME);
			$(hook_inactive).toggle(expandME, collapseME);
		},
		bakeNodeList: function()
		{
			var self = this,
				hook = '.tglNodelist_forumview',
				$hook = $(hook),
				hook_active = hook + '.active',
				hook_inactive = hook + '.inactive',
				cookie_data_prefix = 'ndfw_',
				ndfw_id = $('.tglNodelist_forumview').attr('id'), //will be unique anyway: 1 per page max
				cookieCategory = cookie_data_prefix + ndfw_id,
				toggleME_Expand = '.toggleME_Expand', toggleME_Collapse = '.toggleME_Collapse',
				active = 'active', inactive = 'inactive';
		
			//Wrap all next tags in parent
			$hook.each(function(){
				$(this).nextAll().wrapAll('<div class="toggleMEtarget" />');
			});
	
			//Check if must be closed by default
			if($hook.hasClass('tglNodeOff')){
				$hook.addClass(inactive).children(toggleME_Expand).show();
				$hook.children(toggleME_Collapse).hide();
				$hook.next().hide();
			}else{
				$hook.addClass(active).children(toggleME_Expand).hide();
				$hook.children(toggleME_Collapse).show();
			}
		
			//Cookie check
			if (self.mycookie && !(self.mycookie == 'undefined')){
				if($hook.hasClass('tglNodeOff')){
					var check_regex = new RegExp(cookieCategory + ":0", "i" );

					if (self.mycookie.match(check_regex)){
						$hook.removeClass(inactive).addClass(active).next().show();
						$hook.children(toggleME_Expand).hide();
						$hook.children(toggleME_Collapse).show();					
					}
				}else{
					var check_regex = new RegExp(cookieCategory + ":1", "i" );

					if (self.mycookie.match(check_regex)){
						$hook.removeClass(active).addClass(inactive).next().hide();
						$hook.children(toggleME_Expand).show();
						$hook.children(toggleME_Collapse).hide();
					}
				}
			};
		
			//Let's toogle !
			var genericToggle = function($this, expand)
			{
				var toShow = (expand) ? toggleME_Collapse : toggleME_Expand,
					toHide = (expand) ? toggleME_Expand : toggleME_Collapse,
					classToRemove = (expand) ? inactive : active,
					classToAdd = (expand) ? active : inactive;

				$this.children(toShow).show();
				$this.children(toHide).hide();

				if(expand){
					$this.next().slideDown(self.d, self.e);
				}else{
					$this.next().slideUp(self.d, self.e);				
				}

				$this.removeClass(classToRemove).addClass(classToAdd);
		
				var cookieCategoryValue = (expand) ? '0' : '1';
				self.bakeCookie(self.cookiename, cookieCategory, cookieCategoryValue);
			}

			var collapseME = function(){
				genericToggle($(this), false);
			};

			var expandME = function(){
				genericToggle($(this), true);
			};

			$(hook_active).toggle(collapseME, expandME);
			$(hook_inactive).toggle(expandME, collapseME);
		},
		bakeBlocks: function()
		{
	      		var self = this,
	      			hook = '.tglSidebar',
	      			$hook = $(hook),
				hook_active = hook + '.active',
	      			hook_inactive = hook + '.inactive',
		      		hook_defaultoff = hook + '.tglSbOFF',
		      		cookie_data_prefix = 'sbb',
		      		toggleMEtarget = '.toggleMEtarget',
		      		secondaryContent = 'secondaryContent', secondaryContentCollapsed = 'secondaryContentCollapsed',
		      		active = 'active', inactive = 'inactive',
				WFWR = 'WidgetFramework_WidgetRenderer_';
	      	
	      		$hook.addClass(active);
	      	
	      		//Wrap all next tags in parent
	      		$hook.next().each(function(){
	      			$(this).nextAll().wrapAll('<div class="toggleMEtarget" />');
	      		});
	      	
	      		//Cookie check
	      		if (self.mycookie && !(self.mycookie == 'undefined')){
	      		//The cookie exists, let's proceed
	      			//Let's get all the categories with ID (XenForo Categories -  template_postrender fct || XenForo Add-ons -  template_hook fct)
	      			$hook.each(function(index){
	      				var $this = $(this),
	      					sbb_id = this.id;

     						if(sbb_id != undefined){
		     					sbb_id = sbb_id.replace(WFWR, 'WFWR_');
     						}
     							      					
	      				var check_regex_closed = new RegExp(cookie_data_prefix + sbb_id + ":1", "i" ), //Look inside cookie to check if category was closed
	      					check_regex_opened = new RegExp(cookie_data_prefix + sbb_id + ":0", "i" ); //Look inside cookie to check if category was opened
	      											
	      				if ( (self.mycookie.match(check_regex_closed)) || ($this.hasClass('tglSbOFF') && !(self.mycookie.match(check_regex_opened)) && !(self.mycookie.match(check_regex_closed))) ){
	      					$this.siblings(toggleMEtarget).hide();
	      					$this.removeClass(active).addClass(inactive);
	      					$this.parent().removeClass(secondaryContent).addClass(secondaryContentCollapsed);
	      				}
	      			});
	      	
	      		}else{
	      		//The cookie doesn't exist, manage the defaut closed categories
	      			$(hook_defaultoff).removeClass(active).addClass(inactive).siblings(toggleMEtarget).hide();
	      			$(hook_defaultoff).parent().removeClass(secondaryContent).addClass(secondaryContentCollapsed);
	      		};
	      	
	      		//Let's toogle !
			var genericToggle = function($this, expand)
			{
				var classToRemove = (expand) ? inactive : active,
					classToAdd = (expand) ? active : inactive,
					parentClassToRemove = (expand) ? secondaryContentCollapsed : secondaryContent,
					parentClassToAdd = (expand) ? secondaryContent : secondaryContentCollapsed;

					if(expand){
	      					$this.siblings(toggleMEtarget).slideDown(self.d, self.e);
	      				}else{
	      					$this.siblings(toggleMEtarget).slideUp(self.d, self.e);	      				
	      				}
	      				
	      				$this.removeClass(classToRemove).addClass(classToAdd);
	      				$this.parent().removeClass(parentClassToRemove).addClass(parentClassToAdd);
	      	
      				var num = $this.attr('id');
      			
     				if(num != undefined){
     					num = num.replace(WFWR, 'WFWR_');
     				}
     				      			
				var cookieWidget = cookie_data_prefix + num;
					cookieWidgetValue = (expand) ? '0' : '1';
					
      				self.bakeCookie(self.cookiename, cookieWidget, cookieWidgetValue);
			}

			var collapseME = function(){
				genericToggle($(this), false);
			};

			var expandME = function(){
				genericToggle($(this), true);
			};

	      		$(hook_active).toggle(collapseME, expandME);
	      		$(hook_inactive).toggle(expandME, collapseME);
	      	}
	}

	 XenForo.register('body', 'XenForo.ToggleME.initGlobal');
	 XenForo.register('.messageUserBlock', 'XenForo.ToggleME.initPostbit');	 
}
(jQuery, this, document);