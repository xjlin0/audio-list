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

        add_submenu_page(
            'audio-list-admin',
            'Select Audio to Edit',
            'Update Existing Audio',
            'manage_options',
            'select-audio',
            array($this, 'custom_select_audio_page')
        );
    }

    public function custom_select_audio_page() {
        global $wpdb;
        $audio_list = $wpdb->get_results("SELECT * FROM wp_audio_list ORDER BY sermondate DESC, type, topic, updatedTime DESC");

        ?>
        <div class="wrap">
            <h1>修改錄音證道-選取證道錄音</h1>
            <button class="button button-primary orange" onclick="location.href='<?php echo admin_url('admin.php?page=audio-list-admin'); ?>'">Go Back</button>
            <br><br>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>日期(Date)</th>
                        <th>講員(Speaker)</th>
                        <th>主題(Topic)</th>
                        <th>經節(Section)</th>
                        <th>類型地點 (Type & location)</th>
                        <th>選取(Action)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audio_list as $audio) : ?>
                        <tr title="<?php echo($audio->remark); ?>" class="<?php echo($audio->activeFlag === 'Active' ? '' : 'strikethrough'); ?>">
                            <td><?php echo esc_html($audio->sermondate); ?></td>
                            <td><?php echo esc_html($audio->speaker); ?></td>
                            <td><?php echo esc_html($audio->topic); ?></td>
                            <td><?php echo esc_html($audio->section); ?></td>
                            <td><?php echo esc_html($audio->type) . ' ' . esc_html($audio->location); ?></td>
                            <td>
                                <a class="button <?php echo($audio->activeFlag === 'Active' ? 'button-primary': 'button-primary red'); ?>" href="<?php echo admin_url('admin.php?page=custom-audio-list&id=' . $audio->id); ?>">Select</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
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
            <button class="button button-primary" onclick="location.href='<?php echo admin_url('admin.php?page=select-audio'); ?>'">2.修改證道錄音資料 (Update sermon record)</button>
            <br><br>
            <button class="button button-primary orange" onclick="location.href='<?php echo wp_logout_url(admin_url()); ?>'">登出系統 Log Out</button>
        </div>
        <?php
    }

    public function custom_audio_list_page() {
        global $wpdb;
        $current_user = wp_get_current_user();
        $audio_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $sermondate_value = date('Y-m-d');
        $speaker_value = '';
        $topic_value = '';
        $section_value = '';
        $location_value = '海沃教會';
        $type_value = '';
        $remark_value = '';
        $audiofile_value = '';
        $bibleID_value = 0;

        if ($audio_id) {  // If audio ID is provided, fetch the data from the database
            $audio = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_audio_list WHERE id = %d", $audio_id));
            if ($audio) {  // Pre-fill form fields with fetched data
                $sermondate_value = $audio->sermondate;
                $speaker_value = $audio->speaker;
                $topic_value = $audio->topic;
                $section_value = $audio->section;
                $location_value = $audio->location;
                $type_value = $audio->type;
                $remark_value = $audio->remark;
                $audiofile_value = $audio->audiofile;
                $bibleID_value = $audio->bibleID;
            } else {
                echo 'Audio record not found.';
                return;
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo $audio_id ? 'Update Sermon Record 修改' : 'Create Sermon Record 新增'; ?>錄音證道</h1>
            <h2>Editor: <?php echo $current_user->user_login; ?>(WordPress Site Login)</h2>
            <div class="form-container">
		            <form id="main_form" method="post" action="">
		                <input type="hidden" name="action" value="custom_audio_list_form_submit">
		                <?php wp_nonce_field('my_action', 'csrf_token'); ?>

		                <input type="hidden" name="audio_id" value="<?php echo $audio_id; ?>">

		                <label for="sermondate">日期 (mm/dd/yyyy):	</label>
		                <input type="date" id="sermondate" name="sermondate" value="<?php echo esc_attr($sermondate_value); ?>" required><span class="fielderror">*</span><br>

		                <label for="speaker">講員(Speaker):	</label>
		                <input type="text" id="speaker" name="speaker" maxlength="255" value="<?php echo esc_attr($speaker_value); ?>" required><span class="fielderror">*</span><br>

		                <label for="topic">主題(Topic):	</label>
		                <input type="text" id="topic" name="topic" maxlength="255" value="<?php echo esc_attr($topic_value); ?>" required><span class="fielderror">*</span><br>

		                <label for="section">經節(Section):	</label>
		                <input type="text" id="section" maxlength="255" name="section" value="<?php echo esc_attr($section_value); ?>"><br>

		                <label for="location">地點(Location):	</label>
		                <input type="text" id="location" maxlength="255" name="location" value="<?php echo esc_attr($location_value); ?>" required><span class="fielderror">*</span><br>

		                <label for="type">類型(Type): </label>
		                <select name="type" id="type" maxlength="45" name="type" value="<?php echo esc_attr($type_value); ?>">
										    <option value="主日崇拜">主日崇拜</option>
										    <option value="查經聚會">查經聚會</option>
										    <option value="退修特會">退修特會</option>
										    <option value="其他活動">其他活動</option>
										</select><span class="fielderror">*</span><br>

		                <label for="audiofile">錄音檔名 (Audio File Name):	</label>
		                <input type="text" id="audiofile" maxlength="255" name="audiofile" value="<?php echo esc_attr($audiofile_value); ?>" required><span class="fielderror">*</span><br>

		                <label for="bibleID">聖經連結 (Bible Location ID):	</label>
		                <input type="number" id="bibleID" name="bibleID" value="<?php echo esc_attr($bibleID_value); ?>"><br>

		                <label class="top" for="remark">備註(Remark):	</label>
		                <textarea id="remark" maxlength="255" name="remark" cols="40" rows="5" value="<?php echo esc_attr($remark_value); ?>"></textarea><br>

		                <input class="button button-primary" type="submit" name="submit" value="<?php echo $audio_id ? 'Update' : 'Submit'; ?>">
		                <input class="button button-primary orange" type="button" onclick="history.back()" value="Go Back" class="btn btn-warning">
		            </form>
		            <?php if ($audio_id) : ?>
				            <form id="delete_restore_form" method="post" action="">
						            <input type="hidden" name="action" value="custom_audio_list_form_submit">
						            <?php wp_nonce_field('my_action', 'csrf_token'); ?>
						            <input type="hidden" name="audio_id" value="<?php echo $audio_id; ?>" readonly>
						            <input type="hidden" name="sermondate" value="<?php echo esc_attr($sermondate_value); ?>" readonly>
						            <input type="hidden" name="type" value="<?php echo esc_attr($type_value); ?>" readonly>
						            <input type="hidden" name="speaker" value="<?php echo esc_attr($speaker_value); ?>" readonly>
						            <input type="hidden" name="topic" value="<?php echo esc_attr($topic_value); ?>" readonly>
						            <input type="hidden" name="soft" value="<?php echo $audio->activeFlag === 'Active' ? 'delete' : 'restore'; ?>" readonly>
						            <input onclick="return confirm('are you sure?')" class="button button-primary red" type="submit" value="<?php echo $audio->activeFlag === 'Active' ? 'Delete' : 'Restore'; ?>">
						        </form>
				        <?php endif; ?>
				    </div>
        </div>
        <?php
    }

    public function process_audio_list_form_submission() {
        ob_start();
        if (isset($_POST['csrf_token']) && wp_verify_nonce($_POST['csrf_token'], 'my_action')) {
            global $wpdb;
          	$current_user = wp_get_current_user();
		        $audio_id = isset($_POST['audio_id']) ? intval($_POST['audio_id']) : 0;
            $sermondate = sanitize_text_field($_POST['sermondate']);
            $speaker = sanitize_text_field($_POST['speaker']);
            $topic = sanitize_text_field($_POST['topic']);
            $section = sanitize_text_field($_POST['section']);
            $location = sanitize_text_field($_POST['location']);
            $type = sanitize_text_field($_POST['type']);
            $remark = sanitize_text_field($_POST['remark']);
            $audiofile = sanitize_text_field($_POST['audiofile']);
            $bibleID = intval($_POST['bibleID']);
            $message = 'Audio List ' . $sermondate . ' ' . $type . ' ' . $speaker . ' ' . $topic;

		        if (isset($_POST['soft']) && $audio_id) {  // Perform soft delete (set activeFlag to false)
		        	  $soft = sanitize_text_field($_POST['soft']);
		            $result = $wpdb->update(
		                'wp_audio_list',
		                array(
		                	'activeFlag' => $soft === 'delete' ? 'Inactive' : 'Active',
		                	'updatedBy' => $current_user->user_login
		                ),
		                array('id' => $audio_id)
		            );

		            if ($result !== false) {  // Set a transient message with the magic word 'successfully' to display after redirect
		                set_transient('custom_audio_list_message', $message . ' successfully ' . ($soft === 'delete' ? ' deleted.' : ' restored.'), 30);
		                wp_redirect(admin_url('admin.php?page=select-audio'));  // Redirect to the plugin root admin URL
		                exit;
		            } else {  // Display error message
		                echo '<div class="notice notice-error is-dismissible"><p>Failed to alter audio record. Error: ' . esc_html($wpdb->last_error) . '</p></div>';
		                return;
		            }
		        }

						$data = array(
						    'sermondate' => $sermondate,
						    'speaker' => $speaker,
						    'topic' => $topic,
						    'section' => $section,
						    'location' => $location,
						    'type' => $type,
						    'remark' => $remark,
						    'audiofile' => $audiofile,
						    'bibleID' => $bibleID,
						    'updatedBy' => $current_user->user_login
						);

						if ($audio_id) {  // Update existing audio record
						    $result = $wpdb->update(
						        'wp_audio_list',
						        $data,
						        array('id' => $audio_id)
						    );
						} else {
						    $result = $wpdb->insert(   // Insert new audio record
						        'wp_audio_list',
						        $data
						    );
						}


            if ($result !== false) {  // Set a transient message to display after redirect
                set_transient('custom_audio_list_message', $message . ($audio_id ? ' successfully updated.' : ' successfully added.'), 30);  // Redirect to the plugin root admin URL
                wp_redirect(admin_url('admin.php?page=audio-list-admin'));
                exit;
            } else {  // Display error message
                echo '<div class="notice notice-error is-dismissible"><p>Failed to ' . ($audio_id ? 'update' : 'add') . ' audio record. Error: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        } else {
            error_log("missing nonce_field for CSRF token verification");
            echo '<div class="notice notice-error is-dismissible"><p>Security check failed since there is no CSRF token. Please try again.</p></div>';
        }
    }

    public function custom_admin_notice() {   // Display admin notice if there's a message set
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
