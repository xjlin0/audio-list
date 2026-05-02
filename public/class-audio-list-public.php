<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://xjlin0.github.io
 * @since      1.0.0
 *
 * @package    Audio_List
 * @subpackage Audio_List/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Audio_List
 * @subpackage Audio_List/public
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Audio_List_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
    add_shortcode('audio-list', array($this, 'display_audio_list'));
	}

	/**
	 * Helper function to detect if a URL is a YouTube link
	 *
	 * @param string $url The URL to check
	 * @return bool True if URL is a YouTube link
	 */
	private function is_youtube_url($url) {
		return (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false);
	}

	/**
	 * Helper function to extract YouTube video ID from URL
	 *
	 * @param string $url The YouTube URL
	 * @return string|false The video ID or false if not found
	 */
	private function get_youtube_id($url) {
		$pattern = '/(?:youtube\.com\/(?:live\/|[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
		if (preg_match($pattern, $url, $matches)) {
			return $matches[1];
		}
		return false;
	}

	public function display_audio_list($atts) {
	    global $wpdb;
	    $atts = shortcode_atts(array(
	        'sermondate' => '',
	        'type' => '',
	        'series' => '',
	        'url' => '',
	        'audio_style' => '',
	        'stripe_style' => '',
	        'id' => ''
	    ), $atts);

	    $sermondate = isset($atts['sermondate']) ? sanitize_text_field($atts['sermondate']) : '';
      $type = isset($atts['type']) ? sanitize_text_field($atts['type']) : '';
      $series = isset($atts['series']) ? sanitize_text_field($atts['series']) : '';
      $url = isset($atts['url']) ? esc_url($atts['url']) : '';
      $audioStyle = isset($atts['audio_style']) ? sanitize_text_field($atts['audio_style']) : '';
      $id = isset($atts['id']) ? sanitize_text_field($atts['id']) : '';
      $stripeStyle = isset($atts['stripe_style']) ? sanitize_text_field($atts['stripe_style']) : '';

	    $table_name = $wpdb->prefix . 'audio_list';
		  $where_conditions = array('activeFlag = "Active"');
	    $query_params = array();

	    if (!empty($sermondate)) {
	        $where_conditions[] = "sermondate LIKE %s";
	        $query_params[] = $sermondate;
	    }

	    if (!empty($series)) {
	        $where_conditions[] = "series = %s";
	        $query_params[] = $series;
	    }

	    if (!empty($type)) {
	        $where_conditions[] = "type = %s";
	        $query_params[] = $type;
	    }

	    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

	    $query = $wpdb->prepare("SELECT id, sermondate, type, section, series, audiofile, note, topic, series, speaker, link, url FROM $table_name $where_clause ORDER BY sermondate DESC", $query_params);

	    $results = $wpdb->get_results($query);

			if ($results === false) {  // Error handling: Output an error message with the database error
			    return '<p>Error retrieving audio list: ' . esc_html($wpdb->last_error) . '</p>';
			}

			if (empty($results)) {
				  error_log( 'No audio list row available with given conditions: ' . json_encode($atts, JSON_UNESCAPED_SLASHES));
				  $output = '<ul class="audio-list"><li>No audio available 查無資料.</li></ul>';
			} else {
			    $output = '<ul class="audio-list"><label><input type="checkbox" class="video-players-switcher" checked="checked">顯示錄影預覽</label>';
			    foreach ($results as $index => $result) {
							$src = htmlspecialchars($url . $result->audiofile);
							$filenames = explode('.', $result->audiofile);
							$filename = array_shift($filenames);
							$sermondate = esc_html($result->sermondate);
							$topic = esc_html($result->topic);
							$speaker = esc_html($result->speaker);
							$type = esc_html($result->type);
							$audio_id = htmlspecialchars($id . $filename);
							// Build note and handout link section
							$noteAndHandout = '';
							$noteToggle = '';
							$noteContent = '';
							$handoutContent = '';

							if (!empty($result->note)) {
								$noteToggle = '<details class="note-toggle"><summary class="underline-pointer"><u>按此顯示/隱藏摘要</u></summary></details>';
								$noteContent = '<div class="note-content"><p style="text-align:left;">'.nl2br($result->note).'</p></div>';
							}
							if (!empty($result->link)) {
								$link = trim($result->link);
								$linkExt = strtolower(pathinfo($link, PATHINFO_EXTENSION));
								$escapedLink = esc_url($link);
								if (in_array($linkExt, array('pdf', 'jpg', 'jpeg', 'png', 'apng', 'gif', 'webp', 'svg'))) {
									$handoutContent = '<span class="handout-link-wrapper"><a href="' . $escapedLink . '" target="_blank" rel="noopener noreferrer" class="handout-link"><u>📄 按此另開圖文講義</u></a></span>';
								}
							}

							// Wrap in flex container if either exists (handout first, then note toggle, then note content)
							if ($noteToggle || $handoutContent) {
								$noteAndHandout = '<div class="note-handout-container">' . $handoutContent . $noteToggle . '</div>';
							}

							$li = '<li id="'.($audio_id ? $audio_id : $id.md5($sermondate.$speaker.$topic.$type)).'"' . ($stripeStyle && $index % 2 == 0 ? ' style="' . $stripeStyle . '">' : '>');
							$series = empty($result->series) ? '' : esc_html($result->series) . '&nbsp; 系列&nbsp;&nbsp;';
							$section = empty($result->section) ? '<br/>' . $type . '<br/>' : '<br/>'. $type . ': <span>'. esc_html($result->section) .'</span><br/>' ;

							// Build YouTube player if url field contains YouTube link (lazy load)
							$youtubePlayer = '';
							if (!empty($result->url) && $this->is_youtube_url($result->url)) {
								$youtube_id = $this->get_youtube_id($result->url);
								if ($youtube_id) {
									$youtubePlayer = <<<EOT
										<div class="youtube-item" data-youtube-id="$youtube_id" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin-bottom: 10px; background: #000;">
											<div class="youtube-placeholder" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #fff; font-size: 14px;">▶ 載入中...</div>
										</div>
									EOT;
								}
							}

							// Build audio player (always show if audiofile exists)
							if ($result->audiofile) {
								$audioPlayer = <<<EOT
									<audio style="$audioStyle" preload="none" controls>
										<source src="$src" type="audio/mpeg">
										Your browser doesn't support the audio.
									</audio>
								EOT;
							} else {
								$audioPlayer = '<span>Unavailable 無檔案</span>';
							}
	
							$output .= <<<EOD
								$li
									$sermondate &nbsp; $topic
									$section
									$series $speaker
									<br/>
									$youtubePlayer
									$audioPlayer
									$noteAndHandout
									$noteContent
								</li>
							EOD;
			    }
			    $output .= '</ul>';
			}
	    return $output;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/audio-list-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/audio-list-public.js', array( 'jquery' ), $this->version, false );

	}

}
