<?php

class wp_seolinker_settings {

	public $id = 'wp-seolinker';
	public $data = array();

	public function __construct(){

		register_activation_hook( _WP_SEOLINKER, array( $this, '_create_data' ) );
		register_deactivation_hook( _WP_SEOLINKER, array( $this, '_remove_data' ) );

		$this->_get_data();
	}

	public function save( $data = array() ){

		$this->data = array_merge( (array)$this->data, $data );
		update_option( $this->id, $this->data );
	}

	private function _get_data(){

		$this->data = get_option( $this->id );
		if ( !$this->data ) $this->_create_data();
		if ( $this->data['ver'] != _WP_SEOLINKER_VER ) $this->_upgrade_data();
	}

	public function _create_data(){

		$this->save( array(
			'ver'					=> _WP_SEOLINKER_VER,
			'user'					=> '',
		));
	}

	public function _remove_data(){

		$this->data = array();
		delete_option( $this->id );
	}

	private function _upgrade_data(){

		$this->save( array(
			'ver'					=> _WP_SEOLINKER_VER,
		));
	}

	public function __get( $name ) {

		return isset( $this->data[ $name ] ) ? $this->data[ $name ] : FALSE;
	}
}