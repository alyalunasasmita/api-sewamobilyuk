<!DOCTYPE html>
<html>
<head>
    <title>Kode OTP</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:20px;">

    <div style="
        max-width:500px;
        margin:auto;
        background:white;
        padding:30px;
        border-radius:10px;
        text-align:center;
    ">

        <h2>Reset Password</h2>

        <p>Gunakan kode OTP berikut:</p>

        <div style="
            font-size:32px;
            font-weight:bold;
            letter-spacing:5px;
            margin:20px 0;
            color:#2563eb;
        ">
            {{ $data['otp'] }}
        </div>

        <p>Kode OTP berlaku selama 5 menit.</p>

        <p>Jangan bagikan kode ini kepada siapa pun.</p>

    </div>

</body>
</html>