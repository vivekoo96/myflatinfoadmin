<!DOCTYPE html>
<html>
<head>
    <title>Login Credentials</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; text-align: center;">

<div style="max-width:600px; background:#ffffff; padding:20px; border-radius:10px;
            box-shadow:0px 0px 10px #ccc; margin:0 auto; text-align:center;">

    <!-- Logo -->
    <img src="{{ $logo }}" alt="MyFlatInfo" style="width:160px; display:block; margin:0 auto;">
    
    <h1 style="color:#333;">Hello {{ $user->name }}</h1>
   

    <h2 style="color:#333;">Your password for login in <strong>MyFlatInfo</strong> is</h2>
     <h1 style="color:#2d89ef; font-size:32px; margin-top:10px;">{{ $user->email }}</h1>
    <h1 style="color:#2d89ef; font-size:32px; margin-top:10px;">
        {{ $password }}
    </h1>

    <p style="font-size:16px; color:#555;">
       For security reasons, please change your password after your first login.
    </p>

    <hr style="border:none; border-top:1px solid #e5e5e5; margin:25px 0;">

    <!-- App Download Section -->
    <h3 style="color:#333; margin-bottom:15px;">Download Our Mobile Apps</h3>

    <!-- User App -->
    <p style="font-size:15px; color:#555; margin-bottom:8px;">
        <strong>MyFlatInfo (User App)</strong>
    </p>
    <a href="https://apps.apple.com/in/app/myflatinfo/id6747340557" target="_blank"
       style="display:inline-block; margin:5px;">
        Apple App Store
    </a>
    |
    <a href="https://play.google.com/store/apps/details?id=com.aits.myflatinfo" target="_blank"
       style="display:inline-block; margin:5px;">
        Google Play Store
    </a>

    <br><br>

    <!-- Roles App -->
    <p style="font-size:15px; color:#555; margin-bottom:8px;">
        <strong>MyFlatInfo Roles App</strong>
    </p>
    <a href="https://apps.apple.com/in/app/myflatinfo-roles/id6755715450" target="_blank"
       style="display:inline-block; margin:5px;">
         Apple App Store
    </a>
    |
    <a href="https://play.google.com/store/apps/details?id=com.aits.myflatinforoles" target="_blank"
       style="display:inline-block; margin:5px;">
        Google Play Store
    </a>

    <br><br>

    <!-- Security App -->
    <p style="font-size:15px; color:#555; margin-bottom:8px;">
        <strong>MyFlatInfo Security App</strong>
    </p>
    <a href="https://play.google.com/store/apps/details?id=com.aits.myflatinfosecurity" target="_blank"
       style="display:inline-block; margin:5px;">
        Google Play Store
    </a>

    <hr style="border:none; border-top:1px solid #e5e5e5; margin:25px 0;">

   <p style="font-size:14px; color:#888;">
    Need help? Contact
    <a href="mailto:support@myflatinfo.com"
       style="color:#333; text-decoration:none;">
        support@myflatinfo.com
    </a>
</p>

    <a href="https://myflatinfo.com/Privacy/" style="font-size:14px; color:#2d89ef;">
        Privacy Policy
    </a>

</div>

</body>
</html>
