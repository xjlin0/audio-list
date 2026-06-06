<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock WordPress constants that might be needed
define('WPINC', true);

// Mock WP_Post class which is used for dynamic injection
if (!class_exists('WP_Post')) {
    class WP_Post {
        public $ID;
        public $post_author;
        public $post_date;
        public $post_date_gmt;
        public $post_content;
        public $post_title;
        public $post_excerpt;
        public $post_status;
        public $comment_status;
        public $ping_status;
        public $post_password;
        public $post_name;
        public $to_ping;
        public $pinged;
        public $post_modified;
        public $post_modified_gmt;
        public $post_content_filtered;
        public $post_parent;
        public $guid;
        public $menu_order;
        public $post_type;
        public $post_mime_type;
        public $comment_count;
        public $filter;

        public function __construct($post) {
            foreach (get_object_vars($post) as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

// Include plugin files that are not autoloaded via Composer
require_once dirname(__DIR__) . '/includes/class-aws-handler.php';
require_once dirname(__DIR__) . '/admin/class-audio-list-admin.php';
require_once dirname(__DIR__) . '/public/class-audio-list-public.php';
