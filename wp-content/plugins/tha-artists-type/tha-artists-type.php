<?php
/*
Plugin Name: Tha Artists Type
Plugin URI: http://www.webisti.cz
Description: Declares a plugin that will create a custom post type displaying artists.
Version: 1.1
Author: Fanky
Author URI: http://www.webisti.cz
License: GPLv2
*/


//helper for links to be real links

if(!function_exists('addhttp')){
	function addhttp($url) {
		if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
			$url = "http://" . $url;
		}
		return $url;
	}
}

function create_tha_artist() {
    register_post_type( 'tha_artists',
        array(
            'labels' => array(
                'name' => 'Tha Artists',
                'singular_name' => 'Tha Artist',
                'add_new' => 'Add New',
                'add_new_item' => 'Add tha New Artist',
                'edit' => 'Edit',
                'edit_item' => 'Edit tha Artist',
                'new_item' => 'Make Tha New Artist',
                'view' => 'View',
                'view_item' => 'View tha New Artist',
                'search_items' => 'Search tha Artists',
                'not_found' => 'No Artists found',
                'not_found_in_trash' => 'No Artists found in Trash',
				'hierarchical' => false,
                'parent' => 'Tha Parent Artist'
            ),
 
            'public' => true,
			'rewrite' => true,
            'menu_position' => 9,
			'register_meta_box_cb' => 'add_artists_metaboxes',
            'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes'),
            'taxonomies' => array('tha-artist-category'),
            'menu_icon' => plugins_url( 'img/artists.png', __FILE__ ),
            'has_archive' => true,
			'rewrite' => array('slug' => 'artists')
        )
    );	
	register_taxonomy('tha-artist-category', 'tha_artists',
	array("hierarchical" => true,
	"label" => "Tha Artist Categories",
	'update_count_callback' => '_update_post_term_count',
	'query_var' => true,
	'public' => true,
	'show_ui' => true,
	'show_admin_column' => true,
	'show_tagcloud' => true,
	'_builtin' => false,
	'show_in_nav_menus' => true,
	'show_in_rest' => true,
	'show_in_quick_edit' => true
	)
	);
}

add_action( 'init', 'create_tha_artist' );
// Add the artists Meta Boxes

function add_artists_metaboxes() {
	add_meta_box('headliner', 'Headliner', 'thaartisttype_addbox', 'tha_artists', 'normal', 'high', array( 'name' => 'headliner'));
	add_meta_box('subtitle', 'Žánr', 'thaartisttype_addbox', 'tha_artists', 'normal', 'high', array( 'name' => 'subtitle'));
	add_meta_box('website', 'Website URL', 'thaartisttype_addbox', 'tha_artists', 'normal', '', array( 'name' => 'website'));
	add_meta_box('fbpage', 'Facebook page URL', 'thaartisttype_addbox', 'tha_artists', 'normal', '', array( 'name' => 'fbpage'));
	add_meta_box('video', 'URL jednotlivého videa (ne kanálu) na YT/fb/vimeo', 'thaartisttype_addbox', 'tha_artists', 'normal', '', array( 'name' => 'video'));
}



// The artist Metabox

function thaartisttype_addbox($post, $metabox) {

	
	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="artistmeta_noncename" id="artistmeta_noncename" value="' . 
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	// Get the location data if its already been entered
	$entered = get_post_meta($post->ID, $metabox['args']['name'], true);
	
	// Echo out the field
		
	if($metabox['args']['name']=="headliner") {
		echo '<input type="checkbox" value="on" name="'.$metabox['args']['name'].'" ';
		if($entered=="on") echo ' checked="checked"';
		echo 'class="widefat" /> Umělec je headlinerem festivalu';
	}else{
	
		echo '<input type="text" name="'.$metabox['args']['name'].'" value="' . $entered  . '" class="widefat" />';
	
	}
	
	

}

// Save the Metabox Data

function wpt_save_artists_meta($post_id, $post) {
	
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
		
		 if ( !isset( $_POST['artistmeta_noncename'] ) || !wp_verify_nonce( $_POST['artistmeta_noncename'], plugin_basename(__FILE__) )) {
return $post->ID;
 }
	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post->ID ))
		return $post->ID;

	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though.
	//$places_meta['_buyticketurl'] = $_POST['_buyticketurl']; -using whole $_POST instead
	
	
	$_POST["headliner"]="".$_POST["headliner"]; //if checkbox not send, set it, means unchecked
	// Add values of $artists_meta as custom fields
	foreach ($_POST as $key => $value) { // Cycle through the $places_meta array!
		   
			if( $post->post_type == 'revision' ) return; // Don't store custom data twice
			$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
			if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
			
			
			
			update_post_meta($post->ID, $key, $value);
			} else { // If the custom field doesn't have a value
				add_post_meta($post->ID, $key, $value);
			}
			
			if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
		
	}

}


add_action('save_post', 'wpt_save_artists_meta', 1, 2); // save the custom fields



/**
* add order column to admin listing screen for header text
*/
function add_new_tha_artists_column($tha_artists_posts_columns) {
  $tha_artists_posts_columns['menu_order'] = "Order";
  return $tha_artists_posts_columns;
}
add_action('manage_edit-tha_artists_posts_columns', 'add_new_tha_artists_column');


