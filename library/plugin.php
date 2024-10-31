<?php

class wp_seolinker_plugin {

	protected
		$id = 'wp-seolinker',
		$settings;

	public
		$error = FALSE;

	public function __construct(){
		if ( FALSE == ( $this->settings = &$GLOBALS['wp-seolinker'] [$this->id] ['settings'] ) )
			$this->settings = $GLOBALS['wp-seolinker'] [$this->id] ['settings'] = new wp_seolinker_settings;

		add_action('init', array( $this, '_init') );
		add_action('wp_head', array( $this, '_wp_head') );
		add_action('wp_footer', array( $this, '_wp_footer') );
		add_action('wp_enqueue_scripts', array( $this, '_register_scripts') );
	}

	public function _init(){
	}

	public function _wp_head(){
	}

	public function _wp_footer(){
	}

	public function _register_scripts(){
	}

	public function _error( $message ){
		$this->error []= $message;
	}
}