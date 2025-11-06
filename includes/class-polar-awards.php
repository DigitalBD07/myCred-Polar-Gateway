<?php
/**
 * Points Awarding Logic
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Awards {
    
    /**
     * Award points with PWYW recalculation support
     */
    public static function award_points($user_id, $points, $order_id, $amount_cents, $raw_payload = '') {
        global $wpdb;
        MyCred_Polar_Database::ensure_tables();
        
        $lock_key = 'mycred_polar_lock_' . md5($order_id);
        
        if (get_transient($lock_key)) {
            error_log('Polar Award: lock present for ' . $order_id);
            return true;
        }
        
        set_transient($lock_key, 1, 60);
        
        $logs = $wpdb->prefix . 'mycred_polar_logs';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $logs WHERE order_id=%s LIMIT 1", $order_id));
        
        if ($exists) {
            delete_transient($lock_key);
            return true;
        }
        
        $o = mycred_polar_get_options();
        $pt = $o['point_type'] ?? 'mycred_default';
        $log = $o['log_entry'] ?? 'Points purchased via Polar.sh (Order: %order_id%)';
        $log = str_replace(array('%points%', '%order_id%', '%amount%'), array($points, $order_id, '$' . number_format($amount_cents / 100, 2)), $log);
        
        if (!function_exists('mycred_add')) {
            delete_transient($lock_key);
            return false;
        }
        
        $ok = mycred_add('polar_purchase', $user_id, $points, $log, 0, '', $pt);
        
        if ($ok === false) {
            $wpdb->insert($logs, array(
                'user_id' => $user_id,
                'order_id' => $order_id,
                'points' => $points,
                'amount' => $amount_cents,
                'status' => 'failed',
                'webhook_data' => $raw_payload
            ));
            delete_transient($lock_key);
            return false;
        }
        
        $wpdb->insert($logs, array(
            'user_id' => $user_id,
            'order_id' => $order_id,
            'points' => $points,
            'amount' => $amount_cents,
            'status' => 'success',
            'webhook_data' => $raw_payload
        ));
        
        delete_transient($lock_key);
        error_log("Polar Award: +{$points} pts to user {$user_id} (order {$order_id}, amount: \${$amount_cents})");
        return true;
    }
    
    /**
     * Upsert subscription cache from order
     */
    public static function upsert_subscription_cache_from_order($user_id, $order) {
        if (empty($order['subscription_id'])) return;
        
        global $wpdb;
        $tbl = $wpdb->prefix . 'mycred_polar_subscriptions';
        $meta = $order['subscription']['metadata'] ?? $order['metadata'] ?? array();
        $sub = $order['subscription'] ?? array();
        
        $wpdb->replace($tbl, array(
            'user_id' => $user_id,
            'subscription_id' => $order['subscription_id'],
            'product_id' => $order['product_id'] ?? '',
            'plan_name' => $sub['product']['name'] ?? ($meta['plan_name'] ?? ''),
            'points_per_cycle' => intval($meta['points_per_cycle'] ?? 0),
            'amount' => intval($order['amount'] ?? $order['net_amount'] ?? 0),
            'currency' => $order['currency'] ?? 'usd',
            'recurring_interval' => $sub['recurring_interval'] ?? '',
            'recurring_interval_count' => intval($sub['recurring_interval_count'] ?? 1),
            'status' => $sub['status'] ?? 'active',
            'cancel_at_period_end' => !empty($sub['cancel_at_period_end']) ? 1 : 0,
            'current_period_start' => !empty($sub['current_period_start']) ? gmdate('Y-m-d H:i:s', strtotime($sub['current_period_start'])) : null,
            'current_period_end' => !empty($sub['current_period_end']) ? gmdate('Y-m-d H:i:s', strtotime($sub['current_period_end'])) : null,
            'started_at' => !empty($sub['started_at']) ? gmdate('Y-m-d H:i:s', strtotime($sub['started_at'])) : null,
            'canceled_at' => !empty($sub['canceled_at']) ? gmdate('Y-m-d H:i:s', strtotime($sub['canceled_at'])) : null,
            'ends_at' => !empty($sub['ends_at']) ? gmdate('Y-m-d H:i:s', strtotime($sub['ends_at'])) : null,
            'ended_at' => !empty($sub['ended_at']) ? gmdate('Y-m-d H:i:s', strtotime($sub['ended_at'])) : null,
            'customer_email' => $order['customer']['email'] ?? '',
            'customer_external_id' => $order['customer']['external_id'] ?? '',
        ), array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
    }
}