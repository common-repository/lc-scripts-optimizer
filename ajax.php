<?php

////////////////////////////////////////////////
////// EMPTY SCRIPTS CACHE /////////////////////
////////////////////////////////////////////////

function lcso_empty_scripts_cache() {
	if (!isset($_POST['lcso_nonce']) || !wp_verify_nonce($_POST['lcso_nonce'], 'lcso_ajax')) {die('Cheating?');};
	
	
	lcso_engine_start();
	$engine = new lcso_engine('js', array(), '', $GLOBALS['lcso_basedir'], $GLOBALS['lcso_baseurl']);
	
	// check folder existence
	if(!$engine->can_write()) {
		die( __('No server permissions to manage files', 'lcso_ml') );	
	}
	elseif(!$engine->cache_folder_check()) {
		die( __("Can't find cache folder", 'lcso_ml') );	
	}
	

	// delete files
	$files = (array)@glob($engine->basedir .'/'. $engine->cache_folder.'/*');

	foreach($files as $file){
		if(is_file($file)) {
			@unlink($file);
		}
	}
	
	die('success');
}
add_action('wp_ajax_lcso_empty_scripts_cache', 'lcso_empty_scripts_cache');







////////////////////////////////////////////////
////// STORE QUEUED SCRIPTS/STYLES /////////////
////////////////////////////////////////////////

function lcso_store_queued_scripts() {
	if (!isset($_POST['lcso_nonce']) || !wp_verify_nonce($_POST['lcso_nonce'], 'lcso_ajax')) {die('Cheating?');};
	
	$args = array(
		'timeout'     => 3,
		'redirection' => 1,
	); 
	$response = wp_remote_get( get_home_url() .'?lcso_store_scripts', $args);
	
	if(is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
		die( __('Error calling homepage', 'lcso_ml') );
	}
	else {
		die('success');
	}
}
add_action('wp_ajax_lcso_store_queued_scripts', 'lcso_store_queued_scripts');