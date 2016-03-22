<?php
class Technooze_Trademe_Model_Oauthrequest extends Mage_Core_Model_Abstract{

    protected static $oauth_settings = array(
        'oauth_version' => '1.0',
        'signature_method' => 'HMAC-SHA1',
        'oauth_consumer_key' => null,
        'oauth_consumer_secret' => null,
        'oauth_token' => null,
        'oauth_token_secret' => null
    );

    // oauth fields for request
    protected static $oauth_fields = array();
    protected $exclude_fields = array();

    public function __construct($oauth_settings = null) {
        parent::__construct();
        if (isset($oauth_settings)) {
            self::$oauth_settings = array_merge(self::$oauth_settings, $oauth_settings);

            $tmp = array(
                'oauth_version',
                'oauth_consumer_key',
                'oauth_signature_method',
                'oauth_token'
            );

            foreach ($tmp as $val) {
                if (isset(self::$oauth_settings[$val])) {
                    self::$oauth_fields[$val] = self::$oauth_settings[$val];
                }
            }
        }
    }

    protected function build_request($url, $method, $oauth_fields = array(), $exclude_fields = array()) {
        self::$oauth_fields = array_merge(self::$oauth_fields, $oauth_fields);

        self::$oauth_fields['oauth_timestamp'] = time();
        self::$oauth_fields['oauth_nonce'] = uniqid();

        self::$oauth_fields['oauth_signature'] = self::build_signature($url, $method);

        $this->exclude_fields = $exclude_fields;
    }

    protected function build_signature($url, $method) {
        if (self::$oauth_settings['oauth_signature_method'] == 'HMAC-SHA1') {
            $signature_base = self::build_signature_base($url, $method);

            // debug
            //var_dump($signature_base);

            $signature = base64_encode(
                hash_hmac(
                    'sha1',
                    $signature_base,
                    self::$oauth_settings['oauth_consumer_secret'] . '&' . self::$oauth_settings['oauth_token_secret'],
                    true
                )
            );
        } else {
            $signature = self::$oauth_settings['oauth_consumer_secret'] . '&' . self::$oauth_settings['oauth_token_secret'];
        }

        return $signature;
    }

    protected function build_signature_base($url, $method) {
        if (isset(self::$oauth_fields['oauth_signature'])) {
            unset(self::$oauth_fields['oauth_signature']);
        }

        $str = $method .'&' . rawurlencode($url) . '&';

        ksort(self::$oauth_fields);

        $tmp = '';
        foreach (self::$oauth_fields as $key => $val) {
            $prefix = ($tmp == '')? '' : '&';
            $tmp .= $prefix . $key . '=' . rawurlencode($val);
        }

        $str .= rawurlencode($tmp);

        return $str;
    }

    protected function do_request($url, $method, $headers = array(), $post_fields = '') {
        $headers[] = self::build_header();

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        }

        $res = curl_exec($ch);

        return array($res, curl_getinfo($ch));
    }

    protected function build_header() {
        $str = '';
        foreach (self::$oauth_fields as $key => $val) {
            if (in_array($key, $this->exclude_fields)) {
                continue;
            }
            $str .= ($str == '')? '' : ', ';
            $str .= $key . '="' . rawurlencode($val) . '"';
        }

        return 'Authorization: OAuth ' . $str;
    }
}