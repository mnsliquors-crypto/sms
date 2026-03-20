<?php 
// For now, let's assume a set of standard denominations
$denominations = array(1000, 500, 100, 50, 20, 10, 5);

// Fetch existing data if ID is provided
$is_finalized = false;
$finalized_at = null;
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `cash_denominations` WHERE id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            $$k = $v;
        }
        $is_finalized = isset($reconciliation_status) && intval($reconciliation_status) === 1;
    }
}

// Calculate Expected Balance (Sum of Cash Income - Cash Expense for today)
$reconcile_date = isset($date) ? $date : date("Y-m-d");
$user_id = $_settings->userdata('id');

// Get initial balances for all active accounts
$initial_balances = $conn->query("SELECT id, name, balance FROM `account_list` WHERE status = 1 ORDER BY name ASC");
$account_balances_list = array();

// Try to get recorded daily balances for the reconcile date
$daily_recorded = [];
$dab_qry = $conn->query("SELECT account_id, balance FROM daily_account_balances WHERE balance_date = '{$reconcile_date}'");
if($dab_qry && $dab_qry->num_rows > 0){
    while($dab_row = $dab_qry->fetch_assoc()){
        $daily_recorded[$dab_row['account_id']] = floatval($dab_row['balance']);
    }
}
$has_daily_record = !empty($daily_recorded);

