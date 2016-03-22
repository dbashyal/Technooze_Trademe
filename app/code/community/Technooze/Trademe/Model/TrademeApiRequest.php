<?php
class Technooze_Trademe_Model_TrademeApiRequest extends Technooze_Trademe_Model_Oauthrequest {

    // http codes - see http://developer.trademe.co.nz/api-overview/error-reporting/
    const TM_ERROR_REQUEST = 400;
    const TM_ERROR_AUTHENTICATION = 401;
    const TM_ERROR_RATE_LIMIT = 429;
    const TM_ERROR_GENERIC = 500;

    public $last_error = null;

    private static $url_part;
    private static $callback_url;

    public function __construct($url_part = null, $callback_url = null, $oauth_settings = null) {
        if (isset($url_part)) {
            self::$url_part = $url_part;
        }
        if (isset($callback_url)) {
            self::$callback_url = $callback_url;
        }
        if (isset($oauth_settings)) {
            parent::__construct($oauth_settings);
        }
    }

    public function get_access_tokens($scope = array()) {
        if (!empty($_GET['oauth_verifier']) && !empty($_GET['oauth_token']) && isset($_SESSION['oauth_token_secret'])) {

            parent::$oauth_settings['oauth_token_secret'] = $_SESSION['oauth_token_secret'];
            unset($_SESSION['oauth_token_secret']);

            $oauth_fields['oauth_verifier'] = $_GET['oauth_verifier'];
            $oauth_fields['oauth_token'] = $_GET['oauth_token'];

            $url = 'https://secure.' . self::$url_part . '.co.nz/Oauth/AccessToken';
            $method = 'POST';

            parent::build_request(
                $url,
                $method,
                $oauth_fields
            );

            $res = parent::do_request($url, $method);
            parse_str($res[0], $res[0]);

            if (!isset($res[0]['oauth_token']) || !isset($res[0]['oauth_token_secret'])) {
                switch ($res[1]['http_code']) {
                    case self::TM_ERROR_REQUEST:
                        $this->last_error = self::TM_ERROR_REQUEST;
                        break;
                    case self::TM_ERROR_AUTHENTICATION:
                        $this->last_error = self::TM_ERROR_AUTHENTICATION;
                        break;
                    case self::TM_ERROR_RATE_LIMIT:
                        $this->last_error = self::TM_ERROR_RATE_LIMIT;
                        break;
                    case self::TM_ERROR_GENERIC:
                        $this->last_error = self::TM_ERROR_GENERIC;
                        break;
                }
                return $res;
            }

            parent::$oauth_settings = array_merge(parent::$oauth_settings, array(
                    'oauth_token' => $res[0]['oauth_token'],
                    'oauth_token_secret' => $res[0]['oauth_token_secret']
                )
            );

            return $res;
        }

        $scope_str = implode($scope, ',');

        $oauth_fields = array(
            'oauth_callback' => self::$callback_url,
            'scope' => $scope_str
        );

        $url = 'https://secure.' . self::$url_part . '.co.nz/Oauth/RequestToken';
        $method = 'POST';

        parent::build_request(
            $url,
            $method,
            $oauth_fields,
            array('scope')
        );

        $url .= '?scope=' . $oauth_fields['scope'];

        $res = parent::do_request($url, $method);
        parse_str($res[0], $res[0]);

        if (!isset($res[0]['oauth_token']) || !isset($res[0]['oauth_token_secret'])) {
            switch ($res[1]['http_code']) {
                case self::TM_ERROR_REQUEST:
                    $this->last_error = self::TM_ERROR_REQUEST;
                    break;
                case self::TM_ERROR_AUTHENTICATION:
                    $this->last_error = self::TM_ERROR_AUTHENTICATION;
                    break;
                case self::TM_ERROR_RATE_LIMIT:
                    $this->last_error = self::TM_ERROR_RATE_LIMIT;
                    break;
                case self::TM_ERROR_GENERIC:
                    $this->last_error = self::TM_ERROR_GENERIC;
                    break;
            }
            return $res;
        }

        // store token secret
        $_SESSION['oauth_token_secret'] = $res[0]['oauth_token_secret'];

        // redirect to trademe to get client access token
        header('Location: https://secure.' . self::$url_part . '.co.nz/Oauth/Authorize?oauth_token=' . $res[0]['oauth_token']);
        exit();
    }

