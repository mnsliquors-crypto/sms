<?php
require_once __DIR__ . '/../../../../config.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$category = isset($_GET['category']) ? $_GET['category'] : '';
$data = [];
if($id){
    switch($category){
        case 'account':
            $qry = $conn->query("SELECT t.*, tl.amount, tl.type as amt_type, tl.account_id FROM transactions t JOIN transaction_list tl ON tl.ref_table='transactions' AND tl.ref_id=t.id WHERE t.id='{$id}' AND t.type='opening_balance'");
            if($qry && $qry->num_rows) $data = $qry->fetch_assoc();
            break;
        case 'stock':
            $qry = $conn->query("SELECT t.*, ti.quantity, ti.unit_price, ti.item_id FROM transactions t JOIN transaction_items ti ON ti.transaction_id=t.id WHERE t.id='{$id}' AND t.type='opening_stock'");
            if($qry && $qry->num_rows) $data = $qry->fetch_assoc();
            break;
        case 'vendor':
            $qry = $conn->query("SELECT t.*, v.display_name as vendor_name FROM transactions t JOIN entity_list v ON v.id=t.entity_id AND v.entity_type = 'Supplier' WHERE t.id='{$id}' AND t.type='purchase' AND t.remarks='Opening Balance'");
            if($qry && $qry->num_rows) $data = $qry->fetch_assoc();
            break;
        case 'customer':
            $qry = $conn->query("SELECT t.*, c.display_name as customer_name FROM transactions t JOIN entity_list c ON c.id=t.entity_id AND c.entity_type = 'Customer' WHERE t.id='{$id}' AND t.type='sale' AND t.remarks='Opening Balance'");
            if($qry && $qry->num_rows) $data = $qry->fetch_assoc();
            break;
    }
}
?>
<div class="container-fluid">
    <?php if(empty($data) && $id > 0): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Record not found or access denied.</div>
    <?php else: ?>
    <form id="opening-entry-form">
        <input type="hidden" name="id" value="<?php echo $id ?>">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category) ?>">

        <div class="form-group">
            <label><i class="far fa-calendar-alt mr-1"></i> Transaction Date</label>
            <input type="date" name="transaction_date" class="form-control form-control-sm"
                   value="<?php echo isset($data['transaction_date']) ? date('Y-m-d', strtotime($data['transaction_date'])) : date('Y-m-d') ?>" required>
        </div>

        <?php if($category === 'account'): ?>
        <div class="form-group">
            <label><i class="fas fa-university mr-1"></i> Account</label>
            <select name="account_id" class="form-control form-control-sm" required>
                <?php
                $accs = $conn->query("SELECT * FROM account_list WHERE status = 1 ORDER BY name ASC");
                while($ar = $accs->fetch_assoc()):
                ?>
                <option value="<?php echo $ar['id'] ?>" <?php echo (isset($data['account_id']) && $data['account_id']==$ar['id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($ar['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label><i class="fas fa-dollar-sign mr-1"></i> Amount <small class="text-muted">(negative = debit)</small></label>
            <input type="number" step="any" name="amount" class="form-control form-control-sm"
                   value="<?php echo isset($data['amount']) ? (($data['amt_type']==1?'':'-').$data['amount']) : '' ?>" required>
        </div>

        <?php elseif($category === 'stock'): ?>
        <div class="form-group">
            <label><i class="fas fa-box mr-1"></i> Item</label>
            <select name="item_id" class="form-control form-control-sm" required>
                <?php
                $items = $conn->query("SELECT * FROM item_list WHERE status = 1 ORDER BY name ASC");
                while($it = $items->fetch_assoc()):
                ?>
                <option value="<?php echo $it['id'] ?>" <?php echo (isset($data['item_id']) && $data['item_id']==$it['id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($it['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label><i class="fas fa-sort-numeric-up mr-1"></i> Opening Quantity</label>
            <input type="number" step="any" name="quantity" class="form-control form-control-sm"
                   value="<?php echo isset($data['quantity']) ? $data['quantity'] : '' ?>" required>
        </div>
        <div class="form-group">
            <label><i class="fas fa-tag mr-1"></i> Unit Price / Cost</label>
            <input type="number" step="any" name="unit_price" class="form-control form-control-sm"
                   value="<?php echo isset($data['unit_price']) ? $data['unit_price'] : '' ?>" required>
        </div>

        <?php elseif($category === 'vendor'): ?>
        <div class="alert alert-info border-0 py-2">
            <i class="fas fa-truck mr-1"></i>
            Vendor: <strong><?php echo htmlspecialchars($data['vendor_name'] ?? '—') ?></strong>
        </div>
        <div class="form-group">
            <label><i class="fas fa-dollar-sign mr-1"></i> Opening Payable Amount</label>
            <input type="number" step="any" name="amount" class="form-control form-control-sm"
                   value="<?php echo isset($data['total_amount']) ? $data['total_amount'] : '' ?>" required>
        </div>

        <?php elseif($category === 'customer'): ?>
        <div class="alert alert-info border-0 py-2">
            <i class="fas fa-user-circle mr-1"></i>
            Customer: <strong><?php echo htmlspecialchars($data['customer_name'] ?? '—') ?></strong>
        </div>
        <div class="form-group">
            <label><i class="fas fa-dollar-sign mr-1"></i> Opening Receivable Amount</label>
            <input type="number" step="any" name="amount" class="form-control form-control-sm"
                   value="<?php echo isset($data['total_amount']) ? $data['total_amount'] : '' ?>" required>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label><i class="fas fa-comment mr-1"></i> Remarks</label>
            <input type="text" name="remarks" class="form-control form-control-sm"
                   value="<?php echo htmlspecialchars($data['remarks'] ?? 'Opening Balance') ?>">
        </div>

    </form>
    <?php endif; ?>
</div>

<script>
$(function(){
    $('#uni_modal .modal-footer').show();
    $('#uni_modal #submit').show();
    $('#opening-entry-form').submit(function(e){
    e.preventDefault();
    start_loader();
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=update_opening_balance_entry',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        error: function(err){ console.log(err); alert_toast('An error occurred','error'); end_loader(); },
        success: function(resp){
            if(resp.status == 'success'){
                alert_toast(resp.msg,'success');
                setTimeout(function(){ location.reload(); }, 700);
            } else {
                alert_toast(resp.msg || 'Failed to save','error');
            }
            end_loader();
        }
    });
});
</script>