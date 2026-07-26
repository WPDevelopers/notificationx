<?php
/**
 * LatePoint Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\LatePoint;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Core\Rules;

/**
 * LatePoint Extension Class
 * @method static LatePointConversions get_instance($args = null)
 */
class LatePointConversions extends Extension {
    use GetInstance;

    public $priority        = 12;
    public $id              = 'latepoint';
    public $doc_link        = 'https://notificationx.com/docs/';
    public $types           = 'conversions';
    public $img             = NOTIFICATIONX_ADMIN_URL . 'images/extensions/sources/latepoint.png';
    public $module          = 'modules_latepoint';
    public $module_priority = 40;
    // 'LatePoint' is declared at file load. LATEPOINT_VERSION is only defined
    // during init, so it is not a safe presence probe.
    public $class           = 'LatePoint';

    public function __construct() {
        parent::__construct();
    }

    public function init_extension() {
        $this->title        = __( 'LatePoint', 'notificationx' );
        $this->module_title = __( 'LatePoint', 'notificationx' );
    }

    public function source_error_message( $messages ) {
        if ( ! $this->class_exists() ) {
            $url = admin_url( 'plugin-install.php?s=latepoint&tab=search&type=term' );
            $messages[ $this->id ] = [
                'message' => sprintf(
                    '%s <a href="%s" target="_blank">%s</a> %s',
                    __( 'You have to install', 'notificationx' ),
                    $url,
                    __( 'LatePoint', 'notificationx' ),
                    __( 'plugin first.', 'notificationx' )
                ),
                'html'  => true,
                'type'  => 'error',
                'rules' => Rules::is( 'source', $this->id ),
            ];
        }
        return $messages;
    }

    public function doc() {
        /* translators: %1$s: LatePoint plugin URL, %2$s: documentation URL */
        return sprintf(
            __( '<p>Make sure the <a target="_blank" href="%1$s">LatePoint plugin is installed &amp; configured</a> so NotificationX can read its booking data. For detailed guidelines, check the step-by-step <a target="_blank" href="%2$s">documentation</a>.</p>', 'notificationx' ),
            'https://wordpress.org/plugins/latepoint/',
            'https://notificationx.com/docs/'
        );
    }
}
