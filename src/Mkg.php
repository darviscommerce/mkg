<?php

namespace Mkg;

class Mkg
{
    public  $cookies    = [];
    public  $errors     = [];
    public  $messages   = [];
    public  $logs       = [];
    public $customerId = null;
    public $mkgUrl     = null;
    public $username   = null;
    public $password   = null;
    public $cookie     = 'cookies.txt';

    /**
     * __construct
     *
     * @param  mixed $args
     * @return void
     */
    function __construct($args = ['username' => null, 'password' => null, 'customerId' => null, 'mkgUrl' => null])
    {
        $this->username     = $args['username'];
        $this->password     = $args['password'];
        $this->customerId   = $args['customerId'];
        $this->mkgUrl       = $args['mkgUrl'];
        $this->login();
    }

    /**
     * login
     *
     * @return void
     */
    function login()
    {
        if ($this->mkgUrl != null) {
            $url                = $this->mkgUrl . '/static/auth/j_spring_security_check';
            $this->logs[]       = 'Server IP:' . $_SERVER['SERVER_ADDR'];
            $this->logs[]       = 'Start connection';
            $headers[]          = "X-CustomerID: " . $this->customerId;
            $headers[]          = "Content-Type: application/x-www-form-urlencoded";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, "j_username=" . $this->username . "&j_password=" . $this->password);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            // curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
            $data = curl_exec($ch);
            /************************\
             * DEBUG
            \************************/
            if ($data === false) {
                $this->errors[] = 'Geen data?';
                ob_start();
                printf(
                    "cUrl error (#%d): %s<br>\n",
                    curl_errno($ch),
                    htmlspecialchars(curl_error($ch))
                );
                $this->errors[] = ob_get_contents();
                ob_end_clean();
            }
            if (!curl_errno($ch)) {
                $this->errors[] = 'Error';
                switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                    case 200:  # OK
                        $this->logs[] = 'We are connected. ' . $http_code;
                        break;
                    case 415:  # OK
                        $this->logs[] = 'We are connected. ' . $http_code;
                        break;
                    default:
                        $this->logs[] = 'Unexpected HTTP code: ' . $http_code;
                }
            }
            /************************\
             * EINDE DEBUG
            \************************/
            curl_close($ch);
            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);        // get cookie
            $cookies = array();
            foreach ($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
                $this->logs[]       = $cookies;
            }
            $this->cookies = $cookies;
        } else {
            $this->errors[] = 'Username required';
        }
    }

    function get($args = ['type' => null])
    {
        if ($args['type'] != null) {
            if($args['type'] == 'article'){
                if(isset($args['fieldList'])){
                    $location           = '/arti/' . $args['id'] . '?FieldList=' . $args['fieldList'];
                } else {
                    $this->errors[] = 'Fields for question are missing';
                }
            } elseif($args['type'] == 'packageList'){
                if(isset($args['fieldList'])){
                    $location           = '/pkbr?Filter=vorh_num%20=%20' . $args['id'] . '&FieldList=' . $args['fieldList'];
                } else {
                    $this->errors[] = 'Fields for question are missing';
                }
            } elseif($args['type'] == 'packageListRows'){
                $location           = '/pkbh/'.$args['id'].'/pkbh_pkbr';
            } elseif($args['type'] == 'preNotification'){
                if(isset($args['fieldList'])){
                    $location           = '/admi/' . $args['id'] . '/voormeldingcoaten?FieldList=' . $args['fieldList'];
                } else {
                    $this->errors[] = 'Fields for question are missing';
                }
            }
            if(isset($location)){
                $headers            = [];
                $headers[]          = "X-CustomerID: " . $this->customerId;
                // foreach($this->cookies as $key => $value){
                //     $headers[] = "Cookie: ".$key."=".$value;
                // }
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->mkgUrl . $location);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_ENCODING, "");
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                $response = curl_exec($ch);
                $this->logs[]       = $response;
                $this->logs[]       = $headers;
                $response = curl_exec($ch);
                curl_close($ch);
                return json_decode($response, true);
            } else {
                $this->errors[] = 'Location is missing';
            }
        } else {
            $this->errors[] = 'Type is required';
        }
    }

}
