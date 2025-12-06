<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>OTP Verification - Maximus Hotel</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, sans-serif !important;}
    </style>
    <![endif]-->
    <style>
        /* Reset styles */
        body, table, td, p, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }
        
        /* Main styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, Arial, sans-serif;
            background-color: #f4f4f4;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 20px 0;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .email-header {
            background: linear-gradient(135deg, #084466 0%, #0a5a7a 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .email-body {
            padding: 40px 30px;
        }
        
        .email-body h2 {
            color: #084466;
            font-size: 22px;
            font-weight: 600;
            margin: 0 0 20px 0;
            line-height: 1.4;
        }
        
        .email-body p {
            color: #333333;
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 20px 0;
        }
        
        .otp-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 3px solid #084466;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 2px 8px rgba(8, 68, 102, 0.15);
        }
        
        .otp-label {
            color: #666666;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 15px 0;
        }
        
        .otp-code {
            font-size: 42px;
            font-weight: 700;
            color: #084466;
            letter-spacing: 8px;
            margin: 0;
            font-family: 'Courier New', monospace;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .warning-box {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            border-radius: 6px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .warning-box strong {
            color: #856404;
            font-size: 15px;
            display: block;
            margin-bottom: 8px;
        }
        
        .warning-box p {
            color: #856404;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 5px solid #084466;
            border-radius: 6px;
            padding: 15px 20px;
            margin: 20px 0;
        }
        
        .info-box p {
            color: #084466;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
        
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .email-footer p {
            color: #666666;
            font-size: 12px;
            line-height: 1.6;
            margin: 5px 0;
        }
        
        .email-footer .copyright {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            color: #999999;
        }
        
        /* Mobile responsive */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            
            .email-container {
                border-radius: 8px;
            }
            
            .email-header {
                padding: 30px 20px;
            }
            
            .email-header h1 {
                font-size: 24px;
            }
            
            .email-body {
                padding: 30px 20px;
            }
            
            .email-body h2 {
                font-size: 20px;
            }
            
            .email-body p {
                font-size: 15px;
            }
            
            .otp-container {
                padding: 25px 15px;
                margin: 25px 0;
            }
            
            .otp-code {
                font-size: 36px;
                letter-spacing: 6px;
            }
            
            .warning-box,
            .info-box {
                padding: 15px;
            }
            
            .email-footer {
                padding: 20px 15px;
            }
        }
        
        @media only screen and (max-width: 480px) {
            .otp-code {
                font-size: 32px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1>Maximus Hotel</h1>
            </div>
            
            <!-- Body -->
            <div class="email-body">
                <h2>{{ $purpose === 'password_reset' ? 'Password Reset Verification' : 'Email Verification' }}</h2>
                
                <p>Hello,</p>
                
                <p>
                    @if($purpose === 'password_reset')
                        You have requested to reset your password. Please use the verification code below to complete the process.
                    @else
                        Thank you for registering with Maximus Hotel! To complete your registration, please verify your email address using the code below.
                    @endif
                </p>
                
                <!-- OTP Code Box -->
                <div class="otp-container">
                    <p class="otp-label">Your Verification Code</p>
                    <div class="otp-code">{{ $otp }}</div>
                </div>
                
                <!-- Warning Box -->
                <div class="warning-box">
                    <strong>⚠️ Security Notice</strong>
                    <p>This code will expire in <strong>10 minutes</strong>. For your security, please do not share this code with anyone. Maximus Hotel staff will never ask for your verification code.</p>
                </div>
                
                <!-- Info Box -->
                <div class="info-box">
                    <p>
                        @if($purpose === 'password_reset')
                            If you did not request a password reset, please ignore this email. Your account remains secure.
                        @else
                            If you did not create an account with Maximus Hotel, please disregard this email.
                        @endif
                    </p>
                </div>
                
                <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                
                <p>
                    Best regards,<br>
                    <strong>The Maximus Hotel Team</strong>
                </p>
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <p><strong>Maximus Hotel</strong></p>
                <p>Your trusted hospitality partner</p>
                <div class="copyright">
                    <p>&copy; {{ date('Y') }} Maximus Hotel. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
