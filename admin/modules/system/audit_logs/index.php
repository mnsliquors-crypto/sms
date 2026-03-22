<?php
if($_settings->userdata('type') != 1){
    echo "<script>alert('Access Denied. You do not have permission to access this page.'); window.location.href='".base_url."admin';</script>";
    exit;
}
?>
<div class="card card-outline card-primary rounded-0 shadow">
	<div class="card-header">
		<h3 class="card-title">System Audit Logs</h3>
	</div>
	<div class="card-body">
        <div class="container-fluid">
            <!-- Filters -->
            <form id="filter-form" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="date_from" class="control-label">Date From</label>
                        <input type="date" class="form-control form-control-sm rounded-0" name="date_from" id="date_from" value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="control-label">Date To</label>
                        <input type="date" class="form-control form-control-sm rounded-0" name="date_to" id="date_to" value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="module" class="control-label">Module / Table</label>
                        <select name="module" id="module" class="custom-select custom-select-sm rounded-0 select2">
                            <option value="">All</option>
                            <?php 
                            $tables = $conn->query("SELECT DISTINCT table_name FROM system_logs WHERE table_name IS NOT NULL ORDER BY table_name ASC");
                            while($row = $tables->fetch_assoc()):
                            ?>
                            <option value="<?= $row['table_name'] ?>" <?= isset($_GET['module']) && $_GET['module'] == $row['table_name'] ? 'selected' : '' ?>><?= $row['table_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="action_type" class="control-label">Action Type</label>
                        <select name="action_type" id="action_type" class="custom-select custom-select-sm rounded-0">
                            <option value="">All</option>
                            <option value="CREATE" <?= isset($_GET['action_type']) && $_GET['action_type'] == 'CREATE' ? 'selected' : '' ?>>CREATE</option>
                            <option value="UPDATE" <?= isset($_GET['action_type']) && $_GET['action_type'] == 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                            <option value="DELETE" <?= isset($_GET['action_type']) && $_GET['action_type'] == 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-3">
                        <button class="btn btn-primary btn-sm rounded-0 bg-gradient-primary"><i class="fa fa-filter"></i> Filter</button>
                        <a href="./?page=system/audit_logs" class="btn btn-default btn-sm rounded-0 border"><i class="fa fa-reset"></i> Reset</a>
                    </div>
                </div>
            </form>
            <hr>
			<table class="table table-bordered table-stripped table-hover" id="audit-table">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="15%">
					<col width="10%">
					<col width="30%">
					<col width="15%">
					<col width="10%">
				</colgroup>
				<thead>
					<tr class="bg-gradient-dark text-light">
						<th>#</th>
						<th>Date</th>
						<th>User</th>
						<th>Action</th>
						<th>Details</th>
						<th>IP Address</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$i = 1;
                    $where = "";
                    if(isset($_GET['date_from']) && isset($_GET['date_to'])){
                        $where .= " DATE(date_created) BETWEEN '{$_GET['date_from']}' AND '{$_GET['date_to']}' ";
                    } else {
                        $from = date('Y-m-d', strtotime('-30 days'));
                        $to = date('Y-m-d');
                        $where .= " DATE(date_created) BETWEEN '{$from}' AND '{$to}' ";
                    }
                    if(isset($_GET['module']) && !empty($_GET['module'])){
                        $where .= " AND table_name = '{$_GET['module']}' ";
                    }
                    if(isset($_GET['action_type']) && !empty($_GET['action_type'])){
                        $where .= " AND action_type = '{$_GET['action_type']}' ";
                    }

                    $sql = "SELECT l.*, u.username as user_name FROM `system_logs` l LEFT JOIN `users` u ON l.user_id = u.id WHERE {$where} ORDER BY UNIX_TIMESTAMP(l.date_created) DESC";
					$qry = $conn->query($sql);
					while($row = $qry->fetch_assoc()):
                        // Get Action Name from meta if available
                        $action_info = $row['action'];
                        if(!empty($row['table_name']) && !empty($row['action_type'])){
                            $action_info = "<span class='badge badge-".($row['action_type'] == 'CREATE' ? 'success' : ($row['action_type'] == 'UPDATE' ? 'primary' : 'danger'))."'>{$row['action_type']}</span> <br> <b>{$row['table_name']}</b> [ID: {$row['ref_id']}]";
                        }
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
							<td><?php echo $row['user_name'] ?? 'SYSTEM' ?></td>
							<td><?php echo $action_info ?></td>
							<td>
								<p class="m-0 truncate-1"><small><i><?php echo $row['action'] ?></i></small></p>
                                <?php if(!empty($row['field_name'])): ?>
                                    <p class="m-0 text-muted"><small>Field: <b><?= $row['field_name'] ?></b></small></p>
                                <?php endif; ?>
							</td>
							<td><?php echo $row['ip_address'] ?></td>
                            <td align="center">
                                <?php if(!empty($row['old_data']) || !empty($row['new_data'])): ?>
                                 <button type="button" class="btn btn-flat btn-light border btn-sm view_data" data-id="<?php echo $row['id'] ?>"><i class="fa fa-eye text-primary"></i> View JSON</button>
                                <?php else: ?>
                                    <span class="text-muted"><small>No Data</small></span>
                                <?php endif; ?>
                            </td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.view_data').click(function(){
			uni_modal("Audit Log Details","system/audit_logs/view_log_modal.php?id="+$(this).attr('data-id'), "large")
		})
        $('#filter-form').submit(function(e){
            e.preventDefault();
            var args = $(this).serialize();
            location.href = "./?page=system/audit_logs&"+args;
        });
		$('.table').dataTable({
			columnDefs: [
					{ orderable: false, targets: [4,6] }
			],
			order:[0,'asc']
		});
		$('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
        $('.select2').select2({width:'100%'})
	})
</script>
