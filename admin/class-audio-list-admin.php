<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://xjlin0.github.io
 * @since      1.0.0
 *
 * @package    Audio_List
 * @subpackage Audio_List/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Audio_List
 * @subpackage Audio_List/admin
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Audio_List_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
    error_log("Debug: class-audio-list-admin.php 51 running __construct()");
		$this->plugin_name = $plugin_name;
		$this->version = $version;
    add_action('admin_menu', array($this, 'add_plugin_menu_pages'));
    add_action('admin_post_audio_list_form_submit', array($this, 'process_audio_list_form_submission'));
    add_action('init', array($this, 'custom_rewrite_rules'));
	}


	public function add_plugin_menu_pages() {
	    error_log("Debug: class-audio-list-admin.php 60 running add_plugin_menu_pages()");
		  add_menu_page('Audio List', 'Audio List', 'manage_options', 'audio-list-admin', array($this, 'audio_list_admin_page'));
	}

	public function audio_list_admin_page() {   // Handle rendering of admin page here
		  error_log("Debug: class-audio-list-admin.php 65 running audio_list_admin_page()");
	    ?>
	    <div class="wrap">
	        <h1>Audio List</h1>
	        <button onclick="location.href='<?php echo admin_url('admin-post.php?action=create_audio'); ?>'">Create</button>
	        <button onclick="location.href='<?php echo admin_url('admin-post.php?action=select_audio'); ?>'">Select/Update</button>
		      <button onclick="location.href='<?php echo admin_url('admin.php?page=audio-list-logout-page'); ?>'">Logout</button>
	    </div>
	    <?php
	}

	public function custom_rewrite_rules() {
	    add_rewrite_rule('^audio-list-logout-page/?', 'index.php?custom_logout_page=true', 'top');
	}


	// Form submission handlers
	public function process_audio_list_form_submission() {  // Handle form submissions here
		  error_log("Debug: class-audio-list-admin.php 78 running process_audio_list_form_submission()");
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Audio_List_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Audio_List_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/audio-list-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Audio_List_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Audio_List_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/audio-list-admin.js', array( 'jquery' ), $this->version, false );

	}

}
