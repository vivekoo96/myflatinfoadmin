<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="Ml63mUEFyaGvIM0h52l8aZ6cEGkZ61t2Jw0t9jhv" />
    <title>Building Admin Panel | <?php echo e($setting->bussiness_name); ?></title>
    <link rel="shortcut icon" href="<?php echo e($setting->favicon); ?>">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: url('<?php echo e(url("/public/admin/ChatGPT Image Nov 15_ 2025_ 04_23_23 PM.png")); ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        /* Dark overlay */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(37, 62, 88, 0.6);
            z-index: 1;
        }
        
        .container {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 450px;
            width: 90%;
        }
        
        .login-title {
            color: white;
            font-size: 48px;
            font-weight: 300;
            margin-bottom: 10px;
            letter-spacing: 2px;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .login-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
            font-weight: 300;
            margin-bottom: 30px;
            line-height: 1.5;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .logo-container {
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-width: 300px;
            height: auto;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px;
        }
        
        .form-container {
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            color: white;
            font-size: 14px;
            font-weight: 300;
            margin-bottom: 8px;
            display: block;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 18px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            transition: all 0.3s ease;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .form-input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        }
        
        .form-input::placeholder {
            color: black;
            font-weight: 300;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-append {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: black;
        }
        
        .submit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .submit-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .forgot-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            font-weight: 300;
            transition: all 0.3s ease;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .forgot-link:hover {
            color: white;
            text-decoration: none;
        }
        
        .footer-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            margin-top: 30px;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .footer-text a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }
        
        .footer-text a:hover {
            color: white;
            text-decoration: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 400;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.9);
            color: white;
            border: none;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
        }
        
        @media (max-width: 768px) {
            .login-title {
                font-size: 36px;
            }
            
            .login-subtitle {
                font-size: 16px;
            }
            
            .container {
                width: 95%;
                padding: 0 20px;
            }
            
            .logo-container img {
                max-width: 120px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
          <div class="logo-container">
            <img src="<?php echo e($setting->logo); ?>" alt="Logo">
        </div>
        <h1 class="login-title">BUILDING ADMIN</h1>
        <p class="login-subtitle">Welcome back! Please sign in to your account.</p>
        
      
        
        <div class="form-container">
            <?php if(session()->has('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session()->get('error')); ?>

                </div>
            <?php endif; ?>
            <?php if(session()->has('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session()->get('success')); ?>

                </div>
            <?php endif; ?>
            
            <form action="<?php echo e(url('login')); ?>" method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="device_token" id="device_token" value="">
                
                <div class="form-group">
                    <label>Email or Phone</label>
                    <input type="text" name="email" class="form-input" placeholder="Enter your email or phone" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-input password" placeholder="Enter your password" minlength="8" maxlength="14" id="re_pass" required>
                        <div class="input-group-append">
                            <span class="show-password password-icon"><i class="fa fa-eye-slash"></i></span>
                            <span class="hide-password password-icon" style="display:none;"><i class="fa fa-eye"></i></span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="submit-btn">LOGIN</button>
                </div>
                
                <!--
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?php echo e(url('/')); ?>" class="text-danger" target="_blank">Go to User Panel</a>
                    </div>
                </div>
                -->
                
                <div class="form-group" style="text-align: right;">
                    <a href="<?php echo e(url('forget-password')); ?>" class="forgot-link">Forgot Password?</a>
                </div>
            </form>
        </div>
        
        <div class="footer-text">
            <p><?php echo e(date('Y')); ?> &copy; Copyright
                <a href="https://myflatinfo.com/home/" target="_blank">My Flat Info</a>
            </p>
            <p>Designed by <a href="https://analogueitsolutions.com/" target="_blank">Analogue IT Solutions</a> Pvt Ltd</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $('.hide-password').hide();
            
            $(document).on('click','.show-password',function(){
                $('.password').attr('type','text');
                $('.show-password').hide();
                $('.hide-password').show();
            });
            $(document).on('click','.hide-password',function(){
                $('.password').attr('type','password');
                $('.hide-password').hide();
                $('.show-password').show();
            });
        });
    </script>
    <script>
        $(document).ready(function(){
            window.history.pushState(null, "", window.location.href);
            window.onpopstate = function () {
                window.history.pushState(null, "", window.location.href);
            };
        });
    </script>
    
</body>

<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/9.9.3/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/9.9.3/firebase-analytics.js";
  import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/9.9.3/firebase-messaging.js";

  const firebaseConfig = {

    apiKey: "AIzaSyCMIXqJyXbmxmFMdywPuFbYd6cRUx-l6nc",

    authDomain: "school-979f6.firebaseapp.com",

    databaseURL: "https://school-979f6.firebaseio.com",

    projectId: "school-979f6",

    storageBucket: "school-979f6.appspot.com",

    messagingSenderId: "308636612449",

    appId: "1:308636612449:web:603eb003f33921ad9db720",

    measurementId: "G-45CQ9YMRNN"

  };


  // Initialize Firebase

  const app = initializeApp(firebaseConfig);

  const analytics = getAnalytics(app);
  
  
    import { getMessaging, getToken } from "https://www.gstatic.com/firebasejs/9.9.3/firebase-messaging.js";

    const messaging = getMessaging();
    getToken(messaging, { vapidKey: 'BALAriAKrgC8UL3txmvobWMeu2wRZ4g-7wX4TxOI6-JzE9b5oZWUMwUANBJ2w0V3glmlsBGIV0SNlofoApPf9e0' }).then((currentToken) => {
      if (currentToken) {
        document.getElementById("device_token").value = currentToken;
      } else {
        // Show permission request UI
        console.log('No registration token available. Request permission to generate one.');
        // ...
      }
    }).catch((err) => {
      console.log('An error occurred while retrieving token. ', err);
      // ...
    });
    
    
      setTimeout(function() {
        $('.alert-success, .alert-danger, .alert-warning, .alert-info').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);

</script>

</html><?php /**PATH C:\Users\vvive\Herd\myflatinfos\admin\resources\views/admin/login.blade.php ENDPATH**/ ?>