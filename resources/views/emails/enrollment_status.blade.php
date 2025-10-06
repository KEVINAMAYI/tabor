<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Status Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header {
            background: #004085;
            color: #ffffff;
            text-align: center;
            padding: 20px 30px;
        }

        .content {
            padding: 30px;
            line-height: 1.6;
        }

        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            color: #fff;
            background: {{
                 $status === 'Approved' ? '#28a745' :
                 ($status === 'Rejected' ? '#dc3545' : '#ffc107')
            }};
        }

        .footer {
            background: #f1f1f1;
            text-align: center;
            font-size: 12px;
            color: #666;
            padding: 15px 30px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Enrollment Status Update</h2>
    </div>

    <div class="content">
        <h3>Hi {{ $user->name }},</h3>

        <p>We’d like to inform you of your current enrollment status for the program:</p>

        <p><strong>Program:</strong> {{ $program }}</p>
        <p><strong>Status:</strong> <span class="status">{{ ucfirst($status) }}</span></p>

        <p>Thank you for your interest and effort. Please contact the administration team if you need more
            information.</p>

        <p>Best regards,<br>
            <strong>Admissions Team</strong></p>
    </div>

    <div class="justify-content-center footer">
        This is an automated email. Please do not reply directly.<br>
        &copy; {{ date('Y') }} Your Institution. All rights reserved.
    </div>
</div>
</body>
</html>
