<?php
require_once __DIR__ . '/../../../../config.php';
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `account_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="container-fluid">
    <form action="" id="account-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="name" class="control-label text-info">Account Name</label>
                    <input name="name" id="name" class="form-control form-control-sm rounded-0" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="type" class="control-label text-info">Account Type</label>
                    <select name="type" id="type" class="form-control form-control-sm rounded-0" required>
                        <option value="1" <?php echo isset($type) && $type == 1 ? 'selected' : '' ?>>Cash</option>
                        <option value="2" <?php echo isset($type) && $type == 2 ? 'selected' : '' ?>>Bank</option>
                        <option value="3" <?php echo isset($type) && $type == 3 ? 'selected' : '' ?>>Mobile</option>
                        <option value="4" <?php echo isset($type) && $type == 4 ? 'selected' : '' ?>>Credit</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="description" class="control-label">Description</label>
            <textarea name="description" id="description" cols="30" rows="2" class="form-control form-control-sm rounded-0 no-resize"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="balance" class="control-label text-info">Initial Balance</label>
                    <input type="number" step="any" name="balance" id="balance" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($balance) ? $balance : 0; ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="status" class="control-label text-info">Status</label>
                    <select name="status" id="status" class="form-control form-control-sm rounded-0" required>
                        <option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

    </form>
</div>
<script>
	$(document).ready(function(){
		$('#uni_modal .modal-footer').show();
		$('#uni_modal #submit').show();
		$('#account-form').submit(function(e){

			e.preventDefault();
            var _this = $(this)
			 $('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_account",
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
