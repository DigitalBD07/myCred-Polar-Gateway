<?php
/**
 * Polar.sh API Communication
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_API {
    
    /**
     * Fetch order from Polar API
     */
    public static function fetch_order($order_id) {
        $o = mycred_polar_get_options();
        $mode = $o['mode'];
        $access = ($mode === 'live') ? $o['access_token_live'] : $o['access_token_sandbox'];
        $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
        
        if (empty($access) || empty($order_id)) return null;
        
        $resp = wp_remote_get($api . '/v1/orders/' . rawurlencode($order_id), array(
            'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
            'timeout' => 15
        ));
        
        if (is_wp_error($resp)) return null;
        if (wp_remote_retrieve_response_code($resp) !== 200) return null;
        
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        return is_array($body) ? $body : null;
    }
}