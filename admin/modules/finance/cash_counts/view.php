<?php
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT d.*, concat(u.firstname,' ',u.lastname) as user FROM `cash_denominations` d inner join users u on d.user_id = u.id where d.id = '{$_GET['id']}'");
    if($qry->num_rows >0){
        foreach($qry->fetch_array() as $k => $v){
            $$k = $v;
        }
    }
}
$is_finalized = isset($reconciliation_status) && intval($reconciliation_status) === 1;

// Fetch recorded daily balances for this denomination's date
$rec_date_view = isset($date) ? $date : date('Y-m-d');
$daily_bal_view = [];
$dv_qry = $conn->query("SELECT account_id, account_name, balance FROM daily_account_balances WHERE balance_date = '{$rec_date_view}' ORDER BY account_name ASC");
if($dv_qry && $dv_qry->num_rows > 0){
    while($dvr = $dv_qry->fetch_assoc()){
        $daily_bal_view[$dvr['account_id']] = $dvr;
    }
}
?>
<div class="container-fluid">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">Cash Denomination Details - <?php echo htmlspecialchars($cd_code) ?></h3>
            <div class="card-tools">
                <?php if($is_finalized): ?>
                <span class="badge badge-success mr-2 p-2"><i class="fas fa-lock mr-1"></i> Finalized</span>
                <?php else: ?>
                <a href="./?page=denominations/manage_denomination&id=<?php echo $id ?>" class="btn btn-flat btn-sm btn-primary">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <button class="btn btn-flat btn-sm btn-danger" id="delete_denomination" type="button">
                    <i class="fa fa-trash"></i> Delete
                </button>
                <?php endif; ?>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=denominations">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
        <div class="card-body">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-details-tab" data-toggle="tab" data-target="#nav-details" type="button" role="tab" aria-controls="nav-details" aria-selected="true">Details</button>
                    <button class="nav-link" id="nav-system-tab" data-toggle="tab" data-target="#nav-system" type="button" role="tab" aria-controls="nav-system" aria-selected="false">System Information</button>
                </div>
            </nav>
            <div class="tab-content pt-3" id="nav-tabContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="nav-details" role="tabpanel" aria-labelledby="nav-details-tab">
                    <div id="print_out">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="pl-2"><b><?php echo date("d-m-Y", strtotime($date)) ?></b></div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <label class="control-label text-muted small">Code</label>
                                    <div class="pl-2"><b><?php echo htmlspecialchars($cd_code) ?></b></div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="control-label text-muted small">Expected Movement (Cash)</label>
                                    <div class="pl-2"><b><?php echo number_format($expected_amount, 2) ?></b></div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <label class="control-label text-muted small">Total Counted (Actual Cash)</label>
                                    <div class="pl-2 text-primary"><b><?php echo number_format($total_amount, 2) ?></b></div>
                                </div>
                                <div class="col-md-4 text-right">
                                    <label class="control-label text-muted small">Total Difference</label>
                                    <div class="pl-2 <?php echo $difference < 0 ? 'text-danger' : 'text-success' ?>"><b><?php echo number_format($difference, 2) ?></b></div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h6 class="text-info border-bottom pb-1">Denominations (Cash)</h6>
                                    <table class="table table-bordered table-sm table-striped">
                                        <thead>
                                            <tr class="bg-navy">
                                                <th>Denom</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $denoms = json_decode($denominations, true);
                                            foreach($denoms as $d => $qty):
                                                if($qty > 0):
                                            ?>
                                            <tr>
                                                <td><?php echo number_format($d) ?></td>
                                                <td class="text-center"><?php echo number_format($qty) ?></td>
                                                <td class="text-right"><?php echo number_format(floatval($d) * intval($qty), 2) ?></td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-right">Total</th>
                                                <th class="text-right"><?php echo number_format($total_amount, 2) ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-info border-bottom pb-1">Account Balances</h6>
                                    <table class="table table-bordered table-sm table-striped">
                                        <thead>
                                            <tr class="bg-navy">
                                                <th>Account</th>
                                                <th class="text-right"><i class="fas fa-database mr-1"></i>Recorded Balance</th>
                                                <th class="text-right">Actual Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $acc_balances = json_decode($account_balances, true);
                                            // Merge accounts: daily_bal_view keys + acc_balances keys
                                            $all_acc_ids = array_unique(array_merge(
                                                array_keys($daily_bal_view),
                                                $acc_balances ? array_keys($acc_balances) : []
                                            ));
                                            if(!empty($all_acc_ids)):
                                                foreach($all_acc_ids as $acc_id):
                                                    $acc_name_row = isset($daily_bal_view[$acc_id]) 
                                                        ? $daily_bal_view[$acc_id]['account_name']
                                                        : ($conn->query("SELECT name FROM account_list WHERE id='{$acc_id}'")->fetch_array()[0] ?? 'Unknown');
                                                    $recorded_bal = isset($daily_bal_view[$acc_id]) ? floatval($daily_bal_view[$acc_id]['balance']) : null;
                                                    $actual_bal   = isset($acc_balances[$acc_id]) ? floatval($acc_balances[$acc_id]) : null;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($acc_name_row) ?></td>
                                                <td class="text-right text-info">
                                                    <?php echo $recorded_bal !== null ? number_format($recorded_bal, 2) : '<span class="text-muted">—</span>'; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php echo $actual_bal !== null ? number_format($actual_bal, 2) : '<span class="text-muted">—</span>'; ?>
                                                </td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            else:
                                            ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No account balances recorded</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="nav-system" role="tabpanel" aria-labelledby="nav-system-tab">
                    <div class="container-fluid">
                        <dl>
                            <dt class="text-muted small">Created By</dt>
                            <dd class="pl-3"><b><?php echo htmlspecialchars($user) ?></b></dd>
                            
                            <dt class="text-muted small">Created On</dt>
                            <dd class="pl-3"><b><?php echo date("d-m-Y", strtotime($date_created)) ?></b></dd>
                            
                            <hr>
                            
                            <?php 
                                $updater = "N/A";
                                if(isset($updated_by) && $updated_by > 0){
                                    $u_qry = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM users WHERE id = '{$updated_by}'");
                                    if($u_qry->num_rows > 0) $updater = $u_qry->fetch_assoc()['name'];
                                }
                            ?>
                            <dt class="text-muted small">Last Updated By</dt>
                            <dd class="pl-3"><b><?php echo htmlspecialchars($updater) ?></b></dd>
                            
                            <dt class="text-muted small">Last Updated On</dt>
                            <dd class="pl-3"><b><?php echo (isset($date_updated) && !empty($date_updated)) ? date("d-m-Y", strtotime($date_updated)) : 'N/A' ?></b></dd>

                            <?php if($is_finalized): ?>
                            <hr>
                            <dt class="text-muted small text-success"><i class="fas fa-lock mr-1"></i> Reconciliation Status</dt>
                            <dd class="pl-3"><span class="badge badge-success">Finalized</span></dd>

                            <?php 
                                $finalizer = "N/A";
                                if(isset($finalized_by) && $finalized_by > 0){
                                    $f_qry = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM users WHERE id = '{$finalized_by}'");
                                    if($f_qry->num_rows > 0) $finalizer = $f_qry->fetch_assoc()['name'];
                                }
                            ?>
                            <dt class="text-muted small">Finalized By</dt>
                            <dd class="pl-3"><b><?php echo htmlspecialchars($finalizer) ?></b></dd>

                            <dt class="text-muted small">Finalized On</dt>
                            <dd class="pl-3"><b><?php echo (isset($finalized_at) && !empty($finalized_at)) ? date("d-m-Y H:i", strtotime($finalized_at)) : 'N/A' ?></b></dd>
                            <?php else: ?>
                            <hr>
                            <dt class="text-muted small">Reconciliation Status</dt>
                            <dd class="pl-3"><span class="badge badge-warning">Draft</span></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-flat btn-default" id="print_btn" type="button">
                <i class="fa fa-print"></i> Print
            </button>
            <a class="btn btn-flat btn-dark" href="./?page=denominations">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#delete_denomination').click(function(){
            _conf("Are you sure to delete this cash denomination record permanently?", "delete_denomination", [<?php echo $id ?>]);
        });
        
        $('#print_btn').click(function(){
            print_count();
        });
    });

    function delete_denomination($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_denomination",
            method: "POST",
            data: {id: $id},
            dataType: "json",
            error: function(err){
                console.log(err);
                alert_toast("An error occured.", 'error');
                end_loader();
            },
            success: function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.replace("./?page=denominations");
                    } else {
                        var msg = resp.msg || resp.error || "An error occured.";
                        alert_toast(msg, 'error');
                        end_loader();
                    }
            }
        });
    }
    
    function print_count(){
        var _el = $('<div>');
        var _head = $('head').clone();
        _head.find('title').text("Cash Denomination Details - Print View");
        
        var p = $('#print_out').clone();
        p.find('tr.bg-navy').removeClass("bg-navy text-light");
        
        _el.append(_head);
        _el.append('<div class="container-fluid p-4"><div class="row mb-4"><div class="col-12 text-center"><h3 class="m-0">Cash Denomination Report</h3><hr></div></div></div>');
        _el.append(p);
        
        var nw = window.open("", "", "width=900,height=700,left=250,location=no,titlebar=yes");
        nw.document.write(_el.html());
        nw.document.close();
        setTimeout(function(){
            nw.print();
            setTimeout(function(){
                nw.close();
            }, 500);
        }, 500);
    }
</script>
