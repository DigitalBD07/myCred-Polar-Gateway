<?php
/**
 * Utility Helper Functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Safely get object property
 */
function mycred_polar_obj_get($obj, $prop, $default = null) {
    return (is_object($obj) && property_exists($obj, $prop)) ? $obj->$prop : $default;
}

/**
 * Check if myCred is active
 */
function mycred_polar_check_mycred() {
    if (!function_exists('mycred')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>myCred Polar.sh</strong> requires the myCred plugin to be installed and activated.</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Format money amount
 */
function mycred_polar_money($amount_cents, $currency = 'usd') {
    $prefix = ($currency === 'usd' || strtoupper($currency) === 'USD') ? '$' : '';
    return $prefix . number_format(((int)$amount_cents) / 100, 2) . ($prefix ? '' : ' ' . strtoupper($currency));
}

/**
 * Format datetime
 */
function mycred_polar_dt($iso_or_sql) {
    if (empty($iso_or_sql)) return '';
    $t = strtotime($iso_or_sql);
    if ($t === false) return esc_html($iso_or_sql);
    return esc_html(gmdate('Y-m-d H:i', $t)) . ' UTC';
}

/**
 * Calculate normalized MRR
 */
function mycred_polar_mrr_normalized($amount_cents, $interval = 'month', $count = 1) {
    $amount = max(0.0, (float)$amount_cents / 100.0);
    $count = max(1, (int)$count);
    $interval = strtolower((string)$interval ?: 'month');
    
    switch ($interval) {
        case 'month': $mrr = $amount / $count; break;
        case 'year': $mrr = $amount / (12.0 * $count); break;
        case 'week': $mrr = $amount * (52.0 / 12.0) / max(1, $count); break;
        case 'day': $mrr = $amount * (365.0 / 12.0) / max(1, $count); break;
        default: $mrr = $amount; break;
    }
    
    return $mrr;
}

/**
 * Get plugin options
 */
function mycred_polar_get_options() {
    $defaults = array(
        'mode' => 'sandbox',
        'access_token_live' => '',
        'access_token_sandbox' => '',
        'product_id_live' => '',
        'product_id_sandbox' => '',
        'exchange_rate' => 0.10,
        'min_points' => 50,
        'default_points' => 100,
        'point_type' => 'mycred_default',
        'webhook_secret' => '',
        'webhook_verify_mode' => 'strict',
        'subscription_plans' => array(),
        'log_entry' => 'Points purchased via Polar.sh (Order: %order_id%)',
    );
    
    $stored = get_option('mycred_polar_options', array());
    if (!is_array($stored)) $stored = array();
    
    $merged = wp_parse_args($stored, $defaults);
    if (!is_array($merged['subscription_plans'])) $merged['subscription_plans'] = array();
    
    return $merged;
}