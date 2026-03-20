<?php 
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../config.php';
}
$id = $_GET['id'] ?? null;
$account_amounts = [];
$existing_allocations = [];
if($id){
    $qry = $conn->query("SELECT p.id, p.reference_code as payment_code, p.total_amount as amount, p.transaction_date as date_created, p.remarks, p.account_id, p.parent_id, p.entity_id FROM `transactions` p WHERE p.id = '{$id}' AND p.type='payment'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }

        // Fetch ALL related payments in this group (bulk)
        if(!empty($payment_code)){
            $group_qry = $conn->query("SELECT * FROM `transactions` WHERE reference_code = '{$payment_code}' AND type='payment'");
            while($grow = $group_qry->fetch_assoc()){
                // Aggregate account amounts from transactions (standard bulk payments)
                if(!empty($grow['account_id'])){
                    if(!isset($account_amounts[$grow['account_id']])) $account_amounts[$grow['account_id']] = 0;
                    $account_amounts[$grow['account_id']] += floatval($grow['total_amount']);
                }
                
                // Map allocations for pre-filling the table
                if(!empty($grow['parent_id'])){
                    if(!isset($existing_allocations[$grow['parent_id']])) $existing_allocations[$grow['parent_id']] = 0;
                    $existing_allocations[$grow['parent_id']] += floatval($grow['total_amount']);
                }
            }
        }

        // Also fetch from transaction_list (for consolidated POS payments)
        $tl_qry = $conn->query("SELECT * FROM `transaction_list` WHERE ref_id = '{$id}' AND ref_table = 'transactions'");
        while($tl_row = $tl_qry->fetch_assoc()){
            if(!isset($account_amounts[$tl_row['account_id']])) $account_amounts[$tl_row['account_id']] = 0;
            $account_amounts[$tl_row['account_id']] += floatval($tl_row['amount']);
        }

        
        // Derive Party ID from parent bill
        if(isset($parent_id) && $parent_id > 0){
             $inv_qry = $conn->query("SELECT entity_id as party_id, type as bill_type FROM `transactions` WHERE id = '{$parent_id}'");
             if($inv_qry->num_rows > 0) {
                 $i_row = $inv_qry->fetch_object();
                 $party_id = $i_row->party_id;
                 $type = ($i_row->bill_type == 'sale' ? 1 : 2);
             }
        }
    }
}

$type = $_GET['type'] ?? $type ?? 1;

// Robust Party ID Derivation
$party_id = !empty($_GET['party_id']) ? $_GET['party_id'] : 
           (!empty($_GET['customer_id']) ? $_GET['customer_id'] : 
           (!empty($_GET['vendor_id']) ? $_GET['vendor_id'] : 
           (!empty($party_id) ? $party_id : 
           (!empty($entity_id) ? $entity_id : ''))));

$party_name = '';
if(!empty($party_id)){
    $party_stmt = $conn->prepare("SELECT display_name FROM entity_list WHERE id = ?");
    $party_stmt->bind_param("i", $party_id);
    $party_stmt->execute();
    $p_res = $party_stmt->get_result()->fetch_assoc();
    $party_name = $p_res['display_name'] ?? '';
    $party_stmt->close();
}
?>
<script>
    var existing_allocations = <?php echo json_encode($existing_allocations) ?>;
