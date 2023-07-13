<?php 
/**
* Plugin Name: Switch Theme
* Plugin URI: https://github.com/mmainulhasan/wordpress
* Description: Switch theme based on mobile & web platform.
* Version: 1.0
* Author: Mohammad Mainul Hasan (moh.mainul.hasan@gmail.com)
* Author URI: https://github.com/mmainulhasan
**/


function switch_theme($host = false)
{

	$host = ($host) ? $host : str_replace('www.', '', $_SERVER['SERVER_NAME']);
	$themes = array(
	'm.examplesite.local' => array('mobile_theme','mobile_theme'),
	'examplesite.local' => array('web_theme','web_theme') );

	if (isset($themes[$host])){
		$themes = $themes[$host];
		switch_theme($themes[0],$themes[1]);
	}
}

add_action('setup_theme','switch_theme');


