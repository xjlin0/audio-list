<?php

/**
 * Fired during plugin activation
 *
 * @link       https://xjlin0.github.io
 * @since      1.0.0
 *
 * @package    Audio_List
 * @subpackage Audio_List/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Audio_List
 * @subpackage Audio_List/includes
 * @author     Jack Lin <xjlin0@gmail.com>
 */
class Audio_List_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
      global $wpdb;
      $table_name = $wpdb->prefix . 'audio_list';
      $charset_collate = $wpdb->get_charset_collate();
      $charset_collate_array = explode('COLLATE ', $charset_collate);
      $collate = end($charset_collate_array);

      $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sermondate` date DEFAULT NULL,
        `speaker` varchar(255) COLLATE $collate DEFAULT NULL,
        `topic` varchar(255) COLLATE $collate DEFAULT NULL,
        `section` varchar(255) COLLATE $collate DEFAULT NULL,
        `location` varchar(255) COLLATE $collate DEFAULT NULL,
        `type` varchar(45) COLLATE $collate NOT NULL DEFAULT '主日崇拜',
        `remark` varchar(255) COLLATE $collate DEFAULT NULL,
        `audiofile` varchar(255) COLLATE $collate DEFAULT NULL,
        `bibleID` int(11) DEFAULT '0',
        `updatedBy` varchar(255) COLLATE $collate DEFAULT NULL,
        `updatedTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `activeFlag` varchar(45) COLLATE $collate DEFAULT 'Active',
        PRIMARY KEY (`id`),
        KEY `idx_sermondate` (`sermondate`),
        KEY `idx_type` (`type`),
        KEY `idx_activeFlag` (`activeFlag`)
      ) $charset_collate;";

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );
	}

}
