<?php
/**
 * All AJAX Endpoints
 */

if (!defined('ABSPATH')) exit;

/* -----------------------------------------------------------
   AJAX: Create one-time checkout
----------------------------------------------------------- */
function mycred_polar_create_checkout() {
    check_ajax_referer('mycred_polar_checkout', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(array('message' => 'User not logged in'));
    
    $points = intval($_POST['points'] ?? 0);
    $amount = intval($_POST['amount'] ?? 0);
    $user = wp_get_current_user();
    $o = mycred_polar_get_options();
    
    $mode = $o['mode'];
    $access = ($mode === 'live') ? $o['access_token_live'] : $o['access_token_sandbox'];
    $product_id = ($mode === 'live') ? $o['product_id_live'] : $o['product_id_sandbox'];
    $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
    
    if (empty($access) || empty($product_id)) {
        wp_send_json_error(array('message' => 'Polar.sh not configured.'));
    }
    
    // Validate email domain
    $email_domain = substr(strrchr($user->user_email, "@"), 1);
    if (empty($email_domain) || in_array($email_domain, array('example.com', 'test.com', 'demo.com'))) {
        wp_send_json_error(array('message' => 'Please update your WordPress user email to use a real domain (not demo/test/example).'));
    }
    
    // Store requested points and amount in metadata (for PWYW detection)
    $payload = array(
        'products' => array($product_id),
        'amount' => $amount,
        'customer_email' => $user->user_email,
        'external_customer_id' => (string)$user->ID,
        'metadata' => array(
            'user_id' => (string)$user->ID,
            'points' => (string)$points,
            'amount_cents' => (string)$amount,
            'wp_user_email' => $user->user_email,
            'reason' => 'one_time_points',
            'is_pwyw' => '1' // Flag to recalculate on webhook
        ),
        'success_url' => home_url('/mycred-success?checkout_id={CHECKOUT_ID}'),
    );
    
    $resp = wp_remote_post($api . '/v1/checkouts', array(
        'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode($payload),
        'timeout' => 30,
    ));
    
    if (is_wp_error($resp)) {
        wp_send_json_error(array('message' => 'API Error: ' . $resp->get_error_message()));
    }
    
    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    
    if (($code === 200 || $code === 201) && isset($body['url'])) {
        wp_send_json_success(array('url' => $body['url'], 'checkout_id' => $body['id'] ?? ''));
    }
    
    $err = 'Unknown error';
    if (is_array($body)) {
        if (isset($body['detail'])) {
            if (is_array($body['detail'])) {
                $errors = array();
                foreach ($body['detail'] as $detail_item) {
                    if (isset($detail_item['msg'])) {
                        $errors[] = $detail_item['msg'];
                    }
                }
                $err = !empty($errors) ? implode(' | ', array_unique($errors)) : json_encode($body['detail']);
            } else {
                $err = $body['detail'];
            }
        } elseif (isset($body['message'])) {
            $err = is_array($body['message']) ? json_encode($body['message']) : $body['message'];
        }
    }
    
    wp_send_json_error(array('message' => 'Checkout failed: ' . $err, 'status_code' => $code));
}
add_action('wp_ajax_mycred_polar_create_checkout', 'mycred_polar_create_checkout');

/* -----------------------------------------------------------
   AJAX: Create subscription checkout
----------------------------------------------------------- */
function mycred_polar_create_subscription_checkout() {
    check_ajax_referer('mycred_polar_checkout', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(array('message' => 'User not logged in'));
    
    $idx = intval($_POST['plan_index'] ?? -1);
    $o = mycred_polar_get_options();
    $plans = $o['subscription_plans'];
    if ($idx < 0 || !isset($plans[$idx])) wp_send_json_error(array('message' => 'Invalid plan.'));
    
    $plan = $plans[$idx];
    $user = wp_get_current_user();
    
    $mode = $o['mode'];
    $access = ($mode === 'live') ? $o['access_token_live'] : $o['access_token_sandbox'];
    $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
    
    if (empty($access) || empty($plan['product_id'])) {
        wp_send_json_error(array('message' => 'Polar.sh not configured.'));
    }
    
    // Validate email domain
    $email_domain = substr(strrchr($user->user_email, "@"), 1);
    if (empty($email_domain) || in_array($email_domain, array('example.com', 'test.com', 'demo.com'))) {
        wp_send_json_error(array('message' => 'Please update your WordPress user email to use a real domain (not demo/test/example).'));
    }
    
    $use_custom = !empty($plan['use_custom_amount']);
    $amount = 0;
    if ($use_custom) {
        $amount = intval(round(floatval($o['exchange_rate']) * intval($plan['points_per_cycle']) * 100));
    }
    
    $meta = array(
        'user_id' => (string)$user->ID,
        'points_per_cycle' => (string)intval($plan['points_per_cycle']),
        'plan_name' => (string)$plan['name'],
        'plan_index' => (string)$idx,
        'wp_user_email' => $user->user_email,
        'reason' => 'subscription_points'
    );
    
    $payload = array(
        'products' => array($plan['product_id']),
        'customer_email' => $user->user_email,
        'external_customer_id' => (string)$user->ID,
        'metadata' => $meta,
        'success_url' => home_url('/mycred-success?checkout_id={CHECKOUT_ID}'),
    );
    
    if ($use_custom && $amount > 0) {
        $payload['amount'] = $amount;
    }
    
    $resp = wp_remote_post($api . '/v1/checkouts', array(
        'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode($payload),
        'timeout' => 30,
    ));
    
    if (is_wp_error($resp)) {
        wp_send_json_error(array('message' => 'API Error: ' . $resp->get_error_message()));
    }
    
    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    
    if (($code === 200 || $code === 201) && isset($body['url'])) {
        wp_send_json_success(array('url' => $body['url'], 'checkout_id' => $body['id'] ?? ''));
    }
    
    $err = 'Unknown error';
    if (is_array($body)) {
        if (isset($body['detail'])) {
            if (is_array($body['detail'])) {
                $errors = array();
                foreach ($body['detail'] as $detail_item) {
                    if (isset($detail_item['msg'])) {
                        $errors[] = $detail_item['msg'];
                    }
                }
                $err = !empty($errors) ? implode(' | ', array_unique($errors)) : json_encode($body['detail']);
            } else {
                $err = $body['detail'];
            }
        } elseif (isset($body['message'])) {
            $err = is_array($body['message']) ? json_encode($body['message']) : $body['message'];
        }
    }
    
    wp_send_json_error(array('message' => 'Subscription checkout failed: ' . $err, 'status_code' => $code));
}
add_action('wp_ajax_mycred_polar_create_subscription_checkout', 'mycred_polar_create_subscription_checkout');

/* -----------------------------------------------------------
   AJAX: Test connection
----------------------------------------------------------- */
function mycred_polar_test_connection() {
    check_ajax_referer('mycred_polar_test', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Permission denied'));
    
    $o = mycred_polar_get_options();
    $mode = $o['mode'];
    $access = ($mode === 'live') ? $o['access_token_live'] : $o['access_token_sandbox'];
    $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
    
    if (empty($access)) wp_send_json_error(array('message' => 'Access token not set.'));
    
    $resp = wp_remote_get($api . '/v1/products', array(
        'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
        'timeout' => 15
    ));
    
    if (is_wp_error($resp)) wp_send_json_error(array('message' => 'Connection error: ' . $resp->get_error_message()));
    
    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    
    if ($code === 200) {
        wp_send_json_success(array('message' => 'Connection OK (' . ucfirst($mode) . ') — products: ' . (isset($body['items']) ? count($body['items']) : 'N/A')));
    }
    
    $msg = is_array($body) ? ($body['detail'] ?? ($body['message'] ?? 'Unknown error')) : 'Unknown error';
    wp_send_json_error(array('message' => 'Failed (' . $code . '): ' . $msg));
}
add_action('wp_ajax_mycred_polar_test_connection', 'mycred_polar_test_connection');

/* -----------------------------------------------------------
   AJAX: List user subscriptions (UI)
----------------------------------------------------------- */
function mycred_polar_list_subscriptions() {
    check_ajax_referer('mycred_polar_manage', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(array('message' => 'User not logged in'));
    
    $o = mycred_polar_get_options();
    $mode = $o['mode'];
    $access = ($mode === 'live') ? $o['access_token_live'] : $o['access_token_sandbox'];
    $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
    if (empty($access)) wp_send_json_error(array('message' => 'Access token missing'));
    
    $user = wp_get_current_user();
    
    $items = array();
    
    // Try 1: Query by external_customer_id
    $url1 = add_query_arg(array(
        'external_customer_id' => (string)$user->ID,
        'active' => 'true',
        'limit' => 100,
    ), $api . '/v1/subscriptions');
    
    $r1 = wp_remote_get($url1, array(
        'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
        'timeout' => 15
    ));
    
    if (!is_wp_error($r1) && wp_remote_retrieve_response_code($r1) === 200) {
        $b1 = json_decode(wp_remote_retrieve_body($r1), true);
        if (is_array($b1) && !empty($b1['items'])) $items = $b1['items'];
    }
    
    // Try 2: Query by metadata user_id if first attempt failed
    if (empty($items)) {
        $url2 = add_query_arg(array(
            'active' => 'true',
            'limit' => 100,
            'metadata[user_id]' => (string)$user->ID
        ), $api . '/v1/subscriptions');
        
        $r2 = wp_remote_get($url2, array(
            'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
            'timeout' => 15
        ));
        
        if (!is_wp_error($r2) && wp_remote_retrieve_response_code($r2) === 200) {
            $b2 = json_decode(wp_remote_retrieve_body($r2), true);
            if (is_array($b2) && !empty($b2['items'])) $items = $b2['items'];
        }
    }
    
    // Try 3: Customer state endpoint
    if (empty($items)) {
        $r3 = wp_remote_get($api . '/v1/customers/external/' . rawurlencode((string)$user->ID) . '/state', array(
            'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
            'timeout' => 15
        ));
        
        if (!is_wp_error($r3) && wp_remote_retrieve_response_code($r3) === 200) {
            $b3 = json_decode(wp_remote_retrieve_body($r3), true);
            if (is_array($b3) && !empty($b3['active_subscriptions'])) {
                $items = $b3['active_subscriptions'];
            }
        }
    }
    
    $out = array();
    if (!empty($items)) {
        global $wpdb;
        $subs_tbl = $wpdb->prefix . 'mycred_polar_subscriptions';
        
        foreach ($items as $s) {
            $meta = $s['metadata'] ?? array();
            $out[] = array(
                'id' => $s['id'] ?? '',
                'amount' => isset($s['amount']) ? intval($s['amount']) : null,
                'currency' => $s['currency'] ?? 'usd',
                'recurring_interval' => $s['recurring_interval'] ?? '',
                'recurring_interval_count' => isset($s['recurring_interval_count']) ? intval($s['recurring_interval_count']) : 1,
                'current_period_end' => $s['current_period_end'] ?? '',
                'cancel_at_period_end' => !empty($s['cancel_at_period_end']),
                'plan_name' => ($s['product']['name'] ?? ($meta['plan_name'] ?? '')),
                'points_per_cycle' => isset($meta['points_per_cycle']) ? intval($meta['points_per_cycle']) : null,
                'product_name' => $s['product']['name'] ?? null,
            );
            
            // Cache subscription in database
            if (!empty($s['id'])) {
                $wpdb->replace($subs_tbl, array(
                    'user_id' => get_current_user_id(),
                    'subscription_id' => $s['id'],
                    'product_id' => $s['product_id'] ?? '',
                    'plan_name' => $s['product']['name'] ?? ($meta['plan_name'] ?? ''),
                    'points_per_cycle' => intval($meta['points_per_cycle'] ?? 0),
                    'amount' => intval($s['amount'] ?? 0),
                    'currency' => $s['currency'] ?? 'usd',
                    'recurring_interval' => $s['recurring_interval'] ?? '',
                    'recurring_interval_count' => intval($s['recurring_interval_count'] ?? 1),
                    'status' => $s['status'] ?? '',
                    'cancel_at_period_end' => !empty($s['cancel_at_period_end']) ? 1 : 0,
                    'current_period_start' => !empty($s['current_period_start']) ? gmdate('Y-m-d H:i:s', strtotime($s['current_period_start'])) : null,
                    'current_period_end' => !empty($s['current_period_end']) ? gmdate('Y-m-d H:i:s', strtotime($s['current_period_end'])) : null,
                    'started_at' => !empty($s['started_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['started_at'])) : null,
                    'canceled_at' => !empty($s['canceled_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['canceled_at'])) : null,
                    'ends_at' => !empty($s['ends_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['ends_at'])) : null,
                    'ended_at' => !empty($s['ended_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['ended_at'])) : null,
                    'customer_email' => $s['customer']['email'] ?? '',
                    'customer_external_id' => $s['customer']['external_id'] ?? '',
                ), array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
            }
        }
    }
    
    wp_send_json_success(array('items' => $out));
}
add_action('wp_ajax_mycred_polar_list_subscriptions', 'mycred_polar_list_subscriptions');

/* -----------------------------------------------------------
   AJAX: Cancel subscription (Customer Portal + Core API fallback)
----------------------------------------------------------- */
function mycred_polar_cancel_subscription() {
    check_ajax_referer('mycred_polar_manage', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error(array('message' => 'User not logged in'));
    
    $sub_id = sanitize_text_field($_POST['subscription_id'] ?? '');
    if (empty($sub_id)) wp_send_json_error(array('message' => 'Missing subscription_id'));
    
    $o = mycred_polar_get_options();
    $mode = $o['mode'];
    $access = ($mode === 'live') ? ($o['access_token_live'] ?? '') : ($o['access_token_sandbox'] ?? '');
    $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
    
    if (empty($access)) wp_send_json_error(array('message' => 'Access token missing'));
    
    $user = wp_get_current_user();
    $wp_user_id = (string)$user->ID;
    
    // Step 1: Fetch subscription to verify ownership
    $resp = wp_remote_get($api . '/v1/subscriptions/' . rawurlencode($sub_id), array(
        'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
        'timeout' => 20,
    ));
    
    if (is_wp_error($resp)) wp_send_json_error(array('message' => 'API error: ' . $resp->get_error_message()));
    
    $code = wp_remote_retrieve_response_code($resp);
    $sub = json_decode(wp_remote_retrieve_body($resp), true);
    
    if ($code !== 200 || !is_array($sub)) {
        $msg = is_array($sub) ? ($sub['detail'] ?? ($sub['message'] ?? 'Unknown error')) : 'Unknown error';
        wp_send_json_error(array('message' => 'Failed to load subscription: ' . $msg, 'status_code' => $code));
    }
    
    // Step 2: Verify ownership
    $meta_user_id = (string)($sub['metadata']['user_id'] ?? '');
    $cust_ext_id = (string)($sub['customer']['external_id'] ?? '');
    $customer_id = (string)($sub['customer_id'] ?? ($sub['customer']['id'] ?? ''));
    $owned = ($meta_user_id !== '' && $meta_user_id === $wp_user_id) || ($cust_ext_id !== '' && $cust_ext_id === $wp_user_id);
    
    // Additional ownership check via customer endpoint
    if (!$owned && !empty($customer_id)) {
        $c = wp_remote_get($api . '/v1/customers/' . rawurlencode($customer_id), array(
            'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
            'timeout' => 15
        ));
        
        if (!is_wp_error($c) && wp_remote_retrieve_response_code($c) === 200) {
            $cbody = json_decode(wp_remote_retrieve_body($c), true);
            if (is_array($cbody) && (string)($cbody['external_id'] ?? '') === $wp_user_id) $owned = true;
        }
    }
    
    if (!$owned) wp_send_json_error(array('message' => 'Subscription not found or not owned by you.'));
    
    // Step 3: Try Customer Portal cancellation first (preferred method)
    $cust_token = null;
    if (!empty($customer_id)) {
        $sess_body = array('customer_id' => $customer_id);
        $sess = wp_remote_post($api . '/v1/customer-sessions', array(
            'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
            'body' => wp_json_encode($sess_body),
            'timeout' => 20,
        ));
        
        if (!is_wp_error($sess) && wp_remote_retrieve_response_code($sess) === 201) {
            $sb = json_decode(wp_remote_retrieve_body($sess), true);
            if (is_array($sb) && !empty($sb['token'])) $cust_token = $sb['token'];
        }
    }
    
    $cp_error = null;
    if ($cust_token) {
        $cancel = wp_remote_request($api . '/v1/customer-portal/subscriptions/' . rawurlencode($sub_id), array(
            'method' => 'DELETE',
            'headers' => array('Authorization' => 'Bearer ' . $cust_token, 'Content-Type' => 'application/json'),
            'timeout' => 20,
        ));
        
        if (is_wp_error($cancel)) {
            $cp_error = 'Customer Portal cancel error: ' . $cancel->get_error_message();
        } else {
            $cp_code = wp_remote_retrieve_response_code($cancel);
            $cp_raw = wp_remote_retrieve_body($cancel);
            $cp_body = json_decode($cp_raw, true);
            
            if ($cp_code === 200 && is_array($cp_body)) {
                MyCred_Polar_Database::update_sub_cache_row($sub_id, $cp_body);
                wp_send_json_success(array(
                    'message' => 'Canceled at period end',
                    'cancel_at_period_end' => $cp_body['cancel_at_period_end'] ?? true,
                    'current_period_end' => $cp_body['current_period_end'] ?? null,
                ));
            } else {
                $cp_error = 'Customer Portal cancel failed (' . $cp_code . '): ' . (is_array($cp_body) ? ($cp_body['detail'] ?? ($cp_body['message'] ?? 'Unknown')) : substr((string)$cp_raw, 0, 200));
            }
        }
    }
    
    // Step 4: Fallback to Core API PATCH method
    $patch = wp_remote_request($api . '/v1/subscriptions/' . rawurlencode($sub_id), array(
        'method' => 'PATCH',
        'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode(array('cancel_at_period_end' => true, 'customer_cancellation_reason' => 'other')),
        'timeout' => 20,
    ));
    
    if (!is_wp_error($patch) && wp_remote_retrieve_response_code($patch) === 200) {
        $pb = json_decode(wp_remote_retrieve_body($patch), true);
        if (is_array($pb)) {
            MyCred_Polar_Database::update_sub_cache_row($sub_id, $pb);
            wp_send_json_success(array(
                'message' => 'Canceled at period end (via core API)',
                'cancel_at_period_end' => $pb['cancel_at_period_end'] ?? true,
                'current_period_end' => $pb['current_period_end'] ?? null,
            ));
        }
    }
    
    // Step 5: Both methods failed - return error
    $all_msgs = array();
    if ($cp_error) $all_msgs[] = $cp_error;
    if (!is_wp_error($patch)) {
        $pc = wp_remote_retrieve_response_code($patch);
        $pb = json_decode(wp_remote_retrieve_body($patch), true);
        $all_msgs[] = 'Core API cancel failed (' . $pc . '): ' . (is_array($pb) ? ($pb['detail'] ?? ($pb['message'] ?? 'Unknown')) : 'Unknown');
    } else {
        $all_msgs[] = 'Core API cancel error: ' . $patch->get_error_message();
    }
    
    wp_send_json_error(array('message' => implode(' | ', $all_msgs)));
}
add_action('wp_ajax_mycred_polar_cancel_subscription', 'mycred_polar_cancel_subscription');

/* -----------------------------------------------------------
   AJAX: Admin sync subscriptions from Polar
----------------------------------------------------------- */
function mycred_polar_admin_sync_subscriptions() {
    check_ajax_referer('mycred_polar_admin', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(array('message' => 'Permission denied'));
    
    $o = mycred_polar_get_options();
    $mode = $o['mode'];
    $access = ($mode === 'live') ? $o['access_token_live'] : $o['access_token_sandbox'];
    $api = ($mode === 'live') ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
    
    if (empty($access)) wp_send_json_error(array('message' => 'Access token missing'));
    
    $total = 0;
    $active = 0;
    $canceled = 0;
    $errs = array();
    
    // Pull subscriptions function
    $pull = function($activeFlag) use ($api, $access, &$total, &$active, &$canceled, &$errs) {
        global $wpdb;
        $tbl = $wpdb->prefix . 'mycred_polar_subscriptions';
        $page = 1;
        $limit = 100;
        $pages = 0;
        
        do {
            $q = array('limit' => $limit, 'page' => $page);
            if (!is_null($activeFlag)) $q['active'] = $activeFlag ? 'true' : 'false';
            $url = add_query_arg($q, $api . '/v1/subscriptions');
            
            $resp = wp_remote_get($url, array(
                'headers' => array('Authorization' => 'Bearer ' . $access, 'Content-Type' => 'application/json'),
                'timeout' => 25
            ));
            
            if (is_wp_error($resp)) {
                $errs[] = $resp->get_error_message();
                break;
            }
            
            $code = wp_remote_retrieve_response_code($resp);
            $body = json_decode(wp_remote_retrieve_body($resp), true);
            
            if ($code !== 200 || !is_array($body)) {
                $errs[] = 'HTTP ' . $code;
                break;
            }
            
            $items = $body['items'] ?? array();
            
            foreach ($items as $s) {
                $total++;
                $isActive = in_array(($s['status'] ?? ''), array('active', 'trialing', 'past_due'), true);
                if ($isActive) $active++;
                else $canceled++;
                
                $user_id = 0;
                if (!empty($s['customer']['external_id']) && ctype_digit((string)$s['customer']['external_id'])) {
                    $user_id = intval($s['customer']['external_id']);
                }
                
                $wpdb->replace($tbl, array(
                    'user_id' => $user_id,
                    'subscription_id' => $s['id'] ?? '',
                    'product_id' => $s['product_id'] ?? '',
                    'plan_name' => $s['product']['name'] ?? '',
                    'points_per_cycle' => intval($s['metadata']['points_per_cycle'] ?? 0),
                    'amount' => intval($s['amount'] ?? 0),
                    'currency' => $s['currency'] ?? 'usd',
                    'recurring_interval' => $s['recurring_interval'] ?? '',
                    'recurring_interval_count' => intval($s['recurring_interval_count'] ?? 1),
                    'status' => $s['status'] ?? '',
                    'cancel_at_period_end' => !empty($s['cancel_at_period_end']) ? 1 : 0,
                    'current_period_start' => !empty($s['current_period_start']) ? gmdate('Y-m-d H:i:s', strtotime($s['current_period_start'])) : null,
                    'current_period_end' => !empty($s['current_period_end']) ? gmdate('Y-m-d H:i:s', strtotime($s['current_period_end'])) : null,
                    'started_at' => !empty($s['started_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['started_at'])) : null,
                    'canceled_at' => !empty($s['canceled_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['canceled_at'])) : null,
                    'ends_at' => !empty($s['ends_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['ends_at'])) : null,
                    'ended_at' => !empty($s['ended_at']) ? gmdate('Y-m-d H:i:s', strtotime($s['ended_at'])) : null,
                    'customer_email' => $s['customer']['email'] ?? '',
                    'customer_external_id' => $s['customer']['external_id'] ?? '',
                ), array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
            }
            
            $pages++;
            $page++;
            
        } while (!empty($items) && $pages < 20);
    };
    
    // Pull active subscriptions
    $pull(true);
    
    // Pull inactive/canceled subscriptions
    $pull(false);
    
    // Update last sync timestamp
    update_option('mycred_polar_last_sync', time());
    
    if (!empty($errs)) {
        wp_send_json_error(array(
            'message' => 'Some pages failed: ' . implode(' | ', array_unique($errs)),
            'total' => $total,
            'active' => $active,
            'canceled' => $canceled
        ));
    }
    
    wp_send_json_success(array(
        'total' => $total,
        'active' => $active,
        'canceled' => $canceled
    ));
}
add_action('wp_ajax_mycred_polar_admin_sync_subscriptions', 'mycred_polar_admin_sync_subscriptions');