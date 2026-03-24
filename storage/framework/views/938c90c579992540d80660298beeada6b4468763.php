<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;text-align: center;">
    <div style="max-width: 600px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px #ccc; margin: 0 auto; text-align: center;">
        
        <!-- Logo -->
        <img src="<?php echo e($logo); ?>" alt="MyFlatInfo" style="width: 160px;"><span style="color:white">MyFlatInfo</span>
        <h1>Hello <?php echo e($user->name); ?></h1>
        <h2 style="color: #333;">Your one time code is</h2>
        <h1 style="color: #2d89ef; font-size: 32px; margin-top: 10px"><?php echo e($otp); ?></h1>
        <p style="font-size: 16px; color: #555;">This code can only be used once. It expires in 60 minutes</p>
        <br>
        <p style="font-size: 16px; color: #888;">Email or contact <span style="color: #333;">noreply@myflatinfo.com</span></p>
        <p style="font-size: 16px; color: #333;"><span style="color: #2d89ef;">Privacy policy</span></p>
    </div>
</body>
</html>
<?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/email/forget_password2.blade.php ENDPATH**/ ?>