<?php
/**
 * Webhook Handling & Verification
 */

if (!defined('ABSPATH')) exit;

class MyCred_Polar_Webhook {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoint'));
    }
    
    /**
     * Register REST endpoint
     */
    public function register_endpoint() {
        register_rest_route('mycred-polar/v1', '/webhook', array(
            array('methods' => 'POST', 'callback' => array($this, 'handle_webhook'), 'permission_callback' => '__return_true'),
            array('methods' => 'GET', 'callback' => function() {
                return new WP_REST_Response(array('ok' => true, 'message' => 'myCred Polar webhook alive (POST only).'), 200);
            }, 'permission_callback' => '__return_true'),
        ));
    }
    
    /**
     * Pick header from request
     */
    private function pick_header(WP_REST_Request $request, array $names) {
        foreach ($names as $n) {
            $v = $request->get_header($n);
            if ($v !== null && $v !== '') return $v;
        }
        return '';
    }
    
    /**
     * Verify webhook signature (Svix standard)
     */
    public function verify_signature(WP_REST_Request $request, string $payload, string $secret): bool {
        $id = $this->pick_header($request, array('webhook-id', 'svix-id', 'x-webhook-id'));
        $ts = $this->pick_header($request, array('webhook-timestamp', 'svix-timestamp', 'x-webhook-timestamp'));
        $sig = $this->pick_header($request, array('webhook-signature', 'svix-signature', 'x-webhook-signature', 'signature'));
        
        if ($id === '' || $ts === '' || $sig === '') {
            error_log('Polar Webhook: missing headers');
            return false;
        }
        
        $ts_num = null;
        if (ctype_digit((string)$ts)) {
            $ts_num = (int)$ts;
        } else {
            $p = strtotime($ts);
            if ($p !== false) $ts_num = $p;
        }
        
        if ($ts_num === null || abs(time() - $ts_num) > 900) {
            error_log('Polar Webhook: timestamp outside tolerance');
            return false;
        }
        
        $raw = trim($secret);
        if (preg_match('/^[A-Za-z0-9]+_(.+)$/', $raw, $m) && $m[1] !== '') $raw = $m[1];
        $key = base64_decode($raw, true);
        if ($key === false) {
            $b64 = strtr($raw, '-_', '+/');
            $b64 .= str_repeat('=', (4 - strlen($b64) % 4) % 4);
            $key = base64_decode($b64, true);
        }
        if ($key === false) {
            $key = $secret;
        }
        
        $signed = $id . '.' . $ts . '.' . $payload;
        $mac = hash_hmac('sha256', $signed, $key, true);
        $expected_b64 = base64_encode($mac);
        $expected_b64url = rtrim(strtr($expected_b64, '+/', '-_'), '=');
        
        $candidates = array();
        foreach (preg_split('/[,\s]+/', trim($sig)) as $tok) {
            if ($tok === '') continue;
            if (stripos($tok, 'v1=') === 0 || stripos($tok, 'v1,') === 0) $tok = substr($tok, 3);
            if (preg_match('/^[A-Za-z0-9+\/=_-]{10,}$/', $tok)) $candidates[] = $tok;
        }
        
        foreach ($candidates as $p) {
            if (hash_equals($expected_b64, $p) || hash_equals($expected_b64url, $p)) return true;
        }
        
        return false;
    }
    
    /**
     * Handle webhook with PWYW recalculation
     */
    public function handle_webhook($request) {
        if (!mycred_polar_check_mycred()) return new WP_REST_Response(array('error' => 'myCred not active'), 500);
        
        $o = mycred_polar_get_options();
        $secret = trim($o['webhook_secret'] ?? '');
        $mode = $o['webhook_verify_mode'] ?? 'strict';
        
        $payload = $request->get_body();
        
        $verified = false;
        if (!empty($secret)) $verified = $this->verify_signature($request, $payload, $secret);
        
        $evt = json_decode($payload, true);
        $type = $evt['type'] ?? '';
        
        if ($mode === 'strict' && empty($secret)) {
            return new WP_REST_Response(array('error' => 'Signature required but secret not set'), 403);
        }
        if ($mode === 'strict' && !$verified) {
            return new WP_REST_Response(array('error' => 'Invalid signature'), 403);
        }
        
        if ($type !== 'order.paid') {
            return new WP_REST_Response(array('success' => true, 'message' => 'Event ignored'), 200);
        }
        
        $order = $evt['data'] ?? array();
        $order_id = $order['id'] ?? '';
        $status = $order['status'] ?? '';
        
        if (($mode === 'api_fallback' && !$verified) || $mode === 'disabled') {
            if (!empty($order_id)) {
                $live = MyCred_Polar_API::fetch_order($order_id);
                if (is_array($live)) {
                    $order = $live;
                    $status = $order['status'] ?? $status;
                }
            }
        }
        
        if ($status !== 'paid') {
            return new WP_REST_Response(array('success' => true, 'message' => 'Order not paid'), 200);
        }
        
        $meta = $order['metadata'] ?? array();
        $user_id = intval($meta['user_id'] ?? 0);
        $amount = intval($order['net_amount'] ?? ($order['amount'] ?? 0));
        
        // PWYW RECALCULATION: Calculate points from actual amount
        $points = 0;
        $is_pwyw = !empty($meta['is_pwyw']);
        $is_subscription = !empty($order['subscription_id']);
        
        if ($is_pwyw && !$is_subscription) {
            // One-time PWYW: calculate from actual paid amount
            $exchange_rate = floatval($o['exchange_rate']);
            if ($exchange_rate > 0) {
                $points = intval(round($amount / 100 / $exchange_rate));
                error_log("Polar PWYW: Recalculated points = {$points} from amount {$amount} cents (rate: {$exchange_rate})");
            }
        }
        
        // Fallback to metadata if not calculated
        if ($points <= 0 && isset($meta['points'])) {
            $points = intval($meta['points']);
        }
        if ($points <= 0 && isset($meta['points_per_cycle'])) {
            $points = intval($meta['points_per_cycle']);
        }
        if ($points <= 0 && $is_subscription) {
            $sub_meta = $order['subscription']['metadata'] ?? array();
            if (isset($sub_meta['points_per_cycle'])) {
                $points = intval($sub_meta['points_per_cycle']);
            }
        }
        
        if ($user_id <= 0 || $points <= 0 || empty($order_id)) {
            return new WP_REST_Response(array('error' => 'Invalid data'), 400);
        }
        
        MyCred_Polar_Awards::upsert_subscription_cache_from_order($user_id, $order);
        
        $ok = MyCred_Polar_Awards::award_points($user_id, $points, $order_id, $amount, $payload);
        return new WP_REST_Response(array('success' => $ok), $ok ? 202 : 500);
    }
}

// Initialize webhook handler
new MyCred_Polar_Webhook();