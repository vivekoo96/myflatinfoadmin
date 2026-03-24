<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #f4f4f4;
        }
        .logo {
            max-width: 150px;
            height: auto;
        }
        .content {
            padding: 30px 0;
        }
        .otp-code {
            background-color: #f8f9fa;
            border: 2px dashed #007bff;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 8px;
        }
        .otp-number {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #f4f4f4;
            color: #666;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($logo) && $logo)
            <img src="{{ $logo }}" alt="Logo" class="logo">
        @endif
        <h1>Password Reset Request</h1>
    </div>

    <div class="content">
        <p>Hello {{ $name }},</p>
        
        <p>You have requested to reset your password. Please use the following One-Time Password (OTP) to proceed with your password reset:</p>
        
        <div class="otp-code">
            <p style="margin: 0; font-size: 16px; color: #666;">Your OTP Code:</p>
            <div class="otp-number">{{ $otp }}</div>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">Valid for 10 minutes</p>
        </div>
        
        <div class="warning">
            <strong>Security Notice:</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>This OTP is valid for 10 minutes only</li>
                <li>Do not share this code with anyone</li>
                <li>If you didn't request this, please ignore this email</li>
            </ul>
        </div>
        
        <p>If you did not request a password reset, please ignore this email and your password will remain unchanged.</p>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} Building Admin System. All rights reserved.</p>
    </div>
</body>
</html>
