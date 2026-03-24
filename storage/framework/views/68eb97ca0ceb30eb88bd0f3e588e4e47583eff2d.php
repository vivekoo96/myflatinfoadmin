<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php $setting = \App\Models\Setting::first(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo e(asset('public/vendor/css/login.css')); ?>"/>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <title>Reset Password</title>
    <style>
        @import  url('https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
        body{font-family:Jost;}
        .password-icon{
            float: right;
            margin-top: -22px;
            color:black;
        }
        .signin-image{width:25% !important;}
        .signin-form{width:75% !important;}
        .password-placeholder {margin-top:-23px !important;margin-bottom:10px !important;font-size: 12px !important;}
        @media  screen and (max-width: 768px) {
            .container{width:100% !important;}
            .signin-form{width:90% !important;}
            .signin-image {width: 100% !important;}
            .password-placeholder {font-size: 8px !important;}
        }
        input[type="password"]{
            color: black;
        }
        input[type="email"]{
            color: black !important;
        }
        input::placeholder{
            color: black;
        }
    </style>
</head>
<body>
    <div class="main">

        <!-- Sign up form -->
        <section class="sign-in">
            <div class="container">
                <div class="signin-content">
                    <div class="signin-image">
                        <figure><img src="<?php echo e($setting->logo); ?>" alt="sing up image"></figure>
                        <a href="<?php echo e(url('/')); ?>" class="signup-image-link">Login Here</a>
                    </div>

                    <div class="signin-form">
                        <h2 class="form-title">Reset Password</h2>
                        <?php if(session()->has('error')): ?>
                            <div class="alert alert-danger">
                                <?php echo e(session()->get('error')); ?>

                            </div>
                        <?php endif; ?>
                        <form method="post" action="<?php echo e(url('reset-password')); ?>" class="register-form" id="login-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="token" value="<?php echo e($password_reset->token); ?>">
                            <input type="hidden" name="email" value="<?php echo e($password_reset->email); ?>">
                            <div class="form-group">
                                <label for="your_pass"><i class="fa fa-envelope"></i></label>
                                <input type="email" name="old_email" value="<?php echo e($password_reset->email); ?>" disabled required>
                            </div>
                            
                            <div class="form-group">
                                <label for="pass"><i class="fa fa-lock"></i></label>
                                <input type="password" name="password" class="password1 form-control" id="password" minlength="8" maxlength="14" style="width:95%;"
                                placeholder="New Password" required/>
                                <a href="javascript:void(0)" class="show-password1 password-icon"><i class="fa fa-eye-slash"></i></a>
                                <a href="javascript:void(0)" class="hide-password1 password-icon" style="display:none;"><i class="fa fa-eye" aria-hidden="true"></i></a>
                            </div>
                            <p class="password-placeholder">Enter a combination of at least eight characters and punctuation marks (such as ! and &)</p>
                                <?php if($errors->has('password')): ?>
                                    <p style="color:red"><?php echo e($errors->first('password')); ?></p>
                                <?php endif; ?>
                            <div class="form-group">
                                <label for="re-pass"><i class="fa fa-lock"></i></label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" class="password2" minlength="8" maxlength="14" id="re_pass" style="width:95%;" placeholder="Confirm Password"/>
                                    <a href="javascript:void(0)" class="show-password2 password-icon"><i class="fa fa-eye-slash"></i></a>
                                    <a href="javascript:void(0)" class="hide-password2 password-icon" style="display:none;"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                </div>
                            </div>
                            
                            <div class="form-group form-button">
                            	<input type="submit" id="signin" class="form-submit" value="Change Password"/>
                            </div>
                            
                        </form>
                        
                    </div>
                </div>
            </div>
        </section>
    </div>
    
        <!-- jQuery -->
    <script src="<?php echo e(asset('public/admin/plugins/jquery/jquery.min.js')); ?>"></script>
    
    <script>
        $(document).ready(function(){
            // For first password field
            $(document).on('click','.show-password1',function(){
                $('.password1').attr('type','text');
                $('.show-password1').hide();
                $('.hide-password1').show();
            });
            $(document).on('click','.hide-password1',function(){
                $('.password1').attr('type','password');
                $('.hide-password1').hide();
                $('.show-password1').show();
            });

            // For confirm password field
            $(document).on('click','.show-password2',function(){
                $('.password2').attr('type','text');
                $('.show-password2').hide();
                $('.hide-password2').show();
            });
            $(document).on('click','.hide-password2',function(){
                $('.password2').attr('type','password');
                $('.hide-password2').hide();
                $('.show-password2').show();
            });
        });
    </script>
    
</body>
</html><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/reset_password.blade.php ENDPATH**/ ?>