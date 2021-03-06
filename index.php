<?php
ini_set('display_errors',1); 
error_reporting(E_ALL);

define("ROOT", __DIR__);

require 'Toro.php';
require 'google-api-php-client/src/Google_Client.php';
require 'google-api-php-client/src/contrib/Google_Oauth2Service.php';
require 'google-api-php-client/src/contrib/Google_PlusService.php';
require 'vendor/facebook/facebook.php';
require 'twitterlib/twitter.php';

$client = new Google_Client();
$client->setApplicationName("Rekishi: Year One");
$client->setClientId('930206244765.apps.googleusercontent.com');
$client->setClientSecret('5YHs-QqrqX8kSClcSER12NT0');
$client->setRedirectUri('http://rekishi.in/login/google/cb');
$client->setDeveloperKey('AIzaSyAbLfaJIOSWDGGSGX3W3RG4JbOJpbWTyAA');
$client->setScopes(array('https://www.googleapis.com/auth/userinfo.email','https://www.googleapis.com/auth/userinfo.profile',
	'https://www.googleapis.com/auth/plus.login'));
$oauth_client = new Google_Oauth2Service($client);
$plus = new Google_PlusService($client);

$facebook = new Facebook(array(
  'appId'  => '275332542591675',
  'secret' => '8fb18f042a8494e15eb35aba98a42c41',
));
$user = $facebook->getUser();


class FacebookHandler {
    function get_xhr(){
        global $user;
        global $facebook;
        require 'lib/facebook.php';
    }
}

class FbLoginHandler {
    function get() {
        global $facebook;
                $loginUrl = $facebook->getLoginUrl( array('scope' => 'email,user_photos,friends_photos,read_stream,read_insights',
                                                  'redirect_uri'=>'http://'.$_SERVER["HTTP_HOST"].'/facebook' ));
        header("Location: ".$loginUrl);
    }
}

class GPlusHandler 
{
    function get_xhr(){
        global $client, $oauth_client, $plus;
        if(isset($_SESSION['token'])){
            $client->setAccessToken($_SESSION['token']);
            $list = $plus->activities->listActivities('me','public', array("maxResults"=>100));
            $arr=array();
            foreach($list['items'] as $post){
                $date = $post['published'];
                if(substr($date,5,5)==date("m")."-".date("d"))
                    array_push($arr, $post['url']);
            }
            echo json_encode($arr);
        }
        else{
            echo json_encode("No session token");
        }   
    }
}

class GoogleHandler {
    function get() {
            global $client;
            $authUrl = $client->createAuthUrl();
            header("Location: ".$authUrl);
    }
}

class GoogleCBHandler {
        function get() {
                global $client;
                if (isset($_GET['code'])) {
                        $client->authenticate();
                        $_SESSION['token'] = $client->getAccessToken();
                        header("Location: /");
                }
        }
}


class TwitterLoginHandler {
    function get(){
        $twitter = new Twitter;
        $twitter->login();
    }
}

class TwitterCBHandler {
    function get(){
        $twitter = new Twitter;
        $twitter->callback();
    }
}

class TwitterHandler {
    function get(){
        $twitter = new Twitter;
        // $twitter->tweets("json");
        $twitter->tweets_json();
    }

    function get_xhr(){
        $twitter = new Twitter;
        $twitter->tweets_json();        
    }
}

class HomePageHandler {
    function get(){
        include 'views/home.php';
    }
}

class LoginHandler {
    function get(){
        include 'views/login.html';
    }
}



class  GithubLoginHandler{
    function get() {
        if(!isset($_GET['github']))
      echo "<form action='/login/github' method='get' ><input type='text' name='github'/><input type='submit' name='Submit'/></form>";
      
      else
        {$_SESSION['ghid']=$_GET['github'];
        header("Location: /github");}
      }
}

class GithubHandler{
    function get() {
        if(!isset($_SESSION["ghid"])){
            header("Location: /login/github");
        }
        else{
            global $_SESSION;
            require 'vendor/dom/simple_html_dom.php';
            require 'lib/github.php';
        }
    }
}


Toro::serve(array(
    "/" => "HomePageHandler",
    "/facebook" => "FacebookHandler",
    "/login/google" => "GoogleHandler",
    "/login/google/cb" => "GoogleCBHandler",
    "/login/facebook" => "FbLoginHandler",
    "/login/twitter" => "TwitterLoginHandler",
    "/login/twitter/cb" => "TwitterCBHandler",
    "/google" => "GPlusHandler",
    "/twitter" => "TwitterHandler",
    "/login"=>"LoginHandler",
    "/login/github" => "GithubLoginHandler",
    "/github" => "GithubHandler"
));