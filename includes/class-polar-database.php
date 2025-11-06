<?php
/**
 * Database Operations
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Database {
    
    /**
     * Ensure database tables exist
     */
    public static function ensure_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        
        $logs = $wpdb->prefix . 'mycred_polar_logs';
        $sql1 = "CREATE TABLE IF NOT EXISTS $logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id varchar(255) NOT NULL,
            points int(11) NOT NULL,
            amount int(11) NOT NULL,
            status varchar(50) NOT NULL,
            webhook_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset;";
        
        $subs = $wpdb->prefix . 'mycred_polar_subscriptions';
        $sql2 = "CREATE TABLE IF NOT EXISTS $subs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            subscription_id varchar(255) NOT NULL,
            product_id varchar(255) DEFAULT '',
            plan_name varchar(255) DEFAULT '',
            points_per_cycle int(11) DEFAULT 0,
            amount int(11) DEFAULT 0,
            currency varchar(10) DEFAULT 'usd',
            recurring_interval varchar(16) DEFAULT '',
            recurring_interval_count int(11) DEFAULT 1,
            status varchar(32) DEFAULT '',
            cancel_at_period_end tinyint(1) DEFAULT 0,
            current_period_start datetime DEFAULT NULL,
            current_period_end datetime DEFAULT NULL,
            started_at datetime DEFAULT NULL,
            canceled_at datetime DEFAULT NULL,
            ends_at datetime DEFAULT NULL,
            ended_at datetime DEFAULT NULL,
            customer_email varchar(190) DEFAULT '',
            customer_external_id varchar(190) DEFAULT '',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_sub (subscription_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY currency (currency)
        ) $charset;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }
    
    /**
     * Migrate schema if needed
     */
    public static function maybe_migrate_schema() {
        global $wpdb;
        self::ensure_tables();
        
        $tbl = $wpdb->prefix . 'mycred_polar_subscriptions';
        $cols = $wpdb->get_results("SHOW COLUMNS FROM $tbl", ARRAY_A);
        if (!is_array($cols)) return;
        
        $have = array();
        foreach ($cols as $c) $have[strtolower($c['Field'])] = true;
        
        $adds = array();
        if (empty($have['plan_name']))                $adds[] = "ADD COLUMN plan_name varchar(255) DEFAULT '' AFTER product_id";
        if (empty($have['points_per_cycle']))         $adds[] = "ADD COLUMN points_per_cycle int(11) DEFAULT 0 AFTER plan_name";
        if (empty($have['recurring_interval']))       $adds[] = "ADD COLUMN recurring_interval varchar(16) DEFAULT '' AFTER currency";
        if (empty($have['recurring_interval_count'])) $adds[] = "ADD COLUMN recurring_interval_count int(11) DEFAULT 1 AFTER recurring_interval";
        if (empty($have['current_period_start']))     $adds[] = "ADD COLUMN current_period_start datetime DEFAULT NULL AFTER cancel_at_period_end";
        if (empty($have['started_at']))               $adds[] = "ADD COLUMN started_at datetime DEFAULT NULL AFTER current_period_end";
        if (empty($have['canceled_at']))              $adds[] = "ADD COLUMN canceled_at datetime DEFAULT NULL AFTER started_at";
        if (empty($have['ends_at']))                  $adds[] = "ADD COLUMN ends_at datetime DEFAULT NULL AFTER canceled_at";
        if (empty($have['ended_at']))                 $adds[] = "ADD COLUMN ended_at datetime DEFAULT NULL AFTER ends_at";
        if (empty($have['customer_email']))           $adds[] = "ADD COLUMN customer_email varchar(190) DEFAULT '' AFTER ended_at";
        if (empty($have['customer_external_id']))     $adds[] = "ADD COLUMN customer_external_id varchar(190) DEFAULT '' AFTER customer_email";
        
        if (!empty($adds)) {
            $sql = "ALTER TABLE $tbl " . implode(", ", $adds);
            $wpdb->query($sql);
        }
    }
    
    /**
     * Update subscription cache row
     */
    public static function update_sub_cache_row($sub_id, $payload) {
        global $wpdb;
        $tbl = $wpdb->prefix . 'mycred_polar_subscriptions';
        
        $wpdb->update($tbl, array(
            'cancel_at_period_end' => !empty($payload['cancel_at_period_end']) ? 1 : 0,
            'current_period_start' => !empty($payload['current_period_start']) ? gmdate('Y-m-d H:i:s', strtotime($payload['current_period_start'])) : null,
            'current_period_end' => !empty($payload['current_period_end']) ? gmdate('Y-m-d H:i:s', strtotime($payload['current_period_end'])) : null,
            'status' => $payload['status'] ?? 'active',
            'canceled_at' => !empty($payload['canceled_at']) ? gmdate('Y-m-d H:i:s', strtotime($payload['canceled_at'])) : null,
            'ended_at' => !empty($payload['ended_at']) ? gmdate('Y-m-d H:i:s', strtotime($payload['ended_at'])) : null,
            'ends_at' => !empty($payload['ends_at']) ? gmdate('Y-m-d H:i:s', strtotime($payload['ends_at'])) : null,
        ), array('subscription_id' => $sub_id));
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', array('MyCred_Polar_Database', 'ensure_tables'));
add_action('plugins_loaded', array('MyCred_Polar_Database', 'maybe_migrate_schema'), 20);