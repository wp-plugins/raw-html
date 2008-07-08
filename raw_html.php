<?php
/*
Plugin Name: Raw HTML capability
Plugin URI: http://w-shadow.com/blog/2007/12/13/raw-html-in-wordpress/
Description: Lets you enter raw HTML in your posts. See website for details.
Version: 1.0.5
Author: Janis Elsts
Author URI: http://w-shadow.com/blog/
*/

/*
Created by Janis Elsts (email : whiteshadow@w-shadow.com) 
It's GPL.
*/

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
?>