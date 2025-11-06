<?php
/**
 * Transaction Logs Page
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Logs {
    
    /**
     * Render logs page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) return;
        require MYCRED_POLAR_PATH . 'admin/views/logs-page.php';
    }
}