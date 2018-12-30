<?php

if (!class_exists('xs_facebook')) {

require_once 'api/autoload.php';

class xs_socials_facebook
{

        private $handler = NULL;
        
        private $token = '';
        
        private $credentials = array( 
                        "api_key" => "882a8490361da98702bf97a021ddc14d", //API key for FB android app
                        "api_secret" => "62f8ce9f74b12f84c123cc23437a4a32" //APP Secret for FB android app
                );
        
        public function __construct ($token = '') 
        {
                $this->handler = new Facebook\Facebook([
                'app_id' => $this->credentials['api_key'],
                'app_secret' => $this->credentials['api_secret'],
                'default_access_token' => $token,
                ]);
                $this->token = $token;
        }
        
        public function get_token()
        {
                if(empty($this->token))
                        return false;
                else
                        return $this->token;
        }
        
        public function login($mail, $pass)
        {
                if($this->get_token() != false)
                        return true;
                        


                $sig = md5("api_key=".$this->credentials['api_key'].
                "credentials_type=passwordemail=".trim($mail)."format=JSONgenerate_machine_id=1generate_session_cookies=1locale=en_USmethod=auth.loginpassword=".trim($pass)."return_ssl_resources=0v=1.0".$this->credentials['api_secret']);

                $fb_token_url = "https://api.facebook.com/restserver.php?api_key=".
                $this->credentials['api_key']."&credentials_type=password&email=".urlencode(trim($mail))."&format=JSON&generate_machine_id=1&generate_session_cookies=1&locale=en_US&method=auth.login&password="
                .urlencode(trim($pass))."&return_ssl_resources=0&v=1.0&sig=".$sig;

                $json = file_get_contents($fb_token_url);
                $obj = json_decode($json);
                
                if(!isset($obj->access_token)) {
                        return $obj->error_msg;
                }
                
                $this->token = $obj->access_token;
                
                $this->handler = new Facebook\Facebook([
                'app_id' => $this->credentials['api_key'],
                'app_secret' => $this->credentials['api_secret'],
                'default_access_token' => $this->token,
                ]);
                return true;
        }
        
        public function user_get_info()
        {
                try {
                // Returns a `Facebook\FacebookResponse` object
                $response = $this->handler->get('/me?fields=id,name,email');
                } catch(Facebook\Exceptions\FacebookResponseException $e) {
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
                } catch(Facebook\Exceptions\FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
                }

                $user = $response->getGraphUser();

                return $user;
        }
        
        public function user_get_post()
        {
                try {
                // Returns a `Facebook\FacebookResponse` object
                $response = $this->handler->get('/me/feed');
                } catch(Facebook\Exceptions\FacebookResponseException $e) {
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
                } catch(Facebook\Exceptions\FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
                }

                $posts = $response->getGraphEdge();
                
                foreach ($posts as $page) {
                        var_dump($page->asArray());
                }


                return $posts;
        }
        
        public function post_add($message, $link = NULL)
        {
                //Post property to Facebook
                $linkData = [
                'link' => $link,
                'message' => $message
                ];

                try {
                $response = $this->handler->post('/me/feed', $linkData);
                } catch(Facebook\Exceptions\FacebookResponseException $e) {
                echo 'Graph returned an error: '.$e->getMessage();
                exit;
                } catch(Facebook\Exceptions\FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: '.$e->getMessage();
                exit;
                }
                
                $graphNode = $response->getGraphNode();
        }
}
}

?>
