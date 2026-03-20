<?php
if(!isset($conn) || !$conn){
    require_once __DIR__ . '/../../../../config.php';
}

$qry = $conn->query("SELECT *, display_name as name, role_id as type FROM `entity_list` where id = '{$_GET['id']}' AND entity_type = 'User'");
if($qry->num_rows > 0){
    foreach($qry->fetch_assoc() as $k => $v){
        $$k=$v;
    }
}
?>
<style>
    dl dt { font-size: 0.85rem; color: #6c757d; font-weight: 600; text-transform: uppercase; margin-bottom: 0.1rem; }
    dl dd { font-size: 1.05rem; font-weight: 500; color: #333; padding-left: 0.5rem; border-bottom: 1px solid #eee; margin-bottom: 0.8rem; }
    .img-avatar-view {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 10%;
    }
</style>
<div class="container-fluid pt-2">
    <div class="card card-outline card-primary shadow rounded-0">
        <div class="card-header">
            <h3 class="card-title">User Details - <?php echo htmlspecialchars($name ?? '') ?></h3>
            <div class="card-tools">
                <a href="?page=system/users/manage_user&id=<?php echo $id ?>" class="btn btn-flat btn-sm btn-primary"><i class="fa fa-edit"></i> Edit</a>
                <button type="button" class="btn btn-flat btn-sm btn-danger delete_data" data-id="<?php echo isset($id) ? $id : '' ?>"><i class="fa fa-trash"></i> Delete</button>
                <button class="btn btn-flat btn-sm btn-dark" type="button" data-dismiss="modal"><i class="fa fa-times"></i> Close</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center border-right">
                    <img src="<?php echo validate_image($avatar ?? '') ?>" alt="User Avatar" class="img-avatar-view img-thumbnail shadow-sm mb-3">
                    <h5 class="font-weight-bold"><?php echo ucwords($name ?? 'N/A') ?></h5>
                    <p class="text-muted"><?php echo ($type == 1) ? "Administrator" : "Staff" ?></p>
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt>First Name</dt>
                                <dd><?php echo htmlspecialchars($firstname ?? 'N/A') ?></dd>
                                <dt>Last Name</dt>
                                <dd><?php echo htmlspecialchars($lastname ?? 'N/A') ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl>
                                <dt>Username</dt>
                                <dd><?php echo htmlspecialchars($username ?? 'N/A') ?></dd>
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
                    </div>
                    <dl>
                        <dt>Date Created</dt>
                        <dd class="border-0"><?php echo isset($date_created) ? date("F d, Y h:i A", strtotime($date_created)) : 'N/A' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#uni_modal .modal-footer').hide();
        $('.delete_data').click(function(){
            _conf("Are you sure to delete this user permanently?", "delete_user", [<?php echo $id ?>]);
        });
    });
</script>
