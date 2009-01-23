<?php
/*
Plugin Name: Raw HTML capability
Plugin URI: http://w-shadow.com/blog/2007/12/13/raw-html-in-wordpress/
Description: Lets you enter raw HTML in your posts. You can also enable/disable smart quotes and other automatic formatting on a per-post basis.
Version: 1.1
Author: Janis Elsts
Author URI: http://w-shadow.com/blog/
*/

/*
Created by Janis Elsts (email : whiteshadow@w-shadow.com) 
It's GPL.
*/

/**********************************************
	Filter inline blocks of raw HTML
***********************************************/
global $wsh_raw_parts;
$wsh_raw_parts=array();

function wsh_extraction_callback($matches){
	global $wsh_raw_parts;
	$wsh_raw_parts[]=$matches[2];
	return "!RAWBLOCK".(count($wsh_raw_parts)-1)."!";
}

function wsh_extract_exclusions($text){
	global $wsh_raw_parts;
	return preg_replace_callback("/(<!--\s*start_raw\s*-->|\[RAW\])(.*?)(<!--\s*end_raw\s*-->|\[\/RAW\])/Uis", 
		"wsh_extraction_callback", $text);
}

function wsh_insertion_callback($matches){
	global $wsh_raw_parts;
	return $wsh_raw_parts[intval($matches[1])];
}

function wsh_insert_exclusions($text){
	global $wsh_raw_parts;
	if(!isset($wsh_raw_parts)) return $text;
	return preg_replace_callback("/!RAWBLOCK(\d+)!/Uis", "wsh_insertion_callback", $text);		
}

add_filter('the_content', 'wsh_extract_exclusions', 0);
add_filter('the_content', 'wsh_insert_exclusions', 1001);

/*****************************************************
	Disable default formatting on a per-post level
******************************************************/

//Apply function $func to $content unless it's been disabled for the current post 
function maybe_use_filter($func, $content){
	global $post;
	if (get_post_meta($post->ID, 'disable_'.$func, true) == '1') {
		return $content;
	} else {
		return $func($content);
	}
}

//Stub filters that replace the WP defaults
function maybe_wptexturize($content){
	return maybe_use_filter('wptexturize', $content);
}

function maybe_wpautop($content){
	return maybe_use_filter('wpautop', $content);
}

function maybe_convert_chars($content){
	return maybe_use_filter('convert_chars', $content);
}

function maybe_convert_smilies($content){
	return maybe_use_filter('convert_smilies', $content);
}

// disable default filters
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');
remove_filter('the_content', 'convert_chars');
remove_filter('the_content', 'convert_smilies');
remove_filter('the_excerpt', 'wpautop');
remove_filter('the_excerpt', 'wptexturize');
remove_filter('the_excerpt', 'convert_chars');
remove_filter('the_excerpt', 'convert_smilies');

// add our conditional filters
add_filter('the_content', 'maybe_wptexturize');
add_filter('the_content', 'maybe_wpautop');
add_filter('the_content', 'maybe_convert_chars');
add_filter('the_content', 'maybe_convert_smilies');
add_filter('the_excerpt', 'maybe_wptexturize');
add_filter('the_excerpt', 'maybe_wpautop');
add_filter('the_excerpt', 'maybe_convert_chars');
add_filter('the_excerpt', 'maybe_convert_smilies');

// Add a custom meta box for per-post settings 
add_action('admin_menu', 'rawhtml_add_custom_box');
add_action('save_post', 'rawhtml_save_postdata');

/* Adds a custom section to the "advanced" Post and Page edit screens */
function rawhtml_add_custom_box() {
  //WP 2.5+
  if( function_exists( 'add_meta_box' )) { 
  	foreach( array('post', 'page') as $type ) {
	    add_meta_box( 'rawhtml_meta_box', 'Raw HTML', 'rawhtml_meta_box', $type, 'side' );
    }
  }
}

/* Displays the custom box */
function rawhtml_meta_box(){
	global $post;
	// Use nonce for verification
	echo '<input type="hidden" name="rawhtml_nonce" id="rawhtml_nonce" value="' . 
	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	//Output checkboxes 
	$fields = array(
		'disable_wptexturize' => 'Disable wptexturize',
		'disable_wpautop' => 'Disable automatic paragraphs',
		'disable_convert_chars' => 'Disable convert_chars',
		'disable_convert_smilies' => 'Disable smilies',
	 );
	foreach($fields as $field => $legend){
?>
<label for="rawhtml_<?php echo $field; ?>">
	<input type="checkbox" name="rawhtml_<?php echo $field; ?>" id="rawhtml_<?php echo $field; ?>" <?php
		if (get_post_meta($post->ID, $field, true) == '1')
			echo ' checked="checked"';
	?>/>
	<?php echo $legend; ?>
</label>
<br /> 
<?php
	}
}

/* Saves post metadata */
function rawhtml_save_postdata( $post_id ){
  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  
  if ( !wp_verify_nonce( $_POST['rawhtml_nonce'], plugin_basename(__FILE__) )) {
    return $post_id;
  }

  if ( 'page' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_page', $post_id ))
      return $post_id;
  } else {
    if ( !current_user_can( 'edit_post', $post_id ))
      return $post_id;
  }
  
  // OK, we're authenticated: we need to find and save the data
  $fields  = array('disable_wpautop', 'disable_wptexturize', 'disable_convert_chars', 'disable_convert_smilies');
  foreach ( $fields as $field ){	
  	  if ( !empty($_POST['rawhtml_'.$field]) ){
		update_post_meta($post_id, $field, '1');
	  } else {
		delete_post_meta($post_id, $field);
	  };
  }

  return true;
}
?>