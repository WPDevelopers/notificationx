<?php
/**
 * Ability: read NotificationX global settings and module state.
 *
 * @package NotificationX\Abilities\Read
 */

namespace NotificationX\Abilities\Read;

use NotificationX\Abilities\AbilityBase;
use NotificationX\Core\Modules;
use NotificationX\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns global settings and per-module enabled state. Anything that looks
 * like a secret (key/secret/token/password) is redacted before it leaves.
 */
class GetSettings extends AbilityBase {

    protected $id          = 'notificationx/get-settings';
    protected $label       = 'Get settings';
    protected $description = 'Get NotificationX global settings and the enabled/disabled state of each module. Secret values (API keys, tokens, passwords) are redacted.';

    public function input_schema() {
        return array(
            'type'       => 'object',
            'properties' => (object) array(),
        );
    }

    public function output_schema() {
        return array(
            'type'       => 'object',
            'properties' => array(
                'modules'  => array( 'type' => 'array' ),
                'settings' => array( 'type' => 'object' ),
            ),
        );
    }

    public function execute( $input ) {
        // Module state.
        $modules_list = array();
        $registered   = Modules::get_instance()->get_all();
        if ( is_array( $registered ) ) {
            foreach ( $registered as $key => $module ) {
                $value = is_array( $module ) && isset( $module['value'] ) ? $module['value'] : $key;
                $label = is_array( $module ) && isset( $module['label'] ) ? $module['label'] : $value;
                $modules_list[] = array(
                    'id'      => $value,
                    'label'   => $label,
                    'enabled' => (bool) Modules::get_instance()->is_enabled( $value ),
                );
            }
        }

        // General settings, with secrets redacted.
        $settings = (array) Settings::get_instance()->get( 'settings' );
        unset( $settings['modules'] ); // already reported above
        $settings = $this->redact( $settings );

        return array(
            'modules'  => $modules_list,
            'settings' => $settings,
        );
    }

    /**
     * Recursively redact values whose key suggests a credential.
     *
     * @param array $data Settings array.
     * @return array
     */
    protected function redact( $data ) {
        $needles = array( 'key', 'secret', 'token', 'password', 'client_id', 'auth', 'nonce' );
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $data[ $key ] = $this->redact( $value );
                continue;
            }
            $lower = strtolower( (string) $key );
            foreach ( $needles as $needle ) {
                if ( false !== strpos( $lower, $needle ) && '' !== (string) $value ) {
                    $data[ $key ] = '***redacted***';
                    break;
                }
            }
        }
        return $data;
    }
}
