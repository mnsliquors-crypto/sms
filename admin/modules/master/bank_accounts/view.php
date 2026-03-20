<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../../../config.php';
}

$qry = $conn->query("SELECT * FROM `account_list` where id = '{$_GET['id']}' ");
if($qry->num_rows > 0){
    foreach($qry->fetch_assoc() as $k => $v){
        $$k=$v;
    }
}
$types = ['','Cash','Bank','Mobile','Credit'];
?>
<style>
    dl dt { font-size: 0.85rem; color: #6c757d; font-weight: 600; text-transform: uppercase; margin-bottom: 0.1rem; }
    dl dd { font-size: 1.05rem; font-weight: 500; color: #333; padding-left: 0.5rem; border-bottom: 1px solid #eee; margin-bottom: 0.8rem; }
</style>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">Account Details - <?php echo htmlspecialchars($name ?? '') ?></h3>
            <div class="card-tools">
                <button type="button" class="btn btn-flat btn-sm btn-primary edit_data" data-id="<?php echo isset($id) ? $id : '' ?>"><i class="fa fa-edit"></i> Edit</button>
                <button type="button" class="btn btn-flat btn-sm btn-danger delete_data" data-id="<?php echo isset($id) ? $id : '' ?>"><i class="fa fa-trash"></i> Delete</button>
                <button class="btn btn-flat btn-sm btn-dark" type="button" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 border-right">
                    <dl>
                        <dt>Account ID</dt>
                        <dd><?php echo $id ?></dd>
                        <dt>Account Name</dt>
                        <dd class="font-weight-bold text-navy"><?php echo htmlspecialchars($name ?? 'N/A') ?></dd>
                        <dt>Account Type</dt>
                        <dd><?php echo isset($types[$type]) ? $types[$type] : 'N/A' ?></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Initial Balance</dt>
                        <dd><?php echo number_format($balance ?? 0, 2) ?></dd>
                        <dt>Status</dt>
                        <dd>
                            <?php if(isset($status) && $status == 1): ?>
                                <span class="badge badge-success px-3 rounded-pill">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger px-3 rounded-pill">Inactive</span>
                            <?php endif; ?>
                        </dd>
                        <dt>Description</dt>
                        <dd class="border-0"><?php echo !empty($description) ? nl2br(htmlspecialchars($description)) : 'N/A' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function(){
        // Hide the global uni_modal footer on view pages
        $('#uni_modal .modal-footer').hide();
        
        $('.delete_data').click(function(){
            _conf("Are you sure to delete this account permanently?", "delete_account_confirmed", [<?php echo $id ?>]);
        });
    });

    function delete_account_confirmed($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_account",
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
                    location.replace("./?page=master/bank_accounts");
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        });
    }
</script>
