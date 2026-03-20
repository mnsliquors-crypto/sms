<?php 
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../config.php';
}
$qry = $conn->query("SELECT e.*, a.name as account, concat(u.firstname,' ',u.lastname) as user from `transactions` e left join account_list a on e.account_id = a.id left join users u on e.created_by = u.id where e.id = '{$_GET['id']}' AND e.type='expense'");
if($qry->num_rows >0){
    foreach($qry->fetch_array() as $k => $v){
        if(!is_numeric($k)) $$k = $v;
    }
}
?>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">Expense Details - <?php echo "EXP-".sprintf("%'.04d", $id) ?></h3>
            <div class="card-tools">
                <a class="btn btn-flat btn-sm btn-primary" href="<?php echo base_url.'/admin?page=transactions/expenses/manage_expense&id='.(isset($id) ? $id : '') ?>" title="Edit"><i class="fa fa-edit"></i></a>
                <button class="btn btn-flat btn-sm btn-danger" type="button" id="delete_expense" title="Delete"><i class="fa fa-trash"></i></button>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=transactions/expenses" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" type="button" id="print_btn" title="Print"><i class="fa fa-print"></i></button>
            </div>
        </div>
        <div class="card-body" id="print_out">
    <nav>
        <div class="nav nav-tabs nav-sm" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-details-tab" data-toggle="tab" data-target="#nav-details" type="button" role="tab" aria-controls="nav-details" aria-selected="true">Details</button>
            <button class="nav-link" id="nav-system-tab" data-toggle="tab" data-target="#nav-system" type="button" role="tab" aria-controls="nav-system" aria-selected="false">System Information</button>
        </div>
    </nav>
    <div class="tab-content pt-3" id="nav-tabContent">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="nav-details" role="tabpanel" aria-labelledby="nav-details-tab">
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt class="text-muted small">Code</dt>
                        <dd class="pl-3 border-bottom font-weight-bold"><?php echo "EXP-".sprintf("%'.04d", $id) ?></dd>
                        <dt class="text-muted small">Date</dt>
                        <dd class="pl-3 border-bottom"><?php echo date("d-m-Y", strtotime($transaction_date)) ?></dd>
                        <dt class="text-muted small">User</dt>
                        <dd class="pl-3 border-bottom"><?php echo isset($user) ? $user : 'N/A' ?></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt class="text-muted small">Account Paid From</dt>
                        <dd class="pl-3 border-bottom font-weight-bold"><?php echo isset($account) ? $account : 'N/A' ?></dd>
                        <dt class="text-muted small">Amount</dt>
                        <dd class="pl-3 border-bottom font-weight-bold text-success"><?php echo number_format($total_amount, 2) ?></dd>
                        <dt class="text-muted small">Status</dt>
                        <dd class="pl-3 border-bottom">
                            <?php if($status == 1): ?>
                                <span class="badge badge-success font-weight-normal">Paid</span>
                            <?php else: ?>
                                <span class="badge badge-warning font-weight-normal">Pending</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <dl>
                        <dt class="text-muted small">Remarks</dt>
                        <dd class="pl-3 border-bottom small"><?php echo isset($remarks) ? nl2br(htmlspecialchars($remarks)) : '' ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- System Information Tab -->
        <div class="tab-pane fade" id="nav-system" role="tabpanel" aria-labelledby="nav-system-tab">
            <dl>
                <?php 
                    $creator = "N/A";
                    if(isset($created_by) && $created_by > 0){
                        $c_qry = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM users WHERE id = '{$created_by}'");
                        if($c_qry->num_rows > 0) $creator = $c_qry->fetch_assoc()['name'];
                    }
                    $updater = "N/A";
                    if(isset($updated_by) && $updated_by > 0){
                        $u_qry = $conn->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM users WHERE id = '{$updated_by}'");
                        if($u_qry->num_rows > 0) $updater = $u_qry->fetch_assoc()['name'];
                    }
                ?>
                <dt class="text-muted small">Created By</dt>
                <dd class="pl-3 border-bottom"><b><?php echo htmlspecialchars($creator) ?></b></dd>
                
                <dt class="text-muted small">Created On</dt>
                <dd class="pl-3 border-bottom"><b><?php echo isset($date_created) ? date("d-m-Y", strtotime($date_created)) : 'N/A' ?></b></dd>
                
                <dt class="text-muted small mt-2">Last Updated By</dt>
                <dd class="pl-3 border-bottom"><b><?php echo htmlspecialchars($updater) ?></b></dd>
                
                <dt class="text-muted small">Last Updated On</dt>
                <dd class="pl-3 border-bottom"><b><?php echo (isset($date_updated) && !empty($date_updated)) ? date("d-m-Y", strtotime($date_updated)) : 'N/A' ?></b></dd>
            </dl>
        </div>
    </div>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#print_btn').click(function(){
            start_loader()
            var _h = $('head').clone()
            var _p = $('#print_out').clone()
            var _el = $('<div>')
            _p.find('#nav-tab').remove()
            _p.find('.tab-pane').addClass('show active')
            _el.append(_h)
            _el.append('<h3 class="text-center">Expense Record</h3>')
            _el.append('<hr/>')
            _el.append(_p)
            var nw = window.open("","_blank","width=1000,height=800,left=150,top=50")
            nw.document.write(_el.html())
            nw.document.close()
            setTimeout(() => {
                nw.print()
                setTimeout(() => {
                    nw.close()
                    end_loader()
                }, 200);
            }, 500);
        })
        $('#delete_expense').click(function(){
            _conf("Are you sure to delete this expense record permanently?", "delete_expense", [<?php echo $id ?>]);
        });
    })

    function delete_expense($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_expense",
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
                    location.replace("./?page=transactions/expenses");
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        });
    }
</script>
