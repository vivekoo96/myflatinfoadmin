<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php $setting = \App\Models\Setting::first(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | {{$setting->bussiness_name}}</title>
    <link rel="shortcut icon" href="{{$setting->favicon}}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: url('{{url("/public/admin/ChatGPT Image Nov 15_ 2025_ 04_23_23 PM.png")}}') no-repeat center center fixed;
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
        
        .forgot-title {
            color: white;
            font-size: 48px;
            font-weight: 300;
            margin-bottom: 20px;
            letter-spacing: 2px;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .forgot-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 18px;
            font-weight: 300;
            margin-bottom: 40px;
            line-height: 1.5;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .form-container {
            margin-bottom: 30px;
        }
        
        .email-input {
            width: 100%;
            padding: 18px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .email-input:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        }
        
        .email-input::placeholder {
            color: black;
            font-weight: 300;
            font-family: 'Times New Roman', Times, serif;
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
        }
        
        .submit-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
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
            .forgot-title {
                font-size: 36px;
            }
            
            .forgot-subtitle {
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
        <h1 class="forgot-title">FORGOT PASSWORD</h1>
        <p class="forgot-subtitle">Enter your email address to receive an OTP reset link.</p>
        
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
            
            <form method="post" action="{{url('forget-password')}}">
                @csrf
                <input type="hidden" name="reset_method" value="otp">
                <input type="email" name="email" class="email-input" placeholder="Email Address" required>
                @if($errors->has('email'))
                    <div class="error-text">{{ $errors->first('email') }}</div>
                @endif
                
                <button type="submit" class="submit-btn">Send Reset Request</button>
            </form>
        </div>
        
        <a href="{{url('/')}}" class="back-link">Back to Login</a>
    </div>
</body>
</html>