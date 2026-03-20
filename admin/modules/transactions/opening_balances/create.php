<?php
if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<div class="container-fluid pb-5">
    <?php
    $edit_account = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
    ?>
    <form action="" id="opening-balances-form">
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-0 text-primary font-weight-bold"><i class="fas fa-magic mr-2"></i>Setup Opening Balances</h4>
                                <p class="text-muted small mb-0">Record initial states for your entire system.</p>
                            </div>
                            <div class="col-md-3 ml-auto">
                            <div class="form-group mb-0">
                                <label class="small text-muted mb-1 d-block">Base Ref Code</label>
                                <input type="text" id="op_code_preview" class="form-control form-control-sm border-0 bg-light text-primary font-weight-bold" value="OPB-..." readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold"><i class="far fa-calendar-alt mr-1"></i> Transaction Date</label>
                                    <input type="date" name="transaction_date" value="<?php echo date('Y-m-d') ?>" class="form-control form-control-sm rounded-pill border-primary" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-primary card-outline card-tabs shadow">
            <div class="card-header p-0 pt-1 border-bottom-0 bg-light">
                <ul class="nav nav-tabs" id="openingBalancesTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="accounts-tab" data-toggle="pill" href="#accounts" role="tab" aria-controls="accounts" aria-selected="true">
                            <i class="fas fa-wallet mr-1"></i> Cash Accounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="stock-tab" data-toggle="pill" href="#stock" role="tab" aria-controls="stock" aria-selected="false">
                            <i class="fas fa-boxes mr-1"></i> Inventory Stock
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="vendors-tab" data-toggle="pill" href="#vendors" role="tab" aria-controls="vendors" aria-selected="false">
                            <i class="fas fa-truck mr-1"></i> Vendor Payables
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="customers-tab" data-toggle="pill" href="#customers" role="tab" aria-controls="customers" aria-selected="false">
                            <i class="fas fa-users mr-1"></i> Customer Receivables
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4 bg-white">
                <div class="tab-content" id="openingBalancesTabContent">
                    
                    <!-- Accounts Tab -->
                    <div class="tab-pane fade show active" id="accounts" role="tabpanel" aria-labelledby="accounts-tab">
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <i class="fas fa-info-circle mr-2"></i> Enter current running balance for each cash/bank account.
                        </div>
                        <div class="row">
                            <?php 
                            $accounts = $conn->query("SELECT * FROM `account_list` WHERE status = 1 ORDER BY name ASC");
                            while($row = $accounts->fetch_assoc()):
                            ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100 border-0 shadow-sm bg-gradient-light hover-shadow transition">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-primary-soft p-2 rounded mr-3">
                                                <i class="fas fa-university text-primary"></i>
                                            </div>
                                            <label class="mb-0 font-weight-bold text-dark text-truncate" title="<?php echo $row['name'] ?>"><?php echo $row['name'] ?></label>
                                        </div>
                                        <div class="input-group input-group-sm mt-2">
                                            <div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fas fa-dollar-sign text-muted"></i></span></div>
                                            <input type="number" step="any" name="account_balances[<?php echo $row['id'] ?>]" class="form-control text-right border-left-0 font-weight-bold" placeholder="0.00" value="<?php echo ($edit_account && $edit_account == $row['id']) ? number_format($row['balance'],2,'.','') : '0' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Stock Tab -->
                    <div class="tab-pane fade" id="stock" role="tabpanel" aria-labelledby="stock-tab">
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <i class="fas fa-info-circle mr-2"></i> Record the initial quantity and unit cost for all active items.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm border" id="stock-table">
                                <thead class="bg-gray-light font-weight-bold">
                                    <tr>
                                        <th class="py-2 px-3 border-0">Item Name</th>
                                        <th class="text-center py-2 border-0" style="width:160px">Opening Qty</th>
                                        <th class="text-center py-2 border-0" style="width:160px">Unit Cost</th>
                                        <th class="text-right py-2 px-3 border-0" style="width:180px">Total Valuation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $items = $conn->query("SELECT * FROM `item_list` WHERE status = 1 ORDER BY name ASC");
                                    while($row = $items->fetch_assoc()):
                                    ?>
                                    <tr class="stock-row">
                                        <td class="align-middle px-3 font-weight-bold text-primary"><?php echo $row['name'] ?></td>
                                        <td class="p-2">
                                            <input type="number" step="any" name="item_stocks[<?php echo $row['id'] ?>][qty]" class="form-control form-control-sm text-center qty-input border-dashed" data-id="<?php echo $row['id'] ?>" value="0">
                                        </td>
                                        <td class="p-2">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend"><span class="input-group-text bg-transparent border-0 text-muted">$</span></div>
                                                <input type="number" step="any" name="item_stocks[<?php echo $row['id'] ?>][cost]" class="form-control text-right cost-input border-dashed" data-id="<?php echo $row['id'] ?>" value="<?php echo $row['cost'] ?>">
                                            </div>
                                        </td>
                                        <td class="text-right align-middle px-3 font-weight-bold text-success subtotal" id="subtotal-<?php echo $row['id'] ?>">0.00</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="font-weight-bold">
                                        <th colspan="3" class="text-right py-3 px-3">Total Inventory Opening Value</th>
                                        <th class="text-right py-3 px-3 text-lg text-success" id="grand-total-stock">$ 0.00</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Vendors Tab -->
                    <div class="tab-pane fade" id="vendors" role="tabpanel" aria-labelledby="vendors-tab">
                        <div class="alert alert-warning border-0 shadow-sm mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i> List outstanding amounts you currently owe to each vendor.
                        </div>
                        <div class="row">
                            <?php 
                            $vendors = $conn->query("SELECT *, display_name as name FROM `entity_list` WHERE entity_type = 'Supplier' AND status = 1 ORDER BY display_name ASC");
                            if($vendors->num_rows > 0):
                                while($row = $vendors->fetch_assoc()):
                            ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100 border-0 shadow-sm border-left-danger border-left-strong hover-shadow transition">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-red-soft p-2 rounded mr-3 text-danger"><i class="fas fa-truck-loading"></i></div>
                                            <label class="mb-0 font-weight-bold text-dark text-truncate" title="<?php echo $row['name'] ?>"><?php echo $row['name'] ?></label>
                                        </div>
                                        <div class="input-group input-group-sm mt-2">
                                            <input type="number" step="any" name="vendor_balances[<?php echo $row['id'] ?>]" class="form-control text-right border-danger border-dashed font-weight-bold" placeholder="0.00" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endwhile; 
                            else:
                                echo '<div class="col-12 text-center text-muted p-5"><i class="fas fa-folder-open fa-3x mb-3"></i><br>No active vendors found.</div>';
                            endif;
                            ?>
                        </div>
                    </div>

                    <!-- Customers Tab -->
                    <div class="tab-pane fade" id="customers" role="tabpanel" aria-labelledby="customers-tab">
                        <div class="alert alert-success border-0 shadow-sm mb-4">
                            <i class="fas fa-info-circle mr-2"></i> List outstanding amounts currently receivable from each customer.
                        </div>
                        <div class="row">
                            <?php 
                            $customers = $conn->query("SELECT *, display_name as name FROM `entity_list` WHERE entity_type = 'Customer' AND status = 1 ORDER BY display_name ASC");
                            if($customers->num_rows > 0):
                                while($row = $customers->fetch_assoc()):
                            ?>
                            <div class="col-md-3 mb-4">
                                <div class="card h-100 border-0 shadow-sm border-left-success border-left-strong hover-shadow transition">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bg-green-soft p-2 rounded mr-3 text-success"><i class="fas fa-user-circle"></i></div>
                                            <label class="mb-0 font-weight-bold text-dark text-truncate" title="<?php echo $row['name'] ?>"><?php echo $row['name'] ?></label>
                                        </div>
                                        <div class="input-group input-group-sm mt-2">
                                            <input type="number" step="any" name="customer_balances[<?php echo $row['id'] ?>]" class="form-control text-right border-success border-dashed font-weight-bold" placeholder="0.00" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                endwhile; 
                            else:
                                echo '<div class="col-12 text-center text-muted p-5"><i class="fas fa-folder-open fa-3x mb-3"></i><br>No active customers found.</div>';
                            endif;
                            ?>
                        </div>
                    </div>

                </div>
            </div>
            <div class="card-footer bg-light p-4 shadow-sm">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="text-danger mb-0 font-weight-bold">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Critical Warning
                        </p>
                        <span class="small text-muted">Saving these balances will create irreversible transaction records in your general ledger and inventory history. Please double-check every entry across all tabs before proceeding.</span>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="<?php echo base_url ?>admin/?page=transactions/opening_balances" class="btn btn-secondary rounded-pill px-4 mr-2">
                            <i class="fas fa-arrow-left mr-1"></i> Back to History
                        </a>
                        <button class="btn btn-primary btn-lg rounded-pill px-5 shadow" type="submit">
                            Confirm & Save Everything <i class="fas fa-check-circle ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function calculateSubtotal(id){
        var qty = parseFloat($('input[name="item_stocks['+id+'][qty]"]').val()) || 0;
        var cost = parseFloat($('input[name="item_stocks['+id+'][cost]"]').val()) || 0;
        var sub = qty * cost;
        $('#subtotal-'+id).text(sub.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        calculateGrandTotal();
    }

    function calculateGrandTotal(){
        var grand = 0;
        $('.qty-input').each(function(){
            var id = $(this).data('id');
            var qty = parseFloat($(this).val()) || 0;
            var cost = parseFloat($('input[name="item_stocks['+id+'][cost]"]').val()) || 0;
            grand += (qty * cost);
        });
        $('#grand-total-stock').text('$ ' + grand.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    $(function(){
        function get_next_code(){
            var date = $('[name="transaction_date"]').val();
            if(date == '') return false;
            $.ajax({
                url:_base_url_+"classes/Master.php?f=get_next_ref_code",
                method:'POST',
                data:{type:'opening_balance', date:date},
                dataType:'json',
                error:err=>{
                    console.log(err)
                },
                success:resp=>{
                    if(resp.status == 'success'){
                        $('#op_code_preview').val(resp.code);
                    }
                }
            })
        }

        get_next_code();

        $('[name="transaction_date"]').change(function(){
            get_next_code();
        })

        <?php if($edit_account): ?>
        // scroll/highlight selected account
        var target = $('[name="account_balances[<?php echo $edit_account ?>]"]');
        if(target.length){
            $('html, body').animate({scrollTop: target.offset().top - 120}, 500);
            target.closest('.col-md-3').addClass('border border-primary');
        }
        <?php endif; ?>

        $('.qty-input, .cost-input').on('input change', function(){
            calculateSubtotal($(this).data('id'));
        });

        $('#opening-balances-form').submit(function(e){
            e.preventDefault();
            var _this = $(this)
            $('.err-msg').remove();
            
            var hasData = false;
            _this.find('input[type="number"]').each(function(){
                if(parseFloat($(this).val()) != 0) hasData = true;
            });
            
            if(!hasData){
                alert_toast("All balances are currently zero. Please enter at least one value.",'warning');
                return false;
            }

            _conf("Final Confirmation: Are you absolutely sure? This will create non-reversible balance records in your database.", "confirm_save_opening", []);
        })
    })

    function confirm_save_opening(){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=save_opening_balance",
            data: new FormData($('#opening-balances-form')[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error:err=>{
                console.log(err)
                alert_toast("A critical server error occured. Please check logs.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp =='object' && resp.status == 'success'){
                    alert_toast(resp.msg,'success');
                    setTimeout(() => {
                        location.replace(_base_url_+"admin/?page=transactions/opening_balances");
                    }, 1000);
                }else if(resp.status == 'failed' && !!resp.msg){
                    alert_toast(resp.msg,'error')
                    end_loader()
                }else{
                    alert_toast("An unexpected error failed the operation.",'error');
                    end_loader();
                }
            }
        })
    }
</script>

<style>
    .bg-primary-soft { background-color: rgba(0, 123, 255, 0.1); }
    .bg-red-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-green-soft { background-color: rgba(40, 167, 69, 0.1); }
    .bg-gray-light { background-color: #f8f9fa; }
    
    .border-dashed { border-style: dashed !important; }
    .border-left-strong { border-left-width: 4px !important; }
    
    .hover-shadow:hover { 
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
        transform: translateY(-2px);
    }
    .transition { transition: all 0.3s ease; }
    
    .qty-input, .cost-input {
        background-color: #fff !important;
    }
    .qty-input:focus, .cost-input:focus {
        border-style: solid !important;
        background-color: #fdfdfe !important;
    }

    .nav-tabs .nav-link.active {
        background-color: #fff !important;
        border-bottom-color: transparent !important;
        font-weight: bold;
        color: #007bff !important;
    }
    .nav-tabs .nav-link {
        color: #6c757d;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        padding: 12px 20px;
        margin-right: 5px;
    }
    .card-tabs {
        border-radius: 12px;
        overflow: hidden;
    }
    .info-box { border-radius: 10px; }
    .table th, .table td { vertical-align: middle !important; }
</style>
