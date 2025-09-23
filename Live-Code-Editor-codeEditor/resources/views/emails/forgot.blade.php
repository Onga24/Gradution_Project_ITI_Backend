<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collab App - Reset Password OTP</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .header { background-color: #e63946; color: #ffffff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; text-align: center; color: #333; }
        .content h2 { margin-bottom: 20px; color: #e63946; }
        .otp-box { font-size: 28px; font-weight: bold; letter-spacing: 5px; background: #f3f4f6; padding: 15px 20px; border-radius: 8px; display: inline-block; margin: 20px 0; }
        .footer { background: #f9fafb; color: #555; padding: 15px; font-size: 13px; text-align: center; }
        .footer a { color: #e63946; text-decoration: none; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Collab App</h1>
        </div>

        <div class="content">
            <h2>Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password. Use the OTP below to proceed:</p>
            
            <div class="otp-box">{{ $otp }}</div>

            <p>This code will expire in <strong>5 minutes</strong>. If you did not request a password reset, please ignore this email.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Collab App. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
