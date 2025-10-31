<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>付款成功 - 黑貓Pay</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Instrument Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 48px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .success-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            position: relative;
            animation: successPulse 2s ease-in-out infinite;
        }

        .success-icon::before {
            content: "✓";
            color: white;
            font-size: 40px;
            font-weight: bold;
            animation: checkmark 0.6s ease-in-out;
        }

        @keyframes successPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 20px rgba(76, 175, 80, 0);
            }
        }

        @keyframes checkmark {
            0% {
                transform: scale(0) rotate(45deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.2) rotate(45deg);
                opacity: 1;
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 12px;
            animation: fadeInUp 0.8s ease-out;
        }

        .success-subtitle {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 32px;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .payment-info {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        .info-value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 600;
        }

        .amount-value {
            font-size: 18px;
            color: #27ae60;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 16px;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            color: #495057;
        }

        .countdown {
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .countdown-number {
            font-weight: 600;
            color: #667eea;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .success-container {
                padding: 32px 24px;
                margin: 10px;
            }

            .success-title {
                font-size: 24px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon"></div>

        <h1 class="success-title">付款成功！</h1>
        <p class="success-subtitle">感謝您的付款，訂單已確認處理中</p>

        <div class="payment-info">
            <div class="info-row">
                <span class="info-label">付款方式</span>
                <span class="info-value">黑貓Pay</span>
            </div>
            <div class="info-row">
                <span class="info-label">訂單金額</span>
                <span class="info-value amount-value">NT$ {{ number_format($order_amount) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">付款時間</span>
                <span class="info-value" id="payment-time">{{ $acquire_time }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">交易狀態</span>
                <span class="info-value" style="color: #27ae60; font-weight: 600;">✓ 已付款</span>
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="window.close()" class="btn btn-primary">
                <span>關閉視窗</span>
            </button>
        </div>
    </div>

    <script>
        // 防止頁面重新整理時重複執行
        if (window.performance && window.performance.navigation.type === 1) {
            window.close();
        }
    </script>
</body>
</html>
