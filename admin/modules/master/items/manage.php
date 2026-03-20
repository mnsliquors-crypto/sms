<?php
require_once __DIR__ . '/../../../../config.php';
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `item_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k = $v;
        }
    }
}
?>
<div class="card card-outline card-primary shadow rounded-0">
    <div class="card-header">
        <h3 class="card-title"><?php echo isset($id) ? "Update Item Details - ".htmlspecialchars($name) : "Add New Item" ?></h3>
        <div class="card-tools">
            <a class="btn btn-flat btn-sm btn-dark" href="<?php echo base_url.'/admin?page=master/items'.(isset($id) ? '/view&id='.$id : '') ?>" title="Back"><i class="fa fa-angle-left"></i> Back</a>
        </div>
    </div>
    <div class="card-body">
        <form action="" id="item-form">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name" class="control-label text-info">Item Name</label>
                        <input type="text" name="name" id="name" class="form-control form-control-sm rounded-0" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="unit" class="control-label text-info">Unit</label>
                        <input type="text" name="unit" id="unit" class="form-control form-control-sm rounded-0" value="<?php echo isset($unit) ? $unit : ''; ?>" placeholder="(e.g. pcs)">
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="description" class="control-label">Description</label>
                        <textarea name="description" id="description" cols="30" rows="2" class="form-control form-control-sm rounded-0 no-resize"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="category_id" class="control-label text-info">Category</label>
                        <select name="category_id" id="category_id" class="form-control form-control-sm rounded-0 select2">
                            <option value="" <?php echo !isset($category_id) ? 'selected' : '' ?> disabled></option>
                            <?php 
                            $category = $conn->query("SELECT * FROM `category_list` where status = 1 order by `name` asc");
                            while($row = $category->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id'] ?>" <?php echo isset($category_id) && $category_id == $row['id'] ? "selected" : "" ?>><?php echo htmlspecialchars($row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="vendor_id" class="control-label text-info">Primary Vendor</label>
                        <select name="vendor_id" id="vendor_id" class="form-control form-control-sm rounded-0 select2">
                            <option value="" <?php echo !isset($vendor_id) ? 'selected' : '' ?>></option>
                            <?php 
                            $vendor = $conn->query("SELECT *, display_name as name FROM `entity_list` where entity_type = 'Supplier' and status = 1 order by `display_name` asc");
                            while($row = $vendor->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['id'] ?>" <?php echo isset($vendor_id) && $vendor_id == $row['id'] ? "selected" : "" ?>><?php echo htmlspecialchars($row['display_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tax_type" class="control-label text-info">Tax Category</label>
                        <select name="tax_type" id="tax_type" class="form-control form-control-sm rounded-0" data-no-select2>
                            <option value="1" <?php echo (!isset($tax_type) || $tax_type == 1) ? 'selected' : '' ?>>Taxable</option>
                            <option value="0" <?php echo (isset($tax_type) && $tax_type == 0) ? 'selected' : '' ?>>Non-Taxable</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="cost" class="control-label">Current Cost</label>
                        <input type="number" name="cost" id="cost" step="any" min="0" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($cost) ? $cost : '0'; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="selling_price" class="control-label">Selling Price</label>
                        <input type="number" name="selling_price" id="selling_price" step="any" min="0" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($selling_price) ? $selling_price : '0'; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="mrp" class="control-label">MRP</label>
                        <input type="number" name="mrp" id="mrp" step="any" min="0" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($mrp) ? $mrp : '0'; ?>" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="reorder_level" class="control-label text-danger">Reorder Level</label>
                        <input type="number" step="any" name="reorder_level" id="reorder_level" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($reorder_level) ? $reorder_level : 0; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="customFile" class="control-label">Product Image</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input rounded-0" id="customFile" name="img" onchange="displayImg(this,$(this))">
                            <label class="custom-file-label rounded-0" for="customFile">Choose file</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-center">
                    <img src="<?php echo validate_image(isset($image_path) ? $image_path : "") ?>" alt="" id="cimg" class="img-fluid img-thumbnail" style="max-height: 100px;">
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer py-2 text-center">
        <button type="submit" form="item-form" class="btn btn-primary btn-flat btn-sm">Save Item</button>
        <a class="btn btn-secondary btn-flat btn-sm" href="<?php echo base_url.'/admin?page=master/items'.(isset($id) ? '/view&id='.$id : '') ?>">Cancel</a>
    </div>
</div>
<script>
    function displayImg(input, _this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#cimg').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    $(document).ready(function(){
        // Global init_select2 handles select2 initialization
        
        $('#item-form').submit(function(e){
            e.preventDefault();
            var _this = $(this);
            $('.err-msg').remove();
            start_loader();
            
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_item",
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
                        location.replace(_base_url_ + "admin/?page=master/items/view&id=" + resp.id);
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
