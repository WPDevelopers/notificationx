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

        $token_url = "https://graph.facebook.com/oauth/access_token?"
            . "client_id=" . $this->client_id . "&redirect_uri=" . urlencode($this->redirect)
            . "&client_secret=" . $this->app_secret . "&code=" . $code;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        // echo "<pre>";
        // print_r($result);die;

        // $oauthClient = $this->client->getOAuth2Client();
        // $short_access_token = $oauthClient->getAccessTokenFromCode($code, $this->redirect);
        // $long_accessToken = $oauthClient->getLongLivedAccessToken($short_access_token);

        return [
            'access_token' => $result->access_token,
            'expires_in'   => $result->expires_in,
        ];
    }

}

