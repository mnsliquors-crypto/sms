<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
<body class="hold-transition bg-light">
  <script>
    start_loader()
  </script>
  <style>
    body {
      background-color: #f3f7fa;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
    .main-wrapper {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .brand-logo {
      text-align: center;
      margin-bottom: 30px;
    }
    .brand-logo h2 {
      color: #1886fb;
      font-weight: 700;
      font-size: 1.6rem;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .login-container {
      display: flex;
      background: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      width: 800px;
      max-width: 95%;
      min-height: 450px;
    }
    .login-left {
      background: #fcfdfe;
      padding: 60px 40px;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border-right: 1px solid #eef1f4;
    }
    .login-left h3 {
      font-size: 1.05rem;
      color: #8392a5;
      margin-bottom: 25px;
      font-weight: 600;
    }
    .testimonial-card {
      background: #ffffff;
      border: 1px solid #eef1f4;
      border-radius: 6px;
      padding: 25px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.02);
      position: relative;
      width: 100%;
      max-width: 280px;
    }
    .testimonial-card .author {
      font-size: 0.75rem;
      color: #aeb9c5;
      font-weight: 700;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .testimonial-card p {
      color: #4a5568;
      font-size: 0.9rem;
      line-height: 1.6;
      margin: 0;
    }
    .avatar {
      position: absolute;
      bottom: -18px;
      right: 25px;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #e9ecef;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
      border: 2px solid #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .learn-more {
      margin-top: 45px;
      text-align: center;
    }
    .learn-more a {
      color: #6c757d;
      text-decoration: none;
      font-size: 0.85rem;
      border: 1px solid #eef1f4;
      padding: 8px 16px;
      border-radius: 4px;
      transition: all 0.2s;
      background: #fff;
      font-weight: 500;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .learn-more a:hover {
      background: #f8f9fa;
      color: #495057;
    }
    .login-right {
      padding: 50px 70px;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .login-right h2 {
      font-size: 1.3rem;
      color: #212529;
      font-weight: 700;
      text-align: center;
      margin-bottom: 30px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-control {
      background: #ffffff;
      border: 1px solid #dce1e6;
      border-radius: 4px;
      padding: 12px 15px;
      font-size: 0.9rem;
      color: #495057;
      width: 100%;
      height: 45px;
      box-shadow: none;
      transition: border-color 0.2s;
    }
    .form-control::placeholder {
      color: #adb5bd;
    }
    .form-control:focus {
      border-color: #1886fb;
      outline: none;
      box-shadow: 0 0 0 3px rgba(24,134,251,.1);
    }
    .custom-control {
      margin-bottom: 25px;
      display: flex;
      align-items: center;
    }
    .custom-control-input {
      margin-right: 8px;
    }
    .custom-control-label {
      font-size: 0.85rem;
      color: #6c757d;
      cursor: pointer;
    }
    .btn-primary {
      background: #1886fb;
      border: none;
      border-radius: 4px;
      color: #fff;
      font-weight: 600;
      padding: 12px;
      width: 100%;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.2s;
      height: 45px;
    }
    .btn-primary:hover {
      background: #0d73df;
    }
    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      color: #adb5bd;
      font-size: 0.85rem;
      margin: 15px 0;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid #eef1f4;
    }
    .divider:not(:empty)::before {
      margin-right: .5em;
    }
    .divider:not(:empty)::after {
      margin-left: .5em;
    }
    .btn-google {
      background: #ffffff;
      border: 1px solid #dce1e6;
      border-radius: 4px;
      color: #6c757d;
      font-weight: 500;
      padding: 12px;
      width: 100%;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.2s;
      height: 45px;
    }
    .btn-google:hover {
      background: #f8f9fa;
      color: #495057;
    }
    .forgot-password {
      text-align: center;
      margin-top: 25px;
    }
    .forgot-password a {
      color: #aeb9c5;
      font-size: 0.85rem;
      text-decoration: none;
    }
    .forgot-password a:hover {
      color: #6c757d;
    }
    .footer-text {
      text-align: center;
      margin-top: 35px;
      color: #aeb9c5;
      font-size: 0.85rem;
    }
    .footer-text a {
      color: #1886fb;
      text-decoration: none;
      font-weight: 500;
      margin-left: 5px;
    }
    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
        width: 100%;
        margin: 0 20px;
      }
      .login-left {
        display: none;
      }
      .login-right {
        padding: 40px 30px;
      }
    }
  </style>

<div class="main-wrapper">
  <div class="brand-logo">
    <h2><i class="fas fa-boxes"></i> <?php echo $_settings->info('name') ?></h2>
  </div>

  <div class="login-container">
    <!-- Left Information Panel -->
    <div class="login-left">
      <h3>Messages for Beacon</h3>
      <div class="testimonial-card">
        <div class="author">Admin Team</div>
        <p>Just checking in to see how things are going. We're around to chat if you need any assistance...</p>
        <div class="avatar">
           <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="Avatar" onerror="this.src='<?php echo base_url ?>uploads/logo.png'">
        </div>
      </div>
      <div class="learn-more">
        <a href="#">Learn More</a>
      </div>
    </div>
    
    <!-- Right Login Form Panel -->
    <div class="login-right">
      <h2>Log In</h2>
      <form id="login-frm" action="" method="post">
        <div class="form-group">
          <input type="text" class="form-control" autofocus name="username" placeholder="Username">
        </div>
        <div class="form-group">
          <input type="password" class="form-control" name="password" placeholder="Password">
        </div>
        <div class="custom-control">
          <input type="checkbox" class="custom-control-input" id="rememberMe" checked>
          <label class="custom-control-label" for="rememberMe">Remember me for 30 days</label>
        </div>
        <button type="submit" class="btn btn-primary">Log In</button>
      </form>
      
      <div class="divider">or</div>
      
      <button type="button" class="btn-google">
        <i class="fab fa-google" style="color: #db4437;"></i> Loading...
      </button>

      <div class="forgot-password">
        <a href="#">Forgot your password?</a>
      </div>
    </div>
  </div>

  <div class="footer-text">
    Looking for a better way to talk with customers? <a href="#">Try <?php echo $_settings->info('name') ?></a>
  </div>
</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>

<script>
  $(document).ready(function(){
    end_loader();
  })
</script>
</body>
</html>