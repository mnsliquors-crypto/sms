<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../../../config.php';
}

if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * FROM `transactions` where id = '{$_GET['id']}' AND type = 'transfer'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
        
        // Fetch from/to accounts
        $from_acc = $conn->query("SELECT a.name FROM transaction_list tl JOIN account_list a ON tl.account_id = a.id WHERE tl.ref_table = 'transactions' AND tl.ref_id = '{$id}' AND tl.type = 2")->fetch_assoc()['name'] ?? 'N/A';
        $to_acc = $conn->query("SELECT a.name FROM transaction_list tl JOIN account_list a ON tl.account_id = a.id WHERE tl.ref_table = 'transactions' AND tl.ref_id = '{$id}' AND tl.type = 1")->fetch_assoc()['name'] ?? 'N/A';
    } else {
        echo '<script>alert("Fund Transfer ID is unknown."); location.replace("./?page=master/accounts/transfers")</script>';
    }
} else {
    echo '<script>alert("Fund Transfer ID is required."); location.replace("./?page=master/accounts/transfers")</script>';
}
?>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">
                Transfer Details - <?php echo $reference_code ?>
                <span class="badge badge-success ml-2" style="font-size: 0.8rem; font-weight: 500; vertical-align: middle;">Completed</span>
            </h3>
            <div class="card-tools">
                <a href="./?page=master/accounts/manage_transfer&id=<?php echo $id ?>" class="btn btn-flat btn-sm btn-primary edit_data" title="Edit"><i class="fa fa-edit"></i></a>
                <button class="btn btn-flat btn-sm btn-danger" type="button" id="delete_transfer" title="Delete"><i class="fa fa-trash"></i></button>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=master/accounts/transfers" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" type="button" id="print_btn" title="Print"><i class="fa fa-print"></i></button>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="transferViewTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="true">Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">System Information</a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="transferViewTabContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                    <div id="print_out">
                        <div class="row">
                            <div class="col-md-6 border-right">
                                <dl>
                                    <dt class="text-muted small">Transfer Code</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo $reference_code ?></dd>
                                    <dt class="text-muted small">Transaction Date</dt>
                                    <dd class="pl-3 border-bottom"><?php echo date("d-m-Y", strtotime($transaction_date)) ?></dd>
                                    <dt class="text-muted small">Total Amount</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold text-success"><?php echo number_format($total_amount, 2) ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl>
                                    <dt class="text-muted small">From Account</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo htmlspecialchars($from_acc) ?></dd>
                                    <dt class="text-muted small">To Account</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo htmlspecialchars($to_acc) ?></dd>
                                    <dt class="text-muted small">Remarks</dt>
                                    <dd class="pl-3 border-bottom"><?php echo !empty($remarks) ? htmlspecialchars($remarks) : 'N/A' ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- System Information Tab -->
                <div class="tab-pane fade" id="system" role="tabpanel">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="text-muted w-25">Created At:</td>
                            <td><?php echo isset($date_created) ? date("F d, Y h:i A", strtotime($date_created)) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Updated:</td>
                            <td><?php echo !empty($date_updated) ? date("F d, Y h:i A", strtotime($date_updated)) : 'N/A' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('.edit_data').click(function(e){
            e.preventDefault();
            uni_modal("<i class='fa fa-edit'></i> Update Transfer","modules/master/accounts/manage_transfer.php?id=<?php echo $id ?>","mid-large")
        })

        $('#delete_transfer').click(function(){
            _conf("Are you sure to delete this transfer record permanently?", "delete_transfer_confirmed", [<?php echo $id ?>]);
        });

        $('#print_btn').click(function(){
            var _el = $('<div>');
            var _head = $('head').clone();
            _head.find('title').text("Transfer Details - Print View");
            var p = $('#print_out').clone();
            _el.append(_head);
            _el.append('<div class="container-fluid p-4"><h3 class="text-center">Fund Transfer Details</h3><hr/></div>');
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
        });
    });

    function delete_transfer_confirmed($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_transfer",
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
                    location.replace("./?page=master/accounts/transfers");
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        });
    }
</script>
