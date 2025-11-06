<?php
/**
 * Admin Initialization
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_menu() {
        add_menu_page(
            'myCred Polar.sh',
            'myCred Polar.sh',
            'manage_options',
            'mycred_polar_settings',
            array('MyCred_Polar_Settings', 'render_page'),
            'dashicons-money-alt',
            80
        );
        
        add_submenu_page(
            'mycred_polar_settings',
            'Transaction Logs',
            'Transaction Logs',
            'manage_options',
            'mycred_polar_logs',
            array('MyCred_Polar_Logs', 'render_page')
        );
        
        add_submenu_page(
            'mycred_polar_settings',
            'Subscribe',
            'Subscribe',
            'manage_options',
            'mycred_polar_subscribe',
            array('MyCred_Polar_Subscribe', 'render_page')
        );
    }
}

// Initialize admin
new MyCred_Polar_Admin();