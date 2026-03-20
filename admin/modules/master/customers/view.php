<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../../../config.php';
}

$qry = $conn->query("SELECT *, display_name as name FROM `entity_list` where id = '{$_GET['id']}' AND entity_type = 'Customer'");
if($qry->num_rows > 0){
    foreach($qry->fetch_assoc() as $k => $v){
        $$k=$v;
    }
}
?>
<style>
    #customerViewTab .nav-link { font-weight: 600; color: #6c757d; }
    #customerViewTab .nav-link.active { color: #007bff; border-bottom: 2px solid #007bff; }
    dl dt { font-size: 0.82rem; color: #6c757d; font-weight: 600; text-transform: uppercase; margin-bottom: 0.05rem; }
    dl dd { font-size: 1rem; font-weight: 500; color: #333; padding-left: 0.2rem; border-bottom: 1px solid #f8f8f8; margin-bottom: 0.6rem; }
</style>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">Customer Details - <?php echo htmlspecialchars($name ?? '') ?></h3>
            <div class="card-tools">
                <a class="btn btn-flat btn-sm btn-primary" href="<?php echo base_url.'admin/?page=master/customers/manage&id='.(isset($id) ? $id : '') ?>" title="Edit"><i class="fa fa-edit"></i></a>
                <button class="btn btn-flat btn-sm btn-danger" type="button" id="delete_customer" title="Delete"><i class="fa fa-trash"></i></button>
                <a class="btn btn-flat btn-sm btn-dark" href="<?php echo base_url ?>admin/?page=master/customers" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" type="button" id="print_btn" title="Print"><i class="fa fa-print"></i></button>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="customerViewTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="true">Entity Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="related-tab" data-toggle="tab" href="#related" role="tab" aria-controls="related" aria-selected="false">Sales History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">System Logistics</a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="customerViewTabContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div id="print_out">
                        <div class="row">
                            <div class="col-md-4 border-right">
                                <dl>
                                    <dt>Customer Code</dt>
                                    <dd><?php echo $code ?? 'N/A' ?></dd>
                                    <dt>Full Name</dt>
                                    <dd class="font-weight-bold text-navy h5"><?php echo htmlspecialchars($name ?? 'N/A') ?></dd>
                                    <dt>Tax ID / PAN</dt>
                                    <dd><?php echo htmlspecialchars($tax_id ?? 'N/A') ?></dd>
                                    <dt>Status</dt>
                                    <dd>
                                        <?php if(isset($status) && $status == 1): ?>
                                            <span class="badge badge-success px-3 rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger px-3 rounded-pill">Inactive</span>
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-4 border-right">
                                <dl>
                                    <dt>Primary Contact</dt>
                                    <dd><?php echo htmlspecialchars($contact ?? 'N/A') ?></dd>
                                    <dt>Alternate Contact</dt>
                                    <dd><?php echo htmlspecialchars($alternate_contact ?? 'N/A') ?></dd>
                                    <dt>Location (City, State)</dt>
                                    <dd><?php echo htmlspecialchars(($city ?? '').', '.($state ?? '')) ?></dd>
                                    <dt>Email Address</dt>
                                    <dd><?php echo htmlspecialchars($email ?? 'N/A') ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl>
                                    <dt>Opening Balance</dt>
                                    <dd>Rs. <?php echo number_format($opening_balance ?? 0, 2) ?></dd>
                                    <dt>Credit Limit</dt>
                                    <dd>Rs. <?php echo number_format($credit_limit ?? 0, 2) ?></dd>
                                    <dt>Credit Days</dt>
                                    <dd><?php echo $credit_days ?? 0 ?> Days</dd>
                                    <dt>Address</dt>
                                    <dd class="border-0"><?php echo !empty($address) ? nl2br(htmlspecialchars($address)) : 'N/A' ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales History Tab -->
                <div class="tab-pane fade" id="related" role="tabpanel">
                    <?php 
                    $stat = $conn->query("SELECT SUM(total_amount) as total, 
                                        (SELECT SUM(total_amount) FROM transactions p WHERE p.parent_id IN (SELECT id FROM transactions WHERE entity_id = '{$id}' AND type='sale') AND p.type='payment') as paid
                                        FROM transactions where entity_id = '{$id}' AND type='sale'")->fetch_assoc();
                    $stat['paid'] = $stat['paid'] ?? 0;
                    $stat['balance'] = ($stat['total'] ?? 0) - $stat['paid'];
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="info-box bg-light shadow-sm border py-1" style="min-height:auto">
                                <div class="info-box-content">
                                    <span class="info-box-text small text-muted text-uppercase">Total Sales</span>
                                    <span class="info-box-number h5 mb-0 font-weight-bold text-navy"><?php echo number_format($stat['total'] ?? 0, 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light shadow-sm border py-1" style="min-height:auto">
                                <div class="info-box-content">
                                    <span class="info-box-text small text-muted text-uppercase">Paid Amount</span>
                                    <span class="info-box-number h5 mb-0 font-weight-bold text-success"><?php echo number_format($stat['paid'] ?? 0, 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-light shadow-sm border py-1" style="min-height:auto">
                                <div class="info-box-content">
                                    <span class="info-box-text small text-muted text-uppercase">Outstanding</span>
                                    <span class="info-box-number h5 mb-0 font-weight-bold text-danger"><?php echo number_format($stat['balance'] ?? 0, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm" id="sales-tbl-modal">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Ref Code</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">Paid</th>
                                    <th class="text-right text-danger">Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sales = $conn->query("SELECT t.*, t.reference_code as sales_code, t.transaction_date as date_created, t.total_amount as amount,
                                                    (SELECT COALESCE(SUM(total_amount),0) FROM transactions p WHERE p.parent_id = t.id AND p.type='payment') as paid_amount
                                                    FROM `transactions` t where t.entity_id = '{$id}' AND t.type='sale' order by t.transaction_date desc");
                                while($row=$sales->fetch_assoc()):
                                    $row['balance'] = $row['amount'] - $row['paid_amount'];
                                    $payment_status = ($row['balance'] <= 0) ? 1 : (($row['paid_amount'] > 0) ? 2 : 0);
                                ?>
                                <tr>
                                    <td><?php echo date("d-m-Y",strtotime($row['date_created'])) ?></td>
                                    <td><a href="javascript:void(0)" onclick="view_sale('<?php echo $row['id'] ?>')"><?php echo $row['sales_code'] ?></a></td>
                                    <td class="text-right"><?php echo number_format($row['amount'],2) ?></td>
                                    <td class="text-right"><?php echo number_format($row['paid_amount'],2) ?></td>
                                    <td class="text-right text-danger font-weight-bold"><?php echo number_format($row['balance'],2) ?></td>
                                    <td class="text-center">
                                        <?php if($payment_status == 1): ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php elseif($payment_status == 2): ?>
                                            <span class="badge badge-warning">Partial</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; if($sales->num_rows <= 0): ?>
                                <tr><td colspan="6" class="text-center text-muted">No sales recorded.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
        <div class="modal-footer bg-light px-3 py-2">
            <a class="btn btn-outline-success btn-sm" href="https://wa.me/<?php echo $contact ?>?text=Hello <?php echo $name ?>, please check your account balance." target="_blank"><span class="fab fa-whatsapp"></span> WhatsApp</a>
            <a href="<?php echo base_url.'admin/?page=transactions/sales/manage_sale&customer_id='.$id ?>" class="btn btn-outline-navy btn-sm"><i class="fa fa-shopping-cart"></i> Create Sale</a>
            <a href="<?php echo base_url.'admin/?page=transactions/payments/manage_payment&party_id='.$id.'&type=1' ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-cash-register"></i> Record Payment</a>
            <a href="<?php echo base_url ?>admin/?page=reports/customer_statement&customer_id=<?php echo $id ?>" class="btn btn-outline-info btn-sm"><i class="fa fa-file-invoice"></i> View Statement</a>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#sales-tbl-modal').dataTable();
        $('#print_btn').click(function(){
            var _el = $('<div>');
            var _head = $('head').clone();
            _head.find('title').text("Customer Details - Print View");
            var p = $('#print_out').clone();
            _el.append(_head);
            _el.append('<div class="container-fluid p-4"><h3 class="text-center">Customer Business Profile</h3><hr/></div>');
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
        $('#delete_customer').click(function(){
            _conf("Are you sure to delete this customer permanently?", "delete_customer_confirmed", [<?php echo $id ?>]);
        });
    });

    function view_sale($id){
        uni_modal("<i class='fa fa-info-circle'></i> Sale Details", "modules/transactions/sales/view_sale.php?id="+$id, "large")
    }

    function delete_customer_confirmed($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_customer",
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
                    location.replace("./?page=master/customers");
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        });
    }
</script>

