<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock WordPress constants that might be needed
define('WPINC', true);

// Include plugin files that are not autoloaded via Composer
require_once dirname(__DIR__) . '/includes/class-aws-handler.php';
require_once dirname(__DIR__) . '/admin/class-audio-list-admin.php';
require_once dirname(__DIR__) . '/public/class-audio-list-public.php';