</script>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <?php echo $type == 1 ? "Receive Payment (Sales)" : "Make Payment (Purchases)" ?>
            <?php if(isset($remarks) && strpos($remarks, '[POS]') !== false): ?>
                <span class="badge badge-info shadow-sm ml-2" title="POS Entry">POS</span>
            <?php endif; ?>
        </h3>

    </div>
    <div class="card-body">
        <form action="" id="payment-form">
            <input type="hidden" name="id" value="<?php echo $id ?>">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="payment_code" class="control-label">Payment Code</label>
                        <?php 
                        if(empty($payment_code)){
                            $payment_code = "New";
                        }
                        ?>
                        <input type="text" name="payment_code" id="payment_code" class="form-control form-control-sm rounded-0" value="<?php echo $payment_code ?>" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date_created" class="control-label">Date</label>
                        <input type="date" name="date_created" id="date_created" class="form-control form-control-sm rounded-0" value="<?php echo isset($date_created) ? date("Y-m-d", strtotime($date_created)) : date("Y-m-d") ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="type" class="control-label">Payment Type</label>
                        <div id="type_ss" data-placeholder="Select Payment Type" data-readonly="<?php echo isset($id) ? 'true' : 'false' ?>">
                            <select name="type" id="type">
                                <option value="1" <?php echo $type == 1 ? "selected" : "" ?>>Receive from Customer</option>
                                <option value="2" <?php echo $type == 2 ? "selected" : "" ?>>Pay to Vendor</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="party_id" class="control-label"><?php echo $type == 1 ? "Customer" : "Vendor" ?> <span class="text-danger">*</span></label>
                        <div id="party_id_ss" 
                             data-url="<?php echo base_url ?>classes/Master.php?f=search_parties&type=<?php echo $type == 1 ? 'Customer' : 'Supplier' ?>" 
                             data-placeholder="Select <?php echo $type == 1 ? 'Customer' : 'Vendor' ?>"
                             data-name="party_id"
                             data-readonly="<?php echo isset($id) ? 'true' : 'false' ?>"
                             data-initial-text="<?php echo isset($party_name) && !empty($party_name) ? htmlspecialchars($party_name) : '' ?>"
                             data-initial-value="<?php echo isset($party_id) ? $party_id : '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-8">
                    <h5>Outstanding Bills</h5>
                    <table class="table table-bordered table-striped" id="bills-table">
                        <colgroup>
                            <col width="5%">
                            <col width="15%">
                            <col width="15%">
                            <col width="20%">
                            <col width="20%">
                            <col width="25%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Date</th>
                                <th>Ref. Code</th>
                                <th class="text-right">Total Amount</th>
                                <th class="text-right">Outstanding</th>
                                <th class="text-right">Allocate Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">Select a party to load bills.</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-right">Total Allocated</th>
                                <th class="text-right" id="total-allocated">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-md-4">
                    <div class="card card-default">
                        <div class="card-header">
                            <h5 class="card-title">Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            $accounts = $conn->query("SELECT * FROM account_list WHERE status = 1 ORDER BY name ASC");
                            while($row = $accounts->fetch_assoc()):
                                $val = isset($account_amounts[$row['id']]) ? $account_amounts[$row['id']] : 0;
                            ?>
                            <div class="form-group">
                                <label for="acc_<?php echo $row['id'] ?>"><?php echo htmlspecialchars($row['name']) ?></label>
                                <input type="number" step="any" name="account_amounts[<?php echo $row['id'] ?>]" id="acc_<?php echo $row['id'] ?>" class="form-control text-right payment-input" value="<?php echo number_format($val, 2, '.', '') ?>">
                            </div>
                            <?php endwhile; ?>
                            <hr>
                            <div class="form-group">
                                <label for="total_amount">Total Paying</label>
                                <input type="text" id="total_amount" class="form-control text-right font-weight-bold" value="0.00" readonly>
                            </div>
                             <div class="form-group">
                                <label for="remarks">Remarks</label>
                                <textarea name="remarks" id="remarks" rows="3" class="form-control"><?php echo isset($remarks) ? htmlspecialchars($remarks) : '' ?></textarea>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                             <button class="btn btn-primary btn-sm flex-fill mr-1" type="submit">Complete Payment</button>
                             <a href="./?page=transactions/payments" class="btn btn-default btn-sm flex-fill ml-1">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    var pre_party_id = '<?php echo $party_id ?>';
    
    $(function(){
        function get_next_code(){
            var date = $('#date_created').val();
            if(date == '' || $('[name="id"]').val() != '') return false;
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_next_ref_code",
                method:'POST',
                data:{type:'payment', date:date},
                dataType:'json',
                error:err=>{
                    console.log(err)
                },
                success:resp=>{
                    if(resp.status == 'success'){
                        $('#payment_code').val(resp.code);
                    }
                }
            })
        }

        if($('[name="id"]').val() == ''){
            get_next_code();
        }

        $('#date_created').change(function(){
            get_next_code();
        })

        // Robust SearchableSelect Initialization
        function initSearchableFields() {
            if (typeof SearchableSelect !== 'undefined') {
                // Initialize Payment Type
                new SearchableSelect('#type_ss', {
                    onSelect: function(item){
                        location.href = "./?page=transactions/payments/manage_payment&type=" + item.id;
                    }
                });

                // Initialize Party
                new SearchableSelect('#party_id_ss', {
                    showOnFocus: true,
                    onSelect: function(item){
                        load_bills($('#type').val(), item.id);
                    }
                });
            } else {
                setTimeout(initSearchableFields, 100);
            }
        }
        
        initSearchableFields();
        
        
        
        // Payment Input Logic
        $('.payment-input').on('focus', function(){
            if(parseFloat($(this).val()) == 0) $(this).val('');
        }).on('blur', function(){
            if($(this).val() == '') $(this).val('0.00');
        }).on('input', function(){
            var total = 0;
            $('.payment-input').each(function(){ total += parseFloat($(this).val()) || 0; });
            $('#total_amount').val(total.toFixed(2));
            allocate_payment(total);
        });
        
        // Initial load for Edit
        var pre_party_id = '<?php echo $party_id ?? "" ?>';
        if(pre_party_id != ''){
            load_bills($('#type').val(), pre_party_id);
        }
    });

    function load_bills(type, party_id){
        start_loader();
        $.ajax({
            url: _base_url_+'classes/Master.php?f=get_outstanding_bills',
            method: 'POST',
            data: {type: type, party_id: party_id, payment_code: $('#payment_code').val()},
            dataType: 'json',
            success: function(resp){
                if(resp.status == 'success'){
                    var tbody = $('#bills-table tbody');
                    tbody.empty();
                    if(resp.data.length > 0){
                        resp.data.forEach(function(item, index){
                            var tr = $('<tr>');
                            tr.append('<td class="text-center">' + (index + 1) + '<input type="hidden" name="invoices[]" value="'+item.id+'"></td>');
                            tr.append('<td>' + item.date + '</td>');
                            
                            var view_url = _base_url_ + "admin/?page=" + (type == 1 ? 'transactions/sales/view_sale' : 'transactions/purchases/view_purchase') + "&id=" + item.id;
                            tr.append('<td><a href="'+view_url+'" target="_blank">' + item.code + '</a></td>');
                            
                            tr.append('<td class="text-right">' + parseFloat(item.amount).toLocaleString(undefined, {minimumFractionDigits: 2}) + '</td>');
                            tr.append('<td class="text-right">' + parseFloat(item.outstanding).toLocaleString(undefined, {minimumFractionDigits: 2}) + '<input type="hidden" class="outstanding-val" value="'+item.outstanding+'"></td>');
                            
                            // Pre-fill allocation if editing
                            var existing_val = (typeof existing_allocations !== 'undefined' && existing_allocations[item.id]) ? existing_allocations[item.id] : 0;
                            tr.append('<td class="text-right"><input type="number" step="any" name="allocation[]" class="form-control text-right form-control-sm alloc-input" value="'+existing_val+'" max="'+(parseFloat(item.outstanding) + parseFloat(existing_val)).toFixed(2)+'"></td>');
                            tbody.append(tr);
                        });
                        
                        // Add listener for manual allocation change
                        $('.alloc-input').on('focus', function(){
                            if(parseFloat($(this).val()) == 0) $(this).val('');
                        }).on('blur', function(){
                            if($(this).val() == '') $(this).val('0.00');
                        }).on('input', function(){
                             update_allocated_total();
                        });
                        
                        // Set total paying display on load
                        update_total_paying();
                        
                        // If coming from specific invoice, pre-fill
                        var urlParams = new URLSearchParams(window.location.search);
                        var target_id = urlParams.get('transaction_id');
                        
                        if(target_id){
                             var target_bill = resp.data.find(x => x.id == target_id);
                             if(target_bill){
                                 var amount_to_pay = parseFloat(target_bill.outstanding);
                                 $('#total_amount').val(amount_to_pay.toFixed(2));
                                 // Account amounts should be empty as per user request
                                 $('.payment-input').val('0.00'); 
                                 allocate_payment(amount_to_pay); 
                             }
                        } else {
                             var current_total = parseFloat($('#total_amount').val()) || 0;
                             if(current_total > 0) allocate_payment(current_total);
                        }
                        
                    } else {
                        tbody.html('<tr><td colspan="6" class="text-center">No outstanding bills found.</td></tr>');
                    }
                } else {
                    alert_toast("Error: " + resp.msg, 'error');
                }
            },
            error: function(err){
                console.log(err);
                alert_toast("An error occurred.", 'error');
            },
            complete: function(){
                end_loader();
            }
        });
    }

    function update_total_paying(){
        var total = 0;
        $('.payment-input').each(function(){ total += parseFloat($(this).val()) || 0; });
        $('#total_amount').val(total.toFixed(2));
    }

    function allocate_payment(total_pay){
        var remaining = total_pay;
        var urlParams = new URLSearchParams(window.location.search);
        var target_id = urlParams.get('transaction_id');

        // First, handle the target_id if it exists (Priority)
        if(target_id){
            $('#bills-table tbody tr').each(function(){
                var bid = $(this).find('input[name="invoices[]"]').val();
                if(bid == target_id){
                    var outstanding = parseFloat($(this).find('.outstanding-val').val()) || 0;
                    var alloc = Math.min(outstanding, remaining);
                    $(this).find('.alloc-input').val(alloc.toFixed(2));
                    remaining -= alloc;
                }
            });
        }

        // Then, distribute the rest FIFO
        $('#bills-table tbody tr').each(function(){
            var bid = $(this).find('input[name="invoices[]"]').val();
            if(target_id && bid == target_id) return true; // Already handled

            var outstanding = parseFloat($(this).find('.outstanding-val').val()) || 0;
            var alloc_input = $(this).find('.alloc-input');
            
            if(outstanding > 0 && remaining > 0){
                var alloc = Math.min(outstanding, remaining);
                alloc_input.val(alloc.toFixed(2));
                remaining -= alloc;
            } else {
                 alloc_input.val('0.00');
            }
        });
        update_allocated_total();
    }
    
    function update_allocated_total(){
        var t = 0;
        $('.alloc-input').each(function(){ t += parseFloat($(this).val()) || 0; });
        $('#total-allocated').text(t.toLocaleString(undefined, {minimumFractionDigits: 2}));
    }

    $('#payment-form').submit(function(e){
        e.preventDefault();
        
        if($('#party_id').val() == ''){
            alert_toast("Please select a valid " + ($('#type').val() == 1 ? "Customer" : "Vendor") + ".", "warning");
            return false;
        }

        // Validation...
         start_loader();
         $.ajax({
             url: _base_url_+"classes/Master.php?f=save_bulk_payment",
             data: new FormData($(this)[0]),
             cache: false, contentType: false, processData: false, method: 'POST', type: 'POST', dataType: 'json',
             success: function(resp){
                 if(resp.status == 'success'){
                     alert_toast("Payment Saved", 'success');
                     setTimeout(() => {
                         location.href = _base_url_ + 'admin/?page=transactions/payments/view_payment&id=' + resp.id;
                     }, 1500);
                 } else {
                     alert_toast(resp.msg, 'error');
                     end_loader();
                 }
             }
         })
    });
</script>
