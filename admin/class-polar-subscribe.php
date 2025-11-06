<?php
/**
 * Subscribe Dashboard Page
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Subscribe {
    
    public function __construct() {
        add_action('admin_post_mycred_polar_export_subscriptions', array($this, 'export_csv'));
    }
    
    /**
     * Render subscribe page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) return;
        require MYCRED_POLAR_PATH . 'admin/views/subscribe-page.php';
    }
    
    /**
     * Export subscriptions to CSV
     */
    public function export_csv() {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        check_admin_referer('mycred_polar_export');
        
        global $wpdb;
        $tbl = $wpdb->prefix . 'mycred_polar_subscriptions';
        $rows = $wpdb->get_results("SELECT * FROM $tbl ORDER BY updated_at DESC", ARRAY_A);
        
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=polar_subscriptions_' . gmdate('Ymd_His') . '.csv');
        
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $r) fputcsv($out, $r);
        } else {
            fputcsv($out, array('No data'));
        }
        fclose($out);
        exit;
    }
}

// Initialize subscribe page
new MyCred_Polar_Subscribe();