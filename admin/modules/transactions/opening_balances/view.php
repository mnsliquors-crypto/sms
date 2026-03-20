<?php
require_once __DIR__ . '/../../../../config.php';
$type = isset($_GET['category']) ? $_GET['category'] : 'account';
$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rows = [];
$title = '';
if($type === 'account' && $id){
    $title = 'Account Opening Balances';
    $qry = $conn->query("SELECT t.*, tl.amount, tl.type as amt_type, a.name as account_name
                        FROM transactions t
                        JOIN transaction_list tl ON tl.ref_table='transactions' AND tl.ref_id=t.id
                        JOIN account_list a ON a.id=tl.account_id
                        WHERE t.type='opening_balance' AND tl.account_id = '{$id}'
                        ORDER BY t.transaction_date DESC");
    while($r = $qry->fetch_assoc()) $rows[] = $r;
}elseif($type === 'stock' && $id){
    $title = 'Inventory Opening Stock';
    $qry = $conn->query("SELECT t.*, ti.quantity, ti.unit_price, ti.total_price, il.name as item_name
                        FROM transactions t
                        JOIN transaction_items ti ON ti.transaction_id = t.id
                        JOIN item_list il ON il.id = ti.item_id
                        WHERE t.type='opening_stock' AND ti.item_id = '{$id}'
                        ORDER BY t.transaction_date DESC");
    while($r = $qry->fetch_assoc()) $rows[] = $r;
}elseif($type === 'vendor' && $id){
    $title = 'Vendor Opening Payables';
    $qry = $conn->query("SELECT t.*, v.name as vendor_name
                        FROM transactions t
                        JOIN entity_list v ON v.id = t.entity_id AND v.entity_type = 'Supplier'
                        WHERE t.type='purchase' AND t.remarks='Opening Balance' AND t.entity_id = '{$id}'
                        ORDER BY t.transaction_date DESC");
    while($r = $qry->fetch_assoc()) $rows[] = $r;
}elseif($type === 'customer' && $id){
    $title = 'Customer Opening Receivables';
    $qry = $conn->query("SELECT t.*, c.name as customer_name
                        FROM transactions t
                        JOIN entity_list c ON c.id = t.entity_id AND c.entity_type = 'Customer'
                        WHERE t.type='sale' AND t.remarks='Opening Balance' AND t.entity_id = '{$id}'
                        ORDER BY t.transaction_date DESC");
    while($r = $qry->fetch_assoc()) $rows[] = $r;
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12 text-right">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
    </div>
    <div class="mb-3">
        <h4 class="font-weight-bold mb-0"><?php echo $title ?></h4>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="bg-light">
                <tr>
                    <th>Date</th>
                    <th>Ref Code</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                    <th>Recorded By</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach($rows as $r): ?>
                <tr>
                    <td><?php echo date('d-m-Y', strtotime($r['transaction_date'])) ?></td>
                    <td><?php echo htmlspecialchars($r['reference_code']) ?></td>
                    <td class="text-right"><?php
                        if($type === 'account'){
                            $sign = ($r['amt_type'] == 1) ? '' : '-';
                            echo $sign.number_format($r['amount'],2);
                        } elseif($type === 'stock'){
                            echo number_format($r['quantity'],2).'@'.number_format($r['unit_price'],2);
                        } else {
                            echo number_format($r['total_amount'],2);
                        }
                    ?></td>
                    <td><?php echo htmlspecialchars($r['remarks'] ?? '') ?></td>
                    <td><?php
                        if(isset($r['created_by'])){
                            $u = $conn->query("SELECT CONCAT(firstname,' ',lastname) as name FROM users WHERE id={$r['created_by']}");
                            echo $u && $u->num_rows ? $u->fetch_assoc()['name'] : '';
                        }
                    ?></td>
                    <td class="text-center">
                        <?php if(in_array($type, ['account','stock','vendor','customer'])): ?>
                        <button class="btn btn-sm btn-primary edit-entry" data-id="<?php echo $r['id'] ?>" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger delete-entry" data-id="<?php echo $r['id'] ?>" title="Delete"><i class="fas fa-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
</div>

<script>
$(function(){
    function openEdit(id){
        uni_modal('Edit Opening Entry','transactions/opening_balances/manage.php?id='+id+'&category='+<?php echo json_encode($type) ?>,'mid-large');
    }
    $('.edit-entry').click(function(){
        var id = $(this).data('id');
        openEdit(id);
    });
    $('.delete-entry').click(function(){
        var id = $(this).data('id');
        // Raise confirm_modal above the current uni_modal so it's not hidden beneath it
        $('#confirm_modal').css('z-index', parseInt($('#uni_modal').css('z-index') || 1050) + 10);
        $('.modal-backdrop').last().css('z-index', parseInt($('#uni_modal').css('z-index') || 1050) + 9);
        _conf("Are you sure you want to delete this record?","delete_opening_entry",[id]);
    });

    // When confirm dialog is dismissed (Cancel/Close), restore the view modal's backdrop
    $('#confirm_modal').on('hidden.bs.modal.ob_fix', function(){
        if($('#uni_modal').hasClass('show')){
            $('body').addClass('modal-open');
            if($('.modal-backdrop').length === 0){
                $('<div class="modal-backdrop fade show"></div>').appendTo('body');
            } else {
                $('.modal-backdrop').css('z-index','').last().addClass('show');
            }
        }
        $('#confirm_modal').off('hidden.bs.modal.ob_fix');
    });
});

function delete_opening_entry(id){
    start_loader();
    $.ajax({
        url:_base_url_+'classes/Master.php?f=delete_opening_balance_entry',
        method:'POST',
        data:{id:id},
        dataType:'json',
        error:err=>{
            console.log(err);
            alert_toast('An error occured.','error');
            end_loader();
        },
        success:resp=>{
            if(resp.status=='success'){
                alert_toast(resp.msg,'success');
                setTimeout(()=>{ location.reload(); },500);
            }else{
                alert_toast(resp.msg,'error');
            }
            end_loader();
        }
    });
}
</script>

<?php require_once __DIR__.'/../../../inc/modal_footer_style.php'; ?>