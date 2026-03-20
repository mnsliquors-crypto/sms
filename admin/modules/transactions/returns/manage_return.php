<?php 
if(isset($_GET['id'])){
    // Fetch from transactions with correct entity name joining
    $qry = $conn->query("SELECT t.*, t.reference_code as return_code, t.entity_id as vendor_id, 
                        (CASE WHEN t.entity_type = 'vendor' THEN v.name WHEN t.entity_type = 'customer' THEN c.name ELSE 'N/A' END) as entity_name 
                        FROM `transactions` t 
                        LEFT JOIN entity_list v ON t.entity_id = v.id AND v.entity_type = 'Supplier'
                        LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer' 
                        WHERE t.id = '{$_GET['id']}' AND t.type='return'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }
    }
} else if(isset($_GET['from_sale_id']) || isset($_GET['from_purchase_id'])) {
    $orig_id = isset($_GET['from_sale_id']) ? $_GET['from_sale_id'] : $_GET['from_purchase_id'];
    $orig_type = isset($_GET['from_sale_id']) ? 'sale' : 'purchase';
    
    // Fetch original transaction
    $qry = $conn->query("SELECT * FROM `transactions` WHERE id = '{$orig_id}' AND type = '{$orig_type}'");
    if($qry->num_rows > 0){
        $orig_txn = $qry->fetch_assoc();
        
        $entity_type = ($orig_type == 'sale') ? 'customer' : 'vendor';
        $vendor_id = $orig_txn['entity_id'];
        $original_code_display = $orig_txn['reference_code'];
        // Pre-fill remarks with the original code
        $remarks = "Return against {$orig_type} #" . $orig_txn['reference_code'];
        
        // Items will be preloaded below.
        $is_from_existing = true;
    }
}
?>
<style>
    select[readonly].select2-hidden-accessible + .select2-container {
        pointer-events: none;
        touch-action: none;
        background: #eee;
        box-shadow: none;
    }
    select[readonly].select2-hidden-accessible + .select2-container .select2-selection {
        background: #eee;
        box-shadow: none;
    }
    /* Hide specific select2 container for Return From to prevent redundant display */
    #return_source + .select2-container {
        display: none !important;
    }
