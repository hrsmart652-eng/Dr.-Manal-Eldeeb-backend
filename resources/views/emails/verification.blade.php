<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .code {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            padding: 20px;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 8px;
            margin: 30px 0;
            color: #2c3e50;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>أكاديمية القادة</h1>
            <p>مرحباً {{ $userName }}</p>
        </div>
        
        <p>لإتمام عملية التسجيل، يرجى استخدام رمز التحقق التالي:</p>
        
        <div class="code">
            {{ $code }}
        </div>
        
        <p>هذا الرمز صالح لمدة 15 دقيقة فقط.</p>
        <p>إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.</p>
        
        <div class="footer">
            <p>© 2024 أكاديمية منال الديب. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>