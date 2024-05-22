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

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'add_plugin_menu_pages'));
        add_action('admin_post_custom_audio_list_form_submit', array($this, 'process_audio_list_form_submission'));
        // Handle form submission before any HTML is output
        add_action('admin_init', array($this, 'handle_form_submission'));
        add_action('admin_notices', array($this, 'custom_admin_notice'));
    }

    public function add_plugin_menu_pages() {
        add_menu_page(
            'Audio List',
            'Audio List',
            'manage_options',
            'audio-list-admin',
            array($this, 'audio_list_admin_page')
        );

        add_submenu_page(
            'audio-list-admin',
            'Add New Audio',
            'Add New Audio',
            'manage_options',
            'custom-audio-list',
            array($this, 'custom_audio_list_page')
        );
    }

    public function handle_form_submission() {
        if (isset($_POST['action']) && $_POST['action'] === 'custom_audio_list_form_submit') {
            $this->process_audio_list_form_submission();
        }
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
        // Retrieve previously submitted values, if available
        $sermondate_value = isset($_POST['sermondate']) ? $_POST['sermondate'] : '';
        $speaker_value = isset($_POST['speaker']) ? $_POST['speaker'] : '';
        $topic_value = isset($_POST['topic']) ? $_POST['topic'] : '';
        $section_value = isset($_POST['section']) ? $_POST['section'] : '';
        $location_value = isset($_POST['location']) ? $_POST['location'] : '';
        $type_value = isset($_POST['type']) ? $_POST['type'] : '';
        $remark_value = isset($_POST['remark']) ? $_POST['remark'] : '';
        $audiofile_value = isset($_POST['audiofile']) ? $_POST['audiofile'] : '';
        $bibleID_value = isset($_POST['bibleID']) ? $_POST['bibleID'] : '';

        ?>
        <div class="wrap">
            <h2>Add New Audio</h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="custom_audio_list_form_submit">
                <!-- Form fields for data entry -->
                <label for="sermondate">Sermon Date:</label>
                <input type="date" id="sermondate" name="sermondate" value="<?php echo esc_attr($sermondate_value); ?>" required><br>

                <label for="speaker">Speaker:</label>
                <input type="text" id="speaker" name="speaker" value="<?php echo esc_attr($speaker_value); ?>" required><br>

                <label for="topic">Topic:</label>
                <input type="text" id="topic" name="topic" value="<?php echo esc_attr($topic_value); ?>" required><br>

                <label for="section">Section:</label>
                <input type="text" id="section" name="section" value="<?php echo esc_attr($section_value); ?>"><br>

                <label for="location">Location:</label>
                <input type="text" id="location" name="location" value="<?php echo esc_attr($location_value); ?>"><br>

                <label for="type">Type:</label>
                <input type="text" id="type" name="type" value="<?php echo esc_attr($type_value); ?>"><br>

                <label for="remark">Remark:</label>
                <input type="text" id="remark" name="remark" value="<?php echo esc_attr($remark_value); ?>"><br>

                <label for="audiofile">Audio file:</label>
                <input type="text" id="audiofile" name="audiofile" value="<?php echo esc_attr($audiofile_value); ?>"><br>

                <label for="bibleID">Bible ID:</label>
                <input type="number" id="bibleID" name="bibleID" value="<?php echo esc_attr($bibleID_value); ?>"><br>

                <input type="submit" name="submit" value="Submit">
                <input type="button" onclick="history.back()" value="Go Back" class="btn btn-warning">
            </form>
        </div>
        <?php
    }

    public function process_audio_list_form_submission() {
    	ob_start();
        if (isset($_POST['submit'])) {
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

            global $wpdb;

            // Insert data into the database
            $result = $wpdb->insert(
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

            if ($result) {
                // Set a transient message to display after redirect
                set_transient('custom_audio_list_message', 'Audio List added successfully!', 30);
                // Redirect to the plugin root admin URL
                wp_redirect(admin_url('admin.php?page=audio-list-admin'));
                exit;
            } else {
                // Display error message
                echo '<div class="notice notice-error is-dismissible"><p>Failed to add Audio List. Error: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        }
    }

    public function custom_admin_notice() {
        // Display admin notice if there's a message set
        if ($message = get_transient('custom_audio_list_message')) {
            $klass = false !== strpos($message, 'successfully') ? 'notice-success' : 'notice-error';
            ?>
            <div class="notice <?php echo esc_attr($klass); ?> is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
            <?php
            delete_transient('custom_audio_list_message');
        }
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/audio-list-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/audio-list-admin.js', array('jquery'), $this->version, false);
    }
}
