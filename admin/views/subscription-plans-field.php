<?php
/**
 * Subscription Plans Field Template
 */

if (!defined('ABSPATH')) exit;

$o = mycred_polar_get_options();
$plans = $o['subscription_plans'];
?>

<style>
.mp-sub-table th, .mp-sub-table td { padding:6px; }
.mp-sub-table input[type="text"], .mp-sub-table input[type="number"] { width: 100%; }
textarea#mp-plans-json { width:100%; height:120px; }
.mp-sub-del { color:#b32d2e; cursor:pointer; }
</style>

<p>Define recurring plans. For PWYW subscriptions, check "Use custom amount" and we'll send amount = points_per_cycle × exchange_rate.</p>

<table class="widefat mp-sub-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Recurring Product ID</th>
            <th>Points/cycle</th>
            <th>Use custom amount?</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="mp-plans-body">
        <?php if (empty($plans)): ?>
            <tr>
                <td><input type="text" data-k="name" value=""></td>
                <td><input type="text" data-k="product_id" value=""></td>
                <td><input type="number" min="1" step="1" data-k="points_per_cycle" value="100"></td>
                <td style="text-align:center;"><input type="checkbox" data-k="use_custom_amount"></td>
                <td><span class="mp-sub-del">✕</span></td>
            </tr>
        <?php else: foreach ($plans as $p): ?>
            <tr>
                <td><input type="text" data-k="name" value="<?php echo esc_attr($p['name']); ?>"></td>
                <td><input type="text" data-k="product_id" value="<?php echo esc_attr($p['product_id']); ?>"></td>
                <td><input type="number" min="1" step="1" data-k="points_per_cycle" value="<?php echo esc_attr($p['points_per_cycle']); ?>"></td>
                <td style="text-align:center;"><input type="checkbox" data-k="use_custom_amount" <?php checked(!empty($p['use_custom_amount'])); ?>></td>
                <td><span class="mp-sub-del">✕</span></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<button type="button" class="button mp-sub-add">+ Add Plan</button>

<p>Stored as JSON below; you can edit directly if needed.</p>
<textarea id="mp-plans-json" name="mycred_polar_options[subscription_plans_json]"><?php echo esc_textarea(wp_json_encode($plans)); ?></textarea>

<script>
(function(){
    const body = document.getElementById('mp-plans-body');
    const jsonTA = document.getElementById('mp-plans-json');
    
    function readTable(){ 
        const rows=[...body.querySelectorAll('tr')];
        const out=[];
        rows.forEach(tr=>{
            const name = tr.querySelector('input[data-k="name"]')?.value?.trim()||'';
            const product_id = tr.querySelector('input[data-k="product_id"]')?.value?.trim()||'';
            const points = parseInt(tr.querySelector('input[data-k="points_per_cycle"]')?.value||'0',10)||0;
            const custom = tr.querySelector('input[data-k="use_custom_amount"]')?.checked ? 1 : 0;
            if (name||product_id) out.push({name, product_id, points_per_cycle: points, use_custom_amount: custom});
        });
        jsonTA.value = JSON.stringify(out, null, 2);
    }
    
    function addRow(data={}){
        const tr=document.createElement('tr');
        tr.innerHTML = `<td><input type="text" data-k="name" value="${data.name||''}"></td>
            <td><input type="text" data-k="product_id" value="${data.product_id||''}"></td>
            <td><input type="number" min="1" step="1" data-k="points_per_cycle" value="${data.points_per_cycle||100}"></td>
            <td style="text-align:center;"><input type="checkbox" data-k="use_custom_amount" ${data.use_custom_amount?'checked':''}></td>
            <td><span class="mp-sub-del">✕</span></td>`;
        body.appendChild(tr);
    }
    
    document.querySelector('.mp-sub-add').addEventListener('click', ()=>addRow());
    body.addEventListener('input', readTable);
    body.addEventListener('change', readTable);
    body.addEventListener('click', (e)=>{
        if (e.target.classList.contains('mp-sub-del')) {
            e.target.closest('tr').remove();
            readTable();
        }
    });
    
    readTable();
})();
</script>