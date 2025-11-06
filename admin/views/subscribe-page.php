<?php
/**
 * Subscribe Dashboard Page Template
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$tbl = $wpdb->prefix . 'mycred_polar_subscriptions';

MyCred_Polar_Database::maybe_migrate_schema();

$rows = $wpdb->get_results("SELECT * FROM $tbl ORDER BY updated_at DESC LIMIT 500");

$stats = array();
foreach ($rows as $r) {
    $cur = strtoupper(mycred_polar_obj_get($r, 'currency', 'USD'));
    if (!isset($stats[$cur])) $stats[$cur] = array('active' => 0, 'canceling' => 0, 'canceled_30' => 0, 'mrr' => 0.0);

    $status = (string)mycred_polar_obj_get($r, 'status', '');
    $active_flag = in_array($status, array('active', 'trialing', 'past_due'), true);
    $canceling = $active_flag && (intval(mycred_polar_obj_get($r, 'cancel_at_period_end', 0)) === 1);
    $canceled_recent = (($t = mycred_polar_obj_get($r, 'canceled_at', null)) && strtotime($t) >= strtotime('-30 days'))
                    || (($t = mycred_polar_obj_get($r, 'ended_at', null)) && strtotime($t) >= strtotime('-30 days'));

    if ($active_flag) $stats[$cur]['active']++;
    if ($canceling) $stats[$cur]['canceling']++;
    if ($canceled_recent) $stats[$cur]['canceled_30']++;

    $amount_cents = (int)mycred_polar_obj_get($r, 'amount', 0);
    $iv = (string)mycred_polar_obj_get($r, 'recurring_interval', 'month');
    $ic = (int)mycred_polar_obj_get($r, 'recurring_interval_count', 1);
    
    if ($active_flag && $amount_cents > 0) {
        $stats[$cur]['mrr'] += mycred_polar_mrr_normalized($amount_cents, $iv, $ic);
    }
}

$last_sync = get_option('mycred_polar_last_sync', '');
$export_url = wp_nonce_url(admin_url('admin-post.php?action=mycred_polar_export_subscriptions'), 'mycred_polar_export');
?>

<div class="wrap">
    <h1>Subscribe (Polar Subscriptions Dashboard)</h1>
    <p>Last Sync: <strong><?php echo $last_sync ? esc_html(gmdate('Y-m-d H:i', (int)$last_sync)) . ' UTC' : 'Never'; ?></strong></p>
    <p>
        <button class="button button-primary" id="mp-sync-btn">🔄 Sync from Polar</button>
        <a class="button" href="<?php echo esc_url($export_url); ?>">⬇️ Export CSV</a>
    </p>

    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:10px;">
        <?php if (empty($stats)): ?>
            <div class="notice notice-info inline" style="padding:10px;">No data yet. Click "Sync from Polar".</div>
        <?php else: foreach ($stats as $cur => $st): ?>
            <div style="flex:1; min-width:260px; background:#fff; padding:15px; border-radius:6px; border:1px solid #ddd;">
                <h3 style="margin:0 0 10px;">Currency: <?php echo esc_html($cur); ?></h3>
                <ul style="margin:0; padding-left:16px; line-height:1.6;">
                    <li><strong>Active Subscribers:</strong> <?php echo esc_html($st['active']); ?></li>
                    <li><strong>Canceling (at period end):</strong> <?php echo esc_html($st['canceling']); ?></li>
                    <li><strong>Cancelled (last 30 days):</strong> <?php echo esc_html($st['canceled_30']); ?></li>
                    <li><strong>MRR:</strong> <?php echo esc_html(($cur === 'USD' ? '$' : '') . number_format($st['mrr'], 2) . ' ' . ($cur !== 'USD' ? $cur : '')); ?></li>
                    <li><strong>ARR:</strong> <?php echo esc_html(($cur === 'USD' ? '$' : '') . number_format($st['mrr'] * 12, 2) . ' ' . ($cur !== 'USD' ? $cur : '')); ?></li>
                </ul>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <h2 style="margin-top:25px;">Latest Subscriptions</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Interval</th>
                <th>Status</th>
                <th>Started</th>
                <th>Current Period</th>
                <th>Renews</th>
                <th>Cancel @ End</th>
                <th>Cancelled</th>
                <th>Ended</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="12">No subscriptions found. Click "Sync from Polar".</td></tr>
            <?php else: foreach ($rows as $r):
                $user = (int)mycred_polar_obj_get($r, 'user_id', 0);
                $ud = $user ? get_userdata($user) : null;
                $email = (string)mycred_polar_obj_get($r, 'customer_email', '—');
                $plan = (string)mycred_polar_obj_get($r, 'plan_name', '');
                if ($plan === '') $plan = (string)mycred_polar_obj_get($r, 'product_id', '');
                $amount = mycred_polar_money((int)mycred_polar_obj_get($r, 'amount', 0), (string)mycred_polar_obj_get($r, 'currency', 'usd'));
                $iv = (string)mycred_polar_obj_get($r, 'recurring_interval', '');
                $ic = (int)mycred_polar_obj_get($r, 'recurring_interval_count', 1);
                $interval_txt = trim($iv . ($ic > 1 ? ' x' . $ic : ''));
                ?>
                <tr>
                    <td><?php echo esc_html($ud ? $ud->user_login : (mycred_polar_obj_get($r, 'customer_external_id', '') ? 'ext#' . mycred_polar_obj_get($r, 'customer_external_id', '') : '—')); ?></td>
                    <td><?php echo esc_html($email ?: '—'); ?></td>
                    <td><?php echo esc_html($plan ?: '—'); ?></td>
                    <td><?php echo esc_html($amount); ?></td>
                    <td><?php echo esc_html($interval_txt ?: '—'); ?></td>
                    <td><?php echo esc_html((string)mycred_polar_obj_get($r, 'status', '')); ?></td>
                    <td><?php echo mycred_polar_dt(mycred_polar_obj_get($r, 'started_at', '')); ?></td>
                    <td><?php echo mycred_polar_dt(mycred_polar_obj_get($r, 'current_period_start', '')); ?></td>
                    <td><?php echo mycred_polar_dt(mycred_polar_obj_get($r, 'current_period_end', '')); ?></td>
                    <td><?php echo intval(mycred_polar_obj_get($r, 'cancel_at_period_end', 0)) ? 'Yes' : 'No'; ?></td>
                    <td><?php echo mycred_polar_dt(mycred_polar_obj_get($r, 'canceled_at', '')); ?></td>
                    <td><?php echo mycred_polar_dt(mycred_polar_obj_get($r, 'ended_at', '')); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div id="mp-sync-result" style="margin-top:10px;"></div>

    <script>
    (function(){
        const btn = document.getElementById('mp-sync-btn');
        const box = document.getElementById('mp-sync-result');
        btn?.addEventListener('click', function(){
            btn.disabled = true; btn.textContent = 'Syncing...';
            box.innerHTML='';
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'action=mycred_polar_admin_sync_subscriptions&nonce=<?php echo wp_create_nonce('mycred_polar_admin'); ?>'
            }).then(async r=>{ const t=await r.text(); try{return JSON.parse(t);}catch(e){throw new Error('Non-JSON: '+t.slice(0,200));}})
            .then(d=>{
                if (d.success) {
                    box.innerHTML = '<div class="notice notice-success inline" style="padding:10px;"><p>✅ Synced '+d.data.total+' subscriptions ('+d.data.active+' active, '+d.data.canceled+' canceled). Refreshing…</p></div>';
                    setTimeout(()=>{ window.location.reload(); }, 1200);
                } else {
                    box.innerHTML = '<div class="notice notice-error inline" style="padding:10px;"><p>❌ '+(d.data?.message || 'Sync failed')+'</p></div>';
                }
            }).catch(e=>{
                box.innerHTML = '<div class="notice notice-error inline" style="padding:10px;"><p>❌ '+e.message+'</p></div>';
            }).finally(()=>{ btn.disabled=false; btn.textContent='🔄 Sync from Polar'; });
        });
    })();
    </script>
</div>