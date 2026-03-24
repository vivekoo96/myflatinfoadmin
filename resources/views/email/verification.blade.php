<!DOCTYPE html>
<html>
<head>
    <title>Account Registration Received</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;text-align: center;">
    <div style="max-width: 600px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px #ccc; margin: 0 auto; text-align: center;">
        
        <!-- Logo -->
        <img src="{{$logo}}" alt="MyFlatInfo" style="width: 160px;"><span style="color:white">MyFlatInfo</span>
        <h1>Hello {{$user->name}}</h1>
        <p style="font-size: 16px; color: #555;">Thank you for registering. We are currently reviewing your account, and you will be notified shortly once the review is complete.</p>
        <br>
        <p style="font-size: 16px; color: #888;">Email or contact <span style="color: #333;">noreply@myflatinfo.com</span></p>
        <p style="font-size: 16px; color: #333;"><span style="color: #2d89ef;">Privacy policy</span></p>
    </div>
</body>
</html>
