<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guest Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border: 1px solid #dee2e6;
        }
        .passcode {
            background-color: #28a745;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border-radius: 8px;
            margin: 20px 0;
            letter-spacing: 2px;
        }
        .details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
        .footer {
            background-color: #6c757d;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
        }
        .important {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        @media (max-width: 600px) {
            .detail-row {
                flex-direction: column;
            }
            .detail-label, .detail-value {
                margin: 2px 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 Guest Invitation</h1>
        <p>You're invited to visit {{ $building_name }}</p>
    </div>

    <div class="content">
        <h2>Dear {{ $guest_name }},</h2>
        
        <p>You have been invited to visit <strong>{{ $building_name }}</strong>. Please find your invitation details below:</p>

        <div class="passcode">
            <div>Your Access Passcode</div>
            <div>{{ $passcode }}</div>
        </div>

        <div class="important">
            <strong>⚠️ Important:</strong> Please present this passcode to the security guard at the entrance. Keep this email handy during your visit.
        </div>

        <div class="details">
            <h3>📋 Visit Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">📅 Visit Date:</span>
                <span class="detail-value">{{ $visit_date }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">🕐 Visit Time:</span>
                <span class="detail-value">{{ $visit_time }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">👥 Number of Guests:</span>
                <span class="detail-value">{{ $total_guests }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">📱 Contact Number:</span>
                <span class="detail-value">{{ $contact_number }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">🚗 Vehicle Details:</span>
                <span class="detail-value">{{ $vehicle_details }}</span>
            </div>
        </div>

        <div class="details">
            <h3>🏢 Building Information</h3>
            
            <div class="detail-row">
                <span class="detail-label">🏠 Building Name:</span>
                <span class="detail-value">{{ $building_name }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">📍 Address:</span>
                <span class="detail-value">{{ $building_address }}</span>
            </div>
        </div>

        <div class="details">
            <h3>👤 Contact Information</h3>
            
            <div class="detail-row">
                <span class="detail-label">👨‍💼 Invited By:</span>
                <span class="detail-value">{{ $ba_name }}</span>
            </div>
            
            @if($ba_contact)
            <div class="detail-row">
                <span class="detail-label">📞 Contact:</span>
                <span class="detail-value">{{ $ba_contact }}</span>
            </div>
            @endif
        </div>

        <div class="important">
            <strong>📝 Instructions:</strong>
            <ul>
                <li>Arrive at the scheduled time</li>
                <li>Present this passcode to security</li>
                <li>Carry a valid ID proof</li>
                <li>Follow building safety protocols</li>
                <li>Contact the building admin if you need to reschedule</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated invitation email from {{ $building_name }}</p>
        <p>Please do not reply to this email</p>
        <p><small>Generated on {{ date('d-m-Y H:i:s') }}</small></p>
    </div>
</body>
</html>
