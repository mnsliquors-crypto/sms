<?php
if(isset($_GET['id'])){
    // Fetch from unified transactions
    $qry = $conn->query("SELECT p.*, reference_code as po_code, entity_id as vendor_id, s.display_name as vendor FROM transactions p INNER JOIN entity_list s ON p.entity_id = s.id WHERE p.id = '{$_GET['id']}' AND p.type = 'purchase'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            $$k = $v;
        }
    }
}
if(isset($_GET['vendor_id']) && !empty($_GET['vendor_id'])){
    $vendor_id = intval($_GET['vendor_id']);
}
if(isset($_GET['item_id'])){
    $item_qry = $conn->query("SELECT vendor_id FROM item_list WHERE id = '{$_GET['item_id']}'");
    if($item_qry->num_rows > 0){
        $vendor_id = $item_qry->fetch_array()[0];
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
</style>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h4 class="card-title"><?php echo isset($id) ? "Purchase Bill Details - ".$po_code : 'Create New Purchase Bill' ?></h4>
    </div>
    <div class="card-body">
        <form action="" id="purchase-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-3">
                        <label class="control-label text-info">Bill Code</label>
                        <input type="text" name="po_code" class="form-control form-control-sm rounded-0" value="<?php echo isset($po_code) ? htmlspecialchars($po_code) : '' ?>" placeholder="<?php echo !isset($id) ? 'Auto-generated on save' : '' ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_po" class="control-label text-info">Date</label>
                            <input type="date" name="date_po" id="date_po" class="form-control form-control-sm rounded-0" value="<?php echo isset($transaction_date) ? $transaction_date : date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="vendor_invoice_no" class="control-label text-info">Vendor Invoice #</label>
                            <input type="text" name="vendor_invoice_no" id="vendor_invoice_no" class="form-control form-control-sm rounded-0" value="<?php echo isset($vendor_invoice_no) ? htmlspecialchars($vendor_invoice_no) : '' ?>" placeholder="Enter vendor invoice number">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="vendor_id" class="control-label text-info">Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" id="vendor_id" class="custom-select select2">
                                <option <?php echo !isset($vendor_id) ? 'selected' : '' ?> disabled></option>
                                <?php 
                                $vendor = $conn->query("SELECT *, display_name as name FROM `entity_list` WHERE entity_type = 'Supplier' AND status = 1 ORDER BY `display_name` ASC");
                                while($row = $vendor->fetch_assoc()):
                                ?>
                                <option value="<?php echo $row['id'] ?>" <?php echo isset($vendor_id) && $vendor_id == $row['id'] ? "selected" : "" ?>><?php echo htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
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
                                <input type="number" step="any" min="1" class="form-control rounded-0" id="qty" value="1">
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
                        // Fetch from transaction_items
                        $qry = $conn->query("SELECT p.*, i.name, i.description, i.unit, c.name as category, i.quantity as stock FROM `transaction_items` p INNER JOIN item_list i ON p.item_id = i.id LEFT JOIN category_list c ON i.category_id = c.id WHERE p.transaction_id = '{$id}'");
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
                                <input type="hidden" name="total[]" value="<?php echo $row['total_price']; ?>">
                            </td>
                            <td class="py-1 px-2 text-right total"><?php echo number_format($row['total_price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="py-1 px-2" colspan="6"></td>
                            <td class="py-1 px-2 text-right font-weight-bold">Sub Total</td>
                            <td class="py-1 px-2 text-right font-weight-bold grand-total"><?php echo number_format($total, 2) ?>
                                <input type="hidden" name="amount" value="<?php echo isset($amount) ? $amount : 0 ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="py-1 px-2" colspan="6"></td>
                            <td class="py-1 px-2 text-right font-weight-bold">VAT %</td>
                            <td class="py-1 px-2">
                                <input type="number" step="0.01" min="0" name="tax_perc" id="tax_perc" class="form-control form-control-sm text-right rounded-0" value="<?php echo isset($tax_perc) ? $tax_perc : 0 ?>">
                            </td>
                        </tr>
                        <tr>
                            <td class="py-1 px-2" colspan="6"></td>
                            <td class="py-1 px-2 text-right font-weight-bold">VAT Amount</td>
                            <td class="py-1 px-2">
                                <input type="number" step="any" name="tax" id="tax" class="form-control form-control-sm text-right rounded-0 bg-light" value="<?php echo isset($tax) ? $tax : 0 ?>" readonly>
                            </td>
                        </tr>
                        <tr class="bg-light">
                            <td class="py-1 px-2" colspan="6"></td>
                            <td class="py-1 px-2 text-right font-weight-bold">Grand Total</td>
                            <td class="py-1 px-2 text-right font-weight-bold total-with-tax bg-primary text-white" style="font-size: 14px;"><?php echo number_format((isset($amount) ? $amount : 0) + (isset($tax) ? $tax : 0), 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="remarks" class="text-info control-label">Remarks</label>
                            <textarea name="remarks" id="remarks" rows="2" class="form-control rounded-0"><?php echo isset($remarks) ? htmlspecialchars($remarks) : '' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer py-1 text-center">
        <button class="btn btn-flat btn-primary" type="submit" form="purchase-form">
            <i class="fa fa-save"></i> Save Purchase Bill
        </button>
        <a class="btn btn-flat btn-dark" href="<?php echo base_url.'/admin?page=transactions/purchases' ?>">
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
            <input type="hidden" name="total[]">
        </td>
        <td class="py-1 px-2 text-right total"></td>
    </tr>
</table>
<script>
    var items = <?php echo json_encode($item_arr) ?>;
    
    $(function(){
        function get_next_code(){
            var date = $('#date_po').val();
            if(date == '' || $('[name="id"]').val() != '') return false;
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_next_ref_code",
                method:'POST',
                data:{type:'purchase', date:date},
                dataType:'json',
                error:err=>{
                    console.log(err)
                },
                success:resp=>{
                    if(resp.status == 'success'){
                        $('[name="po_code"]').val(resp.code);
                    }
                }
            })
        }

        if($('[name="id"]').val() == ''){
            get_next_code();
        }

        $('#date_po').change(function(){
            get_next_code();
        })

        $('.select2').select2({placeholder: "Select here", width: 'resolve'});

        // Auto-select vendor if passed via URL
        var preselectedVendor = '<?php echo isset($vendor_id) ? intval($vendor_id) : '' ?>';
        if(preselectedVendor != ''){
            $('#vendor_id').val(preselectedVendor).trigger('change');
        }
        
        // Handle item selection
        $('#item_id').change(function(){
            var option = $(this).find('option:selected');
            var id = $(this).val();
            if(!!items[id]){
                $('#avail_display').text(option.attr('data-stock') || '0');
                $('#unit_display').text(option.attr('data-unit') || '---');
                $('#qty').focus();
            } else {
                $('#avail_display').text('0');
                $('#unit_display').text('---');
            }
        });

        $('#add_to_list').click(function(){
            var item = $('#item_id').val();
            var qty = $('#qty').val();
            var amount = $('#item_amount').val();
            
            if(!item){
                alert_toast('Please select an item.', 'warning');
                return false;
            }
            
            if(!qty || parseFloat(qty) < 1){
                alert_toast('Item quantity must be at least 1.', 'warning');
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

        $('#purchase-form').submit(function(e){
            e.preventDefault();
            if($('#vendor_id').val() == '' || $('#vendor_id').val() == null){
                alert_toast("Please select a vendor.", "warning");
                return false;
            }

            if($('#date_po').val() == ''){
                alert_toast("Please select a bill date.", "warning");
                return false;
            }
            
            if($('#list tbody tr').length <= 0){
                alert_toast("Please add at least 1 item to the list.", "warning");
                return false;
            }

            var qty_err = false;
            $('#list tbody tr').each(function(){
                var qty = parseFloat($(this).find('.qty-input').val()) || 0;
                if(qty < 1){
                    qty_err = true;
                }
            });

            if(qty_err){
                alert_toast("All items must have a quantity of at least 1.", "warning");
                return false;
            }
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_purchase",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: function(err){ 
                    console.log('AJAX Error:', err); 
                    console.log('Status:', err.status);
                    console.log('Response Text:', err.responseText);
                    alert_toast("An error occurred: " + (err.responseText || err.statusText), 'error'); 
                    end_loader(); 
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        alert_toast("Purchase Bill successfully saved.", 'success');
                        setTimeout(function(){
                            location.replace(_base_url_ + "admin/?page=transactions/purchases/view_purchase&id=" + resp.id);
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

        if('<?php echo isset($id) ?>' == 1){
            updateSerialNumbers();
            calc();
            $('#vendor_id').trigger('change');
            $('[name="tax_perc"]').on('input change', function(){ calc(); });
            $('#list tbody tr').each(function(){
                var tr = $(this);
                tr.find('.rem_row').click(function(){ 
                    $(this).closest('tr').remove(); 
                    updateSerialNumbers();
                    calc(); 
                });
            });
        }
        
        // Quick Purchase Logic
        if('<?php echo isset($_GET['item_id']) ? $_GET['item_id'] : '' ?>' != ''){
            var item_id = '<?php echo isset($_GET['item_id']) ? $_GET['item_id'] : '' ?>';
            setTimeout(function(){
                 if($('#item_id option[value="'+item_id+'"]').length > 0){
                     $('#item_id').val(item_id).trigger('change');
                 }
            }, 500);
        }
    });

    function calc(){
        var grand_total = 0;
        $('table#list tbody input[name="total[]"]').each(function(){ 
            grand_total += parseFloat($(this).val()) || 0; 
        });
        var tax_perc = parseFloat($('#tax_perc').val()) || 0;
        var tax_amount = (grand_total * tax_perc) / 100;
        var total_with_tax = grand_total + tax_amount;
        
        $('.grand-total').text(grand_total.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('.total-with-tax').text(total_with_tax.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#tax').val(tax_amount.toFixed(2));
        $('[name="amount"]').val(grand_total.toFixed(2));
    }
</script>
