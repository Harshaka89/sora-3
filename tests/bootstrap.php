<?php
// Basic test bootstrap for plugin

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Minimal WP_Error implementation
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        public function __construct($code, $message = '') {
            $this->code = $code;
            $this->message = $message;
        }
        public function get_error_code() {
            return $this->code;
        }
        public function get_error_message() {
            return $this->message;
        }
    }
}

// WordPress function stubs
function __($text, $domain = null) { return $text; }
function wp_die($message) { throw new Exception($message); }
function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, $args); }
function current_time($type = 'mysql') { return date('Y-m-d H:i:s'); }
function current_user_can($capability) { return true; }
function do_action($hook, ...$args) { /* no-op */ }
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) { /* no-op */ }
function add_menu_page(...$args) { $GLOBALS['menu_pages'][] = $args; }
function add_submenu_page(...$args) { $GLOBALS['submenu_pages'][] = $args; }

$GLOBALS['enqueued_styles'] = [];
$GLOBALS['enqueued_scripts'] = [];
function wp_enqueue_style(...$args) { $GLOBALS['enqueued_styles'][] = $args; }
function wp_enqueue_script(...$args) { $GLOBALS['enqueued_scripts'][] = $args; }

// Plugin constants
define('YRR_PLUGIN_PATH', dirname(__DIR__) . '/Yenolx Restaurant Reservation System/Yenolx Restaurant Reservation System/');
define('YRR_PLUGIN_URL', '');
define('YRR_VERSION', '1.0');
