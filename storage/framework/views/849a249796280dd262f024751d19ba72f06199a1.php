<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .details { background-color: #f5f5f5; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd; }
        .detail-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #555; }
        .value { color: #333; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background-color: #28a745; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Flat Registration Confirmation</h2>
        </div>
        
        <div class="content">
            <p>Dear <?php echo e($userName); ?>,</p>
            
            <p>Your flat has been successfully registered in our system. Below are the details of your registered flat:</p>
            
            <div class="details">
                <div class="detail-row">
                    <span class="label">Building:</span>
                    <span class="value"><?php echo e($buildingName); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Block:</span>
                    <span class="value"><?php echo e($blockName); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Flat Name/Number:</span>
                    <span class="value"><?php echo e($flatName); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Area:</span>
                    <span class="value"><?php echo e($area); ?> Sq Ft</span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Corpus Fund:</span>
                    <span class="value">₹ <?php echo e($corpusFund); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Living Status:</span>
                    <span class="value">
                        <span class="badge badge-info"><?php echo e($livingStatus); ?></span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value">
                        <?php if($status === 'Active'): ?>
                            <span class="badge badge-success"><?php echo e($status); ?></span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><?php echo e($status); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <p>If you have any questions or need to update your flat details, please login to your account and contact the building management.</p>
            
            <p>Best regards,<br>
            Building Management System</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply to this email address.</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/emails/flat_registered.blade.php ENDPATH**/ ?>