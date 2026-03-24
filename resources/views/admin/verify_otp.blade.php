<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php $setting = \App\Models\Setting::first(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | {{$setting->bussiness_name}}</title>
    <link rel="shortcut icon" href="{{$setting->favicon}}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: url('{{asset("/public/admin/ChatGPT Image Nov 15_ 2025_ 04_23_23 PM.png")}}') no-repeat center center fixed;
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
            max-width: 500px;
            width: 90%;
        }
        
        .otp-title {
            color: white;
            font-size: 48px;
            font-weight: 300;
            margin-bottom: 20px;
            letter-spacing: 2px;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .otp-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
            font-weight: 300;
            margin-bottom: 30px;
            line-height: 1.5;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .form-container {
            margin-bottom: 25px;
        }
        
        .otp-input {
            width: 100%;
            padding: 18px 20px;
            font-size: 24px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
            font-family: monospace;
        }
        
        .otp-input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
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
            font-family: 'Times New Roman', Times, serif;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .resend-text {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin-top: 15px;
            margin-bottom: 5px;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .resend-link {
            color: #ffc107;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .resend-link:hover {
            text-decoration: underline;
            color: #ffdf7e;
        }
        
        .back-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 16px;
            font-weight: 300;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .back-link:hover {
            color: white;
            text-decoration: none;
            transform: translateX(-5px);
        }
        
        .back-link::before {
            content: '\2190';
            font-size: 18px;
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
        
        .error-text {
            color: #ff6b6b;
            font-size: 14px;
            margin-top: 5px;
            text-align: left;
            font-family: 'Times New Roman', Times, serif;
        }
        
        @media (max-width: 768px) {
            .otp-title {
                font-size: 36px;
            }
            
            .otp-subtitle {
                font-size: 16px;
            }
            
            .container {
                width: 95%;
                padding: 0 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="otp-title">VERIFY OTP</h1>
        <p class="otp-subtitle">
            We've sent a 6-digit OTP to your email address.<br>
            Please enter it below to reset your password.
        </p>
        
        <div class="form-container">
            @if(session()->has('error'))
                <div class="alert alert-danger">
                    {{ session()->get('error') }}
                </div>
            @endif
            @if(session()->has('success'))
                <div class="alert alert-success">
                    {{ session()->get('success') }}
                </div>
            @endif
            
            <form method="post" action="{{url('verify-otp')}}">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                
                <input type="text" name="otp" class="otp-input" placeholder="000000" maxlength="6" required pattern="[0-9]{6}">
                @if($errors->has('otp'))
                    <div class="error-text">{{ $errors->first('otp') }}</div>
                @endif
                
                <button type="submit" class="submit-btn">Verify OTP</button>
            </form>
            
            <div>
                <p class="resend-text">Didn't receive the code?</p>
                <a href="{{url('resend-otp?email=' . $email)}}" class="resend-link">Resend OTP</a>
            </div>
        </div>
        
        <a href="{{url('forget-password')}}" class="back-link">Back to Forgot Password</a>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var otpInput = document.querySelector('input[name="otp"]');
            if (otpInput) {
                otpInput.focus();
                otpInput.addEventListener('input', function (e) {
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                });
            }
        });
        
          setTimeout(function() {
        $('.alert-success, .alert-danger, .alert-warning, .alert-info').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
    </script>
</body>
</html>
