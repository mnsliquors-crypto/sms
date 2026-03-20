<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
	img#cimg{
		height: 15vh;
		width: 15vh;
		object-fit: scale-down;
		border-radius: 100% 100%;
	}
	img#cimg2{
		height: 50vh;
		width: 100%;
		object-fit: contain;
		/* border-radius: 100% 100%; */
	}
</style>
<div class="col-lg-12">
	<div class="card card-outline card-primary">
		<div class="card-header">
			<h5 class="card-title">System Information</h5>
			<!-- <div class="card-tools">
				<a class="btn btn-block btn-sm btn-default btn-flat border-primary new_department" href="javascript:void(0)"><i class="fa fa-plus"></i> Add New</a>
			</div> -->
		</div>
		<div class="card-body">
			<form action="" id="system-frm">
			<div id="msg" class="form-group"></div>
			<div class="form-group">
				<label for="name" class="control-label">System Name</label>
				<input type="text" class="form-control form-control-sm" name="name" id="name" value="<?php echo $_settings->info('name') ?>">
			</div>
			<div class="form-group">
				<label for="short_name" class="control-label">System Short Name</label>
				<input type="text" class="form-control form-control-sm" name="short_name" id="short_name" value="<?php echo  $_settings->info('short_name') ?>">
			</div>
			<div class="form-group">
				<label for="address" class="control-label">Company Address</label>
				<textarea rows="3" class="form-control form-control-sm" name="address" id="address"><?php echo $_settings->info('address') ?></textarea>
			</div>
			<div class="form-group">
				<label for="contact" class="control-label">Contact No</label>
				<input type="text" class="form-control form-control-sm" name="contact" id="contact" value="<?php echo $_settings->info('contact') ?>">
			</div>
			<div class="form-group">
				<label for="pan_no" class="control-label">PAN No</label>
				<input type="text" class="form-control form-control-sm" name="pan_no" id="pan_no" value="<?php echo $_settings->info('pan_no') ?>">
			</div>
			<div class="form-group">
				<label for="email" class="control-label">Email Address</label>
				<input type="email" class="form-control form-control-sm" name="email" id="email" value="<?php echo $_settings->info('email') ?>">
			</div>
			<!-- <div class="form-group">
				<label for="content[about_us]" class="control-label">About Us</label>
				<textarea type="text" class="form-control form-control-sm summernote" name="content[about_us]" id="about_us"><?php echo  is_file(base_app.'about_us.html') ? file_get_contents(base_app.'about_us.html') : '' ?></textarea>
			</div> -->
			<div class="form-group">
				<label for="" class="control-label">System Logo</label>
				<div class="custom-file">
	              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
	              <label class="custom-file-label" for="customFile">Choose file</label>
	            </div>
			</div>
			<div class="form-group d-flex justify-content-center">
				<img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
			</div>
			<div class="form-group">
				<label for="" class="control-label">Cover</label>
				<div class="custom-file">
	              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="cover" onchange="displayImg2(this,$(this))">
	              <label class="custom-file-label" for="customFile">Choose file</label>
	            </div>
			</div>
			<div class="form-group d-flex justify-content-center">
				<img src="<?php echo validate_image($_settings->info('cover')) ?>" alt="" id="cimg2" class="img-fluid img-thumbnail">
			</div>
			<hr>
			<h5 class="text-primary mt-4">Print Settings</h5>
			<div class="row">
				<div class="col-md-6 border-right">
					<div class="form-group text-dark mb-0">
						<label for="print_title" class="control-label small">Invoice Document Title</label>
						<input type="text" class="form-control form-control-sm" name="print_title" id="print_title" value="<?php echo $_settings->info('print_title') ?: 'Tax Invoice' ?>" placeholder="e.g. Tax Invoice, Sales Receipt">
					</div>
					<div class="form-group text-dark mb-0 mt-2">
						<label for="print_logo_show" class="control-label small">Show Company Logo on Print</label>
						<select name="print_logo_show" id="print_logo_show" class="form-control form-control-sm">
							<option value="1" <?php echo $_settings->info('print_logo_show') == 1 ? 'selected' : '' ?>>Show</option>
							<option value="0" <?php echo $_settings->info('print_logo_show') == 0 ? 'selected' : '' ?>>Hide</option>
						</select>
					</div>
					<div class="form-group text-dark mb-0 mt-2">
						<label for="print_header_cols" class="control-label small">Header Column Layout</label>
						<select name="print_header_cols" id="print_header_cols" class="form-control form-control-sm">
							<option value="2" <?php echo $_settings->info('print_header_cols') == 2 ? 'selected' : '' ?>>2 Columns</option>
							<option value="4" <?php echo $_settings->info('print_header_cols') == 4 ? 'selected' : '' ?>>4 Columns</option>
						</select>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group text-dark mb-0">
						<label for="print_remarks_pos" class="control-label small">Remarks Position</label>
						<select name="print_remarks_pos" id="print_remarks_pos" class="form-control form-control-sm">
							<option value="above" <?php echo $_settings->info('print_remarks_pos') == 'above' ? 'selected' : '' ?>>Above Item Table</option>
							<option value="below" <?php echo $_settings->info('print_remarks_pos') == 'below' ? 'selected' : '' ?>>Below Item Table</option>
						</select>
					</div>
					<div class="form-group text-dark mb-0 mt-2">
						<label for="print_footer_text" class="control-label small">Footer Text/Page Numbering</label>
						<input type="text" class="form-control form-control-sm" name="print_footer_text" id="print_footer_text" value="<?php echo $_settings->info('print_footer_text') ?: 'Page 1 of 1' ?>" placeholder="e.g. Page 1 of 1, Certified Documents">
					</div>
					<div class="form-group text-dark mb-0 mt-2">
						<label for="print_invoice_count" class="control-label small">Invoice Print Copies</label>
						<input type="number" step="1" min="1" class="form-control form-control-sm" name="print_invoice_count" id="print_invoice_count" value="<?php echo $_settings->info('print_invoice_count') ?: 1 ?>">
					</div>
				</div>
			</div>
			</form>
			
		<div class="card-footer">
			<div class="col-md-12">
				<div class="row">
					<button class="btn btn-sm btn-primary" form="system-frm">Update</button>
				</div>
			</div>
		</div>

	</div>
