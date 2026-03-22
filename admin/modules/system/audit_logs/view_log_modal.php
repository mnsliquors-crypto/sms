<?php
require_once('../../../../config.php');
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT l.*, u.username as user_name FROM `system_logs` l LEFT JOIN `users` u ON l.user_id = u.id WHERE l.id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_assoc();
    }else{
        echo '<div class="alert alert-danger">Log record not found.</div>';
        exit;
    }
}else{
    echo '<div class="alert alert-danger">Log ID not provided.</div>';
    exit;
}
?>
<style>
    .json-pre {
        background: #1e1e1e;
        color: #d4d4d4;
        border: 1px solid #333;
        padding: 15px;
        border-radius: 5px;
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
        font-family: Consolas, "Courier New", monospace;
        font-size: 0.85rem;
    }
    .text-old { color: #f2a6a6; }
    .text-new { color: #a6f2a6; }
</style>
<div class="container-fluid">
    <table class="table table-bordered table-sm text-sm">
        <tr>
            <th width="30%" class="bg-light">Date & Time</th>
            <td><?= isset($res['date_created']) ? date("M d, Y H:i:s", strtotime($res['date_created'])) : '' ?></td>
            <th width="20%" class="bg-light">IP Address</th>
            <td><?= isset($res['ip_address']) ? $res['ip_address'] : '' ?></td>
        </tr>
        <tr>
            <th class="bg-light">User</th>
            <td><?= isset($res['user_name']) ? $res['user_name'] : 'SYSTEM' ?></td>
            <th class="bg-light">Device Info</th>
            <td class="truncate-1" title="<?= isset($res['device_info']) ? htmlspecialchars($res['device_info']) : '' ?>"><small><?= isset($res['device_info']) ? htmlspecialchars($res['device_info']) : '' ?></small></td>
        </tr>
        <tr>
            <th class="bg-light">Action Type</th>
            <td>
                <?php 
                    $badge = 'secondary';
                    if($res['action_type'] == 'CREATE') $badge = 'success';
                    if($res['action_type'] == 'UPDATE') $badge = 'primary';
                    if($res['action_type'] == 'DELETE') $badge = 'danger';
                ?>
                <span class="badge badge-<?= $badge ?>"><?= isset($res['action_type']) ? $res['action_type'] : '' ?></span>
            </td>
            <th class="bg-light">Module / Table</th>
            <td><b><?= isset($res['table_name']) ? $res['table_name'] : '' ?></b> (ID: <?= isset($res['ref_id']) ? $res['ref_id'] : '' ?>)</td>
        </tr>
        <?php if(!empty($res['field_name'])): ?>
        <tr>
            <th class="bg-light">Field Changed</th>
            <td colspan="3"><span class="badge badge-info"><?= $res['field_name'] ?></span></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th class="bg-light">Remarks / Action</th>
            <td colspan="3"><i><?= isset($res['action']) ? $res['action'] : '' ?></i></td>
        </tr>
    </table>
    <hr>
    
    <div class="row mt-3">
        <?php if(!empty($res['old_data']) && $res['old_data'] !== '[]' && $res['old_data'] !== 'null'): ?>
        <div class="col-md-<?= empty($res['new_data']) || $res['new_data'] === '[]' || $res['new_data'] === 'null' ? '12' : '6' ?>">
            <h5 class="text-danger border-bottom pb-2 font-weight-bold"><i class="fa fa-minus-circle"></i> Old Data</h5>
            <?php 
                $old_json = json_decode($res['old_data'], true);
                if(json_last_error() === JSON_ERROR_NONE){
                    echo '<pre class="json-pre text-old">'.json_encode($old_json, JSON_PRETTY_PRINT).'</pre>';
                } else {
                    echo '<pre class="json-pre text-old">'.htmlspecialchars($res['old_data']).'</pre>';
                }
            ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($res['new_data']) && $res['new_data'] !== '[]' && $res['new_data'] !== 'null'): ?>
        <div class="col-md-<?= empty($res['old_data']) || $res['old_data'] === '[]' || $res['old_data'] === 'null' ? '12' : '6' ?>">
            <h5 class="text-success border-bottom pb-2 font-weight-bold"><i class="fa fa-plus-circle"></i> New Data</h5>
            <?php 
                $new_json = json_decode($res['new_data'], true);
                if(json_last_error() === JSON_ERROR_NONE){
                    echo '<pre class="json-pre text-new">'.json_encode($new_json, JSON_PRETTY_PRINT).'</pre>';
                } else {
                    echo '<pre class="json-pre text-new">'.htmlspecialchars($res['new_data']).'</pre>';
                }
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<div class="text-right pb-3 pr-3">
    <button class="btn btn-sm btn-flat btn-light border" data-dismiss="modal">Close</button>
</div>
<script>
    $(function(){
        // custom script
    })
</script>
