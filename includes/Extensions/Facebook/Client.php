<?php
/**
 * Facebook Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationX\Extensions\Facebook;


/**
 * Facebook Extension
 * @method static Facebook get_instance($args = null)
 */
class Client {
    public $client_id       = 574146147818358;
    public $app_secret;
    public $redirect = 'https://notificationx-api.test/facebook/v1/index.php';


    public function __construct($client_id = '', $app_secret = '', $redirect = ''){

    }

    public function get_oauth_url($state){
        $scope = 'pages_show_list,pages_read_user_content';
        $state['app_redirect'] = $this->redirect;

        return "https://www.facebook.com/dialog/oauth?client_id="
        . $this->client_id . "&redirect_uri=" . urlencode($this->redirect) . "&state="
        . base64_encode(json_encode($state)) . "&scope=" . $scope;
    }

    public function getRedirectUri()
    {
        return $this->redirect;
    }

    public function get_access_token($code){

        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect,
            'client_secret' => $this->app_secret,
            'code' => $code
        ];
        $token_url = "https://graph.facebook.com/oauth/access_token?" . http_build_query($params);
        $response = wp_remote_get($token_url);
        if (is_wp_error($response)) {
            return false;
        }
        $result = json_decode(wp_remote_retrieve_body($response));

        return [
            'access_token' => $result->access_token,
            'expires_in'   => $result->expires_in,
        ];
    }

}

