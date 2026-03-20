<?php
require_once __DIR__ . '/../../../../config.php';
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT *, display_name as name from `entity_list` where id = '{$_GET['id']}' AND entity_type = 'Customer'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k = $v;
        }
    }
}
?>
<div class="card card-outline card-primary shadow rounded-0">
    <div class="card-header">
        <h3 class="card-title"><?php echo isset($id) ? "Update Customer - ".htmlspecialchars($name) : "Add New Customer" ?></h3>
        <div class="card-tools">
            <a class="btn btn-flat btn-sm btn-dark" href="<?php echo base_url.'/admin?page=master/customers'.(isset($id) ? '/view&id='.$id : '') ?>" title="Back"><i class="fa fa-angle-left"></i> Back</a>
        </div>
    </div>
    <div class="card-body">
        <form action="" id="customer-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="name" class="control-label text-info">Customer Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-sm rounded-0" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="email" class="control-label text-info">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control form-control-sm rounded-0" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tax_id" class="control-label text-info">Tax ID / PAN</label>
                        <input type="text" name="tax_id" id="tax_id" class="form-control form-control-sm rounded-0" value="<?php echo isset($tax_id) ? htmlspecialchars($tax_id) : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="contact" class="control-label">Primary Contact #</label>
                        <input type="text" name="contact" id="contact" class="form-control form-control-sm rounded-0" value="<?php echo isset($contact) ? htmlspecialchars($contact) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="alternate_contact" class="control-label text-info">Alternate Contact</label>
                        <input type="text" name="alternate_contact" id="alternate_contact" class="form-control form-control-sm rounded-0" value="<?php echo isset($alternate_contact) ? htmlspecialchars($alternate_contact) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="status" class="control-label text-info">Status</label>
                        <select name="status" id="status" class="form-control form-control-sm rounded-0" data-no-select2>
                            <option value="1" <?php echo (!isset($status) || $status == 1) ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?php echo (isset($status) && $status == 0) ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="city" class="control-label">City</label>
                        <input type="text" name="city" id="city" class="form-control form-control-sm rounded-0" value="<?php echo isset($city) ? htmlspecialchars($city) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="state" class="control-label">State</label>
                        <input type="text" name="state" id="state" class="form-control form-control-sm rounded-0" value="<?php echo isset($state) ? htmlspecialchars($state) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="credit_limit" class="control-label text-info">Credit Limit</label>
                        <input type="number" step="any" min="0" name="credit_limit" id="credit_limit" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($credit_limit) ? $credit_limit : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="credit_days" class="control-label text-info">Credit Days</label>
                        <input type="number" min="0" name="credit_days" id="credit_days" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($credit_days) ? $credit_days : '0'; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="opening_balance" class="control-label text-info">Opening Balance</label>
                        <input type="number" step="any" name="opening_balance" id="opening_balance" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($opening_balance) ? $opening_balance : '0'; ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="address" class="control-label">Address</label>
                <textarea rows="3" name="address" id="address" class="form-control form-control-sm rounded-0"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
            </div>
        </form>
    </div>
    <div class="card-footer py-2 text-center">
        <button type="submit" form="customer-form" class="btn btn-primary btn-flat btn-sm">Save Customer</button>
        <a class="btn btn-secondary btn-flat btn-sm" href="<?php echo base_url.'/admin?page=master/customers'.(isset($id) ? '/view&id='.$id : '') ?>">Cancel</a>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('#customer-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            $('.err-msg').remove();
            start_loader();
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_customer",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: function(err){
                    console.log(err);
                    alert_toast("An error occured", 'error');
                    end_loader();
                },
                success: function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.replace(_base_url_ + "admin/?page=master/customers/view&id=" + resp.id);
                    } else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>');
                        el.addClass("alert alert-danger err-msg").text(resp.msg);
                        _this.prepend(el);
                        el.show('slow');
                        end_loader();
                    } else {
                        alert_toast("An error occured", 'error');
                        end_loader();
                        console.log(resp);
                    }
                }
            });
        });
    });
</script>