    public function upload_photos($photo_filepaths) {
        $photo_ids = array();

        foreach ($photo_filepaths as $photo_filepath) {
            if (file_exists($photo_filepath)) {
                // build upload photos request
                $post_fields = array(
                    'PhotoData' => base64_encode(file_get_contents($photo_filepath)),
                    'FileName' => basename($photo_filepath),
                    'FileType' => pathinfo($photo_filepath, PATHINFO_EXTENSION)
                );

                $headers = array('Content-type: application/json');
                $post_fields = json_encode($post_fields);

                $url = 'https://api.' . self::$url_part . '.co.nz/v1/Photos.json';
                $method = 'POST';

                parent::build_request($url, $method);

                $res = parent::do_request($url, $method, $headers, $post_fields);
                $res[0] = json_decode($res[0], true);

                if (!array_key_exists('PhotoId', $res[0])) {
                    switch ($res[1]['http_code']) {
                        case self::TM_ERROR_REQUEST:
                            $this->last_error = self::TM_ERROR_REQUEST;
                            break;
                        case self::TM_ERROR_AUTHENTICATION:
                            $this->last_error = self::TM_ERROR_AUTHENTICATION;
                            break;
                        case self::TM_ERROR_RATE_LIMIT:
                            $this->last_error = self::TM_ERROR_RATE_LIMIT;
                            break;
                        case self::TM_ERROR_GENERIC:
                            $this->last_error = self::TM_ERROR_GENERIC;
                            break;
                    }
                } else {
                    $photo_ids[] = $res[0]['PhotoId'];
                }
            }
        }

        return $photo_ids;
    }

    public function list_product($product) {
        $post_fields = array(
            'Category' => $product['product_category_id'],
            'Title' => $product['product_title'],
            'Description' => explode("\r\n", $product['product_description']),
            'StartPrice' => $product['product_price'],
            'Duration' => 7,
            'Pickup' => 1,
            'ShippingOptions' => array(
                array(
                    'Type' => 1
                )
            ),
            'PaymentMethods' => array(
                1
            ),
            'PhotoIds' => $product['photo_ids']
        );

        $headers = array('Content-type: application/json');
        $post_fields = json_encode($post_fields);

        $url = 'https://api.' . self::$url_part . '.co.nz/v1/Selling.json';
        $method = 'POST';

        parent::build_request($url, $method);

        $res = parent::do_request($url, $method, $headers, $post_fields);
        $res[0] = json_decode($res[0], true);

        if (!array_key_exists('Success', $res[0]) || !$res[0]['Success']) {
            switch ($res[1]['http_code']) {
                case self::TM_ERROR_REQUEST:
                    $this->last_error = self::TM_ERROR_REQUEST;
                    break;
                case self::TM_ERROR_AUTHENTICATION:
                    $this->last_error = self::TM_ERROR_AUTHENTICATION;
                    break;
                case self::TM_ERROR_RATE_LIMIT:
                    $this->last_error = self::TM_ERROR_RATE_LIMIT;
                    break;
                case self::TM_ERROR_GENERIC:
                    $this->last_error = self::TM_ERROR_GENERIC;
                    break;
            }
        }

        return $res;
    }

    public function build_response_error($res) {
        $message = '';
        switch ($this->last_error) {
            case self::TM_ERROR_REQUEST:
                $message = 'Request ';
                break;
            case self::TM_ERROR_AUTHENTICATION:
                $message = 'Authentication ';
                break;
            case self::TM_ERROR_RATE_LIMIT:
                $message = 'Rate Limit ';
                break;
            case self::TM_ERROR_GENERIC:
                break;
        }

        $message = '<strong>' . $message . 'Error</strong>';

        // print any error messages contained in response
        foreach ($res as $key => $val) {
            $message .= '<br /><br /><strong>' . $key . '</strong><br />' . $val;
        }
        $message = '<p>' . $message . '</p>';

        return $message;
    }

    public function isEnabled()
    {
        return (bool)Mage::getStoreConfig('tgeneral/trademe/enabled');
    }

    public function getOauthCredentials()
    {
        $oauth_token = Mage::getStoreConfig('tgeneral/trademe/oauth_token');
        $oauth_token_secret = Mage::getStoreConfig('tgeneral/trademe/oauth_token_secret');
        if(!$this->isEnabled() || !$oauth_token || !$oauth_token_secret){
            throw new Exception('Trademe module is either not enabled or properly configured!');
        }
        return Mage::getStoreConfig('tgeneral/trademe');
    }
}