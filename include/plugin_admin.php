<?php

// for admin sessions only

if ( !is_admin() ) return;

// class

class wp_seolinker_admin extends wp_seolinker_plugin {

	function _init(){

		$this->_admin_message();

		add_filter( 'plugin_action_links_' . plugin_basename( _WP_SEOLINKER ), array( $this, '_admin_link' ) );
		add_action( 'admin_init', array( $this, '_admin_init' ) );
		add_action( 'admin_menu', array( $this, '_admin_menu' ) );
	}

	function _admin_page() {
		?>
		<div class="wrap">

			<?php screen_icon(); ?><h2>WP SeoLinker</h2>

			<div style="width: 65%; float: left;">

				<form method="post" action="options.php">

					<?php settings_fields( $this->id ); ?><?php do_settings_sections( $this->id ); ?>

					<input type="hidden" name="<?php echo $this->id?>[ver]" value="<?php echo $this->settings->ver?>" />

					<table class="form-table">

						<tr valign="top"><th scope="row">SeoLinker ID: </th><td>
							<input class="regular-text" type="text" name="<?php echo $this->id?>[user]" value="<?php echo $this->settings->user; ?>" />
						</td></tr>

						<tr valign="top"><th scope="row">Widget:</th><td>
							Použijte <a href="<?php echo admin_url('widgets.php');?>">WP-SEOLINKER widget</a> pro vložení odkazů na své stránky.
						</td></tr>
						
						<tr valign="top"><th scope="row">Cache odkazu:</th><td>
							<a href="<?php echo admin_url('options-general.php?page='.$this->id."&seolinkercache=clean"); ?>">Vyčistit cache</a>
						</td></tr>

					</table>

					<?php submit_button(); ?> 

				</form>

			</div>

			<?php $this->_widget_area(); ?>

		</div>
		<?php
	}

	function _widget_area(){
		?>
		<div style="width: 30%; float: right">

			<h3>Použití pluginu</h3>
			<p>Toto je BETA verze, v případě otázek či připomínek, nás prosím neváhejte kontaktovat. Vaše zpětná vazba napomůže zlepšení našeho systému. Kontaktovat nás můžete na <a href="http://seolinker.cz" target="_blank">SeoLinker.cz</a>.</p>

		</div>
		<?php
	}

	function _news_widget(){
		?>
		<style type="text/css">
			.news_widget a{
				font-size: 100%;
				line-height: 1.2;
				font-family: inherit;
			}
		</style>
		<h3>SeoLinker news</h3>
		<div class="news_widget">
		<?php
			wp_widget_rss_output( array(
				'link' => 'http://seolinker.cz/wp',
				'url' => 'http://seolinker.cz/wp/feed/',
				'title' => 'SeoLinker News',
				'items' => 4,
				'show_summary' => 0,
				'show_author' => 0,
				'show_date' => 0
			) );
		?>
		</div>
		<?php
	}

	// ADMIN MESSAGE

	function _admin_message(){

		global $seolink;

		add_action( 'admin_notices', array( $this, '_admin_notice') );
		
		if ( $_GET['seolinkercache']=='clean' ) {
			if (file_exists(plugin_dir_path(__FILE__).$this->settings->user.'/seolink.links.db')) unlink(plugin_dir_path(__FILE__).$this->settings->user.'/seolink.links.db');
			$this->_error( '<div class="updated"><p><b>WP-SEOLINKER</b>: Cache úspěšně vyčištěn.</p></div>' );
		}

		if ( !$this->settings->user ) {
			$this->_error( '<div class="updated"><p><b>WP-SEOLINKER</b>: Je nutné plugin aktivovat. Navštivte <a href="'.admin_url('options-general.php?page='.$this->id).'">stránku nastavení</a>.</p></div>' );
			return;
		}

		if ( !file_exists( $file = plugin_dir_path(__FILE__).'/'.$this->settings->user.'/seolink.php' ) ) {
			$oldmask = umask(0);
			mkdir(plugin_dir_path(__FILE__).$this->settings->user,0777);
			umask($oldmask);
			copy(plugin_dir_path(__FILE__).'seolink.php',plugin_dir_path(__FILE__).$this->settings->user.'/seolink.php');
			if ( !file_exists( $file = plugin_dir_path(__FILE__).'/'.$this->settings->user.'/seolink.php' ) ) {
				$this->_error( '<div class="error"><p><b>WP-SEOLINKER</b>: Není možné vytvořit složku <b>'.plugin_dir_path(__FILE__).'/'.$this->settings->user.'</b> a vložit do ní soubory <b>seolink.php</b>. Prosím kontaktujte <a href="http://seolinker.cz" target="_blank">podporu</a>.</div>' );
			}
			return;
		}
	}

	// OTHER

	function _admin_notice() {
		if ( $this->error ) foreach( $this->error as $message ) echo $message;
	}

	function _admin_link( $links ){
		return array_merge( array('<a href="'.admin_url('options-general.php?page='.$this->id).'">Settings</a>'), $links );
	}

	function _admin_init(){
		register_setting( $this->id, $this->id );
	}

	function _admin_menu(){
		add_options_page('WP-SEOLINKER settings', 'WP SeoLinker', 'manage_options', $this->id, array( $this, '_admin_page' ) );
	}
}

// init class

$wp_seolinker_admin = new wp_seolinker_admin;
