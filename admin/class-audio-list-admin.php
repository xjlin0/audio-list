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
        add_action('admin_init', array($this, 'handle_custom_audio_list_get_schema'));
    }

    public function add_plugin_menu_pages() {
        add_menu_page(
            'Audio List',            // Page title
            'Audio List',            // Menu title
            'manage_options',        // Capability required to access the menu
            'audio-list-admin',      // Menu slug
            array($this, 'audio_list_admin_page'),  // Callback function to display the menu page
            'dashicons-format-audio' // Icon URL or WordPress Dashicon class
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
        $audioList = $wpdb->get_results("SELECT id, audiofile, activeFlag, sermondate, series, speaker, topic, section, type, location, remark FROM wp_audio_list ORDER BY sermondate DESC, type, topic, updatedTime DESC");
        $params = array();
        parse_str($_SERVER['QUERY_STRING'], $params);
        $circle = $params['circle'] ?? null;

        ?>
        <div class="wrap">
            <h1>Modify audio data - select an audio 修改錄音證道-選取證道錄音</h1>
            <h2>Deleted audios will be striked out and won't be shown in the frontend; records without filenames will be highlighted and players won't be shown in the frontend. 已刪除紀錄將被劃掉且不在前台顯示; 無檔名紀錄將以黃色標示且前台不顯示播放器</h2>
            <a class="button linkbutton orange" href="<?php echo admin_url('admin.php?page=audio-list-admin'); ?>">Go Back</a>
            <br><br>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>日期(Date)</th>
                        <th>講員(Speaker)</th>
                        <th>主題/系列(Topic & series)</th>
                        <th>經節(Section)</th>
                        <th>類型/地點 (Type & location)</th>
                        <th>操作(Operation)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audioList as $audio) : 
                    $topicAndSeries = esc_html($audio->topic). ' ' . esc_html($audio->series);
                    $trStyle = '';
                    if (empty($audio->audiofile)) {
                        $topicAndSeries = '(Unavailable) ' . $topicAndSeries;
                    }?>
                        <tr id="audio-list-<?php echo($audio->id); ?>" class="<?php echo($audio->activeFlag === 'Active' ? '' : 'strikethrough inactive'); ?>" style="<?php echo((empty($audio->audiofile) ? 'background-color: Khaki;' : '').($circle === $audio->id ? 'box-shadow: inset 0 0 10px green;' : '')); ?>">
                            <td><?php echo esc_html($audio->sermondate); ?></td>
                            <td><?php echo esc_html($audio->speaker); ?></td>
                            <td><?php echo $topicAndSeries; ?></td>
                            <td><?php echo esc_html($audio->section); ?></td>
                            <td><?php echo esc_html($audio->type) . ' ' . esc_html($audio->location); ?></td>
                            <td>
                                <a class="button <?php echo($audio->activeFlag === 'Active' ? 'button-primary': 'button-primary red'); ?>" href="<?php echo admin_url('admin.php?page=custom-audio-list&id=' . $audio->id); ?>">Edit</a>
					            <?php if (!empty($audio->remark)) : ?>
									<button class="button button-secondary" title="<?php echo(esc_attr($audio->remark)); ?>" onclick="alert('<?php echo(nl2br(esc_js($audio->remark))); ?>')">
										Remark
									</button>
						        <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_custom_audio_list_get_schema() {
        if (isset($_GET['action']) && $_GET['action'] == 'custom_audio_list_get_schema') {
            if (isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'custom_audio_list_nonce')) {
                $this->custom_audio_list_get_schema();
                exit;
            }
            echo '<div class="notice notice-error is-dismissible"><p>Security check failed for missing token. Please try again.</p></div>';
        }  // Display error message for missing nonce_field for nonce token verification
    }

    public function handle_form_submission() {
        if (isset($_POST['action']) && $_POST['action'] === 'custom_audio_list_form_submit') {
            if (isset($_POST['csrf_token']) && wp_verify_nonce($_POST['csrf_token'], 'my_action')) {
                $this->process_audio_list_form_submission();
            }
            echo '<div class="notice notice-error is-dismissible"><p>Security check failed for missing CSRF token. Please try again.</p></div>';
        }  // Display error message for missing nonce_field for CSRF token verification
    }

    public function audio_list_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo get_bloginfo('description'); ?></h1>
            <h2>Hello! <?php  echo wp_get_current_user()->display_name; ?></h2>
            <br>
            <button class="button button-primary" onclick="location.href='<?php echo admin_url('admin.php?page=custom-audio-list'); ?>'" title="Create audio record">1.新增錄音資料 (Create audio record)</button>
            <br><br>
            <button class="button button-primary" onclick="location.href='<?php echo admin_url('admin.php?page=select-audio'); ?>'" title="Update audio record">2.修改錄音資料 (Update audio record)</button>
            <br><br>
            <a href="<?php echo admin_url('admin.php?page=audio-list-admin&action=custom_audio_list_get_schema&nonce=' . wp_create_nonce('custom_audio_list_nonce')); ?>" class="button linkbutton orange" title="Download audio list SQL">3.下載錄音資料表 Download audio list SQL</a>
            <br><br>
            <button class="button button-primary red" onclick="if(confirm('Are you sure to log out?')){location.href='<?php echo wp_logout_url(admin_url()); ?>'} else {return false;}">登出系統 Log Out</button>
        </div>
        <?php
    }

    public function custom_audio_list_get_schema() {
        global $wpdb;

        $table_name = 'wp_audio_list';

        $schema_results = $wpdb->get_results("SHOW CREATE TABLE {$table_name}", ARRAY_A);

        if (!empty($schema_results) && isset($schema_results[0]['Create Table'])) {
            $schema = $schema_results[0]['Create Table'];

            $data_results = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);

            $insert_statements = '';
            foreach ($data_results as $row) {
                $columns = implode(', ', array_map('esc_sql', array_keys($row)));
                $values = "'" . implode("', '", array_map('esc_sql', array_values($row))) . "'";
                $insert_statements .= "INSERT INTO {$table_name} ($columns) VALUES ($values);\n";
            }

            $combined_sql = "-- Table Schema\n" . preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $schema) . ";\n\n-- Data\n" . $insert_statements;

            $compressed_sql = gzencode($combined_sql);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . sanitize_file_name($table_name) . '.sql.gz"');
            header('Content-Length: ' . strlen($compressed_sql));

            echo $compressed_sql;
            exit;
        } else {
            wp_die("Failed to retrieve table schema.");
        }
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
        $note_value = '';
        $series_value = '';
        $audiofile_value = '';
        $bibleID_value = 0;
        $operation = '';

        if ($audio_id) {  // If audio ID is provided, fetch the data from the database
            $audio = $wpdb->get_row($wpdb->prepare("SELECT sermondate, speaker, topic, section, location, type, remark, note, audiofile, bibleID, series, activeFlag FROM wp_audio_list WHERE id = %d", $audio_id));
            if ($audio) {  // Pre-fill form fields with fetched data
                $sermondate_value = $audio->sermondate;
                $speaker_value = $audio->speaker;
                $topic_value = $audio->topic;
                $section_value = $audio->section;
                $location_value = $audio->location;
                $type_value = $audio->type;
                $remark_value = $audio->remark;
                $note_value = $audio->note;
                $audiofile_value = $audio->audiofile;
                $bibleID_value = $audio->bibleID;
                $series_value = $audio->series;
                $operation = $audio->activeFlag === 'Active' ? 'delete' : 'restore';
            } else {
                echo 'Audio record not found.';
                return;
            }
        }

        ?>
        <div class="wrap">
            <h1><?php echo $audio_id ? 'Update Sermon Record 修改' : 'Create Sermon Record 新增'; ?>錄音證道</h1>
            <h2>Editor: <?php echo $current_user->user_login; ?></h2>
            <div class="form-container">
                <form id="main_form" method="post" action="">
	                <input type="hidden" name="action" value="custom_audio_list_form_submit">
	                <?php wp_nonce_field('my_action', 'csrf_token'); ?>
	                <input type="hidden" name="audio_id" value="<?php echo $audio_id; ?>">

		            <table border="0" class="wp-list-table widefat striped">
						<thead>
						    <tr>
						        <th class="textright">
						            Field
						        </th>
						        <th>
						            Value
						        </th>
						    </tr>
						</thead>
						<tbody>
							<tr>
								<td align="right">
							        日期 (mm/dd/yyyy):
							    </td>
							    <td>
							        <input type="date" onkeydown="return false" name="sermondate" value="<?php echo esc_attr($sermondate_value); ?>" required>
							        <span class="fielderror">*</span>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        講員(Speaker):
							    </td>
							    <td>
							        <input type="text" name="speaker" maxlength="255" value="<?php echo esc_attr($speaker_value); ?>" required>
							        <span class="fielderror">*</span>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        主題(Topic):
							    </td>
							    <td>
							        <input type="text" name="topic" maxlength="255" value="<?php echo esc_attr($topic_value); ?>" required>
							        <span class="fielderror">*</span>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        經節(Section):
							    </td>
							    <td>
							        <input type="text" maxlength="255" name="section" value="<?php echo esc_attr($section_value); ?>">
							    </td>
							</tr>
							<tr>
								<td align="right">
							        地點(Location):
							    </td>
							    <td>
							        <input type="text" maxlength="255" name="location" value="<?php echo esc_attr($location_value); ?>" required>
							        <span class="fielderror">*</span>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        類型(Type):
							    </td>
							    <td>
							        <select name="type" maxlength="45" name="type" value="<?php echo esc_attr($type_value); ?>">
									    <option value="主日崇拜">主日崇拜</option>
									    <option value="查經聚會">查經聚會</option>
									    <option value="退修特會">退修特會</option>
									    <option value="其他活動">其他活動</option>
									</select>
									<span class="fielderror">*</span>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        系列名稱 (series):
							    </td>
							    <td>
							        <input type="text" maxlength="45" name="series" value="<?php echo esc_attr($series_value); ?>">
							    </td>
							</tr>
							<tr>
								<td align="right">
							        錄音檔名 (Audio File Name):
							    </td>
							    <td>
							        <input placeholder="Please fill 請填寫!!" title="For the opration we can't make this required but please fill it when possible. Titles will be automatically labelled as (Unavailable) without filenames. 為作業方便此欄能留空, 但請盡量填寫, 如不填寫網頁上標題會被標記(Unavailable 無檔案)" type="text" maxlength="255" name="audiofile" value="<?php echo esc_attr($audiofile_value); ?>">
							        <span class="fielderror">*</span>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        聖經連結 (Bible Location ID):
							    </td>
							    <td>
							        <input type="number" name="bibleID" value="<?php echo esc_attr($bibleID_value); ?>">
							    </td>
							</tr>
							<tr>
								<td align="right">
							        內部備註(Internal remark):
							    </td>
							    <td>
							        <textarea id="remark" maxlength="255" name="remark" cols="40" rows="5"><?php echo esc_attr($remark_value); ?></textarea>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        公開註記(Public note):
							    </td>
							    <td>
							        <textarea id="note" maxlength="21845" name="note" cols="50" rows="6"><?php echo esc_attr($note_value); ?></textarea>
							    </td>
							</tr>
							<tr>
								<td align="right">
							        狀態(Status):
							    </td>
							    <td>
							        <?php echo $audio_id ? $audio->activeFlag : 'New'; ?>
							    </td>
							</tr>
						</tbody>
				    </table>
	                <input class="button button-primary" onClick="if(confirm('Are you sure to submit?')){this.form.submit(); this.disabled=true; this.value='Submitting…';} else {return false;}" type="submit" name="submit" value="<?php echo $audio_id ? 'Update' : 'Submit'; ?>">
	                <a class="button linkbutton orange" href="<?php echo admin_url('admin.php?page=audio-list-admin'); ?>">Go Back</a>
			    </form>
	            <?php if ($audio_id) : ?>
		            <form id="delete_restore_form" method="post" action="">
			            <input type="hidden" name="action" value="custom_audio_list_form_submit">
			            <?php wp_nonce_field('my_action', 'csrf_token'); ?>
			            <input type="hidden" name="audio_id" value="<?php echo $audio_id; ?>" readonly>
			            <input type="hidden" name="sermondate" value="<?php echo esc_attr($sermondate_value); ?>" comment="for transient message" readonly>
			            <input type="hidden" name="type" value="<?php echo esc_attr($type_value); ?>" comment="for transient message" readonly>
			            <input type="hidden" name="speaker" value="<?php echo esc_attr($speaker_value); ?>" comment="for transient message" readonly>
			            <input type="hidden" name="topic" value="<?php echo esc_attr($topic_value); ?>" comment="for transient message" readonly>
			            <input type="hidden" name="operation" value="<?php echo esc_attr($operation); ?>" readonly>
			            <input onclick="return confirm('Are you sure to <?php echo esc_attr($operation); ?>? (All updates in the form will NOT be saved/updated)')" class="button button-primary red" type="submit" value="<?php echo esc_attr($operation); ?>">
				    </form>
		        <?php endif; ?>
		    </div>
        </div>
        <?php
    }

    public function process_audio_list_form_submission() {
        ob_start();
        global $wpdb;
        $current_user = wp_get_current_user();
        $audio_id = isset($_POST['audio_id']) ? intval($_POST['audio_id']) : 0;
        $sermondate = sanitize_text_field($_POST['sermondate']);
        $speaker = html_entity_decode(sanitize_text_field($_POST['speaker']));
        $topic = html_entity_decode(sanitize_text_field($_POST['topic']));
        $section = html_entity_decode(sanitize_text_field($_POST['section']));
        $location = html_entity_decode(sanitize_text_field($_POST['location']));
        $type = sanitize_text_field($_POST['type']);
        $remark = html_entity_decode(sanitize_text_field($_POST['remark']));
        $note = html_entity_decode(sanitize_text_field($_POST['note']));
        $series = html_entity_decode(sanitize_text_field($_POST['series']));
        $audiofile = (!empty($_POST['audiofile']) && trim($_POST['audiofile'])) ? html_entity_decode(sanitize_text_field($_POST['audiofile'])) : null;
        $bibleID = isset($_POST['bibleID']) ? intval($_POST['bibleID']) : 0;
        $link = '<a href="' . admin_url('admin.php?page=custom-audio-list&id=');
        $message = '">Audio List ' . $sermondate . ' ' . $type . ' ' . $speaker . ' ' . $topic;
        if (isset($_POST['operation']) && $audio_id) {  // Perform soft delete (set activeFlag to false) and exit
            $operation = sanitize_text_field($_POST['operation']);
            $result = $wpdb->update(
                'wp_audio_list',
                array(
                    'activeFlag' => $operation === 'delete' ? 'Inactive' : 'Active',
                    'updatedBy' => $current_user->user_login
                ),
                array('id' => $audio_id)
            );

            if ($result !== false) {  // Set a transient message with the magic word 'successfully' to display after redirect
                set_transient('custom_audio_list_message', $link . $audio_id . $message . ' successfully ' . ($operation === 'delete' ? ' deleted.' : ' restored.') . '</a>', 30);
                $params = array('circle' => $audio_id );
                wp_redirect(admin_url('admin.php?page=select-audio&'.http_build_query($params)).'#audio-list-'.$audio_id);  // Redirect to the plugin root admin URL
                exit;
            } else {  // Display error message
                echo '<div class="notice notice-error is-dismissible"><p>Failed to alter audio record. Error: ' . esc_html($wpdb->last_error) . '</p></div>';
                return;
            }
        }

		$data = array(
		    'sermondate' => $sermondate,
		    'speaker' => trim($speaker),
		    'topic' => trim($topic),
		    'section' => trim($section),
		    'location' => trim($location),
		    'type' => $type,
		    'remark' => trim($remark),
		    'series' => trim($series),
		    'note' => empty($note) ? null : trim($note),
		    'audiofile' => empty($audiofile) ? null : trim($audiofile),
		    'bibleID' => $bibleID,
		    'updatedBy' => $current_user->user_login
		);
		$new_id = null;
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
		    $new_id=$wpdb->insert_id;
		}

        if ($result !== false) {  // Set a transient message to display after redirect
            set_transient('custom_audio_list_message', ($audio_id ? $link . $audio_id . $message . ' successfully updated.' : $link . $new_id . $message . ' successfully added.') . '</a>', 30);  // Redirect to the plugin root admin URL
            $params = array('circle' => $audio_id ? $audio_id : $new_id );
            wp_redirect(admin_url('admin.php?page=select-audio&'.http_build_query($params)).'#audio-list-'.($audio_id ? $audio_id : $new_id));
            exit;
        } else {  // Display error message upon db write error
            echo '<div class="notice notice-error is-dismissible"><p>Failed to ' . ($audio_id ? 'update' : 'add') . ' audio record. Error: ' . esc_html($wpdb->last_error) . '</p><p>Please check the data and save again.</p></div>';
        }
    }

    public function custom_admin_notice() {   // Display admin notice if there's a message set
        if ($message = get_transient('custom_audio_list_message')) {
            $klass = false !== strpos($message, 'successfully') ? 'notice-success' : 'notice-error';
            ?>
            <div class="notice <?php echo esc_attr($klass); ?> is-dismissible">
				<p>
					<?php echo wp_kses_post($message); ?>
				</p>
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
