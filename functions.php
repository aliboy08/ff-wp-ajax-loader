<?php
// Register assets
add_action('wp_enqueue_scripts', 'ff_ajax_loader_register_assets');
function ff_ajax_loader_register_assets(){
	$dir = plugin_dir_url(__FILE__);
	wp_register_style('ff-ajax-loader-styles', $dir . 'css/styles.css');
	wp_register_script('mustache-js', 'https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.min.js', array(), '2.3.0', true);
	wp_register_script('ff-ajax-loader-js', $dir .'js/ff-ajax-loader.js', array('jquery', 'mustache-js'), '2.0', true);
	wp_localize_script('ff-ajax-loader-js', 'wp_ajax_url', admin_url('admin-ajax.php'));
	
	//wp_register_style('bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
	//wp_register_style('fontawesome', 'https://use.fontawesome.com/releases/v5.0.6/css/all.css');
}

// Load assets
function ff_ajax_loader_load_assets(){
	wp_enqueue_style('ff-ajax-loader-styles');
	wp_enqueue_script('mustache-js');
	wp_enqueue_script('ff-ajax-loader-js');
}

if( !function_exists('ff_check_more_posts') ) {
	// Check if query have more posts, for ajax load more
	function ff_check_more_posts($args){
		$args['showpost'] = 1;
		$q = new WP_Query($args);
		if( $q->have_posts() ) {
			return true;
		} else {
			return false;
		}
	}
}

// Shortcode
function ff_ajax_loader($atts){
	ob_start();
	extract( shortcode_atts( array(
	
		// WP Query
		'post_type' => 'post',
		'showposts' => 3,
		
		// Markup
		'container_class' => '',
		'result_container_class' => 'row',
		'item_class' => 'item col-xs-6 col-sm-4',
		'no_more_results_text' => 'No more results',
		'load_more_btn_text' => 'Load more',
		'load_more_btn_class' => '',
		'load_more_btn_container_class' => '',
		'loading_markup' => '<i class="fa fa-circle-notch spin"></i>',
		'template_name' => 'item-post', // normal template file
		'mst_template_name' => 'item-post-mst', // mustache template file
		
		// FF Ajax Loader Options
		'ajax_action' => 'ff_ajax_loader_sample_query',
		'load_more_num' => 6,
		'load_on_init' => 'false',
		'load_on_init_num' => 3,
		
		// Misc
		'load_assets' => true,
		
	), $atts));
	
	if( $load_assets ) {
		ff_ajax_loader_load_assets();
	}
	
	$template_directory = get_template_directory() .'/item-templates/';
	$template_file = $template_directory . $template_name .'.php';
	$mst_template_file = $template_directory . $mst_template_name .'.php';
	
	// check if template file exists
	$template_file_exists = ( file_exists($template_file) ) ? true : false;
	$mst_template_file_exists = ( file_exists($mst_template_file) ) ? true : false;

	$template_file_exists = false;
	$mst_template_file_exists = false;
	
	$query_args = array(
		'post_type' => $post_type,
		'showposts' => $showposts,
	);
	
	$post_not_in = array();
	$q = new WP_Query($query_args);
	if( $q->have_posts() ) {
		while( $q->have_posts() ) { $q->the_post();
			array_push($post_not_in, get_the_ID()); // add ids to exclude list
		}
	}
	?>
	<div class="ff-ajax-loader <?php echo $container_class ?>"
		data-load_on_init_num="<?php echo $load_on_init_num; ?>"
		data-load_on_init="<?php echo $load_on_init; ?>"
		data-load_more_num="<?php echo $load_more_num; ?>"
		data-ajax_action="<?php echo $ajax_action; ?>"
		data-post__not_in="<?php echo json_encode($post_not_in); ?>"
		data-template="#<?php echo $template_name; ?>">
		
		<div class="results-container <?php echo $result_container_class; ?>">
		<?php
		if( $q->have_posts() ) {
			while( $q->have_posts() ) { $q->the_post();
				if( $template_file_exists ) {
					include($template_file);
				} else {
					// Default item template
					ff_ajax_loader_sample_template();
				}
			}
		}
		wp_reset_postdata();
		?>
		</div>
		
		<div id="<?php echo $template_name ?>" style="display:none">
			<?php
			if( $mst_template_file_exists ) {
				include($mst_template_file);
			} else {
				// Default mustache item template
				ff_ajax_loader_sample_mustache_template();
			}
			?>
		</div>
		
		<div class="load-more-button-container <?php echo $load_more_btn_container_class ?>">
			<a href="#" class="btn load-more-button <?php echo $load_more_btn_class; ?>"><?php echo $load_more_btn_text; ?></a>
		</div>
		<div class="no-more-results" style="display:none"><?php echo $no_more_results_text; ?></div>
		<div class="loading" style="display:none"><?php echo $loading_markup; ?></div>
		
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('ff_ajax_loader', 'ff_ajax_loader');

/**
 * Sample Ajax Query
 */
function ff_ajax_loader_sample_query(){
	$data = array();
	
	$args = array(
		'post_type' => 'post',
	);
	
	if( isset( $_POST['posts_per_page'] ) ) {
		$args['posts_per_page'] = $_POST['posts_per_page'];
	} 
	
	$all_post_ids = array();
	if( isset( $_POST['post__not_in'] ) ) {
		$args['post__not_in'] = $_POST['post__not_in'];
		$all_post_ids = $_POST['post__not_in'];
	}
	
	//$data['passed_data'] = $_POST; // debug 1
	//$data['args'] = $args; // debug 2
	
	$q = new WP_Query($args);
	if( $q->have_posts() ) {
		while( $q->have_posts() ) { $q->the_post();
			$p = $q->post;
			array_push($all_post_ids, $p->ID);
			$p->permalink = get_permalink($p->ID);
			$data['posts'][] = $p;
		}
		$data['have_posts'] = true;
		$args['post__not_in'] = $all_post_ids;
		$data['have_more_posts'] = ff_check_more_posts($args);
	} else {
		$data['have_posts'] = false;
	}
	
	die(json_encode($data));
}
add_action( 'wp_ajax_ff_ajax_loader_sample_query', 'ff_ajax_loader_sample_query' );
add_action( 'wp_ajax_nopriv_ff_ajax_loader_sample_query', 'ff_ajax_loader_sample_query' );


/**
 * Sample item template
 */ 
function ff_ajax_loader_sample_template(){
	echo '<div class="item col-xs-6 col-sm-4">';
		echo '<h3 class="title">';
			echo '<a href="'. get_permalink() .'">';
				echo get_the_title();
			echo '</a>';
		echo '</h3>';
		
		echo '<div class="excerpt">'. get_the_excerpt() .'</div>';
		
		echo '<div class="read-more-container">';
			echo '<a href="'. get_permalink() .'" class="read-more">Read More</a>';
		echo '</div>';
	echo '</div>';
}

/**
 * Sample mustache item template
 */
function ff_ajax_loader_sample_mustache_template(){
	echo '<div class="item col-xs-6 col-sm-4">';
		echo '<h3 class="title">';
			echo '<a href="{{permalink}}">';
				echo '{{post_title}}';
			echo '</a>';
		echo '</h3>';
		echo '<div class="excerpt">';
			echo '
			{{#post_excerpt}}
				{{post_excerpt}}
			{{/post_excerpt}}
			{{^post_excerpt}}
			  {{post_content}}
			{{/post_excerpt}}
			';
		echo '</div>';
		echo '<div class="read-more-container">';
			echo '<a href="{{permalink}}" class="read-more">Read More</a>';
		echo '</div>';
	echo '</div>';
}