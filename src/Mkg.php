<?php
namespace Mkg;

use App\Models\PreNotification;
use Illuminate\Support\Facades\DB;

class Mkg {
    public  $cookies = [];
    public  $errors = [];
    public  $messages = [];
    public  $logs = [];
    private $customerId = null;
    private $mkgUrl = null;
    private $username = null;
    private $password = null;

    function __construct()
    {
    }
    
    /**
     * login
     *
     * @return void
     */
    function login()
    {
        if($this->mkgUrl != null){
            $url                = $this->mkgUrl.'static/auth/j_spring_security_check';
            $this->messages[]   = 'Server IP:'.$_SERVER['SERVER_ADDR'].'<br>';
            $this->messages[]   = 'Start connection<br>';
            $headers            = [];
            $headers[]          = "X-CustomerID: ".$this->customerId;
            $headers[]          = "Content-Type: application/x-www-form-urlencoded";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "j_username=".$this->username."&j_password=".$this->password);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HEADER, 1);
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
                    $this->logs[] = 'We are connected. '.var_dump($http_code);
                  break;
                default:
                    $this->errors[] = 'Unexpected HTTP code: '.$http_code;
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
            }
            $this->cookies = $cookies;
        } else {
            $errors[] = 'Username required';
        }
    }
    
    /**
     * article
     *
     * @param  mixed $id
     * @param  mixed $fieldList
     * @return void
     */
    function article($id, $fieldList = null){
        $this->login();
        $curl       = curl_init();
        $headers    = [];
        $headers[]  = "X-CustomerID: ".$this->customerId;
        foreach($this->cookies as $key => $value){
            $headers[] = "Cookie: ".$key."=".$value;
        }
        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->mkgUrl."rest/v1/MKG/Documents/arti/'.$id.'?FieldList=".$fieldList,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $headers,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        return $response;
    }

        
    /**
     * pre_notification
     *
     * @param  mixed $id
     * @param  mixed $fieldList
     * @return void
     */
    function pre_notification($id, $fieldList = null){
        $this->login();
        $curl       = curl_init();
        $headers    = [];
        $headers[]  = "X-CustomerID: ".$this->customerId;
        foreach($this->cookies as $key => $value){
            $headers[] = "Cookie: ".$key."=".$value;
        }
        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->mkgUrl."rest/v1/MKG/Documents/admi/'.$id.'/voormeldingcoaten?FieldList=".$fieldList,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => $headers,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        return $response;
    }

}
