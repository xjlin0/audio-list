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
            <h1><?php echo get_bloginfo('description'); ?></h1>
            <h2>Hello! (WordPress Site Login)</h2>
            <?php if (get_transient('custom_audio_list_message')) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo get_transient('custom_audio_list_message'); ?></p>
                </div>
                <?php delete_transient('custom_audio_list_message'); ?>
            <?php endif; ?>
            <button class="button button-primary" onclick="location.href='<?php echo admin_url('admin.php?page=custom-audio-list'); ?>'">1.新增證道錄音資料 (Create sermon record)</button>
            <br><br>
            <button class="button button-primary" onclick="location.href='<?php echo admin_url('admin-post.php?action=select_audio'); ?>'">2.修改證道錄音資料 (Update sermon record)</button>
            <br><br>
            <button class="button button-secondary" onclick="location.href='<?php echo wp_logout_url(admin_url()); ?>'">Logout</button>
        </div>
        <?php
    }

    public function custom_audio_list_page() {
        $current_user = wp_get_current_user()->user_login;
        $sermondate_value = isset($_POST['sermondate']) ? $_POST['sermondate'] : date('Y-m-d');
        $speaker_value = isset($_POST['speaker']) ? $_POST['speaker'] : '';
        $topic_value = isset($_POST['topic']) ? $_POST['topic'] : '';
        $section_value = isset($_POST['section']) ? $_POST['section'] : '';
        $location_value = isset($_POST['location']) ? $_POST['location'] : '海沃教會';
        $type_value = isset($_POST['type']) ? $_POST['type'] : '';
        $remark_value = isset($_POST['remark']) ? $_POST['remark'] : '';
        $audiofile_value = isset($_POST['audiofile']) ? $_POST['audiofile'] : '';
        $bibleID_value = isset($_POST['bibleID']) ? $_POST['bibleID'] : 0;

        ?>
		        <div class="wrap">
		            <h1>Create Sermon Record (新增錄音證道)</h1>
		            <h2>Editor: <?php echo $current_user; ?></h2>
		            <form method="post" action="">
		                <input type="hidden" name="action" value="custom_audio_list_form_submit">

		                <?php wp_nonce_field( 'my_action', 'csrf_token' ); ?>

		                <label for="sermondate">日期 (mm/dd/yyyy):	</label>
		                <input type="date" id="sermondate" name="sermondate" value="<?php echo esc_attr($sermondate_value); ?>" required><span style="color: red;">*</span><br>

		                <label for="speaker">講員(Speaker):	</label>
		                <input type="text" id="speaker" name="speaker" maxlength="255" value="<?php echo esc_attr($speaker_value); ?>" required><span style="color: red;">*</span><br>

		                <label for="topic">主題(Topic):	</label>
		                <input type="text" id="topic" name="topic" maxlength="255" value="<?php echo esc_attr($topic_value); ?>" required><span style="color: red;">*</span><br>

		                <label for="section">經節(Section):	</label>
		                <input type="text" id="section" maxlength="255" name="section" value="<?php echo esc_attr($section_value); ?>"><br>

		                <label for="bibleID">Bible ID:</label>
		                <input type="number" id="bibleID" name="bibleID" value="<?php echo esc_attr($bibleID_value); ?>"><br>

		                <label for="location">地點(Location):	</label>
		                <input type="text" id="location" maxlength="255" name="location" value="<?php echo esc_attr($location_value); ?>" required><span style="color: red;">*</span><br>

		                <label for="type">類型(Type): </label>
		                <select name="type" id="type" maxlength="45" name="type" value="<?php echo esc_attr($type_value); ?>">
										    <option value="主日崇拜">主日崇拜</option>
										    <option value="查經聚會">查經聚會</option>
										    <option value="退修特會">退修特會</option>
										    <option value="其他活動">其他活動</option>
										</select><span style="color: red;">*</span><br>

		                <label for="audiofile">錄音檔名 (Audio File Name):	</label>
		                <input type="text" id="audiofile" maxlength="255" name="audiofile" value="<?php echo esc_attr($audiofile_value); ?>" required><span style="color: red;">*</span><br>

		                <label style="vertical-align: top;" for="remark">備註(Remark):	</label>
		                <textarea id="remark" maxlength="255" name="remark" cols="40" rows="5" value="<?php echo esc_attr($remark_value); ?>"></textarea><br>

		                <input class="button button-primary" type="submit" name="submit" value="Submit">
		                <input class="button button-secondary" type="button" onclick="history.back()" value="Go Back" class="btn btn-warning">
		            </form>
		        </div>
        <?php
    }

    public function process_audio_list_form_submission() {
    	ob_start();
        if (isset($_POST['submit']) && isset( $_POST['csrf_token'] ) && wp_verify_nonce( $_POST['csrf_token'], 'my_action' )) {
        	  global $wpdb;

            $sermondate = sanitize_text_field($_POST['sermondate']);
            $speaker = sanitize_text_field($_POST['speaker']);
            $topic = sanitize_text_field($_POST['topic']);
            $section = sanitize_text_field($_POST['section']);
            $location = sanitize_text_field($_POST['location']);
            $type = sanitize_text_field($_POST['type']);
            $remark = sanitize_text_field($_POST['remark']);
            $audiofile = sanitize_text_field($_POST['audiofile']);
            $bibleID = intval($_POST['bibleID']);

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
                    'bibleID' => $bibleID,
                    'updatedBy' => $current_user
                )
            );

            $message = 'Audio List ' . $sermondate . ' ' . $speaker . ' ' . $topic ;
            if ($result) {
                // Set a transient message to display after redirect
                set_transient('custom_audio_list_message', $message . ' added successfully!', 30);
                // Redirect to the plugin root admin URL
                wp_redirect(admin_url('admin.php?page=audio-list-admin'));
                exit;
            } else {
                // Display error message
                echo '<div class="notice notice-error is-dismissible"><p>Failed to add '. $message  .'. Error: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        } else {
        	error_log("missing nonce_field for CSRF token verification");
          echo '<div class="notice notice-error is-dismissible"><p>Security check failed since there is no CSRF token. Please try again.</p></div>';
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
