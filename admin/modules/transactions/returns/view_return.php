<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../config.php';
}
$qry = $conn->query("SELECT t.*, t.reference_code as return_code, t.entity_id as vendor_id, t.transaction_date as date_created,
                        e.display_name as entity_name 
                        FROM `transactions` t 
                        LEFT JOIN entity_list e ON t.entity_id = e.id AND (
                            (t.entity_type = 'vendor' AND e.entity_type = 'Supplier') OR
                            (t.entity_type = 'customer' AND e.entity_type = 'Customer')
                        )
                        WHERE t.id = '{$_GET['id']}' AND t.type='return'");
if($qry->num_rows > 0){
    $res = $qry->fetch_assoc();
    foreach($res as $k => $v){
        if(!is_numeric($k)) $$k = $v;
    }
}
$is_vendor = (isset($entity_type) && $entity_type == 'vendor');
?>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">Return Details - <?php echo $return_code ?></h3>
            <div class="card-tools">
                <a class="btn btn-flat btn-sm btn-primary" href="<?php echo base_url.'/admin?page=transactions/returns/manage_return&id='.(isset($id) ? $id : '') ?>" title="Edit"><i class="fa fa-edit"></i></a>
                <button class="btn btn-flat btn-sm btn-danger" type="button" id="delete_return" title="Delete"><i class="fa fa-trash"></i></button>
                <a class="btn btn-flat btn-sm btn-dark" href="./?page=transactions/returns" title="Back to List"><i class="fa fa-list"></i></a>
                <button class="btn btn-flat btn-sm btn-success" type="button" id="print_btn" title="Print"><i class="fa fa-print"></i></button>
            </div>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="returnViewTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">General Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab" aria-controls="system" aria-selected="false">System Information</a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="returnViewTabContent">
                <!-- General Details Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <div id="print_out">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="control-label text-muted small">Return Code</label>
                                    <div class="pl-3"><b><?php echo $return_code ?></b></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="control-label text-muted small">Date</label>
                                    <div class="pl-3"><b><?php echo date("d-m-Y", strtotime($date_created)) ?></b></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label class="control-label text-muted small">Created From</label>
                                    <?php 
                                    $orig_link = '<span class="text-muted">No Source</span>';
                                    if (isset($remarks) && preg_match('/#([A-Z0-9-\/]+)/', $remarks, $matches)) {
                                        $orig_code = $matches[1];
                                        // Find original ID and type
                                        $orig_qry = $conn->query("SELECT id, type FROM transactions WHERE reference_code = '{$orig_code}' AND type IN ('sale', 'purchase')");
                                        if($orig_qry->num_rows > 0){
                                            $orig_data = $orig_qry->fetch_assoc();
                                            $page_type = ($orig_data['type'] == 'sale') ? 'sales' : 'purchases';
                                            $view_page = ($orig_data['type'] == 'sale') ? 'view_sale' : 'view_purchase';
                                            $orig_link = '<a href="./?page=transactions/'.$page_type.'/'.$view_page.'&id='.$orig_data['id'].'" class="text-primary font-weight-bold">'.$orig_code.'</a>';
                                        } else {
                                            $orig_link = "<b>".$orig_code."</b>";
                                        }
                                    }
                                    ?>
                                    <div class="pl-3"><?php echo $orig_link ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label class="control-label text-muted small"><?php echo $is_vendor ? 'Vendor' : 'Customer' ?> Name</label>
                                    <div class="pl-3"><b><?php echo !empty($entity_name) && $entity_name != 'N/A' ? htmlspecialchars($entity_name) : '--- (Not Set) ---' ?></b></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label class="control-label text-muted small">Total Amount</label>
                                    <div class="pl-3"><b><?php echo number_format((float)($total_amount ?? 0), 2) ?></b></div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-sm table-bordered table-striped" id="list">
                                    <thead>
                                        <tr class="bg-navy">
                                            <th class="text-center py-1">#</th>
                                            <th class="text-center py-1">Item Name</th>
                                            <th class="text-center py-1">Category</th>
                                            <th class="text-center py-1">Description</th>
                                            <th class="text-center py-1">Rate</th>
                                            <th class="text-center py-1">Quantity</th>
                                            <th class="text-center py-1">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total = 0;
                                        $i = 1;
                                        $qry = $conn->query("SELECT ti.*, i.name, i.description, c.name as category, ti.quantity as quantity, ti.unit_price as price, ti.total_price as total FROM `transaction_items` ti inner join item_list i on ti.item_id = i.id left join category_list c on i.category_id = c.id where ti.transaction_id = '{$id}'");
                                        while($row = $qry->fetch_assoc()):
                                            $total += $row['total']
                                        ?>
                                        <tr>
                                            <td class="py-1 text-center"><?php echo $i++; ?></td>
                                            <td class="py-1"><?php echo htmlspecialchars($row['name']) ?></td>
                                            <td class="py-1 text-center"><?php echo htmlspecialchars($row['category'] ?? 'N/A') ?></td>
                                            <td class="py-1"><?php echo htmlspecialchars($row['description'] ?? '') ?></td>
                                            <td class="py-1 text-right"><?php echo number_format($row['price'], 2) ?></td>
                                            <td class="py-1 text-center"><?php echo number_format($row['quantity']) ?></td>
                                            <td class="py-1 text-right"><?php echo number_format($row['total'], 2) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="text-right py-1" colspan="6">Total</th>
                                            <th class="text-right py-1"><?php echo number_format($total,2) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="text-muted small">Remarks / Reason</label>
                                    <div class="pl-3" style="border-left: 3px solid #dee2e6;"><?php echo isset($remarks) ? nl2br($remarks) : 'None' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information Tab -->
                <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                    <div class="container-fluid">
                        <dl>
                            <?php 
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
        $('#print_btn').click(function(){
            start_loader()
            var _el = $('<div>')
            var _head = $('head').clone()
            var p = $('#print_out').clone()
            _el.append(_head)
            _el.append('<div class="d-flex justify-content-center text-center flex-column">'+
                      '<div><img src="<?php echo validate_image($_settings->info('logo')) ?>" width="65px" height="65px" /></div>'+
                      '<div><h4 class="text-center"><?php echo $_settings->info('name') ?></h4><h4 class="text-center">Return Slip</h4></div>'+
                      '</div><hr/>')
            _el.append(p.html())
            var nw = window.open("","","width=1200,height=900,left=250")
            nw.document.write(_el.html())
            nw.document.close()
            setTimeout(() => { nw.print(); setTimeout(() => { nw.close(); end_loader(); }, 200); }, 500);
        })

        $('#delete_return').click(function(){
            _conf("Are you sure to delete this Return Record permanently?", "delete_return", ["<?php echo $_GET['id'] ?>"])
        })
    })

    function delete_return($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_return",
            method: "POST",
            data: {id: $id},
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp == 'object' && resp.status == 'success'){
                    location.replace("./?page=transactions/returns");
                }else{
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                }
            }
        })
    }
</script>