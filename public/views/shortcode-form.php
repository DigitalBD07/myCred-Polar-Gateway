<?php
/**
 * Shortcode Form Template - Dark Theme Design
 * Exact replica of the screenshot design
 */

if (!defined('ABSPATH')) exit;
?>

<style>
* {
    box-sizing: border-box;
}

.mycred-polar-wrapper {
    background: #1a1d2e;
    padding: 3rem 2rem;
    min-height: 100vh;
    margin: 0 -20px;
}

.mycred-polar-header {
    text-align: center;
    margin-bottom: 3rem;
}

.mycred-polar-main-title {
    font-size: 3rem;
    font-weight: 700;
    background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0 0 0.75rem 0;
}

.mycred-polar-subtitle {
    color: #9ca3af;
    font-size: 1.125rem;
    margin: 0;
}

.mycred-polar-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.mycred-polar-card {
    background: #252936;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

.mycred-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.mycred-icon-box {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
}

.icon-cyan {
    background: rgba(76, 201, 240, 0.15);
}

.icon-purple {
    background: rgba(114, 9, 183, 0.15);
}

.mycred-card-title {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.mycred-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.mycred-info-label {
    color: #9ca3af;
    font-size: 1rem;
}

.mycred-info-value {
    color: #4cc9f0;
    font-size: 1.125rem;
    font-weight: 600;
}

.mycred-input-field {
    width: 100%;
    background: #1e2230;
    border: 1px solid #374151;
    border-radius: 8px;
    padding: 0.875rem 1rem;
    color: #ffffff;
    font-size: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s;
}

.mycred-input-field:focus {
    outline: none;
    border-color: #4cc9f0;
    box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.1);
}

.mycred-input-label {
    display: block;
    color: #9ca3af;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.mycred-price-display {
    background: linear-gradient(135deg, #06b6d4 0%, #a855f7 100%);
    color: #ffffff;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.mycred-pro-tip {
    display: flex;
    align-items: start;
    gap: 0.5rem;
    color: #9ca3af;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    padding: 0.75rem;
    background: rgba(156, 163, 175, 0.05);
    border-radius: 8px;
}

.mycred-button {
    width: 100%;
    padding: 1rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-size: 1.05rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.mycred-button-cyan {
    background: #06b6d4;
    color: #ffffff;
}

.mycred-button-cyan:hover {
    background: #0891b2;
    transform: translateY(-2px);
}

.mycred-button-gradient {
    background: linear-gradient(135deg, #f72585 0%, #7209b7 100%);
    color: #ffffff;
}

.mycred-button-gradient:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.mycred-button-secondary {
    background: #374151;
    color: #ffffff;
    width: auto;
    padding: 0.75rem 1.25rem;
    font-size: 0.95rem;
}

.mycred-button-secondary:hover {
    background: #4b5563;
}

.mycred-button-danger {
    background: #ef4444;
    color: #ffffff;
    width: auto;
    padding: 0.75rem 1.25rem;
    font-size: 0.95rem;
}

.mycred-button-danger:hover {
    background: #dc2626;
}

.mycred-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.mycred-select {
    width: 100%;
    background: #1e2230;
    border: 1px solid #374151;
    border-radius: 8px;
    padding: 0.875rem 1rem;
    color: #ffffff;
    font-size: 1rem;
    cursor: pointer;
    margin-bottom: 1.5rem;
}

.mycred-plan-info {
    background: rgba(156, 163, 175, 0.05);
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.mycred-plan-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.mycred-plan-row:last-child {
    margin-bottom: 0;
}

.mycred-plan-label {
    color: #9ca3af;
}

.mycred-plan-value {
    font-weight: 600;
    color: #ffffff;
}

.mycred-plan-value-pink {
    color: #f72585;
}

.mycred-current-plan-box {
    background: rgba(76, 201, 240, 0.05);
    border: 1px solid rgba(76, 201, 240, 0.2);
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.mycred-plan-detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.mycred-plan-detail-label {
    color: #9ca3af;
}

.mycred-plan-detail-value {
    color: #ffffff;
    font-weight: 500;
}

.mycred-value-cyan {
    color: #4cc9f0;
    font-weight: 600;
}

.mycred-value-green {
    color: #10b981;
    font-weight: 600;
}

.mycred-billing-info {
    text-align: center;
    margin-bottom: 1.5rem;
}

.mycred-billing-label {
    color: #9ca3af;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}

.mycred-billing-date {
    color: #ffffff;
    font-size: 1.125rem;
    font-weight: 600;
}

.mycred-button-group {
    display: flex;
    gap: 1rem;
}

.mycred-error {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    padding: 0.75rem;
    border-radius: 8px;
    margin-top: 0.75rem;
    display: none;
    border-left: 3px solid #ef4444;
}

.mycred-loading {
    text-align: center;
    color: #4cc9f0;
    padding: 0.75rem;
    display: none;
}

.mycred-empty {
    text-align: center;
    color: #9ca3af;
    padding: 2rem;
}

.mycred-sub-item {
    background: rgba(156, 163, 175, 0.05);
    border: 1px solid #374151;
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}

.mycred-sub-header {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.mycred-sub-details {
    color: #9ca3af;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

@media (max-width: 1200px) {
    .mycred-polar-grid {
        grid-template-columns: 1fr;
    }
    
    .mycred-polar-main-title {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .mycred-polar-wrapper {
        padding: 2rem 1rem;
    }
    
    .mycred-polar-main-title {
        font-size: 2rem;
    }
    
    .mycred-polar-card {
        padding: 1.5rem;
    }
    
    .mycred-button-group {
        flex-direction: column;
    }
    
    .mycred-button-secondary,
    .mycred-button-danger {
        width: 100%;
    }
}
</style>

<div class="mycred-polar-wrapper">
    <div class="mycred-polar-header">
        <h1 class="mycred-polar-main-title">myCred Points</h1>
        <p class="mycred-polar-subtitle">Manage your points ecosystem with precision</p>
    </div>
    
    <div class="mycred-polar-grid">
        <!-- Card 1: One-time Purchase -->
        <div class="mycred-polar-card">
            <div class="mycred-card-header">
                <div class="mycred-icon-box icon-cyan">🛍️</div>
                <h3 class="mycred-card-title">One-time Purchase</h3>
            </div>
            
            <div class="mycred-info-row">
                <span class="mycred-info-label">Current Balance</span>
                <span class="mycred-info-value"><?php echo $myc->format_creds($bal); ?></span>
            </div>
            
            <div class="mycred-info-row" style="margin-bottom: 1.5rem;">
                <span class="mycred-info-label">Rate</span>
                <span class="mycred-info-value">$<?php echo esc_html(number_format($ex, 3)); ?> per point</span>
            </div>
            
            <label class="mycred-input-label">Points</label>
            <input type="number" 
                   id="mp-pts" 
                   class="mycred-input-field" 
                   value="<?php echo esc_attr($defp); ?>" 
                   min="<?php echo esc_attr($minp); ?>" 
                   step="1"
                   placeholder="Enter points amount">
            
            <div class="mycred-price-display">
                $<span id="mp-cost">0.00</span>
            </div>
            
            <div class="mycred-pro-tip">
                <span>ℹ️</span>
                <span>You can adjust the amount on Polar.sh checkout page. Points will be calculated based on actual payment.</span>
            </div>
            
            <button id="mp-buy" class="mycred-button mycred-button-cyan">
                🛒 Purchase Now
            </button>
            
            <div id="mp-one-err" class="mycred-error"></div>
            <div id="mp-one-load" class="mycred-loading">⏳ Creating checkout...</div>
        </div>

        <!-- Card 2: Subscription Plans -->
        <div class="mycred-polar-card">
            <div class="mycred-card-header">
                <div class="mycred-icon-box icon-purple">🔄</div>
                <h3 class="mycred-card-title">Subscription Plans</h3>
            </div>
            
            <?php if (empty($plans)): ?>
                <div class="mycred-empty">
                    <p>No subscription plans configured.</p>
                    <p style="font-size: 0.875rem;">Contact admin to set up plans.</p>
                </div>
            <?php else: ?>
                <label class="mycred-input-label">Select plan:</label>
                <?php
                if (!function_exists('mycred_polar_plan_duration_text')) {
                    function mycred_polar_plan_duration_text($name) {
                        $n = strtolower($name);
                        if (strpos($n, 'daily') !== false || strpos($n, 'day') !== false) return '24 hours';
                        if (strpos($n, 'weekly') !== false || strpos($n, 'week') !== false) return '7 days';
                        if (strpos($n, 'monthly') !== false || strpos($n, 'month') !== false) return '30 days';
                        if (strpos($n, 'yearly') !== false || strpos($n, 'year') !== false || strpos($n, 'annual') !== false) return '365 days';
                        return 'Per cycle';
                    }
                }
                ?>
                <select id="mp-plan" class="mycred-select">
                    <?php foreach ($plans as $idx => $p):
                        $amt = floatval(intval($p['points_per_cycle']) * $ex);
                        $duration_txt = mycred_polar_plan_duration_text($p['name']);
                        $label = $p['name'] . ' — ' . $p['points_per_cycle'] . ' pts/cycle ($' . number_format($amt, 2) . ')';
                        ?>
                        <option value="<?php echo esc_attr($idx); ?>"
                                data-points="<?php echo esc_attr($p['points_per_cycle']); ?>"
                                data-price="<?php echo esc_attr(number_format($amt, 2)); ?>"
                                data-duration="<?php echo esc_attr($duration_txt); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="mycred-plan-info">
                    <div class="mycred-plan-row">
                        <span class="mycred-plan-label">Points per cycle</span>
                        <span class="mycred-plan-value" id="mp-plan-points">
                            <?php echo !empty($plans[0]['points_per_cycle']) ? number_format($plans[0]['points_per_cycle']) : '0'; ?>
                        </span>
                    </div>
                    <div class="mycred-plan-row">
                        <span class="mycred-plan-label">Cycle duration</span>
                        <span class="mycred-plan-value" id="mp-plan-duration">
                            <?php echo esc_html(mycred_polar_plan_duration_text($plans[0]['name'] ?? '')); ?>
                        </span>
                    </div>
                    <div class="mycred-plan-row">
                        <span class="mycred-plan-label">Price</span>
                        <span class="mycred-plan-value mycred-plan-value-pink" id="mp-plan-price">
                            $<?php 
                            $firstPrice = (intval($plans[0]['points_per_cycle'] ?? 0) * $ex);
                            echo number_format($firstPrice, 2);
                            ?>
                        </span>
                    </div>
                </div>
                
                <button id="mp-sub" class="mycred-button mycred-button-gradient">
                    💳 Subscribe Now
                </button>
                
                <div id="mp-sub-err" class="mycred-error"></div>
                <div id="mp-sub-load" class="mycred-loading">⏳ Creating subscription...</div>
            <?php endif; ?>
        </div>

        <!-- Card 3: Manage Subscriptions -->
        <div class="mycred-polar-card">
            <div class="mycred-card-header">
                <div class="mycred-icon-box icon-cyan">⚙️</div>
                <h3 class="mycred-card-title">Manage Subscriptions</h3>
            </div>
            
            <div id="mp-sub-list" class="mycred-loading" style="display:block;">
                Loading your subscriptions...
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const ex = <?php echo json_encode($ex); ?>;
    const minp = <?php echo json_encode($minp); ?>;
    const pts = document.getElementById('mp-pts');
    const cost = document.getElementById('mp-cost');
    const bbtn = document.getElementById('mp-buy');
    const berr = document.getElementById('mp-one-err');
    const bload = document.getElementById('mp-one-load');

    // Update plan info when selection changes
    const planSelect = document.getElementById('mp-plan');
    const planPoints = document.getElementById('mp-plan-points');
    const planPrice = document.getElementById('mp-plan-price');
    const planDuration = document.getElementById('mp-plan-duration');
    
    if (planSelect) {
        planSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const points = option.getAttribute('data-points');
            const price = option.getAttribute('data-price');
            const duration = option.getAttribute('data-duration');
            if (planPoints) planPoints.textContent = parseInt(points).toLocaleString();
            if (planPrice) planPrice.textContent = '$' + price;
            if (planDuration) planDuration.textContent = duration;
        });
    }

    function recalc(){ 
        const p = parseInt(pts.value)||0; 
        cost.textContent=(p*ex).toFixed(2); 
    }
    pts?.addEventListener('input', recalc); 
    recalc();

    bbtn?.addEventListener('click', function(){
        const p = parseInt(pts.value);
        if (isNaN(p)||p<minp){ 
            berr.textContent='Please enter at least '+minp+' points.'; 
            berr.style.display='block'; 
            return; 
        }
        berr.style.display='none'; 
        bload.style.display='block'; 
        bbtn.disabled=true;
        
        const cents = Math.round(p*ex*100);
        const fd = new URLSearchParams();
        fd.append('action','mycred_polar_create_checkout');
        fd.append('points', p);
        fd.append('amount', cents);
        fd.append('nonce','<?php echo wp_create_nonce('mycred_polar_checkout'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>',{
            method:'POST', 
            headers:{'Content-Type':'application/x-www-form-urlencoded'}, 
            body:fd.toString()
        })
        .then(async r=>{ 
            const t=await r.text(); 
            try{ return JSON.parse(t); }
            catch(e){ throw new Error('AJAX returned non‑JSON: '+t.slice(0,300)); }
        })
        .then(d=>{ 
            if(d.success && d.data.url){ window.location.href = d.data.url; } 
            else { berr.textContent='Error: '+(d.data?.message||'Failed'); berr.style.display='block'; }
        })
        .catch(e=>{ berr.textContent='Error: '+e.message; berr.style.display='block'; })
        .finally(()=>{ bload.style.display='none'; bbtn.disabled=false; });
    });

    const sbtn = document.getElementById('mp-sub');
    const sel = document.getElementById('mp-plan');
    const serr = document.getElementById('mp-sub-err');
    const sload = document.getElementById('mp-sub-load');
    
    sbtn?.addEventListener('click', function(){
        if (!sel) return;
        serr.style.display='none'; 
        sload.style.display='block'; 
        sbtn.disabled=true;
        
        const fd = new URLSearchParams();
        fd.append('action','mycred_polar_create_subscription_checkout');
        fd.append('plan_index', sel.value);
        fd.append('nonce','<?php echo wp_create_nonce('mycred_polar_checkout'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>',{
            method:'POST', 
            headers:{'Content-Type':'application/x-www-form-urlencoded'}, 
            body:fd.toString()
        })
        .then(async r=>{ 
            const t=await r.text(); 
            try{ return JSON.parse(t); }
            catch(e){ throw new Error('AJAX returned non‑JSON: '+t.slice(0,300)); }
        })
        .then(d=>{ 
            if(d.success && d.data.url){ window.location.href = d.data.url; } 
            else { serr.textContent='Error: '+(d.data?.message||'Failed'); serr.style.display='block'; }
        })
        .catch(e=>{ serr.textContent='Error: '+e.message; serr.style.display='block'; })
        .finally(()=>{ sload.style.display='none'; sbtn.disabled=false; });
    });

    const listBox = document.getElementById('mp-sub-list');
    
    function fmtDate(iso){ 
        try{ 
            const d=new Date(iso); 
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', timeZoneName: 'short' };
            return d.toLocaleString('en-US', options);
        }catch(e){ return iso||''; } 
    }
    
    function renderSubs(items){
        if (!items || !items.length) { 
            listBox.innerHTML='<div class="mycred-empty"><p>You have no active subscriptions.</p></div>'; 
            listBox.style.display='block';
            return; 
        }
        
        const rows = items.map(it=>{
            const name = it.plan_name || it.product_name || 'Subscription';
            const pts = it.points_per_cycle ? `${it.points_per_cycle} pts/cycle` : '';
            const amt = (typeof it.amount==='number') ? `$${(it.amount/100).toFixed(2)}` : '';
            const next = it.current_period_end ? fmtDate(it.current_period_end) : '—';
            const id = it.id;
            const canceling = it.cancel_at_period_end;
            const status = canceling ? '<span style="color:#ef4444;">Canceling</span>' : '<span class="mycred-value-green">Active</span>';
            
            return `<div class="mycred-current-plan-box">
                <div class="mycred-plan-detail-row">
                    <span class="mycred-plan-detail-label">Current Plan</span>
                    <span class="mycred-plan-detail-value">${name} — ${pts}</span>
                </div>
                <div class="mycred-plan-detail-row">
                    <span class="mycred-plan-detail-label">Price per cycle</span>
                    <span class="mycred-value-cyan">${amt}</span>
                </div>
                <div class="mycred-plan-detail-row">
                    <span class="mycred-plan-detail-label">Status</span>
                    ${status}
                </div>
                <div class="mycred-billing-info">
                    <div class="mycred-billing-label">Next billing:</div>
                    <div class="mycred-billing-date">${next}</div>
                </div>
                ${canceling ? '' : `<div class="mycred-button-group">
                    <button class="mycred-button mycred-button-secondary">
                        🔄 Change Plan
                    </button>
                    <button data-subid="${id}" class="mycred-button mycred-button-danger mp-cancel-sub">
                        ✕ Cancel
                    </button>
                </div>`}
            </div>`;
        }).join('');
        
        listBox.innerHTML = rows;
        listBox.style.display='block';
    }
    
    function loadSubs(){
        const fd = new URLSearchParams();
        fd.append('action','mycred_polar_list_subscriptions');
        fd.append('nonce','<?php echo wp_create_nonce('mycred_polar_manage'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>',{
            method:'POST', 
            headers:{'Content-Type':'application/x-www-form-urlencoded'}, 
            body:fd.toString()
        })
        .then(async r=>{ 
            const t=await r.text(); 
            try{ return JSON.parse(t); }
            catch(e){ throw new Error('AJAX returned non‑JSON: '+t.slice(0,300)); }
        })
        .then(d=>{ 
            if(d.success){ renderSubs(d.data.items||[]); } 
            else { listBox.innerHTML='<div class="mycred-error" style="display:block;">'+(d.data?.message||'Failed to load')+'</div>'; } 
        })
        .catch(e=>{ listBox.innerHTML='<div class="mycred-error" style="display:block;">'+e.message+'</div>'; });
    }
    
    listBox.addEventListener('click', function(e){
        const btn=e.target.closest('.mp-cancel-sub');
        if (!btn) return;
        
        const subId = btn.getAttribute('data-subid');
        if (!confirm('Cancel this subscription at period end?')) return;
        
        btn.disabled=true; 
        btn.textContent='Canceling...';
        
        const fd = new URLSearchParams();
        fd.append('action','mycred_polar_cancel_subscription');
        fd.append('subscription_id', subId);
        fd.append('nonce','<?php echo wp_create_nonce('mycred_polar_manage'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>',{
            method:'POST', 
            headers:{'Content-Type':'application/x-www-form-urlencoded'}, 
            body:fd.toString()
        })
        .then(async r=>{ 
            const t=await r.text(); 
            try{ return JSON.parse(t); }
            catch(e){ throw new Error('AJAX returned non‑JSON: '+t.slice(0,300)); }
        })
        .then(d=>{
            if (d.success) { alert('Subscription will be canceled at period end.'); loadSubs(); } 
            else { alert('Cancel failed: '+(d.data?.message||'Unknown error')); }
        })
        .catch(e=>{ alert('Cancel error: '+e.message); })
        .finally(()=>{ btn.disabled=false; btn.textContent='✕ Cancel'; });
    });
    
    loadSubs();
})();
</script>
