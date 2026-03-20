<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../../../config.php';
}

// Get item details
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$qry = $conn->query("SELECT i.*, s.display_name as vendor, c.name as category 
                      FROM `item_list` i 
                      LEFT JOIN entity_list s ON i.vendor_id = s.id 
                      LEFT JOIN category_list c ON i.category_id = c.id 
                      WHERE i.id = '{$id}'");
if($qry->num_rows > 0){
    foreach($qry->fetch_assoc() as $k => $v){
        $$k = $v;
    }
}

// Get Available Stock from item record
$available_stock = floatval($quantity ?? 0);
?>
<style>
    #itemViewTab .nav-link { font-weight: 600; color: #6c757d; }
    #itemViewTab .nav-link.active { color: #007bff; border-bottom: 2px solid #007bff; }
    .item-img-view { width: 100%; max-height: 200px; object-fit: contain; background: #f8f9fa; border: 1px solid #dee2e6; }
    dl dt { font-size: 0.85rem; color: #6c757d; font-weight: 600; text-transform: uppercase; margin-bottom: 0.1rem; }
    dl dd { font-size: 1.05rem; font-weight: 500; color: #333; padding-left: 0.5rem; border-bottom: 1px solid #eee; margin-bottom: 0.8rem; }
    .price-tag { color: #28a745; font-weight: 700; }
</style>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">Item Details - <?php echo htmlspecialchars($name ?? '') ?></h3>
            <div class="card-tools">
                <a class="btn btn-flat btn-sm btn-primary" href="<?php echo base_url.'admin/?page=master/items/manage&id='.(isset($id) ? $id : '') ?>" title="Edit"><i class="fa fa-edit"></i></a>
                <button class="btn btn-flat btn-sm btn-danger" type="button" id="delete_item" title="Delete"><i class="fa fa-trash"></i></button>
                <a class="btn btn-flat btn-sm btn-dark" href="<?php echo base_url ?>admin/?page=master/items" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" type="button" id="print_btn" title="Print"><i class="fa fa-print"></i></button>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="itemViewTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="true">Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="transactions-tab" data-toggle="tab" href="#transactions" role="tab" aria-controls="transactions" aria-selected="false">History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">System Information</a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="itemViewTabContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <div id="print_out">
                        <div class="row">
                            <!-- Image Column -->
                            <div class="col-md-3 border-right text-center">
                                <img src="<?php echo validate_image($image_path ?? "") ?>" alt="Item Image" class="img-fluid item-img-view rounded mb-3">
                                <div class="mb-3">
                                    <?php if(isset($status) && $status == 1): ?>
                                        <span class="badge badge-success px-3 py-2 rounded-pill">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger px-3 py-2 rounded-pill">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <div class="alert <?php echo $available_stock <= ($reorder_level ?? 0) ? 'alert-danger' : 'alert-info' ?> py-2 px-1">
                                    <small class="font-weight-bold d-block text-uppercase">Stock Level</small>
                                    <h4 class="mb-0 font-weight-bold"><?php echo number_format($available_stock) ?> <small><?php echo htmlspecialchars($unit ?? '') ?></small></h4>
                                    <?php if($available_stock <= ($reorder_level ?? 0)): ?>
                                        <small class="text-danger font-weight-bold"><i class="fa fa-warning"></i> Restock Needed</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Data Columns -->
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-6 border-right">
                                        <dl>
                                            <dt>Item Code</dt>
                                            <dd><?php echo htmlspecialchars($id ?? '') ?></dd>
                                            <dt>Item Name</dt>
                                            <dd class="font-weight-bold text-navy"><?php echo htmlspecialchars($name ?? 'N/A') ?></dd>
                                            <dt>Category</dt>
                                            <dd><?php echo htmlspecialchars($category ?? 'Uncategorized') ?></dd>
                                            <dt>Primary Vendor</dt>
                                            <dd><?php echo htmlspecialchars($vendor ?? 'N/A') ?></dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl>
                                            <dt>Cost Price (Avg)</dt>
                                            <dd class="text-success font-weight-bold"><?php echo number_format($cost ?? 0, 2) ?></dd>
                                            <dt>Selling Price</dt>
                                            <dd class="text-navy font-weight-bold"><?php echo number_format($selling_price ?? 0, 2) ?></dd>
                                            <dt>MRP</dt>
                                            <dd class="text-muted font-weight-bold"><?php echo number_format($mrp ?? 0, 2) ?></dd>
                                            <dt>Reorder Level</dt>
                                            <dd><?php echo number_format($reorder_level ?? 0) ?> <?php echo htmlspecialchars($unit ?? '') ?></dd>

                                            <dt>Unit of Measure</dt>
                                            <dd><?php echo htmlspecialchars($unit ?? 'N/A') ?></dd>
                                        </dl>
                                    </div>
                                </div>
                                <div class="row px-2">
                                    <div class="col-12">
                                        <dl>
                                            <dt>Description</dt>
                                            <dd class="border-0"><?php echo isset($description) && !empty($description) ? nl2br(htmlspecialchars($description)) : '<em>No description provided.</em>' ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade" id="transactions" role="tabpanel">
                    <?php 
                    $stat = $conn->query("SELECT 
                                        SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_in,
                                        SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_out
                                        FROM transaction_items WHERE item_id = '{$id}'")->fetch_assoc();
                    $stat['total_in'] = $stat['total_in'] ?? 0;
                    $stat['total_out'] = $stat['total_out'] ?? 0;
                    $stat['available'] = $stat['total_in'] - $stat['total_out'];
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="info-box bg-light shadow-sm border py-1" style="min-height:auto">
                                <div class="info-box-content">
                                    <span class="info-box-text small text-muted text-uppercase">Total Stock In</span>
                                    <span class="info-box-number h5 mb-0 font-weight-bold text-success"><?php echo number_format($stat['total_in'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light shadow-sm border py-1" style="min-height:auto">
                                <div class="info-box-content">
                                    <span class="info-box-text small text-muted text-uppercase">Total Stock Out</span>
                                    <span class="info-box-number h5 mb-0 font-weight-bold text-warning"><?php echo number_format($stat['total_out'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light shadow-sm border py-1" style="min-height:auto">
                                <div class="info-box-content">
                                    <span class="info-box-text small text-muted text-uppercase">Current Stock</span>
                                    <span class="info-box-number h5 mb-0 font-weight-bold text-navy"><?php echo number_format($stat['available'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-light shadow-sm border py-1" style="min-height:auto">
                                <div class="info-box-content">
                                    <span class="info-box-text small text-muted text-uppercase">Average Cost</span>
                                    <span class="info-box-number h5 mb-0 font-weight-bold text-info"><?php echo number_format($cost ?? 0, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm text-sm" id="transactionTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Ref</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $txns = $conn->query("SELECT ti.*, t.transaction_date as date_created, t.reference_code as ref_code, t.type as trans_type, ti.unit_price as price, ti.total_price as total FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE ti.item_id = '{$id}' ORDER BY t.transaction_date DESC");
                                
                                if($txns->num_rows > 0):
                                    while($row = $txns->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo date("d-m-Y", strtotime($row['date_created'])) ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['quantity'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                                            <?php echo $row['quantity'] > 0 ? 'IN' : 'OUT' ?>
                                        </span>
                                        <small class="text-muted font-weight-bold ml-1" style="text-transform:uppercase"><?php echo $row['trans_type'] ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['ref_code'] ?? '-') ?></td>
                                    <td class="text-right font-weight-bold"><?php echo number_format(abs($row['quantity']), 2) ?></td>
                                    <td class="text-right text-muted"><?php echo number_format($row['price'], 2) ?></td>
                                    <td class="text-right"><?php echo number_format($row['total'], 2) ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center text-muted">No transactions found.</td></tr>
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
        <div class="card-footer bg-light px-3 py-2 text-center">
            <a href="<?php echo base_url.'admin/?page=transactions/sales/manage_sale&item_id='.$id ?>" class="btn btn-outline-success btn-sm"><i class="fa fa-shopping-cart"></i> Create Sale</a>
            <a href="<?php echo base_url.'admin/?page=transactions/purchases/manage_purchase&item_id='.$id ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-truck"></i> Create Purchase</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../../inc/modal_footer_style.php'; ?>
<script>
    $(function(){
        $('#print_btn').click(function(){
            var _el = $('<div>');
            var _head = $('head').clone();
            _head.find('title').text("Item Details - Print View");
            var p = $('#print_out').clone();
            _el.append(_head);
            _el.append('<div class="container-fluid p-4"><h3 class="text-center">Item Product Sheet</h3><hr/></div>');
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
        
        $('#delete_item').click(function(){
            _conf("Are you sure to delete this item permanently?", "delete_item_confirmed", [<?php echo $id ?>]);
        });
    });

    function delete_item_confirmed($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_item",
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
                    location.replace("./?page=master/items");
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        });
    }
</script>
