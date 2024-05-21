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
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'add_plugin_menu_pages'));
        add_action('admin_post_custom_audio_list_form_submit', array($this, 'process_audio_list_form_submission'));
        add_action('admin_notices', array($this, 'custom_admin_notice'));
    }

    public function add_plugin_menu_pages() {
        // Add main menu item
        add_menu_page(
            'Audio List',
            'Audio List',
            'manage_options',
            'audio-list-admin',
            array($this, 'audio_list_admin_page')
        );

        // Add submenu item under main menu item
        add_submenu_page(
            'audio-list-admin',
            'Add New Audio',
            'Add New Audio',
            'manage_options',
            'custom-audio-list',
            array($this, 'custom_audio_list_page')
        );
    }

    public function audio_list_admin_page() {
        ?>
        <div class="wrap">
            <h1>Audio List</h1>
            <?php if (get_transient('custom_audio_list_message')) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo get_transient('custom_audio_list_message'); ?></p>
                </div>
                <?php delete_transient('custom_audio_list_message'); ?>
            <?php endif; ?>
            <button onclick="location.href='<?php echo admin_url('admin.php?page=custom-audio-list'); ?>'">Create</button>
            <button onclick="location.href='<?php echo admin_url('admin-post.php?action=select_audio'); ?>'">Select/Update</button>
            <button onclick="location.href='<?php echo wp_logout_url(admin_url()); ?>'">Logout</button>
        </div>
        <?php
    }

    public function custom_audio_list_page() {
        ?>
        <div class="wrap">
            <h2>Add New Audio</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="custom_audio_list_form_submit">
                <!-- Form fields for data entry -->
                <label for="sermondate">Sermon Date:</label>
                <input type="date" id="sermondate" name="sermondate" required><br>

                <label for="speaker">Speaker:</label>
                <input type="text" id="speaker" name="speaker" required><br>

                <label for="topic">Topic:</label>
                <input type="text" id="topic" name="topic" required><br>

                <label for="section">Section:</label>
                <input type="text" id="section" name="section"><br>

                <label for="location">Location:</label>
                <input type="text" id="location" name="location"><br>

                <label for="type">Type:</label>
                <input type="text" id="type" name="type"><br>

                <label for="remark">Remark:</label>
                <input type="text" id="remark" name="remark"><br>

                <label for="audiofile">Audio file:</label>
                <input type="text" id="audiofile" name="audiofile"><br>

                <label for="bibleID">Bible ID:</label>
                <input type="number" id="bibleID" name="bibleID"><br>

                <input type="submit" name="submit" value="Submit">
                <input type="button" onclick="history.back()" value="Go Back" class="btn btn-warning">
            </form>
        </div>
        <?php
    }

    public function process_audio_list_form_submission() {
        if (isset($_POST['submit'])) {
            // Ensure no output before redirect
            ob_start();

            global $wpdb;

            // Sanitize input data
            $sermondate = sanitize_text_field($_POST['sermondate']);
            $speaker = sanitize_text_field($_POST['speaker']);
            $topic = sanitize_text_field($_POST['topic']);
            $section = sanitize_text_field($_POST['section']);
            $location = sanitize_text_field($_POST['location']);
            $type = sanitize_text_field($_POST['type']);
            $remark = sanitize_text_field($_POST['remark']);
            $audiofile = sanitize_text_field($_POST['audiofile']);
            $bibleID = intval($_POST['bibleID']);

            $wpdb->insert(
                'wp_audio_list',
                array(
                    'sermondate' => $sermondate,
                    'speaker' => $speaker,
                    'topic' => $topic,
                    'section' => $section,
                    'location' => $location,
                    'type' => $type,
                    'remark' => $remark,
                    'audiofile' => $audiofile,
                    'bibleID' => $bibleID
                )
            );

            // Set a transient message to display after redirect
            set_transient('custom_audio_list_message', 'Audio added successfully!', 30);

            // Redirect back to the audio list admin page
            wp_redirect(admin_url('admin.php?page=audio-list-admin'));
            exit;
        }
    }

    public function custom_admin_notice() {
        // Display admin notice if there's a message set
        if ($message = get_transient('custom_audio_list_message')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
            <?php
            delete_transient('custom_audio_list_message');
        }
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

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/audio-list-admin.css', array(), $this->version, 'all');
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

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/audio-list-admin.js', array('jquery'), $this->version, false);
    }
}
