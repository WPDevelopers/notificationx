<?php
/**
 * Entries Mail Receiver
 *
 * Sends an email notification to configured receivers each time a new
 * NotificationX entry is inserted (e.g. a form submission).
 *
 * @package NotificationX\Admin
 */

namespace NotificationX\Admin;

use NotificationX\GetInstance;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @method static EntriesMailReceiver get_instance($args = null)
 */
class EntriesMailReceiver {

    use GetInstance;

    public function __construct() {
        if ( Settings::get_instance()->get( 'settings.enable_entries_mail', false ) ) {
            add_action( 'nx_after_entry_inserted', [ $this, 'notify_on_entry' ] );
        }
    }

    /**
     * Triggered after every new entry insert.
     *
     * @param array $entry
     */
    public function notify_on_entry( $entry ) {
        $emails  = $this->receiver_emails();
        $subject = $this->email_subject();

        if ( empty( $emails ) ) {
            return;
        }

        $body    = $this->build_email_body( $entry );
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            $this->from_header(),
        ];

        wp_mail( $emails, $subject, $body, $headers );
    }

    /**
     * REST handler for the "Send Test Email" button.
     *
     * @param \WP_REST_Request $request
     * @return array|\WP_Error
     */
    public function send_test( $request ) {
        $raw_email = $request->get_param( 'entries_mail_email' );
        $subject   = sanitize_text_field( (string) $request->get_param( 'entries_mail_subject' ) );

        $emails = $this->receiver_emails( ! empty( $raw_email ) ? $raw_email : null );
        if ( empty( $emails ) ) {
            return new \WP_Error( 'nx_invalid_email', __( 'Please provide a valid email address.', 'notificationx' ) );
        }

        if ( empty( $subject ) ) {
            $subject = $this->email_subject();
        }

        $dummy_entry = [
            'source'    => 'test',
            'entry_key' => 'test_entry',
            'data'      => [
                'name'    => 'John Doe',
                'email'   => 'johndoe@example.com',
                'message' => 'This is a test entry notification from NotificationX.',
            ],
        ];

        $body    = $this->build_email_body( $dummy_entry );
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            $this->from_header(),
        ];

        $sent = wp_mail( $emails, $subject, $body, $headers );

        if ( $sent ) {
            return [ 'message' => __( 'Test email sent successfully.', 'notificationx' ) ];
        }

        return new \WP_Error( 'nx_mail_failed', __( 'Failed to send the test email. Please check your mail configuration.', 'notificationx' ) );
    }

    /**
     * Return receiver emails from settings.
     * Handles the simple-repeater array format: [['title' => 'email', ...], ...]
     *
     * @param mixed $raw  Optional override value (e.g. from REST request).
     * @return array
     */
    public function receiver_emails( $raw = null ) {
        if ( is_null( $raw ) ) {
            $raw = Settings::get_instance()->get( 'settings.entries_mail_email', [] );
        }
        $emails = $this->extract_emails( $raw );
        if ( empty( $emails ) ) {
            $emails = [ get_option( 'admin_email' ) ];
        }
        return $emails;
    }

    /**
     * Extract a clean array of validated email strings.
     * Handles simple-repeater format [['title' => 'email@...'], ...] and
     * plain comma/newline-separated strings as a legacy fallback.
     *
     * @param mixed $value
     * @return array
     */
    private function extract_emails( $value ) {
        if ( is_array( $value ) ) {
            $emails = [];
            foreach ( $value as $item ) {
                if ( is_array( $item ) && ! empty( $item['title'] ) ) {
                    $email = trim( $item['title'] );
                    if ( is_email( $email ) ) {
                        $emails[] = $email;
                    }
                }
            }
            return $emails;
        }

        // Legacy plain-string fallback
        $parts = preg_split( '/[\s,]+/', trim( (string) $value ), -1, PREG_SPLIT_NO_EMPTY );
        return array_values( array_filter( $parts, 'is_email' ) );
    }

    /**
     * Return configured subject or a sensible default.
     *
     * @return string
     */
    public function email_subject() {
        $subject = Settings::get_instance()->get( 'settings.entries_mail_subject', '' );
        if ( empty( $subject ) ) {
            $site_name = get_bloginfo( 'name' );
            $subject   = sprintf( __( 'New Entry Received on "%s"', 'notificationx' ), $site_name );
        }
        return sanitize_text_field( $subject );
    }

    /**
     * Build the "From" mail header.
     *
     * Derived from the module settings, falling back to the site name and
     * the site admin email. Avoids a hardcoded wpdeveloper.com address that
     * most sites have no SPF/DKIM for (silent spam-quarantine). Both parts
     * are sanitized so a CR/LF in either can't inject extra headers.
     *
     * @return string
     */
    public function from_header() {
        $settings  = Settings::get_instance();
        $from_name = sanitize_text_field( (string) $settings->get( 'settings.entries_mail_from_name', '' ) );
        if ( empty( $from_name ) ) {
            $from_name = get_bloginfo( 'name' );
        }

        $from_email = trim( (string) $settings->get( 'settings.entries_mail_from_email', '' ) );
        if ( ! is_email( $from_email ) ) {
            $from_email = get_option( 'admin_email' );
        }

        return sprintf( 'From: %s <%s>', $from_name, $from_email );
    }

    /**
     * Build the HTML email body for a given entry.
     *
     * @param array $entry
     * @return string
     */
    private function build_email_body( $entry ) {
        $source    = ! empty( $entry['source'] ) ? esc_html( $entry['source'] ) : __( 'Unknown', 'notificationx' );
        $site_name = get_bloginfo( 'name' );
        $site_url  = get_bloginfo( 'url' );
        $date      = current_time( 'Y-m-d H:i:s' );

        $data_rows = '';
        $data      = ! empty( $entry['data'] ) && is_array( $entry['data'] ) ? $entry['data'] : [];
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $value = implode( ', ', $value );
            }
            $data_rows .= '<tr>'
                . '<td style="padding:8px 12px;font-weight:600;border-bottom:1px solid #eee;white-space:nowrap;">'
                . esc_html( ucwords( str_replace( '_', ' ', $key ) ) )
                . '</td>'
                . '<td style="padding:8px 12px;border-bottom:1px solid #eee;">'
                . esc_html( (string) $value )
                . '</td>'
                . '</tr>';
        }

        $table = $data_rows
            ? '<table style="width:100%;border-collapse:collapse;margin-top:16px;">' . $data_rows . '</table>'
            : '<p style="color:#888;">' . __( 'No entry data available.', 'notificationx' ) . '</p>';

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:20px;background:#f4f4f4;font-family:Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
    <div style="background:#7c3aed;padding:24px;">
        <h2 style="color:#fff;margin:0;font-size:20px;">New Entry Notification</h2>
        <p style="color:#ede9fe;margin:4px 0 0;font-size:13px;">' . esc_html( $site_name ) . '</p>
    </div>
    <div style="padding:24px;">
        <p style="margin:0 0 8px;"><strong>Source:</strong> ' . $source . '</p>
        <p style="margin:0 0 16px;"><strong>Date:</strong> ' . esc_html( $date ) . '</p>
        ' . $table . '
    </div>
    <div style="background:#f9f9f9;padding:12px 24px;font-size:12px;color:#999;text-align:center;">
        Sent by <a href="' . esc_url( $site_url ) . '" style="color:#7c3aed;text-decoration:none;">NotificationX</a>
    </div>
</div>
</body></html>';
    }
}
