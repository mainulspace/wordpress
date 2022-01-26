( function( $ ) {

	"use strict";
	
	//Attach sortable to the tbody, NOT tr
	var tbody = $(".story_list_cont #sorted_list");
	
	tbody.sortable({
		cursor: "move",
		connectWith: ".sort_list", 
	    update: function (event, ui) {
	        
	    }
	});

	//Attach sortable to the tbody, NOT tr
	var tbody1 = $(".top_story_list_cont #top_stories_sorted_list");
	
	tbody1.sortable({
		cursor: "move",
		connectWith: ".sort_list", 
	    update: function (event, ui) {
	        
	    }
	});

	var tbody2 = $(".story_list_cont #un_sorted_list");
	
	tbody2.sortable({
		cursor: "move",
		connectWith: ".sort_list", 
	    update: function (event, ui) {
	        
	    }
	});

	$('.sortable_submit').on('click', function(e){
		$('#sort_loading').css('display','block');
		$('.sort_updated').css('display','none');
		$('.sort_error').css('display','none');	
		data.sort = tbody.sortable('toArray');

		$.post(ajaxurl, data)
		.done(function(response) {
				$('#sort_loading').css('display','none');
				$('.sort_updated').css('display','block');				
			}).fail(function() {
				$('#sort_loading').css('display','none');
				$('.sort_error').css('display','block');				
			});
		top_stories_data.sort = tbody1.sortable('toArray');

		$.post(ajaxurl, top_stories_data)
		.done(function(response) {
				$('#sort_loading').css('display','none');
				$('.sort_updated').css('display','block');				
			}).fail(function() {
				$('#sort_loading').css('display','none');
				$('.sort_error').css('display','block');				
			});
	});

	$('.st_delete').bind('click', function () {
	    $(this).parent().remove();
	});
	

	var options = {
	    valueNames: [ 'story-title', 'postedDate' ]
	};

	var recentstorieList = new List('recentstories', options);

})( jQuery );






