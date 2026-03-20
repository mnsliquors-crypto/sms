<?php
$item_arr = array();

$customers = $conn->query("SELECT *, display_name as name FROM `entity_list` WHERE entity_type = 'Customer' AND status = 1 ORDER BY `display_name` ASC");
$accounts = $conn->query("SELECT * FROM `account_list` WHERE status = 1 ORDER BY `name` ASC");
$acc_arr = array();
while($row = $accounts->fetch_assoc()){
    $acc_arr[] = $row;
}
?>
<style>
    /* Premium POS Styling */
    :root {
        --pos-primary: #4e73df;
        --pos-success: #1cc88a;
        --pos-dark: #2c3e50;
        --pos-total-bg: #1a1a1a;
        --pos-total-text: #00ff88;
    }

    #pos-cart-container { 
        height: 52vh; 
        overflow-y: auto; 
        background: #fff; 
        border: 1px solid #e3e6f0; 
        border-radius: 8px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,.05);
    }
    
    #pos-cart-table th { 
        position: sticky; 
        top: 0; 
        background: var(--pos-dark); 
        color: #fff; 
        z-index: 10; 
        font-size: 0.82rem; 
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 8px;
        border: none;
    }
    
    #pos-cart-table td { 
        font-size: 0.88rem; 
        vertical-align: middle; 
        padding: 10px 8px; 
    }

    .pos-right-panel { 
        background: #ffffff; 
        border-left: 1px solid #e3e6f0; 
        height: 100%; 
        padding: 20px; 
    }

    /* KPI Style Grand Total Card */
    .pos-total-display { 
        font-size: 2.8rem; 
        font-weight: 800; 
        background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); 
        color: var(--pos-total-text); 
        padding: 25px 20px; 
        border-radius: 12px; 
        text-align: right; 
        font-family: 'Courier New', Courier, monospace; 
        letter-spacing: 3px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        text-shadow: 0 0 10px rgba(0,255,136,0.3);
        border: 2px solid rgba(255,255,255,0.05);
    }

    .pos-return-display { 
        font-size: 2rem; 
        font-weight: 800; 
        color: #e74a3b; 
        text-align: right; 
        text-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    /* Better form controls */
    .form-control-sm, .custom-select-sm { border-radius: 6px !important; }
    .btn-lg { border-radius: 10px !important; transition: all 0.3s ease; }
    .btn-lg:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

    /* Fix column overlap on specific resolutions */
    body.sidebar-collapse .content-wrapper { margin-left: 0 !important; }
    
    /* Animation for new rows */
    @keyframes fadeInRow {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #pos-cart-table tbody tr { animation: fadeInRow 0.3s ease-out; }

    /* Custom Scrollbar */
    #pos-cart-container::-webkit-scrollbar { width: 6px; }
    #pos-cart-container::-webkit-scrollbar-track { background: #f1f1f1; }
    #pos-cart-container::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    #pos-cart-container::-webkit-scrollbar-thumb:hover { background: #999; }
</style>

<div class="container-fluid py-2">
    <form action="" id="pos-form">
        <div class="row">
            <!-- Left Panel: Cart & Items (8 cols) -->
            <div class="col-md-8">
                <div class="card card-outline card-primary mb-2">
                    <div class="card-body p-2">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="text-xs mb-0">Date</label>
                                <input type="date" name="pos_date" id="pos_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-8">
                                <label class="text-xs mb-0">Customer <span class="text-danger">*</span></label>
                                <div id="customer_ss" 
                                     data-url="<?php echo base_url ?>classes/Master.php?f=search_customers" 
                                     data-placeholder="-- Walk-in Customer (Type to search) --"
                                     data-name="customer_id">
                                </div>
                            </div>

                        </div>
                        
                        <div class="row align-items-end mb-2" style="background: #e9ecef; padding: 10px; border-radius: 5px;">
                            <div class="col-md-6">
                                <label class="text-xs mb-0">Search Item / Barcode</label>
                                <div id="item_ss" 
                                     data-url="<?php echo base_url ?>classes/Master.php?f=search_pos_items" 
                                     data-placeholder="Type 3+ chars to search items..."
                                     data-name="item_id">
                                </div>

                            </div>
                            <div class="col-md-2">
                                <label class="text-xs mb-0">Qty</label>
                                <input type="number" step="any" min="0.01" class="form-control form-control-sm" id="qty" placeholder="1">
                            </div>
                            <div class="col-md-2">
                                <label class="text-xs mb-0">Rate</label>
                                <input type="number" step="any" class="form-control form-control-sm" id="rate">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-sm btn-primary btn-block shadow-sm" id="add_to_cart_btn">
                                    <i class="fa fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Table -->
                <div id="pos-cart-container" class="shadow-sm">
                            <table class="table table-striped table-hover m-0" id="pos-cart-table">
                            <colgroup>
                                <col width="5%">
                                <col width="35%">
                                <col width="12%">
                                <col width="11%">
                                <col width="15%">
                                <col width="15%">
                                <col width="7%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Item Name</th>
                                    <th class="text-right">Rate</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right text-info">MRP</th>

                                    <th class="text-center"><i class="fa fa-trash"></i></th>
                                </tr>
                            </thead>

                        <tbody>
                            <!-- Cart items go here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Panel: Payment & Checkout (4 cols) -->
            <div class="col-md-4">
                <div class="card card-outline card-success h-100 mb-0 shadow-sm">
                    <div class="card-body pos-right-panel d-flex flex-column">
                        <h5 class="text-center text-muted text-uppercase mb-3 font-weight-bold" style="letter-spacing:1px;">Grand Total</h5>
                        <div class="pos-total-display mb-4" id="grand_total_display">0.00</div>
                        
                        <input type="hidden" name="cart_total" id="cart_total" value="0">
                        
                        <div id="payment-modes-container" class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="text-secondary text-uppercase font-weight-bold small mb-0">Payment Breakdown</label>
                                <button type="button" class="btn btn-xs btn-outline-danger" id="return_change_btn" title="Return Change as Negative Cash">
                                    <i class="fas fa-undo"></i> Return Change
                                </button>
                            </div>
                            <?php foreach($acc_arr as $acc): ?>
                            <div class="form-group mb-2 p-2 border rounded shadow-sm bg-light payment-row" data-type="<?php echo $acc['type'] ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="mb-0 font-weight-bold text-dark" style="font-size:0.85rem;"><?php echo htmlspecialchars($acc['name']) ?></label>
                                    <span class="badge badge-secondary" style="font-size:0.7rem;"><?php 
                                        switch($acc['type']){
                                            case 1: echo 'Cash'; break;
                                            case 2: echo 'Bank'; break;
                                            case 3: echo 'Mobile'; break;
                                            case 4: echo 'Credit'; break;
                                            default: echo 'Other';
                                        }
                                    ?></span>
                                </div>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold">Rs.</span>
                                    </div>
                                    <input type="number" step="any" name="account_amount[<?php echo $acc['id'] ?>]" class="form-control text-right paid-amount-input font-weight-bold" data-id="<?php echo $acc['id'] ?>" data-name="<?php echo htmlspecialchars($acc['name']) ?>" data-type="<?php echo $acc['type'] ?>" placeholder="0.00" style="font-size: 1.1rem;">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>


                        <div class="form-group mb-3 d-none">
                            <label class="text-secondary">Amount Paid (Hidden Total)</label>
                            <input type="number" step="any" name="paid_amount" id="paid_amount" class="form-control" readonly>
                        </div>


                        <div class="row mb-2">
                             <div class="col-6">
                                &nbsp;
                             </div>

                             <div class="col-6 text-right">
                                <label class="text-secondary small text-uppercase">Return Change</label>
                                <div class="pos-return-display" id="return_amount_display" style="font-size: 1.5rem;">0.00</div>
                             </div>
                        </div>


                        <button class="btn btn-success btn-lg btn-block mt-4 py-3 shadow" type="submit" form="pos-form" style="font-size:1.2rem; font-weight:bold; letter-spacing:1px;">
                            <i class="fa fa-shopping-cart mr-2"></i> COMPLETE SALE
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Hidden Clone Template -->
<table id="clone_list" class="d-none">
    <tr>
        <td class="text-center row-idx"></td>
        <td class="item-name font-weight-bold text-dark"></td>
        <td class="text-right">
            <input type="number" step="any" name="price[]" class="form-control form-control-sm text-right price-input border-0 bg-transparent p-0" readonly>
        </td>
        <td class="text-center">
            <input type="number" step="any" name="qty[]" class="form-control form-control-sm text-center qty-input font-weight-bold">
            <input type="hidden" name="item_id[]">
        </td>
        <td class="text-right">
            <input type="text" name="total_price[]" class="form-control form-control-sm text-right total-input border-0 bg-transparent p-0 font-weight-bold text-success" readonly>
        </td>
        <td class="text-right">
            <input type="text" name="mrp[]" class="form-control form-control-sm text-right mrp-input border-0 bg-transparent p-0 font-weight-bold" readonly>
        </td>

        <td class="text-center">
            <button class="btn btn-sm btn-outline-danger rem_row" type="button"><i class="fa fa-times"></i></button>
        </td>
    </tr>

</table>

<script>
    var items = <?php echo json_encode($item_arr) ?>;
    
    $(function(){
        // Initialize collapse sidebar for wider POS view
        $('body').addClass('sidebar-collapse');
        

        // Initialize new SearchableSelect components
        const customerSS = new SearchableSelect('#customer_ss');

        // Initialize new SearchableSelect component for items
        const itemSS = new SearchableSelect('#item_ss', {
            onSelect: function(item) {
                $('#rate').val(item.price);
                $('#qty').val(1).focus().select();
                
                // Store data for cart logic
                items[item.id] = {
                    id: item.id,
                    name: item.text,
                    selling_price: item.price,
                    mrp: item.mrp,
                    available: item.available
                };
            }
        });
        
        setTimeout(() => { itemSS.input.focus(); }, 500);



        // Add to cart via button or Enter key on Qty/Rate
        $('#add_to_cart_btn').click(add_to_cart);
        $('#qty, #rate').keypress(function(e){
            if(e.which == 13){
                e.preventDefault();
                add_to_cart();
            }
        });

        function add_to_cart(){
            var item = $('[name="item_id"]').val();
            var qty = parseFloat($('#qty').val()) || 1;
            var rate = parseFloat($('#rate').val()) || 0;

            if(!item){
                alert_toast('Please select an item first.', 'warning');
                return false;
            }
            if(qty <= 0){
                alert_toast('Quantity must be greater than zero.', 'warning');
                return false;
            }
            
            var available = parseFloat(items[item].available);
            if(qty > available && available > 0){
                alert_toast('Warning: Quantity exceeds available stock (' + available + ').', 'warning');
                // Allow to proceed but display warning
            }

            var existing_row = $('#pos-cart-table tbody').find('tr[data-id="'+item+'"]');
            
            if(existing_row.length > 0){
                // Item exists, just update quantity
                var cur_qty = parseFloat(existing_row.find('.qty-input').val()) || 0;
                var new_qty = cur_qty + qty;
                existing_row.find('.qty-input').val(new_qty).trigger('change');
            } else {
                // New item row
                var total = qty * rate;
                var cost = parseFloat(items[item].cost || 0);
                var profit = total - (cost * qty);
                
                var tr = $('#clone_list tr').clone();
                
                tr.attr('data-id', item);
                tr.find('[name="item_id[]"]').val(item);
                tr.find('.item-name').text(items[item].name);
                tr.find('.price-input').val(rate);
                tr.find('.qty-input').val(qty);
                tr.find('.total-input').val(total.toFixed(2));
                tr.find('.mrp-input').val(parseFloat(items[item].mrp || 0).toFixed(2));

                
                $('#pos-cart-table tbody').prepend(tr);
                
                tr.find('.rem_row').click(function(){ 
                    $(this).closest('tr').remove(); 
                    calc_total(); 
                });
            }
            
            calc_total();

            
            // Reset insertion form and refocus search
            itemSS.input.value = '';
            itemSS.hiddenInput.value = '';
            $('#qty').val('');
            $('#rate').val('');
            itemSS.input.focus();
        }

        // Live calculation on cart qty change
        $('#pos-cart-table').on('input change', '.qty-input', function(){
            var tr = $(this).closest('tr');
            var qty = parseFloat($(this).val()) || 0;
            var price = parseFloat(tr.find('.price-input').val()) || 0;
            if(qty <= 0) {
                tr.remove();
            } else {
                var total = qty * price;
                tr.find('.total-input').val(total.toFixed(2));
            }
            calc_total();
        });



        // Global sum function
        function calc_total(){
            var grand_total = 0;
            var i = 1;
            $('#pos-cart-table tbody tr').each(function(){
                $(this).find('.row-idx').text(i++);
                grand_total += parseFloat($(this).find('.total-input').val()) || 0;
            });
            $('#cart_total').val(grand_total);
            $('#grand_total_display').text(grand_total.toLocaleString('en-US', {minimumFractionDigits: 2}));
            calc_return();
        }



        // Return Amount auto calculator
        $('#payment-modes-container').on('input change', '.paid-amount-input', function(){
            calc_return();
        });

        function calc_return() {
            var grand_total = parseFloat($('#cart_total').val()) || 0;
            var total_paid = 0;
            $('.paid-amount-input').each(function(){
                total_paid += parseFloat($(this).val()) || 0;
            });
            $('#paid_amount').val(total_paid.toFixed(2));
            
            var change = total_paid - grand_total;
            // The display shows change due (positive if overpaid)
            var display_change = change > 0 ? change : 0;
            $('#return_amount_display').text(display_change.toLocaleString('en-US', {minimumFractionDigits: 2}));
            
            // Highlight negative values for emphasis
            $('.paid-amount-input').each(function(){
                if(parseFloat($(this).val()) < 0) $(this).addClass('text-danger');
                else $(this).removeClass('text-danger');
            });
        }

        // Return Change Helper
        $('#return_change_btn').click(function(){
            var grand_total = parseFloat($('#cart_total').val()) || 0;
            var total_paid = 0;
            $('.paid-amount-input').each(function(){
                total_paid += parseFloat($(this).val()) || 0;
            });
            
            var change = total_paid - grand_total;
            if(change > 0){
                // Find primary cash account (type 1)
                var cash_input = $('.paid-amount-input[data-type="1"]').first();
                if(cash_input.length > 0){
                    var current_cash = parseFloat(cash_input.val()) || 0;
                    cash_input.val((current_cash - change).toFixed(2)).trigger('change');
                    alert_toast("Rs. " + change.toFixed(2) + " returned as change.", "info");
                } else {
                    alert_toast("No Cash account found to record return.", "warning");
                }
            } else {
                alert_toast("No overpayment to return.", "warning");
            }
        });


        // Final Submit
        $('#pos-form').submit(function(e){
            e.preventDefault();
            if($('#pos-cart-table tbody tr').length <= 0){
                alert_toast("Cart is empty. Please add items.", "warning");
                return false;
            }
            var paid = parseFloat($('#paid_amount').val()) || 0;
            var total = parseFloat($('#cart_total').val()) || 0;

            // Force Customer Selection
            if($('#customer_id').val() == ''){
                alert_toast("Please select a customer before completing the sale.", "error");
                return false;
            }

            // For Walk-in or no customer selected, make sure they pay enough
            if(paid < (total - 0.01) && $('#customer_id').val() == ''){
                alert_toast("Total paid amount cannot be less than Grand Total for Walk-in Customers.", "error");
                return false;
            }

            // Append cart_total again and items into formdata
            var fd = new FormData($(this)[0]);
            
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_pos",
                data: fd,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: function(err){ 
                    console.log(err); 
                    alert_toast("An error occurred during transaction processing.", 'error'); 
                    end_loader(); 
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        // alert_toast(resp.msg, 'success');
                        
                        // Show return amount overlay instead of reloading
                        var return_amt = $('#return_amount_display').text();
                        var return_html = '<div id="return-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;color:white;">';
                        return_html += '<h1 style="font-size:4rem;color:#00ff00;">CHANGE DUE</h1>';
                        return_html += '<h1 style="font-size:8rem;font-weight:bold;">Rs. '+return_amt+'</h1>';
                        return_html += '<button class="btn btn-lg btn-light mt-5" id="close-overlay-btn" onclick="$(\'#return-overlay\').remove(); $(\'#item_id\').select2(\'open\');">NEXT CUSTOMER (Press Enter / ESC)</button>';
                        return_html += '</div>';
                        $('body').append(return_html);
                        
                        $('#close-overlay-btn').focus();
                        
                        // Close overlay with enter/esc
                        $(document).on('keyup.overlay', function(e){
                            if(e.which == 13 || e.which == 27){
                                $('#return-overlay').remove();
                                $(document).off('keyup.overlay');
                                $('#item_id').select2('open');
                            }
                        });
                        
                        // Reset Form Correctly
                        $('#pos-cart-table tbody').empty();
                        $('.paid-amount-input').val('');
                        $('#paid_amount').val('');
                        
                        customerSS.input.value = '';
                        customerSS.hiddenInput.value = '';
                        calc_total();

                        
                    } else if(resp.status == 'failed' && !!resp.msg){
                        alert_toast(resp.msg, 'error'); 
                    } else {
                        alert_toast("An error occurred", 'error'); 
                    }
                    end_loader();
                }
            });
        });
    });
</script>
