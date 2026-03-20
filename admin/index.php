<?php require_once('../config.php'); ?>
 <!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
  <body class="hold-transition">
    <div class="erp-wrapper">
     <?php require_once('inc/topBarNav.php') ?>
     <main class="erp-main" style="margin-left: 0 !important; width: 100% !important;">
     <?php if($_settings->chk_flashdata('success')): ?>
      <script>
        $(function(){
            alert_toast("<?php echo $_settings->flashdata('success') ?>",'success');
        });
      </script>
      <?php endif;?>    
     <?php $page = isset($_GET['page']) ? $_GET['page'] : 'home';  ?>
      
        <!-- Main content -->
        <section class="content ">
          <div class="container-fluid">
            <?php 
              $include_file = "";
              
              // 1. Check for files in 'pages/'
              if(file_exists("pages/".$page.".php")){
                  $include_file = "pages/".$page.".php";
              } 
              // 2. Check for files in 'modules/'
              else {
                  // If page already has category (e.g. 'master/vendors'), check 'modules/' directly
                  if(strpos($page, '/') !== false){
                      $module_path = "modules/".$page;
                      if(file_exists($module_path.".php")) {
                          $include_file = $module_path.".php";
                      } elseif(is_dir($module_path)) {
                          if(file_exists($module_path."/index.php")){
                              $include_file = $module_path."/index.php";
                          }
                      }
                  }
                  
                  // If not resolved yet, check against categories
                  if(!$include_file){
                      $categories = ['master', 'transactions', 'finance', 'system'];
                      foreach($categories as $cat) {
                          $module_path = "modules/{$cat}/{$page}";
                          if(file_exists($module_path.".php")) {
                              $include_file = $module_path.".php";
                              break;
                          } elseif(is_dir($module_path)) {
                              $include_file = $module_path."/index.php";
                              break;
                          }
                      }
                  }
              }
              
              // 3. Last fallback to root
              if(!$include_file){
                  if(file_exists($page.".php")){
                      $include_file = $page.".php";
                  } elseif(is_dir($page)){
                      $include_file = $page."/index.php";
                  }
              }

              // Include the resolved file or 404
              if($include_file && file_exists($include_file)){
                  include $include_file;
              }else{
                  echo '<div class="text-center py-5">
                      <h1 class="display-4 text-muted"><i class="fas fa-exclamation-triangle"></i></h1>
                      <h3 class="text-muted">404 - Page Not Found</h3>
                      <p class="text-muted">The page <code>'.htmlspecialchars($page).'</code> could not be found.</p>
                      <a href="./" class="btn btn-primary btn-sm"><i class="fa fa-home"></i> Go to Dashboard</a>
                  </div>';
              }
            ?>
          </div>
        </section>
        <!-- /.content -->
  <div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal_right" role='dialog'>
    <div class="modal-dialog modal-full-height  modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span class="fa fa-arrow-right"></span>
        </button>
      </div>
      <div class="modal-body">
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="viewer_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
              <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
              <img src="" alt="">
      </div>
    </div>
  </div>
        <?php require_once(__DIR__ . '/inc/erp_footer.php') ?>
      </main> <!-- /.erp-main -->
    </div> <!-- /.erp-wrapper -->
  </body>
</html>
