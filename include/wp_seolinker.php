<?php

// class

class wp_seolinker extends wp_seolinker_plugin {

	function _init(){
		
		if ( $this->settings->user ) {
			DEFINE(SEOLINK_USER,$this->settings->user);
			@include_once(plugin_dir_path(__FILE__).SEOLINK_USER.'/seolink.php');
		}
		
		// check if plugin ready
		if ( !class_exists('SeolinkClient') ) return;
		
		// init
		$this->_seolink_init();
		$this->_seolink_shortcode();
	}


	function _seolink_init(){

		global $seolink;

		$options = array(
			'sl_charset' => get_bloginfo('charset'),
		);

		if ( $this->settings->debug ) $options += array(
			'sl_force_show_code' => true,
			'sl_verbose' => true,
		);

		$seolink = new SeolinkClient( $options );
	}

	
	function _seolink_shortcode(){
		add_filter( 'widget_text', 'do_shortcode' );
		add_filter( 'the_excerpt', 'do_shortcode' );
		add_shortcode('seolink', array( $this, 'wp_seolinker_shortcode') );
	}

	
	function wp_seolinker_shortcode( $args ){
		global $seolink;
		$text = '';
		while ($link = $seolink->return_links( 1 )) {
			$text.= '<li class="cat-item">'.$link.'</li>';
			if (strpos($link,'left_links_count=0')!==FALSE||strpos($link,'left_links_count')===FALSE) break;
		
		}
		
		return (bool)$text?'<ul>'.$text.'</ul>' : $text;
	}

	// OTHER

	

	function _register_scripts(){
		if ( !$this->settings->disable_css ) wp_enqueue_style( $this->id, plugins_url('style.css', _WP_SEOLINKER ) );
	}

}

// init class

$wp_seolinker = new wp_seolinker;
