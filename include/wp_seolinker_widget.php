<?php
// class

class wp_seolinker_widget extends WP_Widget {

	function __construct() {

		$this->WP_Widget('wp-seolinker', 'WP SeoLinker', array(
			'classname'		=> 'wp-seolinker-swidget',
			'description'	=> 'Zobrazte odkazy SeoLinker na stránce.'
		));
	}

	function form( $data ){

		extract( wp_parse_args( (array) $data, array( 'title' => '' ) ) );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Nadpis:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	function update( $data, $old_data ) {

		$data['title'] = strip_tags( $data['title'] );
		$data['count'] = (int) $data['count'];

		return $data;
	}

	function widget( $args, $data ) {

		// check if shortcode exists
		if ( !shortcode_exists('seolink') ) {
			echo "\n\n<!-- Je nutné plugin aktivovat. Navštivte stránku nastavení. -->\n\n";
			return;
		}

		extract($args);

		$text = '';
		
		$text .= $data['title'] ? $before_title . apply_filters( 'widget_title', $data['title'] ) . $after_title : '';
		$shortcode = ''; foreach( $data as $k => $v ) $shortcode .= " $k=\"$v\""; $shortcode = "[seolink$shortcode]";
		$text .= $shortcode = do_shortcode( $shortcode );
		echo (bool)$shortcode ? $before_widget.$text.$after_widget : '';
	}
}

// init class

add_action('widgets_init', create_function('', "register_widget('wp_seolinker_widget');" ) );
