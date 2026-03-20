<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../../../config.php';
}

if(isset($_GET['id'])){
    $qry = $conn->query("SELECT t.*, ti.item_id, ti.quantity, ti.unit_price as price, i.name as item_name 
                          FROM transactions t 
                          INNER JOIN transaction_items ti ON t.id = ti.transaction_id 
                          INNER JOIN item_list i ON ti.item_id = i.id
                          WHERE t.id = '{$_GET['id']}' AND t.type = 'adjustment'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k = $v;
        }
    } else {
        echo '<script>alert("Stock Adjustment ID is unknown."); location.replace("./?page=transactions/stock_adjustments/adjustments_index")</script>';
    }
}
// Map quantity to type for UI logic
$adj_type = ($quantity > 0) ? 1 : 2;
$display_qty = abs($quantity);
?>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">
                Stock Adjustment Details - <?php echo $reference_code ?>
                <span class="badge <?php echo $adj_type == 1 ? 'badge-success' : 'badge-danger' ?> ml-2" style="font-size: 0.8rem; font-weight: 500; vertical-align: middle;">
                    <?php echo $adj_type == 1 ? 'Addition (+)' : 'Subtraction (-)' ?>
                </span>
            </h3>
            <div class="card-tools">
                <a href="javascript:void(0)" class="btn btn-flat btn-sm btn-primary edit_data" data-id="<?php echo $id ?>" title="Edit"><i class="fa fa-edit"></i></a>
                <button class="btn btn-flat btn-sm btn-danger" type="button" id="delete_adjustment" title="Delete"><i class="fa fa-trash"></i></button>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=transactions/stock_adjustments/adjustments_index" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" type="button" id="print_btn" title="Print"><i class="fa fa-print"></i></button>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="adjustmentViewTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="true">Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">System Information</a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="adjustmentViewTabContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                    <div id="print_out">
                        <div class="row">
                            <div class="col-md-6 border-right">
                                <dl>
                                    <dt class="text-muted small">Reference Code</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo $reference_code ?></dd>
                                    <dt class="text-muted small">Adjustment Date</dt>
                                    <dd class="pl-3 border-bottom"><?php echo date("d-m-Y", strtotime($date_created)) ?></dd>
                                    <dt class="text-muted small">Adjustment Type</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold <?php echo $adj_type == 1 ? 'text-success' : 'text-danger' ?>">
                                        <?php echo $adj_type == 1 ? 'Addition (+)' : 'Subtraction (-)' ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl>
                                    <dt class="text-muted small">Item Adjusted</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo htmlspecialchars($item_name) ?></dd>
                                    <dt class="text-muted small">Quantity</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo number_format($display_qty) ?></dd>
                                    <dt class="text-muted small">Rate</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo number_format($price, 2) ?></dd>
                                    <dt class="text-muted small">Total Value</dt>
                                    <dd class="pl-3 border-bottom font-weight-bold"><?php echo number_format($total_amount, 2) ?></dd>
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
        $('.edit_data').click(function(){
            uni_modal("<i class='fa fa-edit'></i> Update Adjustment","modules/transactions/stock_adjustments/manage_adjustment.php?id="+$(this).attr('data-id'))
        })

        $('#delete_adjustment').click(function(){
            _conf("Are you sure to delete this adjustment record permanently?", "delete_adjustment_confirmed", [<?php echo $id ?>]);
        });

        $('#print_btn').click(function(){
            var _el = $('<div>');
            var _head = $('head').clone();
            _head.find('title').text("Adjustment Details - Print View");
            var p = $('#print_out').clone();
            _el.append(_head);
            _el.append('<div class="container-fluid p-4"><h3 class="text-center">Stock Adjustment Details</h3><hr/></div>');
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

    function delete_adjustment_confirmed($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_adjustment",
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
                    location.replace("./?page=transactions/stock_adjustments/adjustments_index");
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        });
    }
</script>
