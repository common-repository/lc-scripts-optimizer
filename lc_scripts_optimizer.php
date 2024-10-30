<?php
/* 
Plugin Name: LC Scripts Optimizer - by LCweb
Plugin URI: https://lcweb.it/
Description: Let's try optimizing scripts usage to speed-up your site!
Author: Luca Montanari (LCweb)
Version: 1.4.0
Author URI: https://lcweb.it/
*/  




/////////////////////////////////////////////
/////// MAIN DEFINES ////////////////////////
/////////////////////////////////////////////

// plugin path
$wp_plugin_dir = substr(plugin_dir_path(__FILE__), 0, -1);
define('LCSO_DIR', $wp_plugin_dir );

// plugin url
$wp_plugin_url = substr(plugin_dir_url(__FILE__), 0, -1);
define('LCSO_URL', $wp_plugin_url );




/////////////////////////////////////////////
/////// MULTILANGUAGE SUPPORT ///////////////
/////////////////////////////////////////////

function lcso_multilanguage() {
	if(!is_admin()) {return false;}
  
	$param_array = explode(DIRECTORY_SEPARATOR, LCSO_DIR);
	$folder_name = end($param_array);
  
	load_plugin_textdomain('lcso_ml', false, $folder_name . '/languages');  
}
add_action('init', 'lcso_multilanguage', 1);




/////////////////////////////////////////////
/////// MAIN SCRIPT & CSS INCLUDES //////////
/////////////////////////////////////////////

function lcso_scripts() { 
	wp_enqueue_script("jquery");
	wp_enqueue_style('lcso_admin', LCSO_URL .'/css/admin.css');
    
    wp_enqueue_script('lc-switch-v2', LCSO_URL .'/js/lc-switch/lc_switch.min.js', 200, '2.0.3', true);
}
add_action('admin_enqueue_scripts', 'lcso_scripts');




/////////////////////////////////////////////
/////// ENGINE START - GLOBALS DECLARATION //
/////////////////////////////////////////////
function lcso_engine_start() {
	include_once('classes/engine.php');
	
	$GLOBALS['lcso_opts'] = array(
		'no_import' 	=> get_option('lcso_no_css_import', false),
		'no_css_min' 	=> get_option('lcso_no_css_min', false),
		'min_js' 		=> array()
	);
	
	
	$wpul_dir = wp_upload_dir();
	
	$dir_arr = explode('/', $wpul_dir['basedir']);
	array_pop($dir_arr);
	$GLOBALS['lcso_basedir'] = implode('/', $dir_arr);
	
	$url_arr = explode('/', $wpul_dir['baseurl']);
	array_pop($url_arr);
	$GLOBALS['lcso_baseurl'] = implode('/', $url_arr);
}
add_action('init', 'lcso_engine_start');




//////////////////////////////////////////////////
///////// MENU AND INCLUDES //////////////////////
//////////////////////////////////////////////////

function lcso_settings_add_submenu() {	
	add_submenu_page('tools.php', 'LC Scripts Optimizer', 'LC Scripts Optimizer', 'install_plugins', 'lcso_settings', 'lcso_settings_include');
}
add_action('admin_menu', 'lcso_settings_add_submenu');

function lcso_settings_include() {
	include_once(LCSO_DIR .'/settings.php');	
}





// frontend actualization
include_once(LCSO_DIR . '/classes/frontend.php');


// HTML Minification
include_once(LCSO_DIR . '/classes/html_minifier.php');


// ajax operations
include_once(LCSO_DIR . '/ajax.php');

