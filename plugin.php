<?php
/* 
Plugin Name: WP-SeoLinker 
Plugin URI: http://seolinker.cz
Description: Vydělávejte se systémem SeoLinker.  
Version: 0.1 
Author: SeoLinker.cz
Author URI: http://seolinker.cz 
*/

// DEFINE
define( '_WP_SEOLINKER', __FILE__ );
define( '_WP_SEOLINKER_DIR', dirname( __FILE__ ) );
define( '_WP_SEOLINKER_VER', '0.1' );

// LIBRARY
require( _WP_SEOLINKER_DIR.'/library/settings.php' );
require( _WP_SEOLINKER_DIR.'/library/plugin.php' );

// INCLUDE
require( _WP_SEOLINKER_DIR.'/include/wp_seolinker.php' );
require( _WP_SEOLINKER_DIR.'/include/wp_seolinker_widget.php' );
require( _WP_SEOLINKER_DIR.'/include/plugin_admin.php' );
