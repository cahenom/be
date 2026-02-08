<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Berhasil</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease-out;
        }
        
        .icon svg {
            width: 40px;
            height: 40px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        
        h1 {
            color: #1a202c;
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        p {
            color: #718096;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .checkmark {
            animation: drawCheck 0.5s ease-out 0.3s forwards;
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
        }
        
        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 52 52">
                <path class="checkmark" d="M14 27l9 9 19-19"/>
            </svg>
        </div>
        <h1>Deposit Berhasil!</h1>
        <p>Pembayaran Anda telah berhasil diproses. Saldo akan segera ditambahkan ke akun Anda.</p>
        <a href="punyakios://deposit/success" class="btn">Kembali ke Aplikasi</a>
    </div>
</body>
</html>
