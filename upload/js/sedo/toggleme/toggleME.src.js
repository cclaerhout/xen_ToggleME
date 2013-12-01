!function($, window, document, _undefined)
{    	
	/*ToggleME 2.2*/
	XenForo.ToggleME =
	{
		bloodyIE: false,
		cookiename: 'toggleme',
		initGlobal: function($element)
		{
			var t = XenForo.ToggleME;

			//Define ToogleMe Cookie
			t.mycookie = $.getCookie(t.cookiename);
			
			//Init Effects
			t.initEffects();
			
			//Check if is bloodyIE
			if($.browser.msie && parseInt($.browser.version, 10) < 8)
			{
				t.bloodyIE = true;
			}
			
			// NODES with children
			$('.tglWchild').ready(function() 
			{
				var hook = '.tglWchild',
				cookie_data_prefix = 'main';
				
				t.bakeCategories(hook, cookie_data_prefix);
			});
		
			// NODES without children
			$('.tglNOchild').ready(function() 
			{
				var hook = '.tglNOchild',
				cookie_data_prefix = 'mix';
				
				t.bakeCategories(hook, cookie_data_prefix);
			});
		
			// NODELIST in FORUMVIEW
			$('.tglNodelist_forumview').ready(function()
			{
				t.bakeNodeList();
			});
	
			// SIDEBAR BLOCKS
			$('.tglSidebar').ready(function()
			{
				t.bakeBlocks();	
			});		
		
		},
		initEffects: function()
		{
			this.d = XenForo.toogleMeConfig.duration;
			this.e = XenForo.toogleMeConfig.effect;
		},
		initPostbit: function($element)
		{
			var t = XenForo.ToggleME,
				$tglPostbit = $element.find('.tglPosbit'),
				$extraUserInfo = $element.find('.extraUserInfo');

			//Init Effects
			t.initEffects();

			//Check if toggle option is activated
			if($tglPostbit.length == 0)
			{
				return;
			}

			//Needed for ajax
      			/*
      			 	The class "extraUserInfo" is used instead of "tglPosbit" because it's located in 
      				"message_user_info" template AFTER xenForo check content verification, so this
      				will prevent to display the toggle icon if extraUserInfo has no content ^^
      			*/
			
			function open($el) {
				var currentExtraInfo = $el.parents('.messageUserBlock').find('.extraUserInfo');
				currentExtraInfo.slideDown(t.d, t.e).removeClass('toggleHidden');
      				$el.removeClass('inactive').addClass("active");			
			}
			
			function close($el) {
				var currentExtraInfo = $el.parents('.messageUserBlock').find('.extraUserInfo');
				currentExtraInfo.slideUp(t.d, t.e).addClass('toggleHidden');
				$el.removeClass('active').addClass("inactive");			
			}

			if(XenForo.toogleMeConfig.postbit_state === 0){
				$extraUserInfo.hide();
				$tglPostbit.addClass("inactive")
					.toggle(function () { open($(this)); }, function () { close($(this)); });
			}else{
				$extraUserInfo.show();
	      			$tglPostbit.addClass("active")
	      				.toggle(function () { close($(this)); }, function () { open($(this)); });
			}
		},
		bakeCookie: function(cname, ccat, cval) 
		{
			var cdata = ccat + ':' + cval,
			t = this;	
		
			if (t.mycookie && !(t.mycookie == 'undefined')){//if the cookie exists and its value is defined
				var cdatas = t.mycookie.split('[]'),
				ccat_regex = new RegExp( ccat + ":[01]", "i" );
				
				if (t.mycookie.match(ccat_regex)){//if the category is found inside cookie change its value
					t.mycookie = t.mycookie.replace(ccat_regex, cdata);
				} 
				else{//if the category is not found, add it inside the cookie
					t.mycookie = t.mycookie + '[]' + cdata;
				}
			}
			else//if the cookie hasn't been created yet
			{
				t.mycookie = cdata;
			}
	
			//Date Expiratation
			var expires = 90; // number of days
			expires = new Date(new Date().getTime() + expires * 86400000); // milliseconds in a day
		
			return $.setCookie(cname, t.mycookie, expires); //final cookie value
		},
		bakeCategories: function(hook, cookie_data_prefix)
		{
			var hook_active = hook + '.active',
			hook_inactive = hook + '.inactive',
			hook_defaultoff = hook + '.tglWOFF',
			chkClass = false,
	      		t = this;
	
			if(!$(hook).hasClass("tglDnt"))
			{
				chkClass = true;
			}
			
			$(hook).addClass("active");
		
			//Multi theme and multi-addon trick
			$(hook).parent().each(function(){
				$(this).nextAll().not('span').wrapAll('<div class="toggleMEtarget" />');
			});
		
			//Force close option ?
			if(hook_defaultoff)
			{
				/*
					Not needed => if user decided to open a closed category, don't automatically close it again
					$(hook_defaultoff).parent().next().css({display:"none"});
					$(hook_defaultoff).removeClass('active').addClass("inactive");
					$(hook_defaultoff).parent().removeClass("categoryStrip").addClass("CategoryStripCollapsed");
				*/
			}
		
			//Check inside cookie which category has to be collapsed
			if (t.mycookie && !(t.mycookie == 'undefined')){
			//The cookie exists, let's proceed
				//Let's get all the categories with ID (XenForo Categories -  template_postrender fct || XenForo Add-ons -  template_hook fct)
				$(hook).each(function(index){
					var node_id = this.id,
					TargeT = $(this).parent().next(), // = li.category ol ; = toggleMEtarget
					check_regex_closed = new RegExp(cookie_data_prefix + node_id + ":1", "i" ), //Look inside cookie to check if category was closed
					check_regex_opened = new RegExp(cookie_data_prefix + node_id + ":0", "i" ); //Look inside cookie to check if category was opened
												
					if ( (t.mycookie.match(check_regex_closed)) || ($(this).hasClass('tglWOFF') && !(t.mycookie.match(check_regex_opened)) && !(t.mycookie.match(check_regex_closed))) ){
						$(TargeT).hide();
						$(TargeT).prev().children(hook).removeClass('active').addClass('inactive');
						if(chkClass == true)
						{
							$(TargeT).prev().removeClass('categoryStrip').addClass('CategoryStripCollapsed');
						}
					}
				});
		
			}
			else{
			//The cookie doesn't exist, manage the defaut closed categories
				$(hook_defaultoff).parent().next().hide();
				$(hook_defaultoff).removeClass('active').addClass("inactive");
				if(chkClass == true)
				{			
					$(hook_defaultoff).parent().removeClass("categoryStrip").addClass("CategoryStripCollapsed");
				}
			};
		
			//Active class
			$(hook_active).toggle(
				function () {// I was considered as active, COLLAPSE ME !
					if(t.bloodyIE) { $(this).parent().next().slideUp(t.d, t.e).hide(); } else { $(this).parent().next().slideUp(t.d, t.e); }
					
					$(this).removeClass('active').addClass("inactive");
					if(chkClass == true)
					{
						$(this).parent().removeClass('categoryStrip').addClass("CategoryStripCollapsed");
					}
		
					var num = $(this).attr('id'),
					cookieCategory = cookie_data_prefix + num,
					cookieCategoryValue = '1';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
				},
				function () {// I was considered as active and you COLLAPSE ME, EXPAND ME !
					if(t.bloodyIE) { $(this).parent().next().show(); } else { $(this).parent().next().slideDown(t.d, t.e); }
					$(this).removeClass('inactive').addClass("active");
					if(chkClass == true)
					{
						$(this).parent().removeClass("CategoryStripCollapsed").addClass("categoryStrip");
					}
		
					var num = $(this).attr('id'),
					cookieCategory = cookie_data_prefix + num,
					cookieCategoryValue = '0';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
				}
			);
			
			//Inactive class
			$(hook_inactive).toggle(
				function () {// I was considered as inactive, EXPAND ME !
					if(t.bloodyIE) { $(this).parent().next().show(); } else { $(this).parent().next().slideDown(t.d, t.e); }
					$(this).removeClass('inactive').addClass("active");
					if(chkClass == true)
					{
						$(this).parent().removeClass("CategoryStripCollapsed").addClass("categoryStrip");
					}
					var num = $(this).attr('id'),
					cookieCategory = cookie_data_prefix + num,
					cookieCategoryValue = '0';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
		
				},
				function () { // I was considered as inactive and you expanded me, COLLAPSE ME !
					if(t.bloodyIE) { $(this).parent().next().hide(); } else { $(this).parent().next().slideUp(t.d, t.e); }
					$(this).removeClass('active').addClass("inactive");
					if(chkClass == true)
					{				
						$(this).parent().removeClass('categoryStrip').addClass("CategoryStripCollapsed");
					}
	
					var num = $(this).attr('id'),
					cookieCategory = cookie_data_prefix + num,
					cookieCategoryValue = '1';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
				}
			);
		},
		bakeNodeList: function()
		{
			var hook = '.tglNodelist_forumview',
			hook_active = hook + '.active',
			hook_inactive = hook + '.inactive',
			cookie_data_prefix = 'ndfw_',
			ndfw_id = $('.tglNodelist_forumview').attr('id'), //will be unique anyway: 1 per page max
			cookieCategory = cookie_data_prefix + ndfw_id,
	      		t = this;
		
			//Wrap all next tags in parent
			$(hook).each(function(){
				$(this).nextAll().wrapAll('<div class="toggleMEtarget" />');
			});
	
			//Check if must be closed by default
			if($(hook).hasClass("tglNodeOff"))
			{
				$(hook).addClass("inactive");
				$(hook).children('.toggleME_Expand').show();
				$(hook).children('.toggleME_Collapse').hide();
				$(hook).next().hide();
			}
			else
			{
				$(hook).addClass("active");
				$(hook).children('.toggleME_Expand').hide();
				$(hook).children('.toggleME_Collapse').show();
			}
		
			//Cookie check
			if (t.mycookie && !(t.mycookie == 'undefined')){
				var check_regex = new RegExp(cookieCategory + ":1", "i" );
		
				if (t.mycookie.match(check_regex)){
					$(hook).next().hide();
					$(hook).removeClass('active').addClass('inactive');
					$(hook).children('.toggleME_Expand').show();
					$(hook).children('.toggleME_Collapse').hide();
				}
			};
		
			//Let's toogle !
			$(hook_active).toggle(
				function () {// I was considered as active, COLLAPSE ME !
					$(this).children('.toggleME_Collapse').hide();
					$(this).children('.toggleME_Expand').show();
					$(this).next().slideUp(t.d, t.e);
					$(this).removeClass('active').addClass("inactive");
		
					var cookieCategoryValue = '1';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
				},
				function () {// I was considered as active and you COLLAPSE ME, EXPAND ME !
					$(this).children('.toggleME_Collapse').show();
					$(this).children('.toggleME_Expand').hide();
					$(this).next().slideDown(t.d, t.e);
					$(this).removeClass('inactive').addClass("active");
		
					var cookieCategoryValue = '0';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
				}
			);
			$(hook_inactive).toggle(
				function () {// I was considered as inactive, EXPAND ME !
					$(this).children('.toggleME_Collapse').show();
					$(this).children('.toggleME_Expand').hide();
					$(this).next().slideDown(t.d, t.e);
					$(this).removeClass('inactive').addClass("active");
		
					var cookieCategoryValue = '0';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
		
				},
				function () { // I was considered as inactive and you expanded me, COLLAPSE ME !
					$(this).children('.toggleME_Collapse').hide();
					$(this).children('.toggleME_Expand').show();
					$(this).next().slideUp(t.d, t.e);
					$(this).removeClass('active').addClass("inactive");
		
					var cookieCategoryValue = '1';
					t.bakeCookie(t.cookiename, cookieCategory, cookieCategoryValue);
				}
			);
		},
		bakeBlocks: function()
		{
	      		var hook = '.tglSidebar',
	      		hook_active = hook + '.active',
	      		hook_inactive = hook + '.inactive',
	      		hook_defaultoff = hook + '.tglSbOFF',
	      		cookie_data_prefix = 'sbb',
	      		t = this;
	      	
	      		$(hook).addClass("active");
	      	
	      		//Wrap all next tags in parent
	      		$(hook).next().each(function(){
	      			$(this).nextAll().wrapAll('<div class="toggleMEtarget" />');
	      		});
	      	
	      		//Cookie check
	      		if (t.mycookie && !(t.mycookie == 'undefined')){
	      		//The cookie exists, let's proceed
	      			//Let's get all the categories with ID (XenForo Categories -  template_postrender fct || XenForo Add-ons -  template_hook fct)
	      			$(hook).each(function(index){
	      				var sbb_id = this.id;
	      				var check_regex_closed = new RegExp(cookie_data_prefix + sbb_id + ":1", "i" ); //Look inside cookie to check if category was closed
	      				var check_regex_opened = new RegExp(cookie_data_prefix + sbb_id + ":0", "i" ); //Look inside cookie to check if category was opened
	      											
	      				if ( (t.mycookie.match(check_regex_closed)) || ($(this).hasClass('tglSbOFF') && !(t.mycookie.match(check_regex_opened)) && !(t.mycookie.match(check_regex_closed))) ){
	      					$(this).siblings('.toggleMEtarget').hide();
	      					$(this).removeClass('active').addClass("inactive");
	      					$(this).parent().removeClass('secondaryContent').addClass("secondaryContentCollapsed");				
	      				}
	      			});
	      	
	      		}
	      		else{
	      		//The cookie doesn't exist, manage the defaut closed categories
	      			$(hook_defaultoff).siblings('.toggleMEtarget').hide();
	      			$(hook_defaultoff).removeClass('active').addClass("inactive");
	      			$(hook_defaultoff).parent().removeClass('secondaryContent').addClass("secondaryContentCollapsed");				
	      		};
	      	
	      		//Let's toogle !
	      		$(hook_active).toggle(
	      			function () {// I was considered as active, COLLAPSE ME !
	      				$(this).siblings('.toggleMEtarget').slideUp(t.d, t.e);
	      				$(this).removeClass('active').addClass("inactive");
	      				$(this).parent().removeClass('secondaryContent').addClass("secondaryContentCollapsed");
	      	
	      				var num = $(this).attr('id');
	      				var cookieWidget = cookie_data_prefix + num;
	      				var cookieWidgetValue = '1';
	      				t.bakeCookie(t.cookiename, cookieWidget, cookieWidgetValue);
	      			},
	      			function () {// I was considered as active and you COLLAPSE ME, EXPAND ME !
	      				$(this).siblings('.toggleMEtarget').slideDown(t.d, t.e);
	      				$(this).removeClass('inactive').addClass("active");
	      				$(this).parent().removeClass('secondaryContentCollapsed').addClass("secondaryContent");
	      	
	      				var num = $(this).attr('id');
	      				var cookieWidget = cookie_data_prefix + num;
	      				var cookieWidgetValue = '0';
	      				t.bakeCookie(t.cookiename, cookieWidget, cookieWidgetValue);
	      			}
	      		);
	      		$(hook_inactive).toggle(
	      			function () {// I was considered as inactive, EXPAND ME !
	      				$(this).siblings('.toggleMEtarget').slideDown(t.d, t.e);
	      				$(this).removeClass('inactive').addClass("active");
	      				$(this).parent().removeClass('secondaryContentCollapsed').addClass("secondaryContent");
	      	
	      				var num = $(this).attr('id');
	      				var cookieWidget = cookie_data_prefix + num;
	      				var cookieWidgetValue = '0';
	      				t.bakeCookie(t.cookiename, cookieWidget, cookieWidgetValue);
	      	
	      			},
	      			function () { // I was considered as inactive and you expanded me, COLLAPSE ME !
	      				$(this).siblings('.toggleMEtarget').slideUp(t.d, t.e);
	      				$(this).removeClass('active').addClass("inactive");
	      				$(this).parent().removeClass('secondaryContent').addClass("secondaryContentCollapsed");
	      	
	      				var num = $(this).attr('id');
	      				var cookieWidget = cookie_data_prefix + num;
	      				var cookieWidgetValue = '1';
	      				t.bakeCookie(t.cookiename, cookieWidget, cookieWidgetValue);
	      			}
	      		);
	      	}
	}

	 XenForo.register('body', 'XenForo.ToggleME.initGlobal');
	 XenForo.register('.messageUserBlock', 'XenForo.ToggleME.initPostbit');	 
}
(jQuery, this, document);