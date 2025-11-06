<?php
/**
 * Success Page Handler
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Success {
    
    public function __construct() {
        add_action('init', array($this, 'register_rewrite_rules'));
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }
    
    /**
     * Register rewrite rules
     */
    public static function register_rewrite_rules() {
        add_rewrite_rule('^mycred-success/?', 'index.php?mycred_polar_success=1', 'top');
    }
    
    /**
     * Add query vars
     */
    public function query_vars($vars) {
        $vars[] = 'mycred_polar_success';
        return $vars;
    }
    
    /**
     * Template redirect
     */
    public function template_redirect() {
        if (get_query_var('mycred_polar_success')) {
            $this->render_success_page();
            exit;
        }
    }
    
    /**
     * Render success page with fallback credit
     */
    private function render_success_page() {
        if (!isset($_GET['checkout_id'])) return;
        
        $checkout_id = sanitize_text_field($_GET['checkout_id']);
        
        $o = mycred_polar_get_options();
        $mode = $o['mode'];
        $access = ($mode === 'live') ? $o['access_token_live'] : $o['access_token_sandbox'];
        $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
        
        if (!empty($access) && !empty($checkout_id) && mycred_polar_check_mycred()) {
            for ($i = 0; $i < 3; $i++) {
                $url = add_query_arg(array('checkout_id' => $checkout_id), $api . '/v1/orders');
                $resp = wp_remote_get($url, array(
                    'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
                    'timeout' => 15
                ));
                
                if (!is_wp_error($resp)) {
                    $code = wp_remote_retrieve_response_code($resp);
                    $body = json_decode(wp_remote_retrieve_body($resp), true);
                    
                    if ($code === 200 && !empty($body['items'])) {
                        $order = $body['items'][0];
                        if (($order['status'] ?? '') === 'paid') {
                            $meta = $order['metadata'] ?? array();
                            $user_id = intval($meta['user_id'] ?? 0);
                            $amount = intval($order['net_amount'] ?? ($order['amount'] ?? 0));
                            
                            // PWYW RECALCULATION
                            $points = 0;
                            $is_pwyw = !empty($meta['is_pwyw']);
                            $is_subscription = !empty($order['subscription_id']);
                            
                            if ($is_pwyw && !$is_subscription) {
                                $exchange_rate = floatval($o['exchange_rate']);
                                if ($exchange_rate > 0) {
                                    $points = intval(round($amount / 100 / $exchange_rate));
                                }
                            }
                            
                            if ($points <= 0 && isset($meta['points'])) {
                                $points = intval($meta['points']);
                            }
                            if ($points <= 0 && isset($meta['points_per_cycle'])) {
                                $points = intval($meta['points_per_cycle']);
                            }
                            if ($points <= 0 && $is_subscription) {
                                $smeta = $order['subscription']['metadata'] ?? array();
                                if (!empty($smeta['points_per_cycle'])) {
                                    $points = intval($smeta['points_per_cycle']);
                                }
                            }
                            
                            $oid = $order['id'] ?? $checkout_id;
                            
                            if ($user_id > 0 && $points > 0) {
                                MyCred_Polar_Awards::upsert_subscription_cache_from_order($user_id, $order);
                                MyCred_Polar_Awards::award_points($user_id, $points, $oid, $amount, 'success-fallback');
                            }
                            break;
                        }
                    }
                }
                usleep(400000);
            }
        }
        
        require MYCRED_POLAR_PATH . 'public/views/success-page.php';
    }
}

// Initialize success page handler
new MyCred_Polar_Success();