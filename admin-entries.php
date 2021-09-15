<?php
/*
** CISE Ph.D. Student Job Listing admin panel customizations
**
*/


/*Add in custom columns in the admin panel*/
add_filter( 'manage_edit-phd-student-job-listing_columns', 'phdstudjobmkt_columns' ) ;

function phdstudjobmkt_columns( $columns ) {

	$columns = array(
		'cb' => '&lt;input type="checkbox" />',
		'name' => __( 'Name' ),
		'email' => __( 'Email' ),
		'advisor' => __( 'Advisor' ),
		'photo' => __( 'Photo' ),
		'date' => __( 'Date' )		
	);

	return $columns;
}

add_action( 'manage_phd-student-job-listing_posts_custom_column', 'manage_phdstudjobmkt_columns', 10, 2 );

/*Pull in data for the custom columns*/
function manage_phdstudjobmkt_columns( $column, $post_id ) {
	global $post;

	switch( $column ) {

		/* If displaying the 'name' column. */
		case 'name' :

			/* Get the post meta. */
			$name = get_post_meta( $post_id, 'psjm_name', true );

			/* Display the post meta. */
			printf( $name );

			break;

		/* If displaying the 'email' column. */
		case 'email' :

			/* Get the post meta. */
			$email = get_post_meta( $post_id, 'psjm_email', true );

			/* Display the post meta. */
			printf( $email );

			break;			

		/* If displaying the 'advisor' column. */
		case 'advisor' :

			/* Get the post meta. */
			$advisor = get_post_meta( $post_id, 'psjm_advisor', true );

			/* Display the post meta. */
			printf( $advisor );

			break;	
			
		/* If displaying the 'photo' column. */
		case 'photo' :

			/* Get the post meta. */
			$photo = get_post_meta( $post_id, 'psjm_photo', true );

			/* Display the post meta. */
			printf( '<a href="' . $photo . '">Photo</a>');

			break;			

		/* Just break out of the switch statement for everything else. */
		default :
			break;
	}
}

//Make columns sortable in the Admin Edit panel
add_filter( 'manage_edit-phd-student-job-listing_sortable_columns', 'phdstudjobmkt_sortable_columns' ) ;

function phdstudjobmkt_sortable_columns( $columns ) {

	$columns['name'] = 'Name';
	$columns['advisor'] = 'Advisor';

	return $columns;
}

// Only run our customization on the 'edit.php' page in the admin.
add_action( 'load-edit.php', 'my_edit_phdstudjobmkt_load' );

function my_edit_phdstudjobmkt_load() {
	add_filter( 'request', 'my_sort_phdstudjobmkt' );
}

// Sorts the custom phdstudjobmkt columns.
function my_sort_phdstudjobmkt( $vars ) {

	/* Check if we're viewing the 'phd-student-job-listing' post type. */
	if ( isset( $vars['post_type'] ) && 'phd-student-job-listing' == $vars['post_type'] ) {

		/* Check if 'orderby' is set to 'name'. */
		if ( isset( $vars['orderby'] ) && 'Name' == $vars['orderby'] ) {

			/* Merge the query vars with our custom variables. */
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => 'psjm_name',
					'orderby' => 'meta_value'
				)
			);
		}

		/* Check if 'orderby' is set to 'advisor'. */
		if ( isset( $vars['orderby'] ) && 'Advisor' == $vars['orderby'] ) {

			/* Merge the query vars with our custom variables. */
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => 'psjm_advisor',
					'orderby' => 'meta_value'
				)
			);
		}
	}

	return $vars;
}

//Customize the search of admin panel edit page
add_filter( 'posts_join', 'phdstudjobmkt_search' );
function phdstudjobmkt_search ( $join ) {
    global $pagenow, $wpdb;

    // I want the filter only when performing a search on edit page of Custom Post Type named "phd-student-job-listing".
    if ( is_admin() && 'edit.php' === $pagenow && 'phd-student-job-listing' === $_GET['post_type'] && ! empty( $_GET['s'] ) ) {    
        $join .= 'LEFT JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
    }
    return $join;
}

add_filter( 'posts_where', 'phdstudjobmkt_search_where' );
function phdstudjobmkt_search_where( $where ) {
    global $pagenow, $wpdb;

    // I want the filter only when performing a search on edit page of Custom Post Type named "phd-student-job-listing".
    if ( is_admin() && 'edit.php' === $pagenow && 'phd-student-job-listing' === $_GET['post_type'] && ! empty( $_GET['s'] ) ) {
        $where = preg_replace(
            "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
            "(" . $wpdb->posts . ".post_title LIKE $1) OR (" . $wpdb->postmeta . ".meta_value LIKE $1)", $where );
    }
    return $where;
}

function phdstudjobmkt_search_distinct( $where ){
    global $pagenow, $wpdb;

    if ( is_admin() && $pagenow=='edit.php' && $_GET['post_type']=='phd-student-job-listing' && $_GET['s'] != '') {
    return "DISTINCT";

    }
    return $where;
}
add_filter( 'posts_distinct', 'phdstudjobmkt_search_distinct' );
//Ends search of admin panel edit page