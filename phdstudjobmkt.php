<?php
/*
Plugin Name: HWCOE Ph.D. Student Job Market Listing
Description: This plugin allows admin to display a dynamic list of entries using the Ph.D. Student custom_post_type. Use this shortcode to display the table: <strong>[phd-student-job-listing]</strong>.
Requirements: Advanced Custom Fields with the Ph.D. Students on the Job Market field group; hwcoe-ufl theme or hwcoe-ufl-child theme; Gravity Forms with the Ph.D. Students on the Job Market form and Gravity Forms + Custom Post Types plugin. 
Version: 1.0
Author: Allison Logan
Author URI: http://allisoncandreva.com/
*/

function psjm_create_post_type() {
  register_post_type( 'phdstudjobmkt',
    array(
      'labels' => array(
        'name' => __( 'Ph.D. Student Entries' ), //Top of page when in post type
        'singular_name' => __( 'Entry' ), //per post
		'menu_name' => __('Ph.D. Students'), //Shows up on side menu
		'all_items' => __('All Entries'), //On side menu as name of all items
      ),
      'public' => true,
	  'menu_position' => 6,
	  'menu_icon' => 'dashicons-id-alt',
      'has_archive' => true,
    )
  );
}
add_action( 'init', 'psjm_create_post_type' );

/* Enqueue assets */
add_action( 'wp_enqueue_scripts', 'phdstudjobmkt_assets' );
function phdstudjobmkt_assets() {
    wp_register_style( 'phdstudjobmkt', plugins_url( '/css/phdstudjobmkt.css' , __FILE__ ) );

    //wp_register_script( 'phdstudjobmkt', plugins_url( '/js/phdstudjobmkt.js' , __FILE__ ), array( 'jquery' ), null, true );
}

if( is_admin() ){
    include( 'admin-entries.php' );
}

/*Convert Name field to Title Case*/
$theformID = RGFormsModel::get_form_id('Ph.D. Students on the Job Market');
//$thefieldID = RGFormsModel::get_field($theformID, 'name_first');

add_action('gform_pre_submission', 'psjm_titlecase_fields');
function psjm_titlecase_fields($form){
	// add all the field IDs you want to titlecase, to this array
	$form  = GFAPI::get_form( $theformID );
	$fields_to_titlecase = array(
						'input_1_3',
						'input_1_6');
	foreach ($fields_to_titlecase as $each) {
			// for each field, convert the submitted value to lowercase and then title case and assign back to the POST variable
			// the rgpost function strips slashes
			$lowercase = strtolower(rgpost($each));
			$_POST[$each] = ucwords($lowercase);
		} 
	// return the form, even though we did not modify it
	return $form;
}//end field titlecaseing

/**
 * Gravity Wiz // Gravity Forms // Rename Uploaded Files
 *
 * Rename uploaded files for Gravity Forms. 
 *
 * @version   2.3
 * @author    David Smith <david@gravitywiz.com>
 * @license   GPL-2.0+
 * @link      http://gravitywiz.com/rename-uploaded-files-for-gravity-form/
 */
class PSJM_Rename_Uploaded_Files {

	public function __construct( $args = array() ) {

		// set our default arguments, parse against the provided arguments, and store for use throughout the class
		$this->_args = wp_parse_args( $args, array(
			'form_id'  => false,
			'field_id' => false,
			'template' => ''
		) );

		// do version check in the init to make sure if GF is going to be loaded, it is already loaded
		add_action( 'init', array( $this, 'init' ) );

	}

	public function init() {

		// make sure we're running the required minimum version of Gravity Forms
		if( ! is_callable( array( 'GFFormsModel', 'get_physical_file_path' ) ) ) {
			return;
		}

		add_filter( 'gform_entry_post_save', array( $this, 'rename_uploaded_files' ), 9, 2 );
		add_filter( 'gform_entry_post_save', array( $this, 'stash_uploaded_files' ), 99, 2 );

		add_action( 'gform_after_update_entry', array( $this, 'rename_uploaded_files_after_update' ), 9, 2 );
		add_action( 'gform_after_update_entry', array( $this, 'stash_uploaded_files_after_update' ), 99, 2 );

	}

