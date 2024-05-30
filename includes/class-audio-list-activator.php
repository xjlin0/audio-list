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
        `updatedTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `sermondate` date DEFAULT NULL COMMENT 'YYYY-MM-DD',
        `bibleID` int(11) DEFAULT 0 COMMENT 'for referencing verse id to Bible database',
        `type` varchar(45) COLLATE $collate NOT NULL DEFAULT '主日崇拜',
        `updatedBy` varchar(255) COLLATE $collate DEFAULT NULL COMMENT 'username updating the record',
        `activeFlag` varchar(45) COLLATE $collate DEFAULT 'Active' COMMENT 'for soft deletion',
        `audiofile` varchar(255) COLLATE $collate DEFAULT NULL COMMENT 'remote filename',
        `location` varchar(255) COLLATE $collate DEFAULT NULL COMMENT 'remote filename',
        `speaker` varchar(255) COLLATE $collate DEFAULT NULL,
        `topic` varchar(255) COLLATE $collate DEFAULT NULL,
        `section` varchar(255) COLLATE $collate DEFAULT NULL COMMENT 'verse info for human eyes',
        `remark` varchar(255) COLLATE $collate DEFAULT NULL COMMENT 'for internal coworkers',
        `note` text COLLATE $collate DEFAULT NULL COMMENT 'for public display',
        PRIMARY KEY (`id`),
        KEY `idx_sermondate` (`sermondate`) USING BTREE,
        KEY `idx_type` (`type`) USING HASH,
        KEY `idx_activeFlag` (`activeFlag`) USING HASH
      ) $charset_collate COMMENT='Audio List to store meta data';";

      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql );

      $role = get_role( 'contributor' );
          if ( ! empty( $role ) ) {
              $role->add_cap( 'manage_options' );
          }
	}

}