/**
* show custom order column values
*/
function tha_artists_show_order_column($name){
  global $post;

  switch ($name) {
    case 'menu_order':
      $order = $post->menu_order;
      echo $order;
      break;
   default:
      break;
   }
}
add_action('manage_tha_artists_posts_custom_column','tha_artists_show_order_column');

/**
* make column sortable
*/
function tha_artists_order_column_register_sortable($columns){
  $columns['menu_order'] = 'menu_order';
  return $columns;
}
add_filter('manage_edit-tha_artists_sortable_posts_columns','tha_artists_order_column_register_sortable');






/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/************FRONTEND***********************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/
/*******************************************/

function tha_artists_addhttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}
function listartist($post){
	global $post;
	$imagesrc=get_attached_file(get_post_thumbnail_id($post->ID),array(200,140));
	$imagedetails = getimagesize($imagesrc);
	// echo $imagedetails[0]."x".$imagedetails[1];
	// if(($imagedetails[0] / $imagedetails[1]) < 113/148 ) $addclass.=" toothin";
	if(($imagedetails[0] / $imagedetails[1]) < 37/100 ) $addclass.=" toothin";
	elseif($imagedetails[0] < $imagedetails[1]) $addclass.=" tall";
	// elseif(($imagedetails[0] / $imagedetails[1]) > 19/6 ) $addclass.=" toowide";
	elseif(($imagedetails[0] / $imagedetails[1]) > 17/6 ) $addclass.=" toowide";
	?><div class="post-list<?echo $addclass;?>"  id="post-<?php echo $post->ID; ?>"><a class="thaartist<?if (in_category('artists-make-image-bigger', $post->ID)) echo' artists-make-image-bigger';?>" target="_blank" href="<?php echo tha_artists_addhttp(get_post_meta($post->ID, "_artisturl", true)); ?>" rel="bookmark" title="<?php echo $post->post_title; ?>"><div><img src="<?echo get_the_post_thumbnail_url($post->ID,array(200,140));?>"></div></a></div><?
}


function thaartistsshortcode( $atts ) {

	$a = shortcode_atts( array("cat" => ""), $atts );
	$thacat=$a["cat"];
	ob_start();
	$term=get_term_by( 'slug', $thacat, 'tha-artist-category' );
	?><div class="thaartistscont"><?
	if($thacat!==""){?><h3><?echo$term->name;?></h3><?}
	?><div class="thaartists"><?
	$type = 'tha_artists';
	$args=array(
	  'post_type' => $type,
	  'post_status' => 'publish',
	  'posts_per_page' => -1,
	  'orderby' => 'menu_order',
	  'ignore_sticky_posts'=> 1);
	if($thacat!=="")
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'tha-artist-category',
				'field'    => 'slug',
				'terms'    => $thacat,
			)
	    );
	$my_query = null;
	$my_query = new WP_Query($args);
	if( $my_query->have_posts() ) {
		

		  while ($my_query->have_posts()) : $my_query->the_post();
			listartist($post);
		  endwhile;
	}
	wp_reset_query();  // Restore global post data stomped by the_post().
	?></div></div><?
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}
add_shortcode( 'artists', 'thaartistsshortcode' );





















// add custom data to post's REST API


function get_post_meta_for_api( $object, $attr ) {
 //get the id of the post object array
 $post_id = $object['id'];
 $fields=get_post($post_id);
 $metafields=get_post_meta($post_id);
 if($metafields["headliner"][0]=="on") $promoted=true; else $promoted=false;
 if($fields->post_status=="publish") $approved=true; else $approved=false;
 if($metafields["video"][0]) $metafields["video"][0]=addhttp($metafields["video"][0]);
 if($metafields["fbpage"][0]) $metafields["fbpage"][0]=addhttp($metafields["fbpage"][0]);
 if($metafields["website"][0]) $metafields["website"][0]=addhttp($metafields["website"][0]);
 return array(
	// "allmeta" => $metafields,
	"performer_name" => $fields->post_title,
	"title_of_performance" => $fields->post_title,
	"genre" => $metafields["subtitle"][0],
	"youtube_url" => $metafields["video"][0],
	"facebook_url" => $metafields["fbpage"][0],
	"website_url" => $metafields["website"][0], //new, not in Filip's API
	"img_user" => get_the_post_thumbnail_url($post_id),
	"performance_description_cz" => wp_strip_all_tags($fields->post_content),
	"performance_description_en" => wp_strip_all_tags($fields->post_content),
	"promoted" => $promoted,
	"approved" => $approved // "publish" in the table is for events, not artists
 );
 
 
 return get_post_meta( $post_id);
}

function create_api_posts_meta_field() {
 // register_rest_field ( 'name-of-post-type', 'name-of-field-to-return', array-of-callbacks-and-schema() )
 register_rest_field( 'tha_artists', 'thajson', array('get_callback' => 'get_post_meta_for_api','schema' => null));
}

add_action( 'rest_api_init', 'create_api_posts_meta_field' );






?>