	function rename_uploaded_files( $entry, $form ) {

		if( ! $this->is_applicable_form( $form ) ) {
			return $entry;
		}

		foreach( $form['fields'] as &$field ) {

			if( ! $this->is_applicable_field( $field ) ) {
				continue;
			}

			$uploaded_files = rgar( $entry, $field->id );

			if( empty( $uploaded_files ) ) {
				continue;
			}

			$uploaded_files = $this->parse_files( $uploaded_files, $field );
			$stashed_files  = $this->parse_files( gform_get_meta( $entry['id'], 'gprf_stashed_files' ), $field );
			$renamed_files  = array();

			foreach( $uploaded_files as $_file ) {

				// Don't rename the same files twice.
				if( in_array( $_file, $stashed_files ) ) {
					$renamed_files[] = $_file;
					continue;
				}

				$dir  = wp_upload_dir();
				$dir  = $this->get_upload_dir( $form['id'] );
				$file = str_replace( $dir['url'], $dir['path'], $_file );

				if( ! file_exists( $file ) ) {
					continue;
				}

				$renamed_file = $this->rename_file( $file, $entry );

				if ( ! is_dir( dirname( $renamed_file ) ) ) {
					wp_mkdir_p( dirname( $renamed_file ) );
				}

				$result = rename( $file, $renamed_file );

				$renamed_files[] = $this->get_url_by_path( $renamed_file, $form['id'] );

			}

			// In cases where 3rd party add-ons offload the image to a remote location, no images can be renamed.
			if( empty( $renamed_files ) ) {
				continue;
			}

			if( $field->get_input_type() == 'post_image' ) {
				$value = str_replace( $uploaded_files[0], $renamed_files[0], rgar( $entry, $field->id ) );
			} else if( $field->multipleFiles ) {
				$value = json_encode( $renamed_files );
			} else {
				$value = $renamed_files[0];
			}

			GFAPI::update_entry_field( $entry['id'], $field->id, $value );

			$entry[ $field->id ] = $value;

		}

		return $entry;
	}

	function get_upload_dir( $form_id ) {
		$dir = GFFormsModel::get_file_upload_path( $form_id, 'PLACEHOLDER' );
		$dir['path'] = dirname( $dir['path'] );
		$dir['url']  = dirname( $dir['url'] );
		return $dir;
	}

	function rename_uploaded_files_after_update( $form, $entry_id ) {
		$entry = GFAPI::get_entry( $entry_id );
		$this->rename_uploaded_files( $entry, $form );
	}

	/**
	 * Stash the "final" version of the files after other add-ons have had a chance to interact with them.
	 *
	 * @param $entry
	 * @param $form
	 */
	function stash_uploaded_files( $entry, $form ) {

		foreach ( $form['fields'] as &$field ) {

			if ( ! $this->is_applicable_field( $field ) ) {
				continue;
			}

			$uploaded_files = rgar( $entry, $field->id );
			gform_update_meta( $entry['id'], 'gprf_stashed_files', $uploaded_files );

		}

		return $entry;
	}

	function stash_uploaded_files_after_update( $form, $entry_id ) {
		$entry = GFAPI::get_entry( $entry_id );
		$this->stash_uploaded_files( $entry, $form );
	}

	function rename_file( $file, $entry ) {

		$new_file = $this->get_template_value( $this->_args['template'], $file, $entry );
		$new_file = $this->increment_file( $new_file );

		return $new_file;
	}

	function increment_file( $file ) {

		$file_path = GFFormsModel::get_physical_file_path( $file );
		$pathinfo  = pathinfo( $file_path );
		$counter   = 1;

		// increment the filename if it already exists (i.e. balloons.jpg, balloons1.jpg, balloons2.jpg)
		while ( file_exists( $file_path ) ) {
			$file_path = str_replace( ".{$pathinfo['extension']}", "{$counter}.{$pathinfo['extension']}", GFFormsModel::get_physical_file_path( $file ) );
			$counter++;
		}

		$file = str_replace( basename( $file ), basename( $file_path ), $file );

		return $file;
	}

	function is_path( $filename ) {
		return strpos( $filename, '/' ) !== false;
	}

	function get_template_value( $template, $file, $entry ) {

		$info = pathinfo( $file );

		if( strpos( $template, '/' ) === 0 ) {
			$dir      = wp_upload_dir();
			$template = $dir['basedir'] . $template;
		} else {
			$template = $info['dirname'] . '/' . $template;
		}
		
		// removes the original file name - Added by Allison Logan
		$newname = str_replace($info['filename'], "", $info['filename']);
		
		// replace our custom "{filename}" psuedo-merge-tag
		$value = str_replace( '{filename}', $newname, $template );

		// replace merge tags
		$form  = GFAPI::get_form( $entry['form_id'] );
		$value = GFCommon::replace_variables( $value, $form, $entry, false, true, false, 'text' );

		// make sure filename is "clean"
		$filename = $this->clean( basename( $value ) );
		$value    = str_replace( basename( $value ), $filename, $value );

		// append our file ext
		$value .= '.' . $info['extension'];

		return $value;
	}

	function remove_slashes( $value ) {
		return stripslashes( str_replace( '/', '', $value ) );
	}
	
	function is_applicable_form( $form ) {

		$form_id = isset( $form['id'] ) ? $form['id'] : $form;

		return $form_id == $this->_args['form_id'];
	}

	function is_applicable_field( $field ) {

		$is_file_upload_field   = in_array( GFFormsModel::get_input_type( $field ), array( 'fileupload', 'post_image' ) );
		$is_applicable_field_id = $this->_args['field_id'] ? $field['id'] == $this->_args['field_id'] : true;

		return $is_file_upload_field && $is_applicable_field_id;
	}

