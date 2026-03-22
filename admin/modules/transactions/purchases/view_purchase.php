<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../config.php';
}
$id = $_GET['id'] ?? null;
$qry = $conn->query("SELECT p.*, reference_code as po_code, entity_id as vendor_id, transaction_date as date_created, s.display_name as vendor_name 
                      FROM transactions p 
                      LEFT JOIN entity_list s ON p.entity_id = s.id AND s.entity_type = 'Supplier'
                      WHERE p.id = '{$id}' AND p.type = 'purchase'");
if($qry->num_rows > 0){
    foreach($qry->fetch_array() as $k => $v){
        $$k = $v;
    }
}

// Calculate total from items if amount is 0 (correction for old records)
$calc_total = $conn->query("SELECT SUM(total_price) as total FROM transaction_items WHERE transaction_id = '{$id}'")->fetch_assoc()['total'] ?? 0;
// We now rely purely on the transactions amount, but keeping this fallback just in case
if($calc_total > $total_amount){
    $amount = $calc_total; 
} else {
    $amount = isset($total_amount) ? floatval($total_amount) : 0;
}

// Calculate payment and return details
$total_paid = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as paid FROM `transactions` WHERE parent_id = '{$id}' AND type = 'payment'")->fetch_assoc()['paid'] ?? 0;
$total_returned = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as returned FROM `transactions` WHERE parent_id = '{$id}' AND type = 'return'")->fetch_assoc()['returned'] ?? 0;
// Balance = (Amount + Tax) - Paid - Returned
$balance = floatval($amount) + floatval($tax ?? 0) - floatval($total_paid) - floatval($total_returned);
// Determine status
if($balance <= 0.01):
    $status_label = "Paid in Full";
    $badge_class = "badge-success";
elseif(($total_paid + $total_returned) > 0):
    $status_label = "Partially Paid";
    $badge_class = "badge-warning";
else:
    $status_label = "Outstanding";
    $badge_class = "badge-danger";
endif;
?>
<style>
    .btn-return {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: #fff;
        border: none;
        border-radius: 4px;
        font-weight: 600;
        letter-spacing: 0.03em;
        transition: background 0.2s, box-shadow 0.2s;
    }
    .btn-return:hover {
        background: linear-gradient(135deg, #495057, #343a40);
        color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.18);
    }
    .btn-return i { margin-right: 4px; }
</style>
<div class="container-fluid">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">
                Purchase Details - <?php echo htmlspecialchars($po_code) ?> 
                <span class="badge <?php echo $badge_class ?> ml-2" style="font-size: 0.8rem; font-weight: 500; vertical-align: middle;">
                    <?php echo $status_label ?>
                </span>
            </h3>
            <div class="card-tools">
                <?php if($balance > 0): ?>
                <a class="btn btn-flat btn-sm btn-success" id="record_payment" href="<?php echo base_url."admin/?page=transactions/payments/manage_payment&type=2&party_id=".$vendor_id."&transaction_id=".$id ?>" title="Record Payment">
                    <i class="fa fa-cash-register"></i>
                </a>
                <?php endif; ?>
                <a class="btn btn-flat btn-sm btn-return" href="<?php echo base_url ?>admin/?page=transactions/returns/manage_return&from_purchase_id=<?php echo $id ?>" title="Create Return">
                    <i class="fa fa-arrow-left"></i> Return
                </a>
                <a href="./?page=transactions/purchases/manage_purchase&id=<?php echo $id ?>" class="btn btn-flat btn-sm btn-primary" title="Edit">
                    <i class="fa fa-edit"></i>
                </a>
                <button class="btn btn-flat btn-sm btn-danger" id="delete_purchase" type="button" title="Delete">
                    <i class="fa fa-trash"></i>
                </button>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=transactions/purchases" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" id="print_btn" type="button" title="Print"><i class="fa fa-print"></i></button>
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
                        <!-- Details Section -->
                        <div class="row px-3 mb-4">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted small">Vendor Name</dt>
                                    <dd class="col-sm-7 border-bottom font-weight-bold"><?php echo htmlspecialchars($vendor_name ?? 'N/A') ?></dd>
                                    
                                    <dt class="col-sm-5 text-muted small">Date</dt>
                                    <dd class="col-sm-7 border-bottom"><?php echo isset($date_created) ? date("d-m-Y", strtotime($date_created)) : '' ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted small">Grand Total</dt>
                                    <dd class="col-sm-7 border-bottom font-weight-bold text-primary"><?php echo number_format(floatval($amount ?? 0) + floatval($tax ?? 0), 2) ?></dd>
                                    
                                    <dt class="col-sm-5 text-muted small">Outstanding Balance</dt>
                                    <dd class="col-sm-7 border-bottom font-weight-bold text-danger"><?php echo number_format($balance, 2) ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-12 mt-2">
                                <dl class="row mb-0">
                                    <dt class="col-sm-2 text-muted small">Remarks</dt>
                                    <dd class="col-sm-10 border-bottom small"><?php echo !empty($remarks) ? nl2br(htmlspecialchars($remarks)) : 'N/A' ?></dd>
                                </dl>
                            </div>
                        </div>

                        <!-- Item Details Table -->
                        <h5 class="text-info border-bottom pb-1 px-2">Item Details</h5>
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                                <tr class="bg-navy">
                                    <th class="text-center">#</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th class="text-right">Rate</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $i = 1;
                                $items = $conn->query("SELECT ti.*, i.name as item_name, i.description, c.name as category 
                                                      FROM `transaction_items` ti 
                                                      INNER JOIN item_list i ON ti.item_id = i.id 
                                                      LEFT JOIN category_list c ON i.category_id = c.id 
                                                      WHERE ti.transaction_id = '{$id}'");
                                while($row = $items->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++ ?></td>
                                    <td><?php echo htmlspecialchars($row['item_name'] ?? '') ?></td>
                                    <td><small><?php echo htmlspecialchars($row['category'] ?? 'N/A') ?></small></td>
                                    <td><small class="truncate-2"><?php echo htmlspecialchars($row['description'] ?? '') ?></small></td>
                                    <td class="text-right"><?php echo number_format($row['unit_price'], 2) ?></td>
                                    <td class="text-center"><?php echo number_format($row['quantity'], 2) ?></td>
                                    <td class="text-right font-weight-bold"><?php echo number_format($row['total_price'], 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-right">Sub Total</th>
                                    <th class="text-right"><?php echo number_format($amount, 2) ?></th>
                                </tr>
                                <tr>
                                    <th colspan="6" class="text-right">VAT (%)</th>
                                    <th class="text-right text-info"><?php echo floatval($tax_perc ?? 0) ?>%</th>
                                </tr>
                                <tr>
                                    <th colspan="6" class="text-right">VAT Amount</th>
                                    <th class="text-right text-info"><?php echo number_format(floatval($tax ?? 0), 2) ?></th>
                                </tr>
                                <tr class="bg-light">
                                    <th colspan="6" class="text-right">Grand Total</th>
                                    <th class="text-right" style="font-weight: bold; font-size: 1.1em;"><?php echo number_format(floatval($amount ?? 0) + floatval($tax ?? 0), 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <!-- Payment Records Section -->
                        <div class="row mt-4 px-2">
                            <div class="col-md-12">
                                <h5 class="text-info border-bottom pb-1">Payment Details</h5>
                                <table class="table table-bordered table-striped table-sm">
                                        <thead>
                                            <tr class="bg-navy">
                                                <th class="text-center">#</th>
                                                <th>Date</th>
                                                <th>Ref Code</th>
                                                <th>Type</th>
                                                <th class="text-right">Amount</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Get both payments and returns grouped by reference code
                                            $payments = $conn->query("SELECT MAX(transaction_date) as date_created, reference_code, SUM(total_amount) as amount, GROUP_CONCAT(DISTINCT remarks SEPARATOR ', ') as remarks, MAX(type) as type FROM `transactions` WHERE parent_id = '{$id}' AND type IN ('payment', 'return') GROUP BY reference_code ORDER BY date_created DESC");
                                            $i = 1;
                                            while($p = $payments->fetch_assoc()):
                                                $is_return = ($p['type'] == 'return');
                                            ?>
                                            <tr>
                                                <td class="text-center"><?php echo $i++ ?></td>
                                                <td><?php echo date("d-m-Y", strtotime($p['date_created'])) ?></td>
                                                <td><?php echo htmlspecialchars($p['reference_code']) ?></td>
                                                <td>
                                                    <?php if($is_return): ?>
                                                        <span class="badge badge-danger">Return</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Payment</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right <?php echo $is_return ? 'text-danger font-weight-bold' : 'text-success font-weight-bold' ?>">
                                                    <?php 
                                                    // Sum if it is return or if code is sale as per request
                                                    $is_sale_code = (strpos(strtoupper($p['reference_code']), 'SALE') !== false);
                                                    echo number_format($p['amount'], 2);
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($p['remarks'] ?? '') ?></td>
                                            </tr>
                                            <?php endwhile; if($payments->num_rows <= 0): ?>
                                            <tr><td colspan="6" class="text-center">No transactions recorded.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-right text-muted">Total Paid + Returns</th>
                                                <th class="text-right font-weight-bold"><?php echo number_format(floatval($total_paid) + floatval($total_returned), 2) ?></th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <th colspan="4" class="text-right text-primary">Original Grand Total</th>
                                                <th class="text-right font-weight-bold text-primary"><?php echo number_format(floatval($amount) + floatval($tax ?? 0), 2) ?></th>
                                                <th></th>
                                            </tr>
                                            <tr>
                                                <th colspan="4" class="text-right text-danger">Pending Balance</th>
                                                <th class="text-right font-weight-bold text-danger"><?php echo number_format($balance, 2) ?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                </table>
                                
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information Tab -->
                <div class="tab-pane fade" id="nav-system" role="tabpanel" aria-labelledby="nav-system-tab">
                    <div class="container-fluid">
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
        <div class="card-footer"></div>
    </div>
</div>
<script>
    $(function(){
        $('#delete_purchase').click(function(){
            _conf("Are you sure to delete this purchase record permanently?", "delete_purchase", [<?php echo $id ?>]);
        });
        
        $('#print_btn').click(function(){
            print_purchase();
        });
    });

    function delete_purchase($id){
        _conf("Are you sure to delete this purchase record permanently?", "delete_purchase_confirmed", [$id]);
    }

    function delete_purchase_confirmed($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_po",
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
                    location.replace("./?page=transactions/purchases");
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        });
    }
    
    function print_purchase(){
        var _el = $('<div>');
        var _head = $('head').clone();
        _head.find('title').text("Purchase Details - Print View");
        
        var p = $('#print_out').clone();
        p.find('.tab-pane').addClass('active show');
        p.find('.nav-tabs').remove();
        p.find('tr.bg-navy').removeClass("bg-navy text-light");
        
        _el.append(_head);
        _el.append('<div class="container-fluid p-4"><h3 class="text-center">Purchase Details</h3></div>');
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