</style>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h4 class="card-title"><?php echo isset($id) ? "Return Details - ".$return_code : 'Create New Return Record' ?></h4>
    </div>
    <div class="card-body">
        <form action="" id="return-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <input type="hidden" name="parent_id" value="<?php echo isset($parent_id) ? $parent_id : (isset($orig_id) ? $orig_id : '') ?>">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-2">
                        <label class="control-label text-info">Return Code</label>
                        <input type="text" name="return_code" class="form-control form-control-sm rounded-0" value="<?php echo isset($return_code) ? $return_code : '' ?>" placeholder="<?php echo !isset($id) ? 'Auto generated' : '' ?>" readonly>
                    </div>
                    <?php if(isset($original_code_display) || isset($remarks) && preg_match('/#([A-Z0-9-\/]+)/', $remarks, $matches)): ?>
                    <div class="col-md-3">
                        <label class="control-label text-info">Created From</label>
                        <?php 
                        $orig_code = isset($original_code_display) ? $original_code_display : ($matches[1] ?? '');
                        $orig_link = '<b>'.$orig_code.'</b>';
                        if(!empty($orig_code)){
                            $orig_qry = $conn->query("SELECT id, type FROM transactions WHERE reference_code = '{$orig_code}' AND type IN ('sale', 'purchase')");
                            if($orig_qry->num_rows > 0){
                                $orig_data = $orig_qry->fetch_assoc();
                                $page_type = ($orig_data['type'] == 'sale') ? 'sales' : 'purchases';
                                $view_page = ($orig_data['type'] == 'sale') ? 'view_sale' : 'view_purchase';
                                $orig_link = '<a href="./?page=transactions/'.$page_type.'/'.$view_page.'&id='.$orig_data['id'].'" target="_blank" class="text-primary font-weight-bold">'.$orig_code.' <i class="fa fa-external-link-alt small"></i></a>';
                            }
                        }
                        ?>
                        <div class="pl-2" style="height:31px; display:flex; align-items:center; border:1px solid #ced4da; background:#e9ecef"><?php echo $orig_link ?></div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-3">
                        <!-- empty space to keep alignment if no original txn -->
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_created" class="control-label text-info">Date</label>
                            <input type="date" name="date_created" id="date_created" class="form-control form-control-sm rounded-0" value="<?php echo isset($transaction_date) ? $transaction_date : date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="return_source" class="control-label text-info">Return From</label>
                            <select id="return_source" name="entity_type" class="custom-select custom-select-sm rounded-0">
                                <option value="vendor" <?php echo !isset($id) || (isset($entity_type) && $entity_type == 'vendor') ? "selected" : "" ?>>Vendor</option>
                                <option value="customer" <?php echo (isset($entity_type) && $entity_type == 'customer') ? "selected" : "" ?>>Customer</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="vendor_id" class="control-label text-info" id="entity_label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="custom-select select2">
                                <option <?php echo !isset($vendor_id) ? 'selected' : '' ?> disabled></option>
                                <!-- Entities will be populated via JS -->
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <fieldset>
                    <legend class="text-info">Add Item</legend>
                    <div class="row justify-content-center align-items-end">
                        <?php 
                            $item_arr = array();
                            $item_qry = $conn->query("SELECT i.*, c.name as category, i.quantity as stock FROM `item_list` i LEFT JOIN category_list c ON i.category_id = c.id WHERE i.status = 1 ORDER BY i.name ASC");
                            while($row = $item_qry->fetch_assoc()){
                                $item_arr[$row['id']] = $row;
                            }
                        ?>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="item_id" class="control-label">Item</label>
                                <select id="item_id" class="custom-select select2">
                                    <option disabled selected></option>
                                    <?php foreach($item_arr as $k => $v): ?>
                                        <option value="<?php echo $k ?>" data-cost="<?php echo $v['cost'] ?>" data-stock="<?php echo $v['stock'] ?>" data-unit="<?php echo htmlspecialchars($v['unit'] ?? '') ?>" data-category="<?php echo htmlspecialchars($v['category'] ?? 'N/A') ?>" data-description="<?php echo htmlspecialchars($v['description'] ?? '') ?>"><?php echo htmlspecialchars($v['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label">Avail. Stock</label>
                                <div id="avail_display" class="form-control rounded-0 text-center px-1">0</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label">Unit</label>
                                <div id="unit_display" class="form-control rounded-0 text-center px-1">---</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="qty" class="control-label">Qty</label>
                                <input type="number" step="any" min="0.01" class="form-control rounded-0" id="qty">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="item_amount" class="control-label">Amount</label>
                                <input type="number" step="any" class="form-control rounded-0" id="item_amount">
                            </div>
                        </div>
                        <div class="col-md-1 text-center">
                            <div class="form-group">
                                <label class="control-label d-block">&nbsp;</label>
                                <button type="button" class="btn btn-flat btn-sm btn-primary btn-block" id="add_to_list">
                                    <i class="fa fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </fieldset>
                <hr>
                <table class="table table-striped table-bordered" id="list">
                    <colgroup>
                        <col width="5%">
                        <col width="5%">
                        <col width="20%">
                        <col width="15%">
                        <col width="25%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr class="text-light bg-navy">
                            <th class="text-center py-1 px-2">#</th>
                            <th class="text-center py-1 px-2">Action</th>
                            <th class="text-center py-1 px-2">Item Name</th>
                            <th class="text-center py-1 px-2">Category</th>
                            <th class="text-center py-1 px-2">Description</th>
                            <th class="text-center py-1 px-2">Rate</th>
                            <th class="text-center py-1 px-2">Quantity</th>
                            <th class="text-center py-1 px-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        if(isset($id)):
                        $row_num = 1;
                        $qry = $conn->query("SELECT p.*, i.name, i.description, i.unit, c.name as category FROM `transaction_items` p INNER JOIN item_list i ON p.item_id = i.id LEFT JOIN category_list c ON i.category_id = c.id WHERE p.transaction_id = '{$id}'");
                        while($row = $qry->fetch_assoc()):
                            $total += floatval($row['total_price']);
                        ?>
                        <tr data-id="<?php echo $row['item_id']; ?>">
                            <td class="py-1 px-2 text-center row-idx"><?php echo $row_num++; ?></td>
                            <td class="py-1 px-2 text-center">
                                <button class="btn btn-outline-danger btn-sm rem_row" type="button"><i class="fa fa-times"></i></button>
                            </td>
                            <td class="py-1 px-2 item"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="py-1 px-2 text-center category"><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                            <td class="py-1 px-2 description"><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                            <td class="py-1 px-2 text-right">
                                <input type="number" step="any" name="price[]" class="form-control form-control-sm rounded-0 text-right cost-input" value="<?php echo $row['unit_price']; ?>">
                            </td>
                            <td class="py-1 px-2 text-center qty">
                                <input type="number" step="any" name="qty[]" class="form-control form-control-sm rounded-0 text-right qty-input" value="<?php echo $row['quantity']; ?>">
                                <input type="hidden" name="item_id[]" value="<?php echo $row['item_id']; ?>">
                                <input type="hidden" name="unit[]" value="<?php echo $row['unit']; ?>">
                                <input type="hidden" name="total[]" value="<?php echo $row['total_price']; ?>">
                            </td>
                            <td class="py-1 px-2 text-right total"><?php echo number_format($row['total_price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php elseif(isset($is_from_existing)): ?>
                        <?php
                        $row_num = 1;
                        $qry = $conn->query("SELECT p.*, i.name, i.description, i.unit, c.name as category FROM `transaction_items` p INNER JOIN item_list i ON p.item_id = i.id LEFT JOIN category_list c ON i.category_id = c.id WHERE p.transaction_id = '{$orig_id}'");
                        while($row = $qry->fetch_assoc()):
                            $total += floatval($row['total_price']);
                        ?>
                        <tr data-id="<?php echo $row['item_id']; ?>">
                            <td class="py-1 px-2 text-center row-idx"><?php echo $row_num++; ?></td>
                            <td class="py-1 px-2 text-center">
                                <button class="btn btn-outline-danger btn-sm rem_row" type="button"><i class="fa fa-times"></i></button>
                            </td>
                            <td class="py-1 px-2 item"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="py-1 px-2 text-center category"><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                            <td class="py-1 px-2 description"><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                            <td class="py-1 px-2 text-right">
                                <input type="number" step="any" name="price[]" class="form-control form-control-sm rounded-0 text-right cost-input" value="<?php echo $row['unit_price']; ?>">
                            </td>
                            <td class="py-1 px-2 text-center qty">
                                <input type="number" step="any" name="qty[]" class="form-control form-control-sm rounded-0 text-right qty-input" value="<?php echo $row['quantity']; ?>">
                                <input type="hidden" name="item_id[]" value="<?php echo $row['item_id']; ?>">
                                <input type="hidden" name="unit[]" value="<?php echo $row['unit']; ?>">
                                <input type="hidden" name="total[]" value="<?php echo $row['total_price']; ?>">
                            </td>
                            <td class="py-1 px-2 text-right total"><?php echo number_format($row['total_price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td class="py-1 px-2" colspan="6"></td>
                            <td class="py-1 px-2 text-right font-weight-bold">Grand Total</td>
                            <td class="py-1 px-2 text-right font-weight-bold grand-total bg-primary text-white" style="font-size: 14px;"><?php echo number_format($total, 2) ?>
                                <input type="hidden" name="amount" value="<?php echo isset($amount) ? $amount : 0 ?>">
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="remarks" class="text-info control-label">Remarks / Reason</label>
                            <textarea name="remarks" id="remarks" rows="2" class="form-control rounded-0"><?php echo isset($remarks) ? htmlspecialchars($remarks) : '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer py-1 text-center">
        <button class="btn btn-flat btn-primary" type="submit" form="return-form">
            <i class="fa fa-save"></i> Save Return
        </button>
        <a class="btn btn-flat btn-dark" href="<?php echo base_url.'/admin?page=transactions/returns' ?>">
            <i class="fa fa-times"></i> Cancel
        </a>
    </div>
</div>
<table id="clone_list" class="d-none">
    <tr>
        <td class="py-1 px-2 text-center row-idx"></td>
        <td class="py-1 px-2 text-center">
            <button class="btn btn-outline-danger btn-sm rem_row" type="button"><i class="fa fa-times"></i></button>
        </td>
        <td class="py-1 px-2 item"></td>
        <td class="py-1 px-2 text-center category"></td>
        <td class="py-1 px-2 description"></td>
        <td class="py-1 px-2 text-right">
            <input type="number" step="any" name="price[]" class="form-control form-control-sm rounded-0 text-right cost-input">
        </td>
        <td class="py-1 px-2 text-center qty">
            <input type="number" step="any" name="qty[]" class="form-control form-control-sm rounded-0 text-right qty-input">
            <input type="hidden" name="item_id[]">
            <input type="hidden" name="unit[]">
            <input type="hidden" name="total[]">
        </td>
        <td class="py-1 px-2 text-right total"></td>
    </tr>
</table>
<script>
    var items = <?php echo json_encode($item_arr) ?>;
    var vendors = <?php 
        $v_qry = $conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Supplier' AND status = 1 ORDER BY display_name ASC");
        $v_arr = [];
        while($row = $v_qry->fetch_assoc()) $v_arr[] = $row;
        echo json_encode($v_arr);
    ?>;
    var customers = <?php 
        $c_qry = $conn->query("SELECT id, display_name as name FROM entity_list WHERE entity_type = 'Customer' AND status = 1 ORDER BY display_name ASC");
        $c_arr = [];
        while($row = $c_qry->fetch_assoc()) $c_arr[] = $row;
        echo json_encode($c_arr);
    ?>;
    
    $(function(){
        function get_next_code(){
            var date = $('#date_created').val();
            if(date == '' || $('[name="id"]').val() != '') return false;
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_next_ref_code",
                method:'POST',
                data:{type:'return', date:date},
                dataType:'json',
                error:err=>{
                    console.log(err)
                },
                success:resp=>{
                    if(resp.status == 'success'){
                        $('[name="return_code"]').val(resp.code);
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

        // Initialize Select2 but specifically exclude return_source if it was caught by generic selectors
        $('.select2:not(#return_source)').select2({placeholder: "Select here", width: '100%'});
        
        if($('#return_source').hasClass('select2-hidden-accessible')){
            $('#return_source').select2('destroy');
        }
        
        // Ensure it has NO select2 class just in case
        $('#return_source').removeClass('select2');

        function update_entities(selected_id = null){
            var source = $('#return_source').val();
            var list = (source == 'vendor') ? vendors : customers;
            $('#entity_label').text(source == 'vendor' ? 'Vendor' : 'Customer');
            
            $('#vendor_id').html('<option disabled selected></option>');
            list.forEach(function(item){
                var selected = (selected_id && selected_id == item.id) ? 'selected' : '';
                $('#vendor_id').append('<option value="'+item.id+'" '+selected+'>'+item.name+'</option>');
            });
            $('#vendor_id').trigger('change');
        }

        $('#return_source').change(function(){
            update_entities();
        });

        // Initialize entities
        update_entities('<?php echo isset($vendor_id) ? $vendor_id : "" ?>');
        
        // Handle item selection
        $('#item_id').change(function(){
            var option = $(this).find('option:selected');
            var id = $(this).val();
            if(!!items[id]){
                $('#avail_display').text(option.attr('data-stock') || '0');
                $('#unit_display').text(option.attr('data-unit') || '---');
                $('#item_amount').val(option.attr('data-cost') || '0');
                $('#qty').focus();
            } else {
                $('#avail_display').text('0');
                $('#unit_display').text('---');
                $('#item_amount').val('');
            }
        });

        $('#qty').on('input change', function(){
            var qty = parseFloat($(this).val()) || 0;
            var cost = parseFloat($('#item_id option:selected').attr('data-cost')) || 0;
            $('#item_amount').val((qty * cost).toFixed(2));
        });

        $('#item_amount').on('input change', function(){
            var amount = parseFloat($(this).val()) || 0;
            var cost = parseFloat($('#item_id option:selected').attr('data-cost')) || 0;
            if(cost > 0) $('#qty').val((amount / cost).toFixed(2));
        });

        $('#add_to_list').click(function(){
            var item = $('#item_id').val();
            var qty = $('#qty').val();
            var amount = $('#item_amount').val();
            
            if(!item){
                alert_toast('Please select an item.', 'warning');
                return false;
            }
            if(!qty || parseFloat(qty) <= 0){
                alert_toast('Please enter a valid quantity.', 'warning');
                return false;
            }
            if(!amount || parseFloat(amount) < 0){
                alert_toast('Please enter a valid amount.', 'warning');
                return false;
            }
            if($('table#list tbody').find('tr[data-id="'+item+'"]').length > 0){
                alert_toast('Item already on list.', 'error');
                return false;
            }
            
            var qty_val = parseFloat(qty);
            var amount_val = parseFloat(amount);
            var rate_val = amount_val / qty_val;
            
            var tr = $('#clone_list tr').clone();
            tr.find('[name="item_id[]"]').val(item);
            tr.find('[name="qty[]"]').val(qty_val);
            tr.find('[name="unit[]"]').val(items[item].unit);
            tr.find('[name="price[]"]').val(rate_val);
            tr.find('[name="total[]"]').val(amount_val);
            
            tr.attr('data-id', item);
            tr.find('.item').text(items[item].name);
            tr.find('.category').text(items[item].category || 'N/A');
            tr.find('.description').text(items[item].description || '');
            tr.find('.cost-input').val(rate_val.toFixed(2));
            tr.find('.qty-input').val(qty_val);
            tr.find('.total').text(amount_val.toLocaleString('en-US', {minimumFractionDigits: 2}));
            
            $('table#list tbody').append(tr);
            updateSerialNumbers();
            calc();
            $('#item_id').val('').trigger('change');
            $('#qty').val('');
            $('#item_amount').val('');
            
            // Delegated entry calculation handled globally below
            tr.find('.rem_row').click(function(){ 
                $(this).closest('tr').remove(); 
                updateSerialNumbers();
                calc(); 
            });
        });

        // Delegated event handler for price and qty changes
        $('#list tbody').on('input change', '.cost-input, .qty-input', function(){
            var tr = $(this).closest('tr');
            var qty = parseFloat(tr.find('.qty-input').val()) || 0;
            var cost = parseFloat(tr.find('.cost-input').val()) || 0;
            var total = qty * cost;
            tr.find('.total').text(total.toLocaleString('en-US', {minimumFractionDigits: 2}));
            tr.find('[name="total[]"]').val(total);
            calc();
        });

        function updateSerialNumbers(){
            $('table#list tbody tr').each(function(index){
                $(this).find('.row-idx').text(index + 1);
            });
        }

        $('#return-form').submit(function(e){
            e.preventDefault();
            if($('#list tbody tr').length <= 0){
                alert_toast("Please add at least 1 item.", "warning");
                return false;
            }
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_return",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: function(err){ 
                    console.log('AJAX Error:', err); 
                    alert_toast("An error occurred", 'error'); 
                    end_loader(); 
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        alert_toast("Return record successfully saved.", 'success');
                        setTimeout(function(){
                            location.replace(_base_url_ + "admin/?page=returns/view_return&id=" + resp.id);
                        }, 1500);
                    } else if(resp.status == 'failed' && !!resp.msg){
                        alert_toast(resp.msg, 'error'); 
                        end_loader();
                    } else {
                        alert_toast("An error occured", 'error'); 
                        end_loader();
                    }
                }
            });
        });

        if('<?php echo isset($id) || isset($is_from_existing) ?>' == 1){
            updateSerialNumbers();
            calc();
            $('#vendor_id').trigger('change');
            $('#list tbody tr').each(function(){
                var tr = $(this);
                tr.find('.rem_row').click(function(){ 
                    $(this).closest('tr').remove(); 
                    updateSerialNumbers();
                    calc(); 
                });
            });
        }
    });

    function calc(){
        var grand_total = 0;
        $('table#list tbody input[name="total[]"]').each(function(){ 
            grand_total += parseFloat($(this).val()) || 0; 
        });
        $('.grand-total').text(grand_total.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('[name="amount"]').val(grand_total.toFixed(2));
    }
</script>