<?php
/*
Plugin Name: Tha Events Type
Plugin URI: http://www.webisti.cz
Description: Declares a plugin that will create a custom post type displaying events.
Version: 1.0
Author: Fanky
Author URI: http://www.webisti.cz
License: GPLv2
*/


function create_tha_event() {
    register_post_type( 'tha_events',
        array(
            'labels' => array(
                'name' => 'Tha Events',
                'singular_name' => 'Tha Event',
                'add_new' => 'Add New',
                'add_new_item' => 'Add Tha New Event',
                'edit' => 'Edit',
                'edit_item' => 'Edit Tha Event',
                'new_item' => 'Make Tha New Event',
                'view' => 'View',
                'view_item' => 'View Tha Event',
                'search_items' => 'Search Tha Events',
                'not_found' => 'No Events found',
                'not_found_in_trash' => 'No Events found in Trash',
                'parent' => 'Tha Parent Event'
            ),
 
            'public' => true,
			'rewrite' => true,
            'menu_position' => 7,
			//'rewrite' => array('slug' => 'events','with_front' => false),
            'show_in_rest' => true,
            'show_ui' => false,
			'supports' => array( 'title','revisions'),
			'hierarchical' => true,
            'taxonomies' => array('category'),
            'menu_icon' => plugins_url( 'img/events.png', __FILE__ ),
            'has_archive' => true
        )
    );	
}

add_action( 'init', 'create_tha_event' );

function add_tha_events_columns($columns) {
    return array_merge($columns, 
              array('thaartistid' => __('Performer'), /*Artist for some reason changest to "Autor"*/
                    'thastageid' =>__( 'Stage')));
}
add_filter('manage_tha_events_posts_columns' , 'add_tha_events_columns');


function fill_tha_events_type_columns( $column, $post_id ) {
		// Fill in the columns with meta box info associated with each post
	switch ( $column ) {
	case 'thaartistid' :
		$thisid=get_field( 'umelec', $post_id );
		if(!$thisid) echo "not selected";
		else echo "<a href='/wp-admin/post.php?post=".$thisid."&action=edit'>".get_the_title($thisid)."</a>";
		break;
	case 'thastageid' :
		$thisid=get_field( 'stage', $post_id );
		if(!$thisid) echo "not selected";
		else echo "<a href='/wp-admin/post.php?post=".$thisid."&action=edit'>".get_the_title($thisid)."</a>";
		break;
	case 'post_id' :
		echo $post_id; 
			break;
		}
}
// this fills in the columns that were created with each individual post's value
add_action( 'manage_tha_events_posts_custom_column' , 'fill_tha_events_type_columns', 10, 2 );




?>