<?php
/**
 * Settings Page Template
 */

if (!defined('ABSPATH')) exit;

$webhook_url = esc_html(rest_url('mycred-polar/v1/webhook'));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="notice notice-info" style="padding: 15px;">
        <h3>Setup</h3>
        <ol>
            <li>Create Polar products: one-time and/or recurring.</li>
            <li>Create an Access Token with scopes: <code>products:read</code>, <code>checkouts:write</code>, <code>orders:read</code>, <code>subscriptions:read</code>, <strong><code>customer_sessions:write</code></strong>, <strong><code>subscriptions:write</code></strong>.</li>
            <li>Webhook: Event <code>order.paid</code>; Secret below; Endpoint: <code><?php echo $webhook_url; ?></code></li>
            <li>Place <code>[mycred_polar_form]</code> on a page.</li>
            <li><strong>Note:</strong> For PWYW products, points are calculated based on actual payment amount (amount ÷ exchange_rate).</li>
        </ol>
    </div>

    <form action="options.php" method="post">
        <?php settings_fields('mycred_polar_settings_group'); ?>
        <?php do_settings_sections('mycred_polar_settings'); ?>
        <?php submit_button('Save Settings'); ?>
    </form>

    <hr>
    <h2>🧪 Test Connection</h2>
    <button type="button" id="mp-test-btn" class="button button-primary">Test Connection</button>
    <div id="mp-test-result" style="margin-top:10px;"></div>

    <script>
    (function(){
        const btn=document.getElementById('mp-test-btn'), res=document.getElementById('mp-test-result');
        btn.addEventListener('click', function(){
            btn.disabled=true; btn.textContent='Testing...'; res.innerHTML='';
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'action=mycred_polar_test_connection&nonce=<?php echo wp_create_nonce('mycred_polar_test'); ?>'
            }).then(r=>r.json()).then(d=>{
                if (d.success) res.innerHTML='<div class="notice notice-success inline" style="padding:10px;"><p>✅ '+d.data.message+'</p></div>';
                else res.innerHTML='<div class="notice notice-error inline" style="padding:10px;"><p>❌ '+(d.data?.message||'Failed')+'</p></div>';
            }).catch(e=>{
                res.innerHTML='<div class="notice notice-error inline" style="padding:10px;"><p>❌ '+e.message+'</p></div>';
            }).finally(()=>{ btn.disabled=false; btn.textContent='Test Connection'; });
        });
    })();
    </script>
</div>