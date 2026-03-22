<?php
if(isset($_GET['id'])){
    // Fetch from unified transactions
    $qry = $conn->query("SELECT *, reference_code as sales_code, entity_id as customer_id FROM transactions WHERE id = '{$_GET['id']}' AND type = 'sale'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            $$k = $v;
        }
    }
}
if(isset($_GET['customer_id']) && !empty($_GET['customer_id'])){
    $customer_id = intval($_GET['customer_id']);
}
$item_arr = array();
$item_qry = $conn->query("SELECT i.*, c.name as category, i.quantity as available FROM `item_list` i LEFT JOIN category_list c ON i.category_id = c.id WHERE i.status = 1 ORDER BY i.name ASC");
while($row = $item_qry->fetch_assoc()){
    $item_arr[$row['id']] = $row;
}
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h4 class="card-title">
            <?php echo isset($id) ? "Sale Details - ".$sales_code : 'Create New Sale' ?>
            <?php if(isset($remarks) && strpos($remarks, '[POS]') !== false): ?>
                <span class="badge badge-info shadow-sm ml-2" title="POS Entry">POS</span>
            <?php endif; ?>
        </h4>

    </div>
    <div class="card-body">
        <form action="" id="sale-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-3">
                        <label class="control-label text-info">Sale Code</label>
                        <input type="text" name="sales_code" class="form-control form-control-sm rounded-0" value="<?php echo isset($sales_code) ? htmlspecialchars($sales_code) : '' ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_sale" class="control-label text-info">Date</label>
                            <input type="date" name="date_sale" id="date_sale" class="form-control form-control-sm rounded-0" value="<?php echo isset($date_sale) ? $date_sale : date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="customer_id" class="control-label text-info">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="custom-select select2">
                                <option value="" <?php echo !isset($customer_id) ? 'selected' : '' ?>>Walk-in Customer</option>
                                <?php 
                                $customer = $conn->query("SELECT *, display_name as name FROM `entity_list` WHERE entity_type = 'Customer' AND status = 1 ORDER BY `display_name` ASC");
                                while($row = $customer->fetch_assoc()):
                                    // Default to Walk In (ID 6) if no id is provided (new sale)
                                    $selected = '';
                                    if(isset($customer_id)){
                                        if($customer_id == $row['id']) $selected = 'selected';
                                    } else {
                                        if($row['id'] == 6) $selected = 'selected';
                                    }
                                ?>
                                <option value="<?php echo $row['id'] ?>" <?php echo $selected ?>><?php echo htmlspecialchars($row['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="payment_terms" class="control-label text-info">Payment Terms</label>
                            <select name="payment_terms" id="payment_terms" class="custom-select custom-select-sm rounded-0" data-no-select2>
                                <option value="Cash" <?php echo (!isset($payment_terms) || $payment_terms == 'Cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="COD" <?php echo isset($payment_terms) && $payment_terms == 'COD' ? 'selected' : '' ?>>COD (Cash on Delivery)</option>
                                <option value="Net 15" <?php echo isset($payment_terms) && $payment_terms == 'Net 15' ? 'selected' : '' ?>>Net 15</option>
                                <option value="Net 30" <?php echo isset($payment_terms) && $payment_terms == 'Net 30' ? 'selected' : '' ?>>Net 30</option>
                                <option value="Net 60" <?php echo isset($payment_terms) && $payment_terms == 'Net 60' ? 'selected' : '' ?>>Net 60</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label text-info">Credit Amount</label>
                            <div id="customer_credit" class="form-control form-control-sm rounded-0 bg-light text-right">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label text-info">Credit Limit</label>
                            <div id="credit_limit_display" class="form-control form-control-sm rounded-0 bg-light text-right">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label text-info">Remaining Credit</label>
                            <div id="remaining_credit_display" class="form-control form-control-sm rounded-0 bg-light text-right">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label text-info">Contact #</label>
                            <div id="customer_contact" class="form-control form-control-sm rounded-0 bg-light">N/A</div>
                        </div>
                    </div>
                </div>
                <hr>
                <fieldset>
                    <legend class="text-info">Add Item</legend>
                    <div class="row justify-content-center align-items-end">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="item_id" class="control-label">Item</label>
                                <select id="item_id" class="custom-select select2">
                                    <option disabled selected></option>
                                    <?php foreach($item_arr as $k => $v): ?>
                                        <option value="<?php echo $k ?>" data-price="<?php echo $v['selling_price'] ?>" data-cost="<?php echo $v['cost'] ?? 0 ?>" data-available="<?php echo $v['available'] ?>" data-unit="<?php echo htmlspecialchars($v['unit'] ?? '') ?>" data-category="<?php echo htmlspecialchars($v['category'] ?? 'N/A') ?>" data-description="<?php echo htmlspecialchars($v['description'] ?? '') ?>"><?php echo htmlspecialchars($v['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label class="control-label">Avail.</label>
                                <div id="avail_display" class="form-control rounded-0 text-center px-1">0</div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label class="control-label">Unit</label>
                                <div id="unit_display" class="form-control rounded-0 text-center px-1">---</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label">Cost Price</label>
                                <div id="cost_display" class="form-control rounded-0 text-right">0.00</div>
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
                                <label for="rate" class="control-label">Rate</label>
                                <input type="number" step="any" class="form-control rounded-0" id="rate">
                            </div>
                        </div>
                        <div class="col-md-2 text-center">
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
                        <col width="3%">
                        <col width="4%">
                        <col width="16%">
                        <col width="10%">
                        <col width="17%">
                        <col width="10%">
                        <col width="8%">
                        <col width="10%">
                        <col width="11%">
                        <col width="11%">
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
                            <th class="text-center py-1 px-2">Amount</th>
                            <th class="text-center py-1 px-2">Cost Amt</th>
                            <th class="text-center py-1 px-2">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        $total_qty = 0;
                        $total_profit = 0;
                        $total_cost_amt = 0;
                        if(isset($id)):
                        $i = 1;
                        // Fetch from unified transaction_items
                        $qry = $conn->query("
                            SELECT ti.*, i.name, i.description, i.cost as item_cost, c.name as category 
                            FROM `transaction_items` ti 
                            INNER JOIN item_list i ON ti.item_id = i.id 
                            LEFT JOIN category_list c ON i.category_id = c.id 
                            WHERE ti.transaction_id = '{$id}'
                        ");
                        while($row = $qry->fetch_assoc()){
                            $total += floatval($row['total_price']);
                            $total_qty += floatval($row['quantity']);
                            $row_cost_amt = floatval($row['item_cost']) * floatval($row['quantity']);
                            $row_profit = floatval($row['profit']);
                            // If stored profit is 0 (old records), recalculate from cost
                            if($row_profit == 0 && $row_cost_amt > 0){
                                $row_profit = floatval($row['total_price']) - $row_cost_amt;
                            }
                            $total_cost_amt += $row_cost_amt;
                            $total_profit += $row_profit;
                        ?>
                        <tr data-id="<?php echo $row['item_id']; ?>" data-cost="<?php echo floatval($row['item_cost']); ?>">
                            <td class="py-1 px-2 text-center row-idx"><?php echo $i++; ?></td>
                            <td class="py-1 px-2 text-center">
                                <button class="btn btn-outline-danger btn-sm rem_row" type="button"><i class="fa fa-times"></i></button>
                            </td>
                            <td class="py-1 px-2 text-center item"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="py-1 px-2 text-center category"><?php echo htmlspecialchars($row['category']); ?></td>
                            <td class="py-1 px-2 description">
                                <p class="m-0 small" style="white-space:normal; line-height:1.1"><?php echo htmlspecialchars($row['description']); ?></p>
                            </td>
                            <td class="py-1 px-2 text-right">
                                <input type="number" step="any" name="price[]" class="form-control form-control-sm rounded-0 text-right price-input" value="<?php echo $row['unit_price']; ?>">
                            </td>
                            <td class="py-1 px-2 text-center qty">
                                <input type="number" step="any" name="qty[]" class="form-control form-control-sm rounded-0 text-right qty-input" value="<?php echo $row['quantity']; ?>">
                                <input type="hidden" name="item_id[]" value="<?php echo $row['item_id']; ?>">
                                <input type="hidden" name="total_price[]" value="<?php echo $row['total_price']; ?>">
                            </td>
                            <td class="py-1 px-2 text-right total"><?php echo number_format($row['total_price'], 2); ?></td>
                            <td class="py-1 px-2 text-right cost-amt-cell" style="white-space:nowrap;"><?php echo number_format($row_cost_amt, 2); ?></td>
                            <td class="py-1 px-2 profit-cell">
                                <input type="number" step="any" name="profit[]" class="form-control form-control-sm rounded-0 text-right profit-input" value="<?php echo number_format($row_profit, 2, '.', ''); ?>" style="border-color:#ffc107; min-width:80px;">
                            </td>
                        </tr>
                        <?php } ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-right py-1 px-2" colspan="6">Sub-Total</th>
                            <th class="text-center py-1 px-2 grand-total-qty"><?php echo number_format($total_qty ?? 0, 2) ?></th>
                            <th class="text-right py-1 px-2 sub-total-amount"><?php echo number_format($total, 2) ?></th>
                            <th class="text-right py-1 px-2 sub-total-cost"><?php echo number_format($total_cost_amt ?? 0, 2) ?></th>
                            <th class="text-right py-1 px-2 sub-total-profit"><?php echo number_format($total_profit ?? 0, 2) ?></th>
                        </tr>
                        <tr>
                            <th class="text-right py-1 px-2" colspan="9">Discount</th>
                            <th class="text-right py-1 px-2">
                                <input type="number" step="any" name="discount" id="discount" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($discount) ? $discount : 0 ?>">
                            </th>
                        </tr>
                        <tr>
                            <th class="text-right py-1 px-2" colspan="9">Grand Total</th>
                            <th class="text-right py-1 px-2 grand-total-amount">
                                <?php echo number_format($total - ($discount ?? 0), 2) ?>
                                <input type="hidden" name="amount" value="<?php echo isset($amount) ? $amount : 0 ?>">
                            </th>
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
        <button class="btn btn-flat btn-primary" type="submit" form="sale-form">
            <i class="fa fa-save"></i> Save Sale
        </button>
        <a class="btn btn-flat btn-dark" href="<?php echo base_url.'/admin?page=transactions/sales' ?>">
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
        <td class="py-1 px-2 text-center item"></td>
        <td class="py-1 px-2 text-center category"></td>
        <td class="py-1 px-2 description">
            <p class="m-0 small" style="white-space:normal; line-height:1.1"></p>
        </td>
        <td class="py-1 px-2 text-right">
            <input type="number" step="any" name="price[]" class="form-control form-control-sm rounded-0 text-right price-input">
        </td>
        <td class="py-1 px-2 text-center qty">
            <input type="number" step="any" name="qty[]" class="form-control form-control-sm rounded-0 text-right qty-input">
            <input type="hidden" name="item_id[]">
            <input type="hidden" name="total_price[]">
        </td>
        <td class="py-1 px-2 text-right total"></td>
        <td class="py-1 px-2 text-right cost-amt-cell" style="white-space:nowrap;"></td>
        <td class="py-1 px-2 profit-cell">
            <input type="number" step="any" name="profit[]" class="form-control form-control-sm rounded-0 text-right profit-input" style="border-color:#ffc107; min-width:80px;">
        </td>
    </tr>
</table>
<script>
    var items = <?php echo json_encode($item_arr) ?>;
    
    $(function(){
        function get_next_code(){
            var date = $('#date_sale').val();
            if(date == '' || $('[name="id"]').val() != '') return false;
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_next_ref_code",
                method:'POST',
                data:{type:'sale', date:date},
                dataType:'json',
                error:err=>{
                    console.log(err)
                },
                success:resp=>{
                    if(resp.status == 'success'){
                        $('[name="sales_code"]').val(resp.code);
                    }
                }
            })
        }

        if($('[name="id"]').val() == ''){
            get_next_code();
        }

        $('#date_sale').change(function(){
            get_next_code();
        })

        $('.select2').select2({placeholder: "Select here", width: 'resolve'});

        // Auto-select customer if passed via URL
        var preselectedCustomer = '<?php echo isset($customer_id) ? intval($customer_id) : '' ?>';
        if(preselectedCustomer != ''){
            $('#customer_id').val(preselectedCustomer).trigger('change');
        }

        $('#customer_id').change(function(){
            var id = $(this).val();
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_customer_details",
                method:'POST',
                data:{id:id},
                dataType:'json',
                error:err=>{
                    console.log(err);
                    alert_toast("An error occured",'error');
                    end_loader();
                },
                success:function(resp){
                    if(resp.status == 'success'){
                        var outstanding = parseFloat(resp.data.outstanding || 0);
                        var credit_limit = parseFloat(resp.data.credit_limit || 0);
                        var remaining = parseFloat(resp.data.remaining_credit || 0);

                        $('#customer_contact').text(resp.data.contact || 'N/A');
                        $('#customer_credit').text(outstanding.toLocaleString('en-US', {minimumFractionDigits:2}));
                        $('#credit_limit_display').text(credit_limit.toLocaleString('en-US', {minimumFractionDigits:2}));
                        
                        if(credit_limit == 0) $('#remaining_credit_display').text('Unlimited');
                        else $('#remaining_credit_display').text(remaining.toLocaleString('en-US', {minimumFractionDigits:2}));
                        
                        $('#customer_id').attr('data-remaining', remaining);
                        $('#customer_id').attr('data-limit', credit_limit);
                    }
                    end_loader();
                }
            })
        });

        if($('#customer_id').val() > 0){
            $('#customer_id').trigger('change');
        }

        $('#item_id').change(function(){
            var id = $(this).val();
            if(!!items[id]){
                $('#avail_display').text(items[id].available);
                $('#unit_display').text(items[id].unit || '---');
                $('#cost_display').text(parseFloat(items[id].cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
                $('#rate').val(items[id].selling_price);
                $('#qty').focus();
            } else {
                $('#avail_display').text('0');
                $('#unit_display').text('---');
                $('#cost_display').text('0.00');
            }
        });

        $('#add_to_list').click(function(){
            var item = $('#item_id').val();
            var qty = $('#qty').val();
            var rate = $('#rate').val();

            if(!item || !qty || !rate){
                alert_toast('Item, Rate and Quantity are required.', 'warning');
                return false;
            }

            if(parseFloat(qty) < 1){
                alert_toast('Item quantity must be at least 1.', 'warning');
                return false;
            }
            
            var qty_val = parseFloat(qty);
            var rate_val = parseFloat(rate);
            var available = parseFloat(items[item].available);
            
            if(qty_val > available && available > 0){
                alert_toast('Quantity exceeds available stock (' + available + ').', 'warning');
                return false;
            }
            
            if($('table#list tbody').find('tr[data-id="'+item+'"]').length > 0){
                alert_toast('Item already on list.', 'error');
                return false;
            }
            
            var total = qty_val * rate_val;
            var cost = parseFloat(items[item].cost || 0);
            var cost_amt = cost * qty_val;
            var profit = total - cost_amt;
            
            var tr = $('#clone_list tr').clone();
            tr.find('[name="item_id[]"]').val(item);
            tr.find('[name="qty[]"]').val(qty_val);
            tr.find('[name="price[]"]').val(rate_val);
            tr.find('[name="total_price[]"]').val(total);
            tr.find('.profit-input').val(profit.toFixed(2));
            
            tr.attr('data-id', item);
            tr.attr('data-cost', cost);
            tr.find('.item').text(items[item].name);
            tr.find('.category').text(items[item].category || 'N/A');
            tr.find('.description p').text(items[item].description || '');
            tr.find('.total').text(total.toLocaleString('en-US', {minimumFractionDigits: 2}));
            tr.find('.cost-amt-cell').text(cost_amt.toLocaleString('en-US', {minimumFractionDigits: 2}));
            
            // Delegated entry calculation handled globally below
            $('table#list tbody').append(tr);
            calc();
            $('#item_id').val('').trigger('change');
            $('#qty').val('');
            $('#rate').val('');
            
            tr.find('.rem_row').click(function(){ $(this).closest('tr').remove(); calc(); });
        });

        // Delegated event handler for price, qty, and profit changes
        $('#list tbody').on('input change', '.price-input, .qty-input, .profit-input', function(){
            var tr = $(this).closest('tr');
            var qty = parseFloat(tr.find('.qty-input').val()) || 0;
            var price = parseFloat(tr.find('.price-input').val()) || 0;
            var row_cost = parseFloat(tr.attr('data-cost')) || 0;
            var total = qty * price;
            
            if($(this).hasClass('price-input') || $(this).hasClass('qty-input')){
                var new_cost_amt = row_cost * qty;
                var new_profit = total - new_cost_amt;
                tr.find('.profit-input').val(new_profit.toFixed(2));
            }
            calc();
        });

        $('#sale-form').submit(function(e){
            e.preventDefault();
            if($('#customer_id').val() == '' || $('#customer_id').val() == null){
                alert_toast("Please select a customer.", "warning");
                return false;
            }

            if($('#date_sale').val() == ''){
                alert_toast("Please select a sale date.", "warning");
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
            
            var remaining = parseFloat($('#customer_id').attr('data-remaining')) || 0;
            var limit = parseFloat($('#customer_id').attr('data-limit')) || 0;
            var amount = parseFloat($('[name="amount"]').val()) || 0;
            
            if(limit > 0 && amount > remaining){
                if(!confirm("The sale amount exceeds the customer's remaining credit limit. Do you want to proceed?")){
                    return false;
                }
            }
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_sale",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: function(err){ 
                    console.log(err); 
                    alert_toast("An error occured", 'error'); 
                    end_loader(); 
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        location.replace(_base_url_ + "admin/?page=transactions/sales/view_sale&id=" + resp.id);
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

        if('<?php echo isset($id) ?>' == 1) {
            $('#list tbody tr').each(function(){
                var tr = $(this);
                var qty = parseFloat(tr.find('.qty-input').val()) || 0;
                var price = parseFloat(tr.find('.price-input').val()) || 0;
                var row_cost = parseFloat(tr.attr('data-cost')) || 0;
                var total = qty * price;
                var cost_amt = row_cost * qty;
                var profit = total - cost_amt;
                // Recalculate profit if stored value is 0 or missing
                var storedProfit = parseFloat(tr.find('.profit-input').val()) || 0;
                if(storedProfit == 0 && cost_amt > 0){
                    tr.find('.profit-input').val(profit.toFixed(2));
                }
                tr.find('.rem_row').click(function(){ $(this).closest('tr').remove(); calc(); });
            });
            calc();
        }
        
        // Quick Sale Logic
        if('<?php echo isset($_GET['item_id']) ? $_GET['item_id'] : '' ?>' != ''){
            var item_id = '<?php echo isset($_GET['item_id']) ? $_GET['item_id'] : '' ?>';
            setTimeout(function(){
                 if($('#item_id option[value="'+item_id+'"]').length > 0){
                     $('#item_id').val(item_id).trigger('change');
                     $('#qty').val(1);
                     $('#add_to_list').trigger('click');
                 }
            }, 500);
        }
    });

    function calc(){
        var sub_total = 0;
        var grand_qty = 0;
        var total_cost_amt = 0;
        var total_profit = 0;
        var i = 1;
        $('table#list tbody tr').each(function(){
            var tr = $(this);
            tr.find('.row-idx').text(i++);
            var qty = parseFloat(tr.find('.qty-input').val()) || 0;
            var price = parseFloat(tr.find('.price-input').val()) || 0;
            var row_cost = parseFloat(tr.attr('data-cost')) || 0;
            var total = qty * price;
            var cost_amt = row_cost * qty;
            var profit = parseFloat(tr.find('.profit-input').val()) || 0;
            tr.find('.total').text(total.toLocaleString('en-US', {minimumFractionDigits: 2}));
            tr.find('input[name="total_price[]"]').val(total);
            tr.find('.cost-amt-cell').text(cost_amt.toLocaleString('en-US', {minimumFractionDigits: 2}));
            sub_total += total;
            grand_qty += qty;
            total_cost_amt += cost_amt;
            total_profit += profit;
        });
        $('.sub-total-amount').text(sub_total.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('.grand-total-qty').text(grand_qty.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('.sub-total-cost').text(total_cost_amt.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('.sub-total-profit').text(total_profit.toLocaleString('en-US', {minimumFractionDigits: 2}));
        
        var discount = parseFloat($('#discount').val()) || 0;
        var grand_total = sub_total - discount;
        
        $('.grand-total-amount').html(grand_total.toLocaleString('en-US', {minimumFractionDigits: 2}) + '<input type="hidden" name="amount" value="'+grand_total.toFixed(2)+'">');
    }

    $(function(){
        $('#discount').on('input change', function(){
            calc();
        });
    })
</script>
