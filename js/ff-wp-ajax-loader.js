(function($){
	
	/*! FF WP Ajax Loader v2.0 */
	$.fn.ff_wp_ajax_loader = function(options){
		
		var instance = this,
			$this = $(this);
			
		var settings = $.extend({
			load_on_init: false,
			load_on_init_num: 9,
			load_more_num: 9,
			
			query_data: {},
			ajax_action: '',
			current_post: null,
			post__not_in: [],
			
			template: $($this.data('template')).html(),
			loading_html: $this.find('.loading'),
			load_more_button: $this.find('.load-more-button'),
			load_more_button_container: $this.find('.load-more-button').parent(),
			no_more_results_html: $this.find('.no-more-results'),
			results_container: $this.find('.results-container'),
			
			display_method: 'append', // 'append' or 'replace'
			
			append_delay: 0,
			fade_speed: 400,
			slide_down_speed: 400,
			height_animation_speed: 250,
			
			on_complete: null,
			on_append_complete: null,
			//on_refresh: null,
			on_init: null,
		}, options );
		
		var $results_container = settings.results_container,
			 $loading = settings.loading_html,
			 $load_more = settings.load_more_button_container,
			 $load_more_btn = settings.load_more_button,
			 $no_more_results = settings.no_more_results_html;
		
		// Set inline settings
		var exclude_settings = ['template']; // e.g ['template'];
		var instance_data = instance.data();
		// Loop through all html data attribute
		for(var prop in instance_data) {
			if( exclude_settings.indexOf(prop) != -1 ) continue; // exclude automatic inline setting, for custom data validation
			settings[prop] = instance_data[prop];
		}
		
		function init(){
			
			$this.addClass('ff-ajax-loader-init');
			
			$loading.hide();
			
			// Load posts on init
			if( settings.load_on_init ) loadMore();
			
			// On load more button click
			settings.load_more_button.click(function(e){
				e.preventDefault();
				loadMore();
			});
			
			// On init callback
			if( typeof settings.on_init === 'function' ) settings.on_init();
		}
		
		init();
		
		function loadMore(){
			
			displayLoading('show');
			
			var query_data = $.extend({
				action: settings.ajax_action,
				posts_per_page: settings.load_more_num,
				paged: settings.posts_paged,
				post__not_in: settings.post__not_in,
			}, settings.query_data);
			
			//console.log('query_data', query_data); // debug point 1
			
			$.ajax({
				type: 'POST',
				url: wp_ajax_url,
				dataType: 'json',
				data: query_data,
				success: function(data){
					
					//console.log('Data', data); // debug point 2
					displayLoading('hide');
					
					if( data.have_posts ) {
						// Have posts
						if( settings.display_method === 'append' ) {
							// Append content
							showItems(data.posts);
							
						} else {
							// Replace content
							$results_container.html('');
							showItems(data.posts);
						}
						
						if( !data.have_more_posts ) {
							// No more posts
							$load_more.fadeOut(100);
							settings.no_more_results_html.fadeIn(100);
							updateResultContainerHeight();
							
							// On complete callback
							if( typeof settings.on_complete === 'function' ) settings.on_complete();
						}
						
					} else {
						// No results
						$load_more.fadeOut(100);
						$no_more_results.fadeIn(100);
						displayLoading('hide');
						updateResultContainerHeight();
					}
					
				},
				error: function(request, status, error){
					console.log('Ajax error: '+ status, error);
					$load_more.fadeOut(100);
					$no_more_results.fadeIn(100);
				}
			}); // $.ajax
			
		} // loadMore()
		
		function showItems(data){
			var nItems = data.length;
			$.each(data, function(index){
				var $post = $(this)[0];
				// console.log('post '+ index, $post); debug point 3
				var item = Mustache.render(settings.template, $post);
				settings.post__not_in.push($post.ID);
				appendItems(item, index, nItems);
			});
		}
		
		function displayLoading(state){
			if( state === 'show' ) {
				$loading.fadeIn(100);
				$load_more.fadeOut(100);
			} else {
				$loading.fadeOut(100);
				$load_more.fadeIn(100);
			}
		}
		
		function updateResultContainerHeight(){
			var origH = $results_container.height();
			$results_container.height('auto');
			var newH = $results_container.height();
			if( newH < origH ) {
				$results_container.height(origH);
				if( settings.slide_down_speed === 0 ) {
					$results_container.height('auto');
				} else {
					$results_container.animate({'height': newH}, settings.height_animation_speed, function(){
						$results_container.height('auto');
					});
				}
			}
		}
		
		function appendItems(item, index, nItems){
			var $item = $(item),
				isLastItem = false;
				
			if( index + 1 === nItems ) {
				isLastItem = true;
			}
			
			$item.appendTo($results_container);
			
			// On append item callback
			if (typeof settings.on_append_complete === "function") {
				settings.on_append_complete();
			}
			
			var origH = $results_container.height();
			$results_container.height('auto');
			var newH = $results_container.height();
			if( newH < origH ) {
				$results_container.height(origH);
			}
			
			$item
				.hide()
				.css({'opacity': 0})
				.slideDown(settings.slide_down_speed)
				.delay(index*settings.append_delay)
				.animate({'opacity': 1}, settings.fade_speed, function(){
					if(isLastItem) {
						updateResultContainerHeight();
						// On load complete callback
						if (typeof settings.on_complete === "function") {
							settings.on_complete();
						}
					}
				});
		}
		
		function clear() {
			$results_container.html();
			settings.post__not_in = [];
		}
		
		function refresh(options){
			if (typeof settings.on_refresh === "function") {
				settings.on_refresh();
			}
			clear();
			$reults_wrap.height('auto');
			loadMore();
			return this;
		}
		
		return this;
	}
	
	// Initalize instances
	$('.ff-ajax-loader').each(function(){
		$(this).ff_wp_ajax_loader();
	});
	
})(jQuery)