</div>
<script>
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        	_this.siblings('.custom-file-label').html(input.files[0].name)
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	function displayImg2(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	_this.siblings('.custom-file-label').html(input.files[0].name)
	        	$('#cimg2').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	function displayImg3(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	_this.siblings('.custom-file-label').html(input.files[0].name)
	        	$('#cimg3').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	$(document).ready(function(){
		 $('.summernote').summernote({
		        height: 200,
		        toolbar: [
		            [ 'style', [ 'style' ] ],
		            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear'] ],
		            [ 'fontname', [ 'fontname' ] ],
		            [ 'fontsize', [ 'fontsize' ] ],
		            [ 'color', [ 'color' ] ],
		            [ 'para', [ 'ol', 'ul', 'paragraph', 'height' ] ],
		            [ 'table', [ 'table' ] ],
		            [ 'view', [ 'undo', 'redo', 'fullscreen', 'codeview', 'help' ] ]
		        ]
		    })

		$('#system-frm').submit(function(e){
			e.preventDefault()
			start_loader()
			$.ajax({
				url:_base_url_+'classes/SystemSettings.php?f=update_settings',
				data: new FormData($(this)[0]),
				cache: false,
				contentType: false,
				processData: false,
				method: 'POST',
				type: 'POST',
				success:function(resp){
					if(resp == 1){
						location.reload()
					}else{
						$('#msg').html('<div class="alert alert-danger">Error updating settings.</div>')
						end_loader()
					}
				}
			})
		})
	})
</script>