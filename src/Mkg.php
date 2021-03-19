<?php

namespace Mkg;

class Mkg
{
    public  $cookies    = [];
    public  $errors     = [];
    public  $messages   = [];
    public  $logs       = [];
    public $customerId = null;
    public $docsUrl     = null;
    public $authUrl     = null;
    public $username   = null;
    public $password   = null;

    /**
     * __construct
     *
     * @param  mixed $args
     * @return void
     */
    function __construct($args = ['username' => null, 'password' => null, 'customerId' => null, 'docsUrl' => null, 'authUrl' => null])
    {
        $this->username     = $args['username'];
        $this->password     = $args['password'];
        $this->customerId   = $args['customerId'];
        $this->docsUrl      = $args['docsUrl'];
        $this->authUrl      = $args['authUrl'];
        $this->login();
    }

    /**
     * login
     *
     * @return void
     */
    function login()
    {
        if ($this->docsUrl != null) {
            $this->logs[]       = 'Server IP:' . $_SERVER['SERVER_ADDR'];
            $this->logs[]       = 'Start connection';
            $headers[]          = "X-CustomerID: " . $this->customerId;
            $headers[]          = "Content-Type: application/x-www-form-urlencoded";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->authUrl);
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
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_TCP_FASTOPEN, 1);

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

    /**
     * get
     *
     * @param  mixed $args
     * @return void
     */
    function getLocation($location = null)
    {
        if ($location != null) {
            if(isset($location)){
                $headers            = array(
                    'Connection: keep-alive',
                    'Cache-Control: no-cache',
                    'Accept: */*',
                    'Accept-Encoding: gzip, deflate, sdch, br',
                    'Accept-Language: en-US,en;q=0.8',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36'
                );
                $headers[]          = "X-CustomerID: " . $this->customerId;
                foreach($this->cookies as $key => $value){
                    $headers[] = "Cookie: ".$key."=".$value;
                }
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $this->docsUrl.$location);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_ENCODING, "");
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1);
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                // $memory1 =  memory_get_usage(); // 36640
                // $time_start = microtime_float();
                $response = curl_exec($ch);
                // $memory2 = memory_get_usage(); // 36640
                // $time_end = microtime_float();
                // $time = $time_end - $time_start;
                // echo "Did nothing in $time seconds<br>";
                // echo convertSize($memory1).' - <br>';
                // if($memory2 > $memory1){
                //     echo convertSize($memory2).' - <br>';
                //     echo convertSize($memory2 - $memory1).' -- <br>';
                // }
                $this->logs[]       = 'get';
                $this->logs[]       = $response;
                $this->logs[]       = 'headers';
                $this->logs[]       = $headers;
                curl_close($ch);

                return json_decode($response, true);
            } else {
                $this->errors[] = 'Location is missing';
            }
        } else {
            $this->errors[] = 'Type is required';
        }
    }

    /**
     * get
     *
     * @param  mixed $args
     * @return void
     */
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
                foreach($this->cookies as $key => $value){
                    $headers[] = "Cookie: ".$key."=".$value;
                }
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->docsUrl.$location);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_ENCODING, "");
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_TCP_FASTOPEN, 1);
                $response = curl_exec($ch);
                $this->logs[]       = 'get';
                $this->logs[]       = $response;
                $this->logs[]       = 'headers';
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
