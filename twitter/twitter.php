<?php

require_once 'api/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

if (!class_exists('xs_socials_twitter')) {

if (!isset($_SESSION)) session_start();


class xs_socials_twitter 
{
        
        
        private $handler = NULL;
        private $token = '';
        
        private $credentials = array( 
                "api_key" => "g4V6boCONg47xJVmq3SBPAOtx", //CONSUMER API KEY
                "api_secret" => "z7QOXi9me3G4rSDR6vDayps2nITm7AekWSPIl9F7Fph9RH6Ueb", //CONSUMER API SECRET
                "oauth_token" => "",
                "oauth_token_secret" => ""
        );
        
        public function __construct($array)
        {
                if(isset($array["oauth_token"]) && isset($array["oauth_token_secret"])) {
                        $this->credentials["oauth_token"] = $array["oauth_token"];
                        $this->credentials["oauth_token_secret"] = $array["oauth_token_secret"];
                        $this->handler = new TwitterOAuth($this->credentials["api_key"], $this->credentials["api_secret"], $array["oauth_token"], $array["oauth_token_secret"]);

                }
                else
                        $this->handler = new TwitterOAuth($this->credentials["api_key"], $this->credentials["api_secret"]);
        }
        
        public function callback_url($callback_file)
        {
                $offset = array(
                                'oauth_token' => '',
                                'oauth_token_secret' => '',
                                'callback_url' => ''
                        );
                
                $request_token = $this->handler->oauth('oauth/request_token', ['oauth_callback' => $callback_file]);

                // throw exception if something gone wrong
                if($this->handler->getLastHttpCode() != 200) {
                throw new \Exception('There was a problem performing this request');
                }
                
                // save token of application to session
                $offset['oauth_token'] = $request_token['oauth_token'];
                $offset['oauth_token_secret'] = $request_token['oauth_token_secret'];
                
                // generate the URL to make request to authorize our application
                $offset['url'] = $this->handler->url('oauth/authorize', [ 'oauth_token' => $request_token['oauth_token']]);
                
                return $offset;
        }
        
        public function verify($oauth_verifier)
        {
                // request user token
                $token = $this->handler->oauth(
                'oauth/access_token', [
                        'oauth_verifier' => $oauth_verifier
                ]
                );
                return $token;
        }

        public function post_add($status)
        {
                $statues = $this->handler->post("statuses/update", ["status" => $status]);
                return $statues;
        }

}

}

?>
