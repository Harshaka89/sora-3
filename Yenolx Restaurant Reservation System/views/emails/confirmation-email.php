<?php
/**
 * Confirmation Email View for Yenolx Restaurant Reservation System
 *
 * This template generates the HTML email sent to customers upon booking.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Note: The variables $reservation and $restaurant_info are passed from the controller.
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reservation Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">

    <div style="background-color: #f7f7f7; padding: 20px; text-align: center;">
        <h1 style="margin: 0; color: #2c3e50;"><?php echo esc_html($restaurant_info['name']); ?></h1>
    </div>

    <div style="padding: 20px;">
        <p style="font-size: 18px;">Dear <?php echo esc_html($reservation->customer_name); ?>,</p>
        <p>Thank you for your reservation! We are pleased to confirm your booking with the following details:</p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr style="background-color: #f2f2f2;">
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Reservation Code:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($reservation->reservation_code); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Date:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html(date('l, F j, Y', strtotime($reservation->reservation_date))); ?></td>
            </tr>
            <tr style="background-color: #f2f2f2;">
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Time:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html(date('g:i A', strtotime($reservation->reservation_time))); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Party Size:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($reservation->party_size); ?> people</td>
            </tr>
            <?php if (!empty($reservation->table_number)) : ?>
            <tr style="background-color: #f2f2f2;">
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Table:</td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($reservation->table_number); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <p>If you have any questions or need to make changes to your reservation, please contact us at <?php echo esc_html($restaurant_info['email']); ?>.</p>
        <p>We look forward to seeing you!</p>
        <p>Sincerely,<br>The Team at <?php echo esc_html($restaurant_info['name']); ?></p>
    </div>

    <div style="background-color: #f7f7f7; padding: 10px; text-align: center; font-size: 12px; color: #777;">
        <p><?php echo esc_html($restaurant_info['address']); ?></p>
        <p>This is an automated email. Please do not reply.</p>
    </div>

</body>
</html>
