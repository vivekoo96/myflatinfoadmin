<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;text-align: center;">
    <div style="max-width: 600px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px #ccc; margin: 0 auto; text-align: center;">
        
        <!-- Logo -->
        <img src="{{$logo}}" alt="MyFlatInfo" style="width: 160px;"><span style="color:white">MyFlatInfo</span>
        <h1>Hello {{$name}}</h1>
        <h2 style="color: #333;">We have received your request for a reset password</h2>
        <p style="color: #2d89ef; font-size: 14px; margin-top: 10px"><a href="{{ $link }}">{{ $link }}</a></p>
        
        <p style="font-size: 16px; color: #555;">This link can only be used once. It expires in 60 minutes</p>
        <br>
        <p style="font-size: 16px; color: #888;">Email or contact <span style="color: #333;">admin@myflatinfo.com</span></p>
        <p style="font-size: 16px; color: #333;"><span style="color: #2d89ef;">Privacy policy</span></p>
    </div>
</body>
</html>