while($row = $initial_balances->fetch_assoc()){
    if(isset($account_balances)){
        $saved_balances = json_decode($account_balances, true);
        if(isset($saved_balances[$row['id']])){
            $row['balance'] = $saved_balances[$row['id']];
        }
    }
    $account_balances_list[] = $row;
}
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h4 class="card-title"><?php echo isset($id) ? 'Edit Cash Count (Reconciliation)' : 'New Cash Count (Reconciliation)' ?></h4>
        <?php if($is_finalized): ?>
        <div class="card-tools">
            <span class="badge badge-success badge-lg"><i class="fas fa-lock mr-1"></i> Finalized</span>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">

        <?php if($is_finalized): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="fas fa-lock mr-2"></i>
            <strong>This date is already reconciled.</strong> Data entry is not allowed. 
            <?php if($finalized_at): ?>
            <small class="ml-2 text-muted">Finalized on: <?php echo date('d-m-Y H:i', strtotime($finalized_at)) ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Locked Banner (shown by JS when date is finalized) -->
        <div id="locked-banner" class="alert alert-warning" style="display:none;">
            <i class="fas fa-lock mr-2"></i>
            <strong>This date is already reconciled.</strong> Data entry is not allowed.
            <span id="locked-banner-detail" class="ml-2 text-muted small"></span>
        </div>

        <!-- Display Date and Balances -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="control-label text-muted small">Date</label>
                            <input type="date" name="date" id="reconciliation_date" class="form-control form-control-sm" value="<?php echo $reconcile_date ?>" <?php echo $is_finalized ? 'disabled' : '' ?>>
                        </div>
                    </div>
                    <?php if(isset($cd_code)): ?>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="control-label text-muted small">Code</label>
                            <div class="h5"><strong><?php echo $cd_code ?></strong></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php foreach($account_balances_list as $acc): 
                        $text_class = 'text-primary';
                        if(stripos($acc['name'], 'Cash') !== false) $text_class = 'text-success';
                        if(stripos($acc['name'], 'QR') !== false) $text_class = 'text-info';
                        // Use daily recorded balance for header display if available
                        $header_bal = isset($daily_recorded[$acc['id']]) ? $daily_recorded[$acc['id']] : $acc['balance'];
                    ?>
                    <div class="col-md-3 header-account" data-id="<?php echo $acc['id'] ?>">
                        <div class="form-group mb-0">
                            <label class="control-label text-muted small"><?php echo $acc['name'] ?> Balance</label>
                            <div class="pl-2 h5 <?php echo $text_class ?>"><strong><?php echo number_format($header_bal, 2) ?></strong></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <hr>

        <form action="" id="denomination-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <input type="hidden" name="expected_amount" value="<?php echo isset($expected_amount) ? $expected_amount : 0 ?>">
            <input type="hidden" id="form_denomination_id" value="<?php echo isset($id) ? $id : '' ?>">
            
            <div class="row">
                <div class="col-md-6 border-right">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr class="bg-navy">
                                <th>Denomination</th>
                                <th>Count</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $saved_counts = isset($denominations) && !is_array($denominations) ? json_decode($denominations, true) : [];
                            foreach($denominations as $d): 
                                $count_val = isset($saved_counts[$d]) ? $saved_counts[$d] : 0;
                                $total_val = floatval($d) * intval($count_val);
                            ?>
                            <tr>
                                <td class="align-middle"><strong><?php echo number_format($d) ?></strong></td>
                                <td>
                                    <input type="number" step="1" min="0" name="counts[<?php echo $d ?>]" class="form-control form-control-sm text-right d-count clr-on-focus" data-val="<?php echo $d ?>" value="<?php echo $count_val ?>" <?php echo $is_finalized ? 'readonly' : '' ?>>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-right d-total" readonly value="<?php echo number_format($total_val, 2, '.', '') ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-navy">
                                <th colspan="2" class="text-right">Total</th>
                                <th class="text-right" id="cash_footer_total"><?php echo number_format(isset($total_amount) ? $total_amount : 0, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="total_amount" id="total_amount" value="<?php echo isset($total_amount) ? number_format($total_amount, 2, '.', '') : '0.00' ?>">

                    <hr class="my-3">
                    
                    <h5>Actual Balances of All Accounts</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr class="bg-navy">
                                <th>Account</th>
                                <th class="text-right">
                                    <?php if($has_daily_record): ?>
                                    <span title="Recorded closing balance for <?php echo date('d-m-Y', strtotime($reconcile_date)) ?>">Recorded Balance <i class="fas fa-database text-info ml-1"></i></span>
                                    <?php else: ?>
                                    Exp. Balance
                                    <?php endif; ?>
                                </th>
                                <th class="text-right">Actual Balance</th>
                                <th class="text-right">Diff</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $accounts = $conn->query("SELECT * FROM account_list WHERE status = 1 ORDER BY name ASC");
                            $saved_balances = isset($account_balances) ? json_decode($account_balances, true) : [];
                            while($row = $accounts->fetch_assoc()):
                                $actual_val = isset($saved_balances[$row['id']]) ? floatval($saved_balances[$row['id']]) : 0;
                                // Use recorded daily balance as expected if available, otherwise use live account balance
                                $exp_val = isset($daily_recorded[$row['id']]) ? $daily_recorded[$row['id']] : floatval($row['balance']);
                                $diff_val = $actual_val - $exp_val;
                                $diff_class = $diff_val < 0 ? 'text-danger' : ($diff_val > 0 ? 'text-success' : '');
                            ?>
                            <tr class="account-row" data-id="<?php echo $row['id'] ?>">
                                <td><?php echo $row['name'] ?></td>
                                <td class="text-right exp-balance" data-val="<?php echo $exp_val ?>"><?php echo number_format($exp_val, 2) ?></td>
                                <td>
                                    <input type="number" step="any" name="account_balances[<?php echo $row['id'] ?>]" class="form-control form-control-sm text-right actual-balance clr-on-focus" value="<?php echo number_format($actual_val, 2, '.', '') ?>" <?php echo $is_finalized ? 'readonly' : '' ?>>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm text-right balance-diff <?php echo $diff_class ?>" readonly value="<?php echo number_format($diff_val, 2, '.', '') ?>">
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th colspan="3" class="text-right">Total Difference</th>
                                <th class="text-right" id="total_diff_footer">0.00</th>
                            </tr>
                        </tfoot>
                    </table>

                    <input type="hidden" name="difference" id="overall_difference" value="0">
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer text-center" id="form-footer">
        <?php if(!$is_finalized): ?>
        <button class="btn btn-flat btn-primary" type="submit" form="denomination-form" id="save-btn">
            <i class="fas fa-save mr-1"></i> Save Reconciliation
        </button>
        <?php if(isset($id)): ?>
        <button class="btn btn-flat btn-success ml-2" type="button" id="finalize-btn" data-id="<?php echo $id ?>">
            <i class="fas fa-lock mr-1"></i> Finalize Reconciliation
        </button>
        <?php endif; ?>
        <?php else: ?>
        <span class="badge badge-success p-2"><i class="fas fa-check-circle mr-1"></i> This record is finalized and locked</span>
        <?php endif; ?>
        <a class="btn btn-flat btn-dark ml-2" href="<?php echo base_url.'/admin?page=denominations' ?>">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>
<script>
    $(function(){
        // ---- Denomination Count Calculation ----
        $('.d-count').on('input', function(){
            var d = $(this).attr('data-val')
            var c = $(this).val() || 0
            var t = parseFloat(d) * parseInt(c)
            $(this).closest('tr').find('.d-total').val(t.toFixed(2))
            calc_grand()
        })

        // ---- Account Balance Difference Calculation ----
        $('.actual-balance').on('input', function(){
            var actual = parseFloat($(this).val()) || 0
            var exp = parseFloat($(this).closest('tr').find('.exp-balance').attr('data-val')) || 0
            var diff = actual - exp
            var diff_input = $(this).closest('tr').find('.balance-diff')
            diff_input.val(diff.toFixed(2))
            
            if(diff < 0) {
                diff_input.removeClass('text-success').addClass('text-danger')
            } else if(diff > 0) {
                diff_input.removeClass('text-danger').addClass('text-success')
            } else {
                diff_input.removeClass('text-danger text-success')
            }
            calc_overall_diff()
        })

        // ---- Clear on Focus ----
        $('.clr-on-focus').on('focus', function(){
            if($(this).val() == 0) $(this).val('')
        }).on('blur', function(){
            if($(this).val() == '') $(this).val(0)
        })

        // ---- Overall Difference Calculation ----
        function calc_overall_diff(){
            var total_diff = 0;
            $('.balance-diff').each(function(){
                total_diff += parseFloat($(this).val()) || 0
            })
            $('#overall_difference').val(total_diff.toFixed(2))
            var $footer = $('#total_diff_footer')
            $footer.text(total_diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}))
            if(total_diff < 0) $footer.removeClass('text-success').addClass('text-danger')
            else if(total_diff > 0) $footer.removeClass('text-danger').addClass('text-success')
            else $footer.removeClass('text-danger text-success')
        }

        function calc_grand(){
            var grand = 0;
            $('.d-total').each(function(){
                grand += parseFloat($(this).val()) || 0
            })
            $('#total_amount').val(grand.toFixed(2))
            $('#cash_footer_total').text(grand.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}))
        }

        // ---- Lock / Unlock Form ----
        function lockForm(msg, detail){
            $('.d-count, .actual-balance').prop('readonly', true)
            $('#save-btn').hide()
            $('#finalize-btn').hide()
            $('#locked-banner').show()
            if(detail) $('#locked-banner-detail').text(detail)
        }

        function unlockForm(){
            $('.d-count, .actual-balance').prop('readonly', false)
            $('#save-btn').show()
            // Only show finalize if we have a saved ID
            var savedId = $('#form_denomination_id').val()
            if(savedId) $('#finalize-btn').show()
            $('#locked-banner').hide()
        }

        // ---- Date Change: Check Reconciliation Status ----
        function resetToToday(input){
            var today = new Date().toISOString().split('T')[0];
            if(input.val() !== today){
                input.val(today).trigger('change');
            }
        }

        $('#reconciliation_date').change(function(){
            var _this = $(this)
            var date = _this.val()
            if(!date) return;
            if(_this.attr('data-fetching') == 'true') return;
            _this.attr('data-fetching', 'true');

            start_loader()
            
            // First check reconciliation status
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=check_reconciliation_status",
                method: 'POST',
                data: {date: date},
                dataType: 'json',
                success: function(statusResp){
                    if(statusResp && statusResp.is_finalized){
                        lockForm(statusResp.msg, statusResp.finalized_at ? 'Finalized on: ' + statusResp.finalized_at : '')
                        end_loader()
                        _this.removeAttr('data-fetching')
                        return;
                    }
                    // Not finalized - first try recorded daily balances, then fallback to transaction computation
                    unlockForm()
                    $.ajax({
                        url: _base_url_ + "classes/Master.php?f=get_balances_by_date",
                        method: 'POST',
                        data: {date: date},
                        dataType: 'json',
                        error: function(err){
                            console.error("AJAX Error:", err)
                            end_loader()
                            _this.removeAttr('data-fetching');
                            alert("No data found or account balances are not recorded on this date. Please try another date.");
                            resetToToday(_this)
                        },
                        success: function(resp){
                            _this.removeAttr('data-fetching');
                            if(resp && !resp.error && resp.accounts){
                                // Update Exp. Balance column header label
                                var fromRecorded = resp.from_daily_record === true;
                                var thLabel = fromRecorded
                                    ? 'Recorded Balance <i class="fas fa-database text-info ml-1"></i>'
                                    : 'Exp. Balance';
                                $('.exp-balance').closest('table').find('thead tr th:nth-child(2)').html(thLabel);

                                if(resp.net_movement !== undefined){
                                    $('input[name="expected_amount"]').val(resp.net_movement);
                                }
                                Object.keys(resp.accounts).forEach(id => {
                                    var data = resp.accounts[id]
                                    $('.header-account').each(function(){
                                        if($(this).attr('data-id') == id){
                                            $(this).find('strong').text(parseFloat(data.balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}))
                                        }
                                    })
                                    $('.account-row').each(function(){
                                        if($(this).attr('data-id') == id){
                                            $(this).find('.exp-balance').attr('data-val', data.balance).text(parseFloat(data.balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}))
                                            $(this).find('.actual-balance').trigger('input')
                                        }
                                    })
                                })
                            } else {
                                var msg = (resp && resp.msg) ? resp.msg : "No data found or account balances are not recorded on this date. Please try another date.";
                                alert(msg);
                                resetToToday(_this)
                            }
                            end_loader()
                        }
                    })
                },
                error: function(){
                    _this.removeAttr('data-fetching')
                    end_loader()
                }
            })
        })

        // ---- Save Form ----
        $('#denomination-form').submit(function(e){
            e.preventDefault();
            start_loader();
            var $modal = $('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Saving cash count record... Please wait.</div>');
            $('.card-body').prepend($modal);
            
            $.ajax({
                url: _base_url_+"classes/Master.php?f=save_denomination",
                data: new FormData($(this)[0]),
                cache: false, contentType: false, processData: false, method: 'POST', type: 'POST', dataType: 'json',
                timeout: 30000,
                success: function(resp){
                    end_loader();
                    if(resp.status == 'success'){
                        alert_toast("Cash count saved successfully!", 'success');
                        // If we got an ID back, update the finalize button
                        if(resp.id){
                            $('#form_denomination_id').val(resp.id)
                            if($('#finalize-btn').length == 0){
                                var $btn = $('<button class="btn btn-flat btn-success ml-2" type="button" id="finalize-btn"><i class="fas fa-lock mr-1"></i> Finalize Reconciliation</button>')
                                $btn.attr('data-id', resp.id)
                                $('#save-btn').after($btn)
                                bindFinalizeBtn()
                            }
                        }
                        setTimeout(function(){
                            location.replace(_base_url_+"admin/?page=denominations");
                        }, 500);
                    }else{
                        alert_toast("Error: " + (resp.msg || "An error occurred"), 'error');
                        $modal.remove();
                    }
                },
                error: function(xhr, status, error){
                    end_loader();
                    $modal.remove();
                    alert_toast("Network error or server timeout. Please try again.", 'error');
                }
            })
        })

        // ---- Finalize Reconciliation ----
        function bindFinalizeBtn(){
            $(document).on('click', '#finalize-btn', function(){
                var id = $(this).attr('data-id') || $('#form_denomination_id').val()
                if(!id){
                    alert_toast("Please save the record first before finalizing.", 'warning')
                    return;
                }
                if(!confirm("Are you sure you want to FINALIZE this reconciliation?\n\nThis will permanently LOCK this record. No further edits will be allowed.")){
                    return;
                }
                start_loader()
                $.ajax({
                    url: _base_url_+"classes/Master.php?f=finalize_reconciliation",
                    method: 'POST',
                    data: {id: id},
                    dataType: 'json',
                    success: function(resp){
                        end_loader()
                        if(resp.status == 'success'){
                            alert_toast(resp.msg, 'success')
                            setTimeout(function(){ location.reload() }, 1000)
                        } else {
                            alert_toast(resp.msg || "An error occurred", 'error')
                        }
                    },
                    error: function(){
                        end_loader()
                        alert_toast("Network error. Please try again.", 'error')
                    }
                })
            })
        }
        bindFinalizeBtn()

        // ---- Initial Calculation ----
        $('.actual-balance').trigger('input')
        calc_grand()
    })
</script>