	function clean( $str ) {
		return $this->remove_slashes( sanitize_title_with_dashes( strtr(
			utf8_decode( $str ),
			utf8_decode( 'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
			'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
		), 'save' ) );
	}

	function get_url_by_path( $file, $form_id ) {

		$dir = $this->get_upload_dir( $form_id );
		$url = str_replace( $dir['path'], $dir['url'], $file );

		return $url;
	}

	function parse_files( $files, $field ) {

		if( empty( $files ) ) {
			return array();
		}

		if( $field->get_input_type() == 'post_image' ) {
			$file_bits = explode( '|:|', $files );
			$files = array( $file_bits[0] );
		} else if( $field->multipleFiles ) {
			$files = json_decode( $files );
		} else {
			$files = array( $files );
		}

		return $files;
	}

}

# Configuration

new PSJM_Rename_Uploaded_Files( array(
	'form_id' => $theformID,
	'field_id' => 14,
	'template' => '{Name (First):1.3}_{Name (Last):1.6}'
) ); //end file renaming 


/*Plugin shortcode*/
function phdstudjobmkt_shortcode() {

	// Assets 
    wp_enqueue_style( 'phdstudjobmkt' );
    //wp_enqueue_script( 'phdstudjobmkt' );
	
	//Query
	$the_query = new WP_Query(array( 'post_type' => 'phdstudjobmkt', 'posts_per_page' => -1, 'orderby' => 'title', 'order'   => 'ASC' ));
	
	//Display Entries
	$output = '<div id="psjm-container">';
	
	while ( $the_query->have_posts() ) : $the_query->the_post();
	
			//$imageArray  = get_field( 'psjm_photo' );
			//$image       = esc_url($imageArray['sizes']['psjm_image']);

	
			$output .= '<div id="psjm-entry">
							<img class="psjm-image" src="' . get_field( 'psjm_photo' ) . '" alt="' .get_field( 'psjm_name' ).'" style="width: 240px; height: 240px; object-fit: cover;"/>
							<hr>
							<h5>' .get_field( 'psjm_name' ).'</h5>';
				if(get_field( 'psjm_website' )):  //if the field is not empty
					$output .= '<p><a href="mailto:' .get_field( 'psjm_email' ).'">' .get_field( 'psjm_email' ).'</a></br>
								<a href="' .get_field( 'psjm_website' ). '" target="_blank">Website</a></p>'; //display it
					else: 
					$output .= '<p><a href="mailto:' .get_field( 'psjm_email' ).'">' .get_field( 'psjm_email' ).'</a></p>';
					endif; 		
				$output .= '<p><strong>Areas of Expertise:</strong></br>' .get_field( 'psjm_areas_of_expertise' ). '</p>
							<p><strong>Dissertation Topic:</strong></br>' .get_field( 'psjm_dissertation_topic' ). '</p>';

				$string = get_field( 'psjm_preferred_employment' );
				$str = trim($string);
				$list = explode(" ", $str);

				if ( count($list) > 1 ) {
					$last = array_pop($list);
					$output .=  '<p><strong>Preferred Employment:</strong></br>' . implode(", ", $list) . " or {$last}" . '</p>';
				}
				else    {
					$output .= '<p><strong>Preferred Employment:</strong></br>' . $string .'</p>';
				}
					
				$advisor = get_field( 'psjm_advisor' );    
				$array = array("Dr.","Phd","Ph.D.","Ph.D.", ", ");
				$output .= 	'<p><strong>Advisor:</strong></br>' . str_ireplace($array, '', $advisor) . ', Ph.D.</p>
							<p><strong>Expected Date Available:</strong></br>' .get_field( 'psjm_date_available' ). '</p>';
			$output .= '</div>';
	endwhile;
	wp_reset_query();
	
	$output .= '</div>';
	
	//Return code
	return $output;
}

add_shortcode('phd-student-job-listing', 'phdstudjobmkt_shortcode'); 


// Add field groups for HWCOE Ph.D. Student Job Market Listing

add_filter('acf/settings/save_json', 'hwcoe_phdstudjobmkt_acf_json_save_point');

if (!function_exists('hwcoe_phdstudjobmkt_acf_json_save_point')) { 
	function hwcoe_phdstudjobmkt_acf_json_save_point( $path ) {
		// update path
		$paths[] = plugin_dir_path(__FILE__) . 'inc/acf-json';
		return $path; 
	}
}

add_filter('acf/settings/load_json', 'hwcoe_phdstudjobmkt_acf_json_load_point');

if (!function_exists('hwcoe_phdstudjobmkt_acf_json_load_point')) {
	function hwcoe_phdstudjobmkt_acf_json_load_point( $paths ) {	
		// remove original path (optional)
		unset($paths[0]);

		// append path
		$paths[] = plugin_dir_path(__FILE__) . 'inc/acf-json';
		
		// return
		return $paths;
	}
}