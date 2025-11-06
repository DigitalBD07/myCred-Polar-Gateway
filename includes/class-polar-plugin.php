<?php
/**
 * Main Plugin Class
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        
        // Activation/Deactivation hooks
        register_activation_hook(MYCRED_POLAR_FILE, array($this, 'activate'));
        register_deactivation_hook(MYCRED_POLAR_FILE, array($this, 'deactivate'));
        
        // Settings link
        add_filter('plugin_action_links_' . plugin_basename(MYCRED_POLAR_FILE), array($this, 'settings_link'));
    }
    
    public function init() {
        mycred_polar_check_mycred();
    }
    
    public function activate() {
        MyCred_Polar_Database::ensure_tables();
        MyCred_Polar_Database::maybe_migrate_schema();
        MyCred_Polar_Success::register_rewrite_rules();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function settings_link($links) {
        $settings_link = '<a href="admin.php?page=mycred_polar_settings">Settings</a>';
        $subs_link = '<a href="admin.php?page=mycred_polar_subscribe">Subscribe</a>';
        array_unshift($links, $settings_link, $subs_link);
        return $links;
    }
}