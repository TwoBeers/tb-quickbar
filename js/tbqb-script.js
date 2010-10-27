//quickbar dropdown animation (v1.0)
//powered by jQuery
jQuery(document).ready(function($) { //jquery loaded in noConflict mode
	$('.tbqb_widget').each( function(){ //get every widget in qb
		var gap = 50; // the "fall" distance of the floating container
		var list = $(this).find('div.fw_pul') //the floating container
		if (list) {
			list.css('opacity', '0');
			list.css('margin-bottom', gap);
			$(this).mouseenter(function(){ //when mouse enters the widget field
				list.stop();
				list.animate({ 
					marginBottom: 0,
					opacity: 1
				  }, 200 );
			}).mouseleave(function(){  //when mouse leaves the widget field
				list.stop();
				list.animate({ 
					marginBottom: gap,
					opacity: 0
				  }, 200 );
			});
		};
	});
	$('.tbqb-minibutton').each( function(){ //get every button in the easynavi
		var gap = 20; // the "fall" distance of the floating container
		var list = $(this).find('.nb_tooltip') //the floating container
		if (list) {
			list.css('opacity', '0');
			list.css('margin-right', gap);
			$(this).mouseenter(function(){ //when mouse enters the widget field
				list.stop();
				list.animate({ 
					marginRight: 0,
					opacity: 1
				  }, 200 );
			}).mouseleave(function(){  //when mouse leaves the widget field
				list.stop();
				list.animate({ 
					marginRight: gap,
					opacity: 0
				  }, 200 );
			});
		};
	});
});





















