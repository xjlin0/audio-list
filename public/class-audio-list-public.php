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

	    $query = $wpdb->prepare("SELECT id, sermondate, type, section, series, audiofile, note, topic, series, speaker FROM $table_name $where_clause ORDER BY sermondate DESC", $query_params);

	    $results = $wpdb->get_results($query);

			if ($results === false) {  // Error handling: Output an error message with the database error
			    return '<p>Error retrieving audio list: ' . esc_html($wpdb->last_error) . '</p>';
			}

			if (empty($results)) {
				  $output = '<p>No audio list available given the conditions: ' . json_encode($atts, JSON_UNESCAPED_SLASHES) . '</p>';
			} else {
			    $output = '<ul>';
			    foreach ($results as $index => $result) {
							$src = htmlspecialchars($url . $result->audiofile);
							$filenames = explode('.', $result->audiofile);
							$filename = array_shift($filenames);
							$audio_id = htmlspecialchars($id . $filename);
							$liTitleWithoutEnd = empty($result->note) ? '<li' : '<li title="'. htmlspecialchars($result->note) .'"';
							$li = $liTitleWithoutEnd . ($stripeStyle && $index % 2 == 0 ? ' style="' . $stripeStyle . '">' : '>');
							$sermondate = esc_html($result->sermondate);
							$topic = esc_html($result->topic);
							$series = empty($result->series) ? '' : esc_html($result->series) . '&nbsp; 系列&nbsp;&nbsp;';
							$speaker = esc_html($result->speaker);
							$section = empty($result->section) ? '<br/>' . esc_html($result->type) . '<br/>' : '<br/>'. esc_html($result->type) . ': <span>'. esc_html($result->section) .'</span><br/>' ;
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
								<p>
									<a id="$audio_id"></a>
								</p>

								$li
									$sermondate &nbsp; $topic
									$section
									$series $speaker
									<br/>
									$audioPlayer
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
