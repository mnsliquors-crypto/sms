<?php
require_once('../../../../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `reference_code_settings` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="container-fluid">
	<form action="" id="ref-code-form">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<div class="form-group">
			<label for="display_name" class="control-label">Transaction Name</label>
			<input name="display_name" id="display_name" type="text" class="form-control form-control-sm rounded-0" value="<?php echo isset($display_name) ? $display_name : ''; ?>" readonly>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label for="prefix" class="control-label">Prefix</label>
					<input name="prefix" id="prefix" type="text" class="form-control form-control-sm rounded-0" value="<?php echo isset($prefix) ? $prefix : ''; ?>">
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="suffix" class="control-label">Suffix</label>
					<input name="suffix" id="suffix" type="text" class="form-control form-control-sm rounded-0" value="<?php echo isset($suffix) ? $suffix : ''; ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label for="start_date" class="control-label">Starting Date</label>
					<input name="start_date" id="start_date" type="date" class="form-control form-control-sm rounded-0" value="<?php echo isset($start_date) ? $start_date : ''; ?>">
				</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label for="end_date" class="control-label">Ending Date</label>
					<input name="end_date" id="end_date" type="date" class="form-control form-control-sm rounded-0" value="<?php echo isset($end_date) ? $end_date : ''; ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<div class="form-group">
					<label for="current_number" class="control-label">Current Number</label>
					<input name="current_number" id="current_number" type="number" class="form-control form-control-sm rounded-0" value="<?php echo isset($current_number) ? $current_number : 0; ?>">
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="next_number" class="control-label">Next Number</label>
					<input name="next_number" id="next_number" type="number" class="form-control form-control-sm rounded-0" value="<?php echo isset($next_number) ? $next_number : 1; ?>">
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group">
					<label for="padding" class="control-label">No. of Digits (Padding)</label>
					<input name="padding" id="padding" type="number" class="form-control form-control-sm rounded-0" value="<?php echo isset($padding) ? $padding : 4; ?>">
				</div>
			</div>
		</div>
		<div class="form-group">
			<label for="status" class="control-label">Status</label>
			<select name="status" id="status" class="form-control form-control-sm rounded-0" required>
				<option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
				<option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
			</select>
		</div>
	</form>
</div>
<script>
	$(document).ready(function(){
		$('#ref-code-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			 $('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_ref_code_setting",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.reload();
					}else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            $("html, body, .modal").animate({ scrollTop: 0 }, "fast");
                            end_loader()
                    }else{
						alert_toast("An error occured",'error');
						end_loader();
                        console.log(resp)
					}
				}
			})
		})
	})
</script>
