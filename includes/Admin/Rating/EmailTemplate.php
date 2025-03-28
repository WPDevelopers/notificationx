<?php
namespace NotificationX\Admin\Rating;

class EmailTemplate 
{
    public function template_body($data, $frequency) {
        $rating = $data['rating'] ?? 'N/A';
        $review = $data['review'] ?? 'No review provided';

        // Fetch Site Owner Info
        $site_name   = get_bloginfo('name');
        $site_url    = get_site_url();
        $admin_email = get_option('admin_email');
        $wp_version  = get_bloginfo('version');

        $admin_user = get_user_by('ID', 1);
        if ($admin_user) {
            $first_name = get_user_meta($admin_user->ID, 'first_name', true);
            $last_name = get_user_meta($admin_user->ID, 'last_name', true);
            
            $admin_full_name = trim("$first_name $last_name");
            
            // Fallback to display name if full name is not set
            if (empty($admin_full_name)) {
                $admin_full_name = $admin_user->display_name;
            }
        } else {
            $admin_full_name = 'Unknown';
        }
        
        $emailTemplate = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>NotificationX Feedback Received</title>
            <link href="https://fonts.googleapis.com/css?family=Lato:400,700&display=swap" rel="stylesheet">
            <style>
                @media (max-width: 600px) {
                    .notificationx-email-container .notificationx-email-body {
                        width: 100% !important;
                        max-width: 100%;
                    }
                }
            </style>
        </head>
        <body class="notificationx-email-wrapper" style="background-color: #f3f7fa; margin: 0; padding: 0">
        <table class="notificationx-email-container" cellpadding="25" cellspacing="0" border="0" width="100%" align="center"
               bgcolor="#f3f7fa" style="background-color: #f3f7fa; margin: 0; padding: 25px 0 0">
            <tbody>
            <tr>
                <td>
                    <table width="540" cellpadding="0" cellspacing="0" border="0" class="notificationx-email-body" bgcolor="#fff"
                           align="center"
                           style="box-shadow: 0 0 15px rgba(0,0,0,0.15); width: 800px;border-radius: 10px; text-align: left">
                        <tbody>
                        <tr>
                            <td>
                                <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0"
                                       style="border-bottom: 1px solid #eee;">
                                    <tbody>
                                    <tr>
                                        <td style="text-align: center; padding: 20px">
                                            <a href="https://notificationx.com" target="_blank"
                                               style="text-align: center">
                                                <img src="https://notificationx.com/wp-content/uploads/2025/02/NotificationX-300x80.png" alt="' . $site_name . ' Logo">
                                            </a>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">
                                    <tbody>
                                    <tr>
                                        <td style="text-align: center; padding: 20px 10px 10px">
                                            <h3 style="font-size: 20px; font-weight: 700; font-family: Lato, sans-serif; color: #052d3d;">
                                                Feedback Overview</h3>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <table cellpadding="5" cellspacing="2" align="center" border="0" style="border: 1px solid #eee; margin-bottom: 20px; border-collapse: collapse; min-width: 75%">
                                                <tr>
                                                    <td colspan="2" style="border-bottom: 1px solid #eee; text-align:left; padding: 6px 15px;">
                                                        <strong style="font-size: 16px; font-family: Lato, sans-serif; color: #052d3d;">Email:</strong>
                                                        <a href="mailto:' . $admin_email . '">' . $admin_email . '</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="border-bottom: 1px solid #eee; padding: 6px 15px">
                                                        <strong style="font-size: 16px; font-family: Lato, sans-serif; color: #052d3d;">Rating:</strong>
                                                        <span style="font-size: 16px; font-family: Lato, sans-serif; color: #052d3d;">
                                                           ' . $rating . ' ⭐
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" style="border-bottom: 1px solid #eee;text-align:left; padding: 6px 15px;">
                                                        <strong style="font-size: 16px; font-family: Lato, sans-serif; color: #052d3d;">User:</strong>
                                                         <a target="_blank">' . $admin_full_name . '</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" style="border-bottom: 1px solid #eee;text-align:left; padding: 6px 15px;">
                                                        <strong style="font-size: 16px; font-family: Lato, sans-serif; color: #052d3d;">Site URL:</strong>
                                                         <a href="' . $site_url . '" target="_blank">' . $site_url . '</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" style="text-align:left;padding: 6px 15px">
                                                        <strong style="font-size: 16px; font-family: Lato, sans-serif; color: #052d3d;">Feedback:</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" style="border-bottom: 1px solid #eee;text-align: justify; padding: 8px 15px">
                                                        <span style="font-size: 16px; font-family: Lato, sans-serif; color: #052d3d;">
                                                           ' . nl2br($review) . '
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
        </body>
        </html>';
        
        return $emailTemplate;
    }
}
