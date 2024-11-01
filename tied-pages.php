<?php
namespace tied_pages;
/**
 * Plugin Name: Tied-Pages
 * Description: Tie a page to a master page to adopt the master content. If you change the master page, the changes are automatically applied to the tied page as well 
 * Author: Picture-Planet GmbH
 * Author URI: https://www.picture-planet.ch
 * Version: 0.2.0
 * Requires at least: 5.6
 * Requires PHP: 7.3
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tied-pages
 * Domain Path: /languages
 *
 *
 */
require( 'includes/base_class.php' );

/**
 * Summary of Tiedpages_Tied_Pages
 */
class Tiedpages_Tied_Pages extends Tiedpages_Base {

	private $wp_tp_controller;			//
	private $wp_tp_admin_page_edit;		//

	public function __construct() {

		require( 'includes/core_controller_class.php' );
		require( 'includes/admin_page_edit_class.php' );

		// Functions that are executed during the first activation
		register_activation_hook( __FILE__, array( &$this, 'tied_pages_plugin_activate' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'tied_pages_plugin_deactivate' ) );

		add_action( 'init', array( &$this, 'tied_pages_plugin_init' ) );
		add_action( 'enqueue_block_editor_assets', array( &$this, 'tp_block_editor_reload_enqueue' ) );

		// instantiate Plugin classes
		$this->wp_tp_controller = new Tiedpages_Admin_Page_Edit( plugin_dir_url( __FILE__ ) );
		$this->tp_controller = new Tiedpages_Controller();
	}

	public function tied_pages_plugin_activate() {
		add_option( 'tied-pages-version', '0.1' );
	}

	public function tied_pages_plugin_deactivate() {
		delete_option( 'tied-pages-version' );
	}

	/**
	 * Summary of tp_block_editor_reload_enqueue
	 */
	public function tp_block_editor_reload_enqueue() {
		wp_enqueue_script( 'tp_block_editor_reload-script',
			plugins_url( 'js/tp_block_editor_reload.js', __FILE__ ),
			array( 'wp-blocks' )
		);

		// the translations to be used inside the js-file
		$arr_js_translation = array(
			'notice_title' => __( 'Tied-Page info', 'tied-pages' ),
			'view_master_page' => __( 'View master page', 'tied-pages' ),
			'view_tied_page_menu' => __( 'View tied-page menu', 'tied-pages' )
		);

		wp_localize_script( 'tp_block_editor_reload-script', 'tp_translations', $arr_js_translation );
	}

	public function tied_pages_plugin_init() {
		load_plugin_textdomain( 'tied-pages' );
	}

} // End Class

new Tiedpages_Tied_Pages();

