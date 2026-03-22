<?php
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT p.id, p.reference_code as payment_code, p.total_amount as amount, p.transaction_date as date_created, p.remarks, p.account_id, a.name as account_name, p.created_by, p.updated_by, p.date_updated, p.date_created as system_date FROM `transactions` p left join account_list a on p.account_id = a.id where p.id = '{$_GET['id']}' AND p.type='payment'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }
    }
    
    // Fetch Party and Type from parent bill
    $parent_qry = $conn->query("SELECT b.type, b.entity_id FROM transactions b JOIN transactions p ON p.parent_id = b.id WHERE p.id = '{$_GET['id']}'");
    if($parent_qry->num_rows > 0){
        $p_data = $parent_qry->fetch_assoc();
        $is_sale = ($p_data['type'] == 'sale');
        $type = $is_sale ? 1 : 2;
        $party_id = $p_data['entity_id'];
        
        if($is_sale){
            $party_name = $conn->query("SELECT display_name as name FROM entity_list WHERE id = '{$party_id}' AND entity_type = 'Customer'")->fetch_assoc()['name'] ?? 'N/A';
        } else {
            $party_name = $conn->query("SELECT display_name as name FROM entity_list WHERE id = '{$party_id}' AND entity_type = 'Supplier'")->fetch_assoc()['name'] ?? 'N/A';
        }
    }

    // Calculate total paid and account breakdown for this payment group (Reference Code)
    $total_paid = $conn->query("SELECT SUM(total_amount) as total FROM transactions WHERE reference_code = '{$payment_code}' AND type='payment'")->fetch_assoc()['total'] ?? 0;
    
    $accounts_breakdown = [];
    $acc_qry = $conn->query("SELECT a.name, SUM(tl.amount) as amount FROM transaction_list tl JOIN account_list a ON tl.account_id = a.id WHERE tl.ref_table = 'transactions' AND tl.trans_code = '{$payment_code}' AND tl.type = 1 GROUP BY tl.account_id");
    while($ar = $acc_qry->fetch_assoc()){
        $accounts_breakdown[] = $ar;
    }

    // Allocation Details (To get Bill Total)
    $allocations = [];
    $t_bill_amt = 0;
    $t_paid_amt = 0;
    $alloc_qry = $conn->query("SELECT p.parent_id, p.transaction_date, p.remarks as p_remarks, SUM(p.total_amount) as paid_amount, b.reference_code as bill_code, b.total_amount as bill_total, b.transaction_date as bill_date
                               FROM transactions p 
                               INNER JOIN transactions b ON p.parent_id = b.id 
                               WHERE p.reference_code = '{$payment_code}' AND p.type='payment'
                               GROUP BY p.parent_id");
    while($row = $alloc_qry->fetch_assoc()){
        $allocations[] = $row;
        $t_bill_amt += floatval($row['bill_total']);
        $t_paid_amt += floatval($row['paid_amount']);
    }
}
?>
<div class="container-fluid">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">
                Payment Details - <?php echo htmlspecialchars($payment_code ?? '') ?>
                <?php if(isset($remarks) && strpos($remarks, '[POS]') !== false): ?>
                    <span class="badge badge-info shadow-sm ml-2" style="font-size: 0.8rem; font-weight: 500; vertical-align: middle;" title="POS Entry">POS</span>
                <?php endif; ?>
            </h3>

            <div class="card-tools">
                <a href="./?page=transactions/payments/manage_payment&id=<?php echo $id ?>" class="btn btn-flat btn-sm btn-primary" title="Edit"><i class="fa fa-edit"></i></a>
                <button class="btn btn-flat btn-sm btn-danger" id="delete_payment" type="button" title="Delete"><i class="fa fa-trash"></i></button>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=transactions/payments" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" type="button" id="print_btn" title="Print"><i class="fa fa-print"></i></button>
            </div>
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
                        <div class="row px-3 mb-4">
                            <!-- Left: Header Info -->
                            <div class="col-md-7 border-right">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4 text-muted small">Date</dt>
                                    <dd class="col-sm-8 border-bottom"><?php echo isset($date_created) ? date("d M Y", strtotime($date_created)) : 'N/A' ?></dd>

                                    <dt class="col-sm-4 text-muted small"><?php echo (isset($type) && $type == 1) ? "Customer" : "Vendor" ?></dt>
                                    <dd class="col-sm-8 border-bottom font-weight-bold text-primary"><?php echo htmlspecialchars($party_name ?? 'N/A') ?></dd>
                                    
                                    <dt class="col-sm-4 text-muted small padding-top">Total Paid Amount</dt>
                                    <dd class="col-sm-8 border-bottom font-weight-bold text-success" style="font-size: 1.2rem;">Rs. <?php echo number_format($total_paid, 2) ?></dd>

                                    <dt class="col-sm-4 text-muted small">Bill Total</dt>
                                    <dd class="col-sm-8 border-bottom font-weight-bold">Rs. <?php echo number_format($t_bill_amt, 2) ?></dd>

                                    <dt class="col-sm-4 text-muted small">Memo</dt>
                                    <dd class="col-sm-8 border-bottom text-muted italic" style="font-style:italic;"><?php echo htmlspecialchars($remarks ?? 'No remarks provided.') ?></dd>
                                </dl>
                            </div>

                            <!-- Right: Account Breakdown -->
                            <div class="col-md-5">
                                <h6 class="text-secondary font-weight-bold border-bottom pb-1"><i class="fa fa-wallet mr-2"></i>Account & Amount</h6>
                                <div class="px-2">
                                    <?php if(empty($accounts_breakdown)): ?>
                                        <p class="text-muted small italic">No account data found in ledger.</p>
                                    <?php else: ?>
                                        <table class="table table-sm table-borderless m-0">
                                            <?php foreach($accounts_breakdown as $ab): ?>
                                            <tr>
                                                <td class="py-1"><b><?php echo htmlspecialchars($ab['name']) ?></b></td>
                                                <td class="py-1 text-right">Rs. <?php echo number_format($ab['amount'], 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Allocation Details Table -->
                        <h5 class="text-info border-bottom pb-1 px-2 mt-4"><i class="fa fa-list-alt mr-2"></i>Payment Details (Allocations)</h5>
                        <table class="table table-sm table-bordered table-striped mt-2">
                            <thead>
                                <tr class="bg-navy">
                                    <th class="text-center">#</th>
                                    <th>Date</th>
                                    <th>Code (Bill Reference)</th>
                                    <th class="text-right">Bill Total</th>
                                    <th class="text-right">Paid Amount</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($allocations)): ?>
                                    <tr><td colspan="6" class="text-center">No allocations found.</td></tr>
                                <?php else: ?>
                                    <?php $i=1; foreach($allocations as $row): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i++; ?></td>
                                        <td><?php echo date("d-m-Y", strtotime($row['transaction_date'])) ?></td>
                                        <td><b><?php echo htmlspecialchars($row['bill_code'] ?? 'N/A') ?></b></td>
                                        <td class="text-right"><?php echo number_format($row['bill_total'], 2) ?></td>
                                        <td class="text-right text-success font-weight-bold"><?php echo number_format($row['paid_amount'], 2) ?></td>
                                        <td><small><?php echo htmlspecialchars($row['p_remarks'] ?? '') ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="3" class="text-right font-weight-bold">Summary</th>
                                    <th class="text-right font-weight-bold"><?php echo number_format($t_bill_amt, 2) ?></th>
                                    <th class="text-right text-success font-weight-bold"><?php echo number_format($t_paid_amt, 2) ?></th>
                                    <th></th>
                                </tr>
                                <?php if(($t_bill_amt - $t_paid_amt) != 0): ?>
                                <tr>
                                    <th colspan="4" class="text-right text-danger">Remaining Balance</th>
                                    <th class="text-right text-danger font-weight-bold"><?php echo number_format($t_bill_amt - $t_paid_amt, 2) ?></th>
                                    <th></th>
                                </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>

                    </div>
                </div>
                
                <!-- System Information Tab -->
                <div class="tab-pane fade" id="nav-system" role="tabpanel" aria-labelledby="nav-system-tab">
                    <div class="container-fluid">
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
                            <dd class="pl-3"><b><?php echo htmlspecialchars($creator) ?></b></dd>
                            
                            <dt class="text-muted small">Created On</dt>
                            <dd class="pl-3"><b><?php echo isset($date_created) ? date("d-m-Y", strtotime($date_created)) : 'N/A' ?></b></dd>
                            
                            <hr>
                            
                            <dt class="text-muted small">Last Updated By</dt>
                            <dd class="pl-3"><b><?php echo htmlspecialchars($updater) ?></b></dd>
                            
                            <dt class="text-muted small">Last Updated On</dt>
                            <dd class="pl-3"><b><?php echo (isset($date_updated) && !empty($date_updated)) ? date("d-m-Y", strtotime($date_updated)) : 'N/A' ?></b></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('#delete_payment').click(function(){
            _conf("Are you sure to delete this payment record?","delete_payment",['<?php echo $id ?>'])
        })
        
        $('#print_btn').click(function(){
            var nw = window.open("","","width=900,height=700")
            var content = $('#print_out').clone()
            nw.document.write($('header').html())
            nw.document.write('<style>html, body, .wrapper { min-height: unset !important; }</style>')
            nw.document.write(content.html())
            nw.document.close()
            setTimeout(() => {
                nw.print()
                setTimeout(() => {
                    nw.close()
                }, 200);
            }, 500);
        })
    })
    
    function delete_payment($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_payment",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("An error occured.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.href = './?page=transactions/payments';
                }else{
                    alert_toast("An error occured.",'error');
                    end_loader();
                }
            }
        })
    }
</script>
