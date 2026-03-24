<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
             font-family: "DejaVu Sans", sans-serif !important;
        }
        
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background: #fff;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header img {
            width: 120px;
            margin-bottom: 10px;
        }
        
        .header h2 {
            margin: 10px 0 5px;
            color: #333;
            font-size: 24px;
        }
        
        .header p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        
        .content {
            margin: 20px 0;
        }
        
        .receipt-table {
            width: 100%;
            font-size: 14px;
            border-collapse: collapse;
            line-height: 1.8;
        }
        
        .receipt-table tr {
            border-bottom: 1px solid #ddd;
        }
        
        .receipt-table td {
            padding: 10px;
        }
        
        .receipt-table td:first-child {
            width: 40%;
            font-weight: bold;
        }
        
        .receipt-table td:last-child {
            text-align: left;
        }
        
        .amount-row td:last-child {
            font-weight: bold;
            color: #28a745;
        }
        
        .reason-row td:last-child {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .footer p {
            margin: 5px 0;
            font-size: 12px;
        }
        
        .footer .issued-by {
            font-weight: bold;
        }
        
        .footer .issued-on {
            color: #666;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($logo && $logo != '')
            <img src="{{ $logo }}" alt="Logo">
            @endif
            <h2>RECEIPT</h2>
            <p>Building: {{ $building_name }}</p>
        </div>
        
        <div class="content">
            <table class="receipt-table">
                <tr>
                    <td>Receipt No:</td>
                    <td>{{ $receipt_no }}</td>
                </tr>
                <tr>
                    <td>Type:</td>
                    <td>{{ $model }}</td>
                </tr>
                <tr>
                    <td>Model/Reference:</td>
                    <td>{{ $model_name }}</td>
                </tr>
                <tr class="amount-row">
                    <td>Amount:</td>
                    <td>₹{{ $amount }}</td>
                </tr>
                <tr>
                    <td>Payment Mode:</td>
                    <td>{{ $payment_type }}</td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td>{{ $date }}</td>
                </tr>
                <tr class="reason-row">
                    <td>Reason:</td>
                    <td>{{ $reason }}</td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p class="issued-by">Authorized By:</p>
            <p>{{ $issued_by }}</p>
            <p class="issued-on">Issued on: {{ $issued_on }}</p>
        </div>
    </div>
</body>
</html>
