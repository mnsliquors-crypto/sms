<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../config.php';
}
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT t.*, c.display_name as customer_name, c.contact as customer_contact, c.address as customer_address, c.tax_id as customer_pan, t.entity_id as customer_id, t.reference_code as sales_code, t.transaction_date as date_sale, t.total_amount as amount
                          FROM transactions t 
                          LEFT JOIN entity_list c ON t.entity_id = c.id AND c.entity_type = 'Customer'
                          WHERE t.id = '{$_GET['id']}' AND t.type = 'sale'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k => $v){
            if(!is_numeric($k)) $$k = $v;
        }
    }
}

// Calculate payment and return details
$total_paid = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as paid FROM `transactions` WHERE parent_id = '{$id}' AND type = 'payment'")->fetch_assoc()['paid'] ?? 0;
$total_returned = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as returned FROM `transactions` WHERE parent_id = '{$id}' AND type = 'return'")->fetch_assoc()['returned'] ?? 0;
// Balance = Sale Amount - Total Paid - Total Returned
$balance = floatval($amount) - floatval($total_paid) - floatval($total_returned);
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
                Sale Details - <?php echo htmlspecialchars($sales_code) ?>
                <span class="badge <?php echo $badge_class ?> ml-2" style="font-size: 0.8rem; font-weight: 500; vertical-align: middle;">
                    <?php echo $status_label ?>
                </span>
                <?php if(isset($remarks) && strpos($remarks, '[POS]') !== false): ?>
                    <span class="badge badge-info shadow-sm ml-2" style="font-size: 0.8rem; font-weight: 500; vertical-align: middle;" title="POS Entry">POS</span>
                <?php endif; ?>
            </h3>

            <div class="card-tools">
                <?php if(floatval($balance) > 0): ?>
                <a class="btn btn-flat btn-sm btn-success" id="record_payment" href="<?php echo base_url."admin/?page=transactions/payments/manage_payment&type=1&party_id=".$customer_id."&transaction_id=".$id ?>" title="Record Payment">
                    <i class="fa fa-cash-register"></i>
                </a>
                <?php endif; ?>
                <a class="btn btn-flat btn-sm btn-return" href="<?php echo base_url ?>admin/?page=transactions/returns/manage_return&from_sale_id=<?php echo $id ?>" title="Create Return">
                    <i class="fa fa-arrow-left"></i> Return
                </a>
                <a href="./?page=transactions/sales/manage_sale&id=<?php echo $id ?>" class="btn btn-flat btn-sm btn-primary" title="Edit">
                    <i class="fa fa-edit"></i>
                </a>
                <button class="btn btn-flat btn-sm btn-danger" id="delete_sale" type="button" title="Delete">
                    <i class="fa fa-trash"></i>
                </button>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=transactions/sales" title="Back to List"><i class="fa fa-list"></i></a>
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
                        <!-- Details Section (On-screen View) -->
                        <div class="row px-3 mb-4 d-print-none">
                            <div class="col-md-4 border-right">
                                <h6 class="text-info border-bottom pb-1">Sale Information</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-5 text-muted small">Date:</dt>
                                    <dd class="col-sm-7 border-bottom small"><?php echo isset($date_sale) ? date("d-M-Y", strtotime($date_sale)) : '' ?></dd>

                                    <dt class="col-sm-5 text-muted small">Invoice No:</dt>
                                    <dd class="col-sm-7 border-bottom font-weight-bold text-primary small"><?php echo htmlspecialchars($sales_code) ?></dd>

                                    <dt class="col-sm-5 text-muted small">Grand Total:</dt>
                                    <dd class="col-sm-7 border-bottom font-weight-bold text-success small"><?php echo number_format($amount, 2) ?></dd>
                                    
                                    <dt class="col-sm-5 text-muted small">Balance:</dt>
                                    <dd class="col-sm-7 border-bottom font-weight-bold text-danger small"><?php echo number_format($balance, 2) ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4 border-right">
                                <h6 class="text-info border-bottom pb-1">Customer Information</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4 text-muted small">Name:</dt>
                                    <dd class="col-sm-8 border-bottom font-weight-bold text-truncate small"><?php echo htmlspecialchars($customer_name ?? 'Walk-in') ?></dd>

                                    <dt class="col-sm-4 text-muted small">Address:</dt>
                                    <dd class="col-sm-8 border-bottom small text-truncate"><?php echo !empty($customer_address) ? htmlspecialchars($customer_address) : 'N/A' ?></dd>

                                    <dt class="col-sm-4 text-muted small">Contact:</dt>
                                    <dd class="col-sm-8 border-bottom small"><?php echo !empty($customer_contact) ? htmlspecialchars($customer_contact) : 'N/A' ?></dd>

                                    <dt class="col-sm-4 text-muted small">PAN No:</dt>
                                    <dd class="col-sm-8 border-bottom small"><?php echo !empty($customer_pan) ? htmlspecialchars($customer_pan) : 'N/A' ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-info border-bottom pb-1">Remarks</h6>
                                <div class="bg-light p-2 border rounded small" style="min-height: 80px;">
                                    <?php echo !empty($remarks) ? nl2br(htmlspecialchars($remarks)) : '<span class="text-muted">No remarks available.</span>' ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Print-only Header (Generated by JS) -->
                        <div id="print_header_placeholder" class="d-none d-print-block"></div>

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
                                <?php 
                                    // Calculate subtotal by adding back discount
                                    $sub_total = floatval($amount) + floatval($discount ?? 0);
                                ?>
                                <tr>
                                    <th colspan="6" class="text-right">Sub Total</th>
                                    <th class="text-right"><?php echo number_format($sub_total, 2) ?></th>
                                </tr>
                                <tr>
                                    <th colspan="6" class="text-right text-danger">Discount</th>
                                    <th class="text-right text-danger">-<?php echo number_format(floatval($discount ?? 0), 2) ?></th>
                                </tr>
                                <tr class="bg-light">
                                    <th colspan="6" class="text-right">Grand Total</th>
                                    <th class="text-right" style="font-weight: bold; font-size: 1.1em;"><?php echo number_format($amount, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>


                        
                        <!-- Payment Records Section -->
                        <div class="row mt-4 px-2 payment-info-section">
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
                                                <th class="text-right font-weight-bold text-primary"><?php echo number_format(floatval($amount), 2) ?></th>
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
                        <dl>
                            <?php 
                                $creator = "N/A";
                                if(isset($created_by) && $created_by > 0){
                                    $c_qry = $conn->query("SELECT display_name as name FROM entity_list WHERE id = '{$created_by}' AND entity_type = 'User'");
                                    if($c_qry->num_rows > 0) $creator = $c_qry->fetch_assoc()['name'];
                                }
                                $updater = "N/A";
                                if(isset($updated_by) && $updated_by > 0){
                                    $u_qry = $conn->query("SELECT display_name as name FROM entity_list WHERE id = '{$updated_by}' AND entity_type = 'User'");
                                    if($u_qry->num_rows > 0) $updater = $u_qry->fetch_assoc()['name'];
                                }
                            ?>
                            <dt class="text-muted small">Created By</dt>
                            <dd class="pl-3"><b><?php echo htmlspecialchars($creator) ?></b></dd>
                            
                            <dt class="text-muted small">Created On</dt>
                            <dd class="pl-3"><b><?php echo isset($date_created) ? date("d-m-Y H:i", strtotime($date_created)) : 'N/A' ?></b></dd>
                            
                            <hr>
                            
                            <dt class="text-muted small">Last Updated By</dt>
                            <dd class="pl-3"><b><?php echo htmlspecialchars($updater) ?></b></dd>
                            
                            <dt class="text-muted small">Last Updated On</dt>
                            <dd class="pl-3"><b><?php echo (isset($date_updated) && !empty($date_updated)) ? date("d-m-Y H:i", strtotime($date_updated)) : 'N/A' ?></b></dd>
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
        $('#delete_sale').click(function(){
            _conf("Are you sure to delete this sale record permanently?", "delete_sale", [<?php echo $id ?>]);
        });
        
        $('#print_btn').click(function(){
            window.open(_base_url_ + "admin/modules/transactions/sales/print_invoice.php?id=<?php echo $id ?>", "_blank", "width=900,height=700");
        });
    });

    function delete_sale($id){
        _conf("Are you sure to delete this sale record permanently?", "delete_sale_confirmed", [$id]);
    }

    function delete_sale_confirmed($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_sale",
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
                    location.replace("./?page=transactions/sales");
                } else {
                    var msg = resp.msg || resp.error || "An error occured.";
                    alert_toast(msg, 'error');
                    end_loader();
                }
            }
        });
    }
    
    function print_sale(){
        var _el = $('<div>');
        var _head = $('head').clone();
        _head.find('title').text("Sale Details - Print View");
        
        var p = $('#print_out').clone();
        p.find('.tab-pane').addClass('active show');
        p.find('.nav-tabs').remove();
        p.find('tr.bg-navy').removeClass("bg-navy text-light");
        
        // Remove payment info from print
        p.find('.payment-info-section').remove();
        
        // Dynamic Settings from System Information
        var printTitle = "<?php echo $_settings->info('print_title') ?: 'Tax Invoice' ?>";
        var showLogo = parseInt("<?php echo $_settings->info('print_logo_show') !== '' ? $_settings->info('print_logo_show') : 1 ?>");
        var headerCols = parseInt("<?php echo $_settings->info('print_header_cols') ?: 4 ?>");
        var remarksPos = "<?php echo $_settings->info('print_remarks_pos') ?: 'below' ?>";
        var footerText = "<?php echo $_settings->info('print_footer_text') ?: 'Page 1 of 1' ?>";

        var _header = $('<div class="container-fluid mb-4 border-bottom pb-2">');
        var _row = $('<div class="row align-items-center">');
        
        // Logo Column (Left) - Conditional
        if(showLogo == 1){
            var _logo_col = $('<div class="col-2 text-center">');
            var logo_path = "<?php echo validate_image($_settings->info('logo')) ?>";
            _logo_col.append('<img src="'+logo_path+'" alt="Logo" class="img-fluid" style="max-height: 80px; object-fit: contain;">');
            _row.append(_logo_col);
        }
        
        // Info Column (Right/Center)
        var _info_col_class = (showLogo == 1) ? "col-10 text-center" : "col-12 text-center";
        var _info_col = $('<div class="'+_info_col_class+'">');
        _info_col.append('<h2 class="mb-0 font-weight-bold"><?php echo strtoupper($_settings->info('name')) ?></h2>');
        _info_col.append('<div class="small"><?php echo $_settings->info('address') ?></div>');
        _info_col.append('<div class="small">Contact: <?php echo $_settings->info('contact') ?> | Email: <?php echo $_settings->info('email') ?></div>');
        _info_col.append('<div class="small font-weight-bold">PAN No: <?php echo $_settings->info('pan_no') ?></div>');
        
        _row.append(_info_col);
        _header.append(_row);
        _header.append('<h4 class="text-center mt-3 text-uppercase font-weight-bold" style="letter-spacing:2px">'+printTitle+'</h4>');

        // Construct dynamic header columns for print only
        var _print_header = $('<div class="row mb-4 px-3">');
        
        if(headerCols == 4){
            // 4-column layout
            var _col1 = $('<div class="col-3 border-right">');
            _col1.append('<div class="small text-muted">Date:</div><div class="border-bottom mb-1"><?php echo isset($date_sale) ? date("d-m-Y", strtotime($date_sale)) : "" ?></div>');
            _col1.append('<div class="small text-muted">Invoice No:</div><div class="font-weight-bold text-primary border-bottom"><?php echo htmlspecialchars($sales_code) ?></div>');
            
            var _col2 = $('<div class="col-3 border-right">');
            _col2.append('<div class="small text-muted">Customer Name:</div><div class="font-weight-bold border-bottom"><?php echo htmlspecialchars($customer_name ?? "Walk-in") ?></div>');
            
            var _col3 = $('<div class="col-3 border-right">');
            _col3.append('<div class="small text-muted">Address:</div><div class="small border-bottom"><?php echo !empty($customer_address) ? htmlspecialchars($customer_address) : "N/A" ?></div>');
            
            var _col4 = $('<div class="col-3">');
            _col4.append('<div class="small text-muted">Contact No:</div><div class="small border-bottom mb-1"><?php echo !empty($customer_contact) ? htmlspecialchars($customer_contact) : "N/A" ?></div>');
            _col4.append('<div class="small text-muted">PAN No:</div><div class="small border-bottom"><?php echo !empty($customer_pan) ? htmlspecialchars($customer_pan) : "N/A" ?></div>');
            
            _print_header.append(_col1).append(_col2).append(_col3).append(_col4);
        } else {
            // 2-column layout
            var _colLeft = $('<div class="col-6 border-right">');
            _colLeft.append('<div class="small text-muted">Date:</div><div class="border-bottom mb-1"><?php echo isset($date_sale) ? date("d-m-Y", strtotime($date_sale)) : "" ?></div>');
            _colLeft.append('<div class="small text-muted">Invoice No:</div><div class="font-weight-bold text-primary mb-1"><?php echo htmlspecialchars($sales_code) ?></div>');
            _colLeft.append('<div class="small text-muted">Customer Name:</div><div class="font-weight-bold"><?php echo htmlspecialchars($customer_name ?? "Walk-in") ?></div>');
            
            var _colRight = $('<div class="col-6">');
            _colRight.append('<div class="small text-muted">Address:</div><div class="small border-bottom mb-1"><?php echo !empty($customer_address) ? htmlspecialchars($customer_address) : "N/A" ?></div>');
            _colRight.append('<div class="small text-muted">Contact No:</div><div class="small border-bottom mb-1"><?php echo !empty($customer_contact) ? htmlspecialchars($customer_contact) : "N/A" ?></div>');
            _colRight.append('<div class="small text-muted">PAN No:</div><div class="small"><?php echo !empty($customer_pan) ? htmlspecialchars($customer_pan) : "N/A" ?></div>');
            
            _print_header.append(_colLeft).append(_colRight);
        }
        
        p.find('#print_header_placeholder').replaceWith(_print_header);

        // Dynamic Remarks for Print
        var _remarks_val = '<?php echo !empty($remarks) ? str_replace(["\r", "\n"], ["", "<br>"], addslashes(htmlspecialchars($remarks))) : "" ?>';
        if(_remarks_val != ""){
            var _remarks_html = $('<div class="row px-3 mb-4">');
            _remarks_html.append('<div class="col-12"><div class="small text-muted border-bottom mb-1">Remarks:</div><div class="small">'+_remarks_val+'</div></div>');
            
            if(remarksPos == 'above'){
                var itemTableTitle = p.find('h5:contains("Item Details")');
                _remarks_html.insertBefore(itemTableTitle);
            } else {
                p.append(_remarks_html);
            }
        }

        _el.append(_head);
        _el.append(_header);
        _el.append(p);
        
        // Add Dynamic Footer
        var _footer = $('<div style="position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; border-top: 1px solid #dee2e6; padding-top: 5px;">');
        _footer.append(footerText);
        _el.append(_footer);
        
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

