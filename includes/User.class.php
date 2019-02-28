<?php 
/**
 * ====================================================================================
 *                           Premium URL Shortener (c) KBRmedia
 * ----------------------------------------------------------------------------------
 * @copyright This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in an illegal activity. You must delete this software immediately or buy a proper
 *  license from http://codecanyon.net/user/KBRmedia/portfolio?ref=KBRmedia.
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author KBRmedia (http://gempixel.com)
 * @link http://gempixel.com 
 * @license http://gempixel.com/license
 * @package Premium URL Shortener
 * @subpackage User Class
 */
class User extends App{	
	/**
	 * Current Language
	 * @since 4.0
	 **/	
 	public $lang="";
	/**
	 * Items Per Page
	 * @since 4.0
	 **/
	public $limit=15;
	/**
	 * Template Variables
	 * @since 4.0
	 **/
	protected $isHome=FALSE;
	protected $footerShow=TRUE;
	protected $headerShow=TRUE;
	protected $is404=FALSE;
	protected $isUser=FALSE;
	/**
	 * Application Variables
	 * @since 4.0
	 **/
	protected $page=1, $db, $config = array(), $id="", $http="http";
	/**
	 * User Variables
	 * @since 4.0
	 **/
	protected $logged=FALSE;
	protected $admin=FALSE, $user=NULL, $userid="0";	
	/**
	 * Constructor: Checks logged user status
	 * @since 4.2.4
	 **/
	public function __construct($db,$config){
  	$this->config=$config;
  	$this->db=$db;
  	// Clean Request
  	if(isset($_GET)) $_GET=array_map("Main::clean", $_GET);
		if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"]>0) $this->page=Main::clean($_GET["page"]);
		$this->http=((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)?"https":"http");
		$this->check();
		// Check if banned
		if($this->logged() && $this->user->banned){					
			return $this->logout();
		}				
	}	
	/**
	 * Run User Methods
	 * @since 4.0
	 **/
	protected function initiate($do,$id){
		$this->id = $id;
		return $this->$do();
	}
	/**
	 * User Login
	 * @since 4.2.4
	 **/
	protected function login(){
		if(!empty($this->id)){
			// Check if private
			if($this->config["private"] || !$this->config["user"] || $this->config["maintenance"]) Main::redirect("?error",array("danger",e("Sorry, we are not accepting users right now.")));	
			// Get method		
			$fn = "login_{$this->id}";
			if(in_array($this->id, array("facebook","google","twitter")) && method_exists("User",$fn)){				
				return $this->$fn();
			}else{
				return $this->_404();
			}
		}
		// Login Count
		if(!isset($_SESSION["login_count"])){
			$_SESSION["login_count"]=0;
		}
		// Check if form is posted
		if(isset($_POST["token"])){
			// Prevent Bots from submitting the form
			if(Main::bot()) return $this->_404();			
			// Validate CSRF Token
			if(!Main::validate_csrf_token($_POST["token"])){
				return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Invalid token. Please try again.")));
			}
			// Clean Current Session
			$this->logout(FALSE);
			// Block User
			if(Main::cookie("__bl")){
				return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You have been blocked for 1 hour due to many unsuccessful login attempts.")));
			}			
			// Validate Email
			if(empty($_POST["email"])) return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Please enter a valid email or username.")));
			
			// Validate Password
			if(empty($_POST["password"]) || strlen($_POST["password"]) < 5) return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong email and password combination.")));

			// Check captcha
			if($this->config["captcha"]){
				$captcha = Main::check_captcha($_POST);
				if($captcha != 'ok'){
					return Main::redirect(Main::href("user/login","",FALSE),array("danger",e($captcha)));
				}
			}	

			// Check if user exists - Check username and email
			if(!Main::email($_POST["email"])){
				$user = $this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["email"]));
			}else{
				$user = $this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]));
			}		
			if(!$user){
				return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong email and password combination")));
			}			
			// Upgrade password from MD5
			if($user->password === md5($this->config["security"].Main::clean($_POST["password"],3,FALSE))){
				$this->db->update("user",array("password"=>"?"),array("id"=>$user->id),array(Main::encode($_POST["password"])));
			}else{
				// Check new Password
				if(!Main::validate_pass($_POST["password"],$user->password)){
					// Login Attempt Count
					$max=5;
					$_SESSION["login_count"]++;
					if($_SESSION["login_count"] >= $max){
						// Block user for 1 hour
						Main::cookie("__bl",1,60);
					}		
					return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong email and password combination")));
				}
			}
			// Check Auth Key: If empty generate one
			if(empty($user->auth_key)){	
				$user->auth_key=Main::encode(Main::strrand(12));
				// Update database
				$this->db->update("user",array("auth_key"=>"?"),array("id"=>$user->id),array($user->auth_key));
			}

			// Check if banned
			if($user->banned){
				return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
			}
			// Check if inactive
			if(!$user->active){
				return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
			}
			// Check if expired
			if(strtotime($user->expiration) < time()){
				$this->db->update("user",array("pro"=>0),array("id"=>$user->id));
			}
			// Set Session
			$json=base64_encode(json_encode(array("loggedin"=>TRUE,"key"=>$user->auth_key.$user->id)));
			if(isset($_POST["rememberme"]) && $_POST["rememberme"]=="1"){
				// Set Cookie for 14 days
				setcookie("login",$json, time()+60*60*24*14, "/","",FALSE,TRUE);
			}else{
				$_SESSION["login"]=$json;
			}
			if(isset($_SESSION["redirect"])){
				$r = Main::clean($_SESSION["redirect"], 3, TRUE);
				unset($_SESSION["redirect"]);
				return Main::redirect($r ,array("success",e("You have been successfully logged in.")));	
			}
			// Return to /user
			return Main::redirect("",array("success",e("You have been successfully logged in.")));
		}

		// Set meta info
		Main::set("title",e("Login to your account"));
		Main::set("description","Login to your account and bookmark your favorite sites.");		
		Main::set("url","{$this->config["url"]}/user/login");	

		$this->headerShow=FALSE;
		$this->footerShow=FALSE;
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();		
	}
			/**
			 * User Login with Facebook
			 * @since 5.0
			 **/
			private function login_facebook(){
		    //Facebook Auth
		    if(!$this->config["fb_connect"]) return Main::redirect("",array("danger",e("Sorry, Facebook connect is not available right now.")));
		    if(isset($_GET["error"])) return Main::redirect("",array("danger",e("You must grant access to this application to use your facebook account.")));

				include 'library/auth/Facebook/autoload.php';
				
				$fb = new Facebook\Facebook([
				  'app_id' => $this->config["facebook_app_id"],
				  'app_secret' => $this->config["facebook_secret"],
				  'default_graph_version' => 'v2.12',
			  ]);				
		    

				$helper = $fb->getRedirectLoginHelper(Main::href("user/login/facebook"));

				try {
				  $accessToken = $helper->getAccessToken();
				} catch(Facebook\Exceptions\FacebookResponseException $e) {
					// Graph Error
				  error_log($e->getMessage());
				  return Main::redirect("",array("danger",e("An error has occured. Please try again later.")));
				} catch(Facebook\Exceptions\FacebookSDKException $e) {
					// SDK Error
					error_log($e->getMessage());
				  return Main::redirect("",array("danger",e("An error has occured. Please try again later.")));
				}

		    if(isset($accessToken) && !empty($accessToken)) { 
		    	
				  $request = $fb->get('/me?fields=id,email,name', $accessToken);
		 			$FBuser = $request->getGraphUser();

		      if(!$FBuser->getEmail()) return Main::redirect("",array("danger",e("You must grant permission to this application to use your profile information.")));

		      // Check if email is already taken
		      if($this->db->get("user","auth!='facebook' AND email='".$FBuser->getEmail()."'",array("limit"=> "1"))){
		      	 return Main::redirect("user/login",array("danger",e("The email linked to your account has been already used. If you have used that, please login to your existing account otherwise please contact us."))); 
		      }

		      // Let's see if the user is registered
		      if($user = $this->db->get("user","auth='facebook' AND (email='".$FBuser->getEmail()."' OR auth_id='".$FBuser->getId()."')",array("limit" => "1"))){

						// Check Auth Key: If empty generate one
						if(empty($user->auth_key)){	
							$user->auth_key=Main::encode(Main::strrand(12));
							// Update database
							$this->db->update("user",array("auth_key" => "?"),array("id"=>$user->id),array($user->auth_key));
						}
						// Inser AuthID
						if(empty($user->auth_id) && $FBuser->getId()){	
							// Update database
							$this->db->update("user",array("auth_id" => "?"),array("id" => $user->id),array($FBuser->getId()));
						}

						// Check if banned
						if($user->banned){
							return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
						}
						// Check if inactive
						if(!$user->active){
							return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
						}

		      }else{		      	
		      	// Let's register the user
		      	$auth_key = Main::encode(Main::strrand(12));
		      	$data = array(
		      			":email" => Main::clean($FBuser->getEmail(),3,TRUE),
		      			":username" => "",
		      			":password" => Main::encode(Main::strrand(12)),
		      			":date" => "NOW()",
		      			":auth" => "facebook",
		      			":auth_id" => $FBuser->getId() ? Main::clean($FBuser->getId(),3,TRUE) : "",
		      			":api" => Main::strrand(12),
		      			":auth_key" => $auth_key
		      		);
		      	if($this->db->insert("user", $data)){
							$user=$this->db->get("user",array("auth"=>"facebook","email" => $FBuser->getEmail()),array("limit" => "1"));    		
		      	}
		      }
					// Ok Let's login te user
					$json=base64_encode(json_encode(array("loggedin"=>TRUE, "key"=>$user->auth_key.$user->id)));
					$_SESSION["login"] = $json;

					// Return to /user
					return Main::redirect("",array("success",e("You have been successfully logged in.")));
		    }else{  

		    	// Redirect to facebook
					$loginUrl = $helper->getLoginUrl(Main::href("user/login/facebook"), ["email"]);		  
		      header("Location: ".$loginUrl);  
		      return;
		    }  
			}
			/**
			 * User Login with Twitter
			 * @since 4.0
			 **/			
			private function login_twitter(){
				// Check for error
		    if(isset($_GET["denied"])) return Main::redirect("",array("danger",e("You must grant permission to this application to use your twitter account.")));

		    if(!$this->config["tw_connect"]) return Main::redirect("",array("danger",e("Sorry, Twitter connect is not available right now.")));
		    // Get Library
				require(ROOT."/includes/library/auth/twitter.php"); 

		    if(!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret'])){

		      $twitteroauth = new TwitterOAuth($this->config["twitter_key"], $this->config["twitter_secret"], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		      $access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']); 
		      // Save it in a session var 
		      $_SESSION['access_token'] = $access_token; 
		      // Let's get the user's info 
		      $tw = $twitteroauth->get('account/verify_credentials');

		      if(!isset($tw->id)) return Main::redirect("",array("danger",e("An error occured, please try again later.")));
		      // Let's see if the user is registered
		      if($user=$this->db->get("user","auth='twitter' AND auth_id='{$tw->id}'",array("limit"=>1))){

						// Check Auth Key: If empty generate one
						if(empty($user->auth_key)){	
							$user->auth_key=Main::encode(Main::strrand(12));
							// Update database
							$this->db->update("user",array("auth_key"=>"?"),array("id"=>$user->id),array($user->auth_key));
						}

						// Check if banned
						if($user->banned){
							return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
						}
						// Check if inactive
						if(!$user->active){
							return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
						}

		      }else{ 
		      	// Let's register the user
		      	$auth_key = Main::encode(Main::strrand(12));
		      	$data = array(
		      			":email" => "",
		      			":username" => isset($tw->screen_name) ? Main::clean($tw->screen_name,3,TRUE) : "",
		      			":password" => Main::encode(Main::strrand(12)),
		      			":date" => "NOW()",
		      			":auth" => "twitter",
		      			":auth_id" => isset($tw->id) ? Main::clean($tw->id,3,TRUE) : "",
		      			":api" => Main::strrand(12),
		      			":auth_key" => $auth_key
		      		);
		      	if($this->db->insert("user",$data)){
							$user=$this->db->get("user",array("auth"=>"twitter","auth_id"=>$tw->id),array("limit"=>1));    		
		      	}
		      }
					// Ok Let's login te user
					$json=base64_encode(json_encode(array("loggedin"=>TRUE,"key"=>$user->auth_key.$user->id)));
					$_SESSION["login"]=$json;

					// Return to /user
					return Main::redirect("",array("success",e("You have been successfully logged in.")));

		    }
		    // The TwitterOAuth instance  
		    $twitteroauth = new TwitterOAuth($this->config["twitter_key"],$this->config["twitter_secret"]); 
		    // Requesting authentication tokens, the parameter is the URL we will be redirected to  
		    $request_token = $twitteroauth->getRequestToken("{$this->config["url"]}/user/login/twitter");
		    // Saving them into the session  
		    $_SESSION['oauth_token'] = $request_token['oauth_token'];  
		    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];  
		    // If everything goes well..  
		    if($twitteroauth->http_code==200){  
	        // Let's generate the URL and redirect  
	        $url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']); 
	        header('Location: '. $url); 
	        exit;
		    } else { 
		      return Main::redirect("user/login",array('danger','An error has occured! Please make sure that you have set up this application as instructed.'));  
		    }		    
			}
			/**
			 * User Login with Google
			 * @since 4.2
			 **/			
			private function login_google(){
				// Check to make sure Google Auth is enabled
				if(!$this->config["gl_connect"] || empty($this->config["google_cid"]) || empty($this->config["google_cs"])) {
					return Main::redirect("",array("danger",e("Sorry, Google connect is not available right now.")));
				}
				// Get Class
				require(ROOT."/includes/library/auth/google.php"); 
		    try {
		    	$google = new Google_Auth($this->config["google_cid"], $this->config["google_cs"], Main::href("user/login/google"), FALSE);

		    	if(!is_null($google->error)){
		    		return Main::redirect("",array("danger",$google->error));
		    	}
		    	
		    	$go = $google->info();
		    	if($go){
						if(!isset($go->email) || empty($go->email)){
			    		echo "Kitty";
							return Main::redirect("",array("danger",e("You must grant permission to this application to use your google account.")));
			    	}
						// Check if email is already taken
						if($this->db->get("user","auth!='google' AND email='{$go->email}'",array("limit"=>1))){
							 return Main::redirect("user/login",array("danger",e("The email linked to your account has been already used. If you have used that, please login to you existing account otherwise please contact us."))); 
						}

						// Let's see if the user is registered
						if($user=$this->db->get("user",array("auth"=>"google","email"=>$go->email),array("limit"=>1))){

							// Check Auth Key: If empty generate one
							if(empty($user->auth_key)){	
								$user->auth_key=Main::encode(Main::strrand(12));
								// Update database
								$this->db->update("user",array("auth_key"=>"?"),array("id"=>$user->id),array($user->auth_key));
							}

							// Check if banned
							if($user->banned){
								return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
							}
							// Check if inactive
							if(!$user->active){
								return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
							}
						}else{
							// Let's register the user
							$auth_key = Main::encode(Main::strrand(12));
							$data = array(
									":email" => Main::clean($go->email,3,TRUE),
									":username" => isset($go->name) ? Main::slug($go->name).rand(1,100) : "",
									":password" => Main::encode(Main::strrand(12)),
									":date" => "NOW()",
									":auth" => "google",
									":api" => Main::strrand(12),
									":auth_key" => $auth_key
								);
							if($this->db->insert("user",$data)){
								$user=$this->db->get("user",array("auth"=>"google","email"=>$go->email),array("limit"=>1));    		
							}
						}
						// Ok Let's login te user
						$json=base64_encode(json_encode(array("loggedin"=>TRUE,"key"=>$user->auth_key.$user->id)));
						$_SESSION["login"]=$json;

						// Return to /user
						return Main::redirect("",array("success",e("You have been successfully logged in.")));	
		    	}
        
		    } catch(ErrorException $e) {
		      return Main::redirect("",array("danger",e("An error occured, please try again later.")));
		    }
    		exit;
			}
	/**
	 * User Logout
	 * @since 4.0
	 **/
	protected function logout($redirect=TRUE){
		// Destroy Cookie
		if(isset($_COOKIE["login"])) setcookie('login','',time()-3600,'/');
		// Destroy Session
		if(isset($_SESSION["login"])) unset($_SESSION["login"]);
		if($redirect) return Main::redirect("");
	}
	/**
	 * User Register
	 * @since 4.0
	 **/
	protected function register(){
		// If user Module is disabled		
		if(!$this->config["user"] || $this->config["private"] || $this->config["maintenance"]) return Main::redirect("",array("danger",e("We are not accepting users at this time.")));

		// Filter ID
		$this->filter($this->id);
		// Check if form is posted
		if(isset($_POST["token"])){
			// Don't let bots register
			if(Main::bot()) return $this->_404();			
			// Validate CSRF Token
			if(!Main::validate_csrf_token($_POST["token"])){
				return Main::redirect(Main::href("user/register","",FALSE),array("danger",e("Invalid token. Please try again.")));
			}
			$error="";	
			// Validate Email
			if(empty($_POST["email"]) || !Main::email($_POST["email"])) $error.="<span>".e("Please enter a valid email.")."</span>";
			// Check email in database
			if(!empty($_POST["email"]) && $this->db->get("user",array("email"=>"?"),"",array($_POST["email"]))) return Main::redirect(Main::href("user/register","",FALSE),array("danger",e("An account is already associated with this email.")));
			// Check Password
			if(empty($_POST["password"]) || strlen($_POST["password"])<5) $error.="<span>".e("Password must contain at least 5 characters.")."</span>";
			// Check second password
			if(empty($_POST["cpassword"]) || $_POST["password"]!==$_POST["cpassword"]) $error.="<span>".e("Passwords don't match.")."</span>";

			// Check captcha
			if($this->config["captcha"]){
				$captcha=Main::check_captcha($_POST);
				if($captcha!='ok'){
					$error.="<span>".$captcha."</span>";
				}
			}	

			// Check terms
			if(!isset($_POST["terms"]) || (empty($_POST["terms"]) || $_POST["terms"]!=="1")) $error.="<span>".e("You must agree to our terms of service.")."</span>";

			// Generate unique auth key
			$auth_key=Main::encode($this->config["security"].Main::strrand());
			$unique=Main::strrand(12);
			// Prepare Data
			$data=array(
					":email"=>Main::clean($_POST["email"],3),
					":password"=>Main::encode($_POST["password"]),
					":auth_key"=>$auth_key,
					":api"=>$unique,
					":date"=>"NOW()"
				);
			// Validate Name
			if(!empty($_POST["username"])){
				if (!Main::username($_POST["username"])){
				  $error.="<span>".e("Please enter a valid username.")."</span>";
				}elseif($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
					$error.="<span>".e("An account is already associated with this username.")."</span>";
				}else{
					$data[":username"]=Main::slug(Main::clean($_POST["username"],3,TRUE));
				}
			}	

			// Return errors
			if(!empty($error)) Main::redirect(Main::href("user/register","",FALSE),array("danger",$error));
				
			// Check if user activation is required
			if($this->config["user_activate"]) $data[":active"]="0";

			// Register User
			if($this->db->insert("user",$data)){		

				// Send Activation Email
				if($this->config["user_activate"]){
					// Send Email
					$mail["to"]=Main::clean($_POST["email"],3);
					$key=str_replace("=","",base64_encode("P1U2{$unique}".Main::strrand(5)));
					$activate="{$this->config["url"]}/user/activate/$key?email={$mail["to"]}";

					$mail["subject"]="[{$this->config["title"]}] Registration has been successful.";							
					$mail["message"]="<b>Hello!</b>
		      	<p>You have been successfully registered at {$this->config["title"]}. To login you will have to activate your account by clicking the URL below.</p>
		      	<p><a href='$activate' target='_blank'>$activate</a></p>";

		      Main::send($mail);
					return Main::redirect(Main::href("user/login","",FALSE),array("success",e("An email has been sent to activate your account. Please check your spam folder if you didn't receive it.")));
				}

				// Send Email
				$mail["to"]=Main::clean($_POST["email"],3);
				$mail["subject"]="[{$this->config["title"]}] Registration has been successful.";
				$mail["message"]="<b>Hello</b>
	      	<p>You have been successfully registered at {$this->config["title"]}. You can now login to our site at <a href='{$this->config["url"]}'>{$this->config["url"]}</a></p>";

	      Main::send($mail);				
				return Main::redirect(Main::href("user/login","",FALSE),array("success",e("You have been successfully registered.")));				
			}
		}
		// Set Meta titles
		Main::set("body_class","dark");
		Main::set("title",e("Register and manage your urls."));
		Main::set("description","Register an account and gain control over your urls. Manage them, edit them or remove them without hassle.");
		$this->headerShow=FALSE;
		$this->footerShow=FALSE;
		
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();		
	}	
	/**
	 * User Activate
	 * @since 4.0
	 **/
	protected function activate(){
		if(Main::bot()) return $this->_404();
		if(!empty($this->id)){
			$email=Main::clean($_GET["email"],3,TRUE);
			$id=str_replace("P1U2","",base64_decode($this->id));
			$id=substr($id, 0,12);
			if($user=$this->db->get("user",array("api"=>"?","active"=>"0","email"=>"?"),array("limit"=>1),array($id,$email))){
				$this->db->update("user",array("active"=>"1"),array("id"=>$user->id));
				// Send Email
				$mail["to"]=Main::clean($user->email,3);
				$mail["subject"]="[{$this->config["title"]}] Your account has been activated.";
				$mail["message"]="<b>Hello</b><p>Your account has been successfully activated at {$this->config["title"]}.</p>";

	      Main::send($mail);
				return Main::redirect(Main::href("user/login","",FALSE),array("success",e("Your account has been successfully activated.")));
			}
		}
		return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong activation token or account already activated.")));
	}
	/**
	 * User Forgot
	 * @since 4.0
	 **/
	protected function forgot(){
		// Change Password if valid token
		if(isset($this->id) && !empty($this->id)){
			$new=base64_decode($this->id);
			$key=substr($new, 12);
			$unique=substr($new, 0,12);
			if($key==Main::encode($this->config["security"].": Expires on".strtotime(date('Y-m-d')),"md5")){
				// Change Password
				if(isset($_POST["token"])){
					// Validate CSRF Token
					if(!Main::validate_csrf_token($_POST["token"])){
						return Main::redirect(Main::href("user/forgot/{$this->id}","",FALSE),array("danger",e("Invalid token. Please try again.")));
					}
					// Check Password
					if(empty($_POST["password"]) || strlen($_POST["password"])<5) return Main::redirect(Main::href("user/forgot/{$this->id}","",FALSE),array("danger",e("Password must contain at least 5 characters.")));
					// Check second password
					if(empty($_POST["cpassword"]) || $_POST["password"]!==$_POST["cpassword"]) return Main::redirect(Main::href("user/forgot/{$this->id}","",FALSE),array("danger",e("Passwords don't match.")));
					// Add to database
					$auth_key=Main::encode(Main::strrand(12));
					if($this->db->update("user",array("password"=>"?","auth_key"=>"?"),array("api"=>"?"),array(Main::encode($_POST["password"]),$auth_key,$unique))){
						return Main::redirect(Main::href("user/login","",FALSE),array("success",e("Your password has been changed.")));
					}
				}
				// Set Meta titles
				Main::set("body_class","dark");
				Main::set("title",e("Reset Password"));
				$this->headerShow=FALSE;
				$this->footerShow=FALSE;

				$this->header();
				include($this->t(__FUNCTION__));
				$this->footer();
				return;
			}
			return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e("Token has expired, please request another link.")));
		}		
		// Check if form is posted to send token
		if(isset($_POST["token"])){
			// Validate CSRF Token
			if(!Main::validate_csrf_token($_POST["token"])){
				return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e("Invalid token. Please try again.")));
			}
			// Validate email
			if(empty($_POST["email"]) || !Main::email($_POST["email"])) return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e("Please enter a valid email.")));
				
			// Check captcha
			if($this->config["captcha"]){
				$captcha = Main::check_captcha($_POST);
				if($captcha != 'ok'){
					return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e($captcha)));
				}
			}	

			// Check email
			if($user=$this->db->get("user",array("email"=>"?","banned"=>"0"),array("limit"=>1),array($_POST["email"]))){
				// Generate key
				$forgot_url=Main::href("user/forgot/".str_replace("=","", base64_encode($user->api.Main::encode($this->config["security"].": Expires on".strtotime(date('Y-m-d')),"md5"))));
		 		$mail["to"] = Main::clean($user->email);
		    $mail["subject"] = "[{$this->config["title"]}] Password Reset Instructions";
				$mail["message"] = "
		      <p><b>A request to reset your password was made.</b> If you <b>didn't</b> make this request, please ignore and delete this email otherwise click the link below to reset your password.</p>
		      <a href='$forgot_url' class='link'><b>Click here to reset your password.</b></a>
		      <p>If you cannot click on the link above, simply copy &amp; paste the following link into your browser.</p>
		      <a href='$forgot_url' class='link'>$forgot_url</a>
		      <p><b>Note: This link is only valid for one day. If it expires, you can request another one.</b></p>";		
		    // Send email
		    Main::send($mail);
			}			
			return Main::redirect(Main::href("user/login","",FALSE),array("success",e("If an active account is associated with this email, you should receive an email shortly.")));
		}
		return Main::redirect(Main::href("user/login#forgot","",FALSE));
	}		
	/**
	 * Search URLs (AJAX only)
	 * @since v4.0
	 */	
	private function search(){
		if(!isset($_POST["token"]) || $_POST["token"]!==$this->config["public_token"]) return $this->_404();
		// Prevent Bots from submitting the form
		if(Main::bot()) return $this->_404();
		$q=Main::clean($_POST["q"],3);
		if(!empty($q) && strlen($q)>=3){
			if($urls=$this->db->search("url",array(array("userid",$this->userid),"url"=>":q","alias"=>":q","custom"=>":q","meta_title"=>":q","description"=>":q"),array("order"=>"date"),array(":q"=>"%{$q}%"))){
				echo "<a href='#clear' class='btn btn-xs btn-default clear-search'>".e('Clear Search')."</a>";
				foreach ($urls as $url) {
					include(TEMPLATE."/shared/url_loop.php");
				}
				return;
			}
		}
			echo "<a href='#clear' class='btn btn-xs btn-default clear-search'>".e('Clear Search')."</a>
			<div class='alert alert-danger'>".e('Nothing found.')."</div>";
	}
	/**
	 * Arhive
	 * @since 44.0
	 **/
	protected function archive(){
		// Get URLs
		$order=array("date",FALSE,"newest");
		if(isset($_GET["sort"])){
			if(Main::clean($_GET["sort"],3,TRUE)=="popular"){
				$order=array("click",FALSE,"popular");
			}elseif(Main::clean($_GET["sort"],3,TRUE)=="oldest"){
				$order=array("date",TRUE,"oldest");
			}
		}
		$urls=$this->db->get("url",array("userid"=>"?","archived"=>"1"),array("order"=>$order[0],"limit"=>(($this->page-1)*$this->limit).", {$this->limit}","count"=>TRUE,"asc"=>$order[1]),array($this->userid));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }   
    if($this->page > 1 && $this->page > $max) Main::redirect("user",array("danger","No URLs found."));
    $pagination = Main::pagination($max,$this->page,Main::href("user/archive?filter={$order[2]}&amp;page=%d"));

    // Show Template		
		$this->isUser=TRUE;
		Main::cdn("datepicker");
		Main::set("title",e("Archived URLs"));
		$this->header();
		include($this->t("user"));
	 	$this->footer();		
	}
	/**
	 * Expired URLs
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @return  [type] [description]
	 */
	protected function expired(){
		// Get URLs
		$order = array("date",FALSE,"newest");

		if(isset($_GET["sort"])){
			if(Main::clean($_GET["sort"],3,TRUE)=="popular"){
				$order=array("click",FALSE,"popular");
			}elseif(Main::clean($_GET["sort"],3,TRUE)=="oldest"){
				$order=array("date",TRUE,"oldest");
			}
		}

		$urls = $this->db->get("url","userid = '{$this->userid}' AND expiry < DATE(CURDATE()) AND archived = '0'", array("order"=>$order[0], "limit"=>(($this->page-1)*$this->limit).", {$this->limit}","count"=>TRUE,"asc"=>$order[1]),array($this->userid));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }   
    if($this->page > 1 && $this->page > $max) Main::redirect("user",array("danger","No URLs found."));
    $pagination = Main::pagination($max,$this->page,Main::href("user/expired?filter={$order[2]}&amp;page=%d"));

    // Show Template		
		$this->isUser=TRUE;
		Main::cdn("datepicker");
		Main::set("title",e("Expired URLs"));
		$this->header();
		include($this->t("user"));
	 	$this->footer();		
	}	
	/**
	 * Bundles
	 * @since  4.3
	 **/
	protected function bundles(){
		if(isset($_POST["token"])){
			if($this->id=="add"){
				// Validate CSRF Token
				if(!Main::validate_csrf_token($_POST["token"])){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Invalid token. Please try again.")));
				}
				if(empty($_POST["name"]) || strlen(Main::clean($_POST["name"]))<2){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Bundle name cannot be empty and must have at least 2 characters.")));
				}
				if($this->db->get("bundle",array("name"=>"?","userid"=>"?"),"",array(Main::clean(trim($_POST["name"])),$this->userid))){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("You already have a bundle with that name.")));
				}
				$data = array(
					":name"=> ucfirst(Main::clean($_POST["name"],3,TRUE)),
					":access"=> in_array(Main::clean($_POST["access"],3,TRUE), array("public","private"))?Main::clean($_POST["access"],3,TRUE):"private",
					":userid"=> $this->userid,
					":date"=>"NOW()"
					);
				if($this->db->insert("bundle",$data)){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("Bundle was successfully created. You may start adding URLs in it now.")));
				}						
			}
			if($this->id=="edit"){
				// Validate CSRF Token
				if(!Main::validate_csrf_token($_POST["token"])){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Invalid token. Please try again.")));
				}
				if(empty($_POST["name"]) || strlen(Main::clean($_POST["name"]))<2){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Bundle name cannot be empty and must have at least 2 characters.")));
				}
				if($this->db->get("bundle","name=? AND userid=? AND id!=?","",array(Main::clean(trim($_POST["name"])),$this->userid,Main::clean($_POST["id"])))){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("You already have a bundle with that name.")));
				}
				$data = array(
					":name"=> ucfirst(Main::clean($_POST["name"],3,TRUE)),
					":access"=> in_array(Main::clean($_POST["access"],3,TRUE), array("public","private"))?Main::clean($_POST["access"],3,TRUE):"private"
					);
				if($this->db->update("bundle","",array("userid"=>$this->userid,"id"=>Main::clean($_POST["id"],3,TRUE)),$data)){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("Bundle has been updated.")));
				}	
			}
			if($this->id=="update"){
				if(!Main::validate_csrf_token($_POST["token"])){
					Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Something went wrong, please try again.")));
					return;
				}	
				// Check if user owns bundle
				if(!empty($_POST["bundle_id"]) && !$this->db->get("bundle",array("id"=>"?","userid"=>"?"),"",array(Main::clean($_POST["bundle_id"],3,TRUE),$this->userid))){
					return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Something went wrong, please try again.")));					
				}
				if($this->db->update("url",array("bundle"=>"?"),array("id"=>"?","userid"=>"?"),array(Main::clean($_POST["bundle_id"],3,TRUE),Main::clean($_POST["url_id"],3,TRUE),$this->userid))){
					Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("This URL has been added to the bundle.")));
					return;				
				}					
			}
			return Main::redirect(Main::href("user/bundles","",FALSE));
		}

		$this->filter($this->id);
		$bundles=$this->db->get("bundle",array("userid"=>"?"),array("order"=>"date","count"=>TRUE,"limit"=>($this->page-1)*$this->limit.", {$this->limit}"),array($this->userid));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }   

    if($this->page > 1 && $this->page > $max) Main::redirect("user/bundles",array("danger",e("No URLs found.")));
    $pagination = Main::pagination($max,$this->page,Main::href("user/bundles?page=%d"));		
    // Show Template
		$this->isUser=TRUE;
		Main::set("title",e("Manage your bundles"));
		Main::set("description","Manage your bundles, share them or delete them.");
		$this->header();
		include($this->t(__FUNCTION__));
	 	$this->footer();	
	}
  /**
   * Delete URL
   * @since 4.0
   **/
  private function delete(){
    // Mass Delete URLs
    if(isset($_POST["token"]) && isset($_POST["delete-id"]) && is_array($_POST["delete-id"])){
      // Validate Token
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::href("user","",FALSE),array("danger",e("Invalid token. Please try again.")));
      }     
      $query="(";
      $query2="(";
      $c=count($_POST["delete-id"]);
      $p = [];
      $i = 1;
      foreach ($_POST["delete-id"] as $id) {
        if($i>=$c){
          $query.="(`alias` = :id$i OR `custom`= :id$i)";
          $query2.="`short` = :id$i";
        }else{
          $query.="(`alias` = :id$i OR `custom`= :id$i) OR ";
          $query2.="`short` = :id$i OR ";
        }

        $p[':id'.$i]=$id;
        $i++;
      }  
      $p[":user"]=$this->userid;
      $query.=") AND userid=:user";
      $query2.=") AND urluserid=:user";
      $this->db->delete("url", $query, $p);
      $this->db->delete("stats", $query2, $p);
      return Main::redirect(Main::href("user","",FALSE),array("success",e("Selected URLs have been deleted.")));
    }        
    // Delete single URL
    if(!empty($this->id) && is_numeric($this->id)){
      // Validated Nonce
      if(Main::validate_nonce("delete_url-{$this->id}")){
	      $url=$this->db->get("url",array("id"=>"?","userid"=>"?"),array("limit"=>1),array($this->id,$this->userid));
	      $this->db->delete("url",array("id"=>"?","userid"=>"?"),array($this->id,$this->userid));
	      $this->db->delete("stats",array("short"=>"?"),array($url->alias.$url->custom));
	      return Main::redirect(Main::href("user","",FALSE),array("success",e("URL has been deleted.")));
      }
      // Validated Nonce
      if(Main::validate_nonce("delete_bundle-{$this->id}")){
				$this->db->update("url",array("bundle"=>"?"),array("userid"=>"?","bundle"=>"?"),array("",$this->userid,$this->id));
				$this->db->delete("bundle",array("userid"=>"?","id"=>"?"),array($this->userid,$this->id));
				return Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("This bundle has been deleted.")));
      }  
     // Validated Nonce
      if(Main::validate_nonce("delete_splash-{$this->id}")){
      	return $this->splash_delete();
      }           
    } 
    return Main::redirect(Main::href("user","",FALSE),array("danger",e("Security token expired. Please try again.")));
  }
  /**
   * Edit URLs
   * @since 5.0
   **/
  private function edit(){
  	// Edit URL
  	if(isset($_POST["token"])){
			if(!Main::validate_csrf_token($_POST["token"])) {
				Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
				return;
			}
			if($this->config["demo"]){
				Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
				return;
			}			
			if($this->pro()){
				if(empty($_POST["url"]) || !Main::is_url($_POST["url"])) {
					return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("danger",e("Please enter a valid URL.")));
				}
			}

			if(!empty($_POST['location'][0]) && !empty($_POST['target'][0])){
					foreach ($_POST['location'] as $i => $country) {
						if(!empty($country) && !empty($_POST['target'][$i]) && Main::is_url($_POST['target'][$i])){
							$countries[strtolower(Main::clean($country,3,TRUE))]=$_POST['target'][$i];
					  }
					}
					$countries=json_encode($countries);
				}else{
					$countries='';
				}
				$data = array(			
						":location" => $countries,
						":pass" => Main::clean($_POST["pass"],3,TRUE),
						":description"=> Main::clean($_POST["description"],3,TRUE),
						":public" => in_array($_POST["public"], array("0","1")) ? Main::clean($_POST["public"]) : 0,
						":domain" => (isset($_POST["domain"]) && !empty($_POST["domain"]) && $this->validate_domain_names($_POST["domain"])) ? Main::clean($_POST["domain"],TRUE,3) : ""
					);
				// Pro users only
				if($this->pro()){
					$data[":url"] = Main::clean($_POST["url"],3,TRUE);					
					//Edit URL
					if(in_array($_POST["type"], array("direct","frame","splash","overlay")) || is_numeric($_POST["type"])){
						$data[":type"]=Main::clean($_POST["type"],3,TRUE);
					}
				}
				if($this->db->update("url","",array("id"=>$this->id, "userid"=>$this->userid),$data)){
					return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("success",e("This URL has been successfully updated.")));
				}
				return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE));
  	}
  	if(empty($this->id) || !is_numeric($this->id)) return Main::redirect("user",array("danger",e("This URL doesn't exist.")));
		if(!$url = $this->db->get("url",array("id"=>"?", "userid" => "?"),array("limit"=>1),array($this->id, $this->userid))) return Main::redirect("user",array("danger",e("This URL doesn't exist.")));

		if($this->config["multiple_domains"]){
			$domain_list='<div class="form-group"><label class="col-sm-3 control-label">'.e("Domain Name").'</label><div class="col-sm-9"><select name="domain" id="domains">';
			$domain_list.='<optgroup label="'.e('Choose Domain').'" />';
			$domains=explode("\n", $this->config["domain_names"]);
			$domain_list.='<option value="" '.(($url->domain  == $this->config["url"] || empty($url->domain) || !$url->domain)?'selected':'').'>'.ucfirst(str_replace("https://","",str_replace("http://", "",$this->config["url"]))).'</option>';
			foreach ($domains as $domain) {
				$domain_list.='<option value="'.strtolower(rtrim($domain)).'" '.($url->domain ==strtolower(rtrim($domain))?'selected':'').'>'.ucfirst(str_replace("https://","",str_replace("http://", "", trim($domain)))).'</option>';
			}
			$domain_list.='</select></div></div> ';
		}else{
			$domain_list="";
		}

    $before="<div class='form-group country hide' style='display:none'>
                <div class='col-sm-6'>
                  <label>Country</label>
                    <select name='location[]'>
                      ".Main::countries()."
                    </select>
                </div>
                <div class='col-sm-6'>
                <label>URL</label>
                  <input type='text' class='form-control' name='target[]' id='meta_description' value=''>                          
                </div>
              </div>";		

		$header=e("Edit URL");
    $content="      
	    <form action='".Main::href("user/edit/{$url->id}")."' method='post' class='form-horizontal' role='form'>
	      <div class='form-group'>
	        <label for='url' class='col-sm-3 control-label'>".e("Long URL")."</label>
	        <div class='col-sm-9'>
	          <input type='url' class='form-control' name='url' id='url' value='{$url->url}'>
	          <p class='help-block'>Please note that only pro users can edit URLs once they are shortened.</p>
	        </div>
	      </div>  

	      <div class='form-group'>
	        <label for='alias' class='col-sm-3 control-label'>Alias</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' name='alias' id='alias' value='{$url->alias}' disabled>
	          <p class='help-block'>The short alias cannot be changed.</p>
	        </div>
	      </div>  

	      <div class='form-group'>
	        <label for='custom' class='col-sm-3 control-label'>Custom</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' id='custom' value='{$url->custom}' disabled>
	          <p class='help-block'>The custom alias cannot be changed.</p>
	        </div>
	      </div> 

	      <div class='form-group'>
	        <label for='pass' class='col-sm-3 control-label'>".e("Password")."</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' name='pass' id='pass' value='{$url->pass}'>
	          <div class='help-block'>".e("Note that the password might be encrypted. To update this simply enter the password again.")."</div>
	        </div>
	      </div>

	      <div class='form-group'>
	        <label for='description' class='col-sm-3 control-label'>".e("Note")." (".e("optional").")</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' name='description' id='description' value='{$url->description}'>
	        </div>
	      </div>
	      $domain_list";
	  if($this->pro()){
	  	$splash = $this->db->get("splash",array("userid"=>"?"),array("order"=>"date"),array($this->userid));
	  	$content.="<hr><div class='form-group'>
	        <label for='description' class='col-sm-3 control-label'>".e("Redirection")."</label>
	        <div class='col-sm-9'>
			      <select name='type'>
			      	<optgroup label='".e("Redirection")."'>
			        <option value='direct'".($url->type=="direct" || $url->type=="" ?" selected":"").">".e('Direct')."</option>
			        <option value='frame'".($url->type=="frame"?" selected":"").">".e('Frame')."</option>
			        <option value='splash'".($url->type=="splash"?" selected":"").">".e('Splash')."</option>
			        <option value='overlay'".($url->type=="overlay"?" selected":"").">".e("Overlay")."</option>";
						if($splash){
							$content.='<optgroup label="'.e('Custom Splash').'">';
							foreach ($splash as $type) {
								$content.='<option value="'.$type->id.'"'.($url->type==$type->id?" selected":"").'>'.ucfirst($type->name).'</option>"';
							}				
							$content.="</optgroup>";
						}		        
			$content.="</select>	          
			        </div>
			      </div>	      
				    <div class='form-group'>
			        <label for='meta_title' class='col-sm-3 control-label'>".e("Meta Title")."</label>
			        <div class='col-sm-9'>
			          <input type='text' class='form-control' name='meta_title' id='meta_title' value='{$url->meta_title}'>
			        </div>
			      </div>
			      <div class='form-group'>
			        <label for='meta_description' class='col-sm-3 control-label'>".e("Meta Description")."</label>
			        <div class='col-sm-9'>
			          <textarea class='form-control' name='meta_description' id='meta_description'>{$url->meta_description}</textarea>
			        </div>
			      </div>"; 			      
	  }
			$content.="<hr>
		      <h4>".e("Geotargeting Data")." <a href='#' class='btn btn-primary btn-xs pull-right add_geo'>".e("Add a Field")."</a></h4>
		      <div id='geo'>";    	
		    if(!empty($url->location)){		      
	        $geo=json_decode($url->location);
	        foreach ($geo as $country => $link){
	          $content.="<div class='form-group'>
	                      <div class='col-sm-6'>
	                        <label>".e("Country")."</label>
	                          <select name='location[]'>
	                            ".Main::countries($country)."
	                          </select>
	                      </div>
	                      <div class='col-sm-6'>
	                      <label>".e("Long URL")."</label>
	                        <input type='text' class='form-control' name='target[]' id='meta_description' value='$link'>                          
	                      </div>
	                    </div><p><a href='#' class='btn btn-danger btn-xs delete_geo' data-holder='div.form-group'>".e("Delete")."</a></p>";
	        }
        }
      $content.="<div class='form-group'>
                  <div class='col-sm-6'>
                    <label>".e("Country")."</label>
                      <select name='location[]'>
                        ".Main::countries()."
                      </select>
                  </div>
                  <div class='col-sm-6'>
                  <label>".e("Long URL")."</label>
                    <input type='text' class='form-control' name='target[]' id='target_url' value=''>                          
                  </div>
                </div>";	        
	  $content.="</div><hr>";	       
	  $content.="
	    <ul class='form_opt' data-id='public'>
	      <li class='text-label'>".e("URL Access")." <small>".e("If you set it to private, only you can access the URLs").".</small></li>
	      <li><a href='' class='last".(!$url->public?' current':'')."' data-value='0'>".e("Private")."</a></li>
	      <li><a href='' class='first".($url->public?' current':'')."' data-value='1'>".e("Public")."</a></li>
	    </ul>
	    <input type='hidden' name='public' id='public' value='".$url->public."' />             
	    ".Main::csrf_token(TRUE)."
	    <input type='submit' value='".e("Update")."' class='btn btn-primary' />
	    <a href='".Main::href("{$url->alias}{$url->custom}+")."' class='btn btn-success' target='_blank'>".e("Stats")."</a>
	    <a href='".Main::href("user/delete/{$url->id}").Main::nonce("delete_url-{$url->id}")."' class='btn btn-danger delete pull-right'>".e("Delete")."</a>"; 
	  // Add widget
	  $widgets=$this->widget_countries(array("urlid"=>$url->alias.$url->custom));
	  $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
				$widgets.='<h3>'.e("URL Info").'</h3>';
				$widgets.='<p><em>'.$url->click.'</em> '.e("Clicks").' '.e("since").' '.date("F, d Y",strtotime($url->date)).'</p>';
				$widgets.="<p><i class='glyphicon glyphicon-link'></i> {$this->user->domain}/{$url->alias}{$url->custom} <a href='#' class='inline-copy copy' data-clipboard-text='{$this->user->domain}/{$url->alias}{$url->custom}'>".e("Copy")."</a></p>";
				$widgets.="<p><i class='glyphicon glyphicon-qrcode'></i> {$this->user->domain}/{$url->alias}{$url->custom}/qr <a href='#' class='inline-copy copy' data-clipboard-text='{$this->user->domain}/{$url->alias}{$url->custom}/qr'>".e("Copy")."</a></p>";
				$widgets.="<p>".e("To change the dimension of the QR code, add the size parameter to the url and then your desired dimension. (Max 500x500)")." e.g. ?size=300x300</p>";
			$widgets.='</div>';
		$widgets.= $this->widgets("export",$url->id);

    // Show Template
		$this->isUser=TRUE;
		Main::set("title",e("Edit URL"));
		$this->header();
		include($this->t("shared/user_template"));
	 	$this->footer();	  	
  }
  /**
   * Splash Pages
   * @since 4.0
   **/
  private function splash(){
  	if(!$this->pro()) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
  	// Create page
  	if($this->id=="create") return $this->splash_create();
  	// Edit
  	if(is_numeric($this->id)) return $this->splash_edit();

  	$splashs = $this->db->get("splash",array("userid"=>"?"),array("order"=>"date","limit"=>5),array($this->userid));
		Main::set("title",e("Create a Custom Splash Page"));
		Main::set("description","Customize the splash page to attract more customers to your product or site.");
		$before="";
		if($splashs){
			$content='<ul class="list-group bundles">';
				foreach ($splashs as $splash) {
					$content.='<li class="list-group-item">';
							$content.= "<h4>{$splash->name}</h4>";
							$content.= "<p class='list-group-item-text'>
														<a href='".Main::href("user/splash/{$splash->id}")."'>".e("Edit")."</a>
														&nbsp;&nbsp;&bullet;&nbsp;&nbsp; 
														<a href='".Main::href("user/delete/{$splash->id}").Main::nonce("delete_splash-{$splash->id}")."' class='delete'>".e("Delete")."</a>
											    	&nbsp;&nbsp;&bullet;&nbsp;&nbsp;
														".Main::timeago($splash->date)."
											    </p>";
					$content.='</li>';
				}
			$content.='</ul>';
		}else{
			$content = "<p class='center'>".e("You don't have any active splash pages.")."</p>";
		}

		$header=e("Create a Custom Splash Page")."<a href='".Main::href("user/splash/create")."' class='btn btn-primary btn-xs pull-right'>".e("Create")."</a>";
	  $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
			$widgets.='<h3>'.e("Info").'</h3>';				
			$widgets.="<p>".e("A custom splash page is a transitional page where you can add a banner and an avatar along with a message to represent your brand or company. You can have up to a maximum of 5 splash pages and you can choose one for each URL.")."</p>";
			if($this->pro()){
				$p = $this->db->count("splash","userid='{$this->userid}'") / $this->variable("max_splash")*100;
	    	$widgets.='<br><div class="progress side-stats">
								  <div class="progress-bar'.($p >= 80?' progress-bar-danger':'').'" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: '.$p.'%;">
								  </div>
								</div>';								
			}				
		$widgets.='</div>';		

  	$this->isUser=TRUE;  	
  	$this->header();
		include($this->t("shared/user_template"));
  	$this->footer();
  }
		/**
		 * Create Splash Page
		 * @since 4.0
		 **/
		private function splash_create(){
			// Check if max limit is reached
			if($this->db->count("splash","userid='{$this->userid}'") >= $this->variable("max_splash")) Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("You have reached your max limit.")));		
			// Upload splash page
			if(isset($_POST["token"])){
				if(!Main::validate_csrf_token($_POST["token"])){
					Main::redirect(Main::href("user/splash/create","",FALSE),array("danger",e("Something went wrong, please try again.")));
					return;
				}		
				if($this->config["demo"]){
					Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
					return;
				}
				$upload_path=ROOT."/content/";
				$ext=array("image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg");

				$_POST["title"]=Main::clean($_POST["title"],3,TRUE);
				$_POST["message"]=Main::clean($_POST["message"],3,TRUE);
				$_POST["product"]=Main::clean($_POST["product"],3,TRUE);
				
				if(empty($_POST["name"])) return Main::redirect("user/splash/create",array("danger",e("Please enter unique name.")));

				if(!filter_var($_POST["product"],FILTER_VALIDATE_URL) || !Main::is_url($_POST["product"])) return Main::redirect("user/splash/create",array("danger",e("Please enter a valid URL.")));
				if(strlen($_POST["message"])>140) return Main::redirect("user/splash/create",array("danger",e("Message is too long. It must be less than 140 characters.")));
				
				if(!isset($ext[$_FILES["avatar"]["type"]])) return Main::redirect("user/splash/create",array("danger",e("Avatar must be either a PNG or a JPEG.")));
				if(!isset($ext[$_FILES["banner"]["type"]])) return Main::redirect("user/splash/create",array("danger",e("Banner must be either a PNG or a JPEG.")));

				if($_FILES["avatar"]["size"]>300*1024) return Main::redirect("user/splash/create",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));		
				if($_FILES["banner"]["size"]>500*1024) return Main::redirect("user/splash/create",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));

				list($width, $height) = getimagesize($_FILES["avatar"]["tmp_name"]);
				if($width!=100 && $height!=100)	return Main::redirect("user/splash/create",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));	

				list($width, $height) = getimagesize($_FILES["banner"]["tmp_name"]);
				if($width < 980 || ($height<250 || $height >500))	return Main::redirect("user/splash/create",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));	

				$unique=Main::strrand(8);
				$avatar=$unique."_avatar.".$ext[$_FILES["avatar"]["type"]];
				$banner=$unique."_banner.".$ext[$_FILES["banner"]["type"]];
		    move_uploaded_file($_FILES["avatar"]['tmp_name'], $upload_path.$avatar);
				move_uploaded_file($_FILES["banner"]['tmp_name'], $upload_path.$banner);

				$array=array(
					"title" => Main::truncate($_POST["title"],50),
					"message" => Main::truncate($_POST["message"],140),
					"banner"=> $unique."_banner.".$ext[$_FILES["banner"]["type"]],
					"avatar"=> $unique."_avatar.".$ext[$_FILES["avatar"]["type"]],
					"product" => $_POST["product"]
					);
				$array=json_encode($array);
				$data = array(
						":userid" => $this->userid,
						":name" => Main::clean($_POST["name"],3,TRUE),
						":data" => $array,
						":date" => "NOW()"
					);
				if($this->db->insert("splash",$data)){
					return Main::redirect(Main::href("user/splash","",FALSE),array("success",e("Splash page has been created.")));
				}
				return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("Security token expired. Please try again.")));				
			}
			Main::set("title",e("Create a Custom Splash Page"));
			Main::set("description","Customize the splash page to attract more customers to your product or site.");
			$before="";
			$header=e("Create your splash page");
		  $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
				$widgets.='<h3>'.e("Info").'</h3>';				
				$widgets.="<p>".e("A custom splash page is a transitional page where you can add a banner and an avatar along with a message to represent your brand or company. You can have up to a maximum of 5 splash pages and you can choose one for each URL.")."</p>";
			$widgets.='</div>';	
			// Upload form
			$content="
					<form action='".Main::href("user/splash/create")."' class='form' method='post' enctype='multipart/form-data'>
						<div class='form-group'>
							<label for='name'>".e('Unique name')."</label>
							<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. My Brand'>
						</div>						
						<div class='form-group'>
							<label for='product'>".e('Link to Product')."</label>
							<input type='text' class='form-control' name='product' id='product'  placeholder='e.g. http://mysite.com/'>
						</div>
						<div class='form-group'>
							<label for='avatar'>".e('Upload Avatar')." (100x100, PNG or JPEG, MAX 300KB)</label>
							<input type='file' class='form-control' name='avatar' id='avatar'  placeholder='e.g. http://mysite.com/avatar.jpg'>
						</div>
						<div class='form-group'>
							<label for='banner'>".e('Upload Banner')."</label>
							<input type='file' class='form-control' name='banner' id='banner' placeholder='e.g. http://mysite.com/banner.jpg'>
							<div class='help-block'>".e("The minimum width must be 980px and the height must be between 250 and 500. The format must be a PNG or a JPG. Maximum size is 500KB.")."</div>
						</div>
						<div class='form-group'>
							<label for='title'>".e('Custom Title')."</label>
							<input type='text' class='form-control' name='title' id='title' placeholder='e.g. Get a $10 discount'>
						</div>
						<div class='form-group'>
							<label for='message'>".e('Custom Message')." (Max: 140 chars)</label>
							<textarea name='message' id='message' cols='30' rows='5' class='form-control' placeholder='e.g. Get a $10 discount with any purchase more than $50'></textarea>
						</div>
						".Main::csrf_token(TRUE)."	
						<button class='btn btn-primary'>".e('Create Splash Page')."</button>
					</form><!-- /.form -->";

	  	$this->isUser=TRUE;  	
	  	$this->header();
			include($this->t("shared/user_template"));
	  	$this->footer();			
		}
		/**
		 * Create Splash Page
		 * @since 4.0
		 **/
		private function splash_edit(){
			if(!$splash = $this->db->get("splash",array("userid"=>"?","id"=>"?"),array("limit"=>1),array($this->userid,$this->id))) return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("This splash page doesn't exist."))); 
			$data = json_decode($splash->data);
			// Edit Splash
			if(isset($_POST["token"])){
				if($this->config["demo"]){
					Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
					return;
				}				
				if(!Main::validate_csrf_token($_POST["token"])){
					Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
					return;
				}		

				$upload_path=ROOT."/content/";
				$ext=array("image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg");

				$_POST["title"]=Main::clean($_POST["title"],3,TRUE);
				$_POST["message"]=Main::clean($_POST["message"],3,TRUE);
				$_POST["product"]=Main::clean($_POST["product"],3,TRUE);
				
				if(empty($_POST["title"]) || empty($_POST["message"]) || empty($_POST["product"]) || empty($_POST["name"])) return Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("danger",e("The name, title, message and link cannot be empty.")));
				if(!Main::is_url($_POST["product"])) return Main::redirect("user/splash/{$this->id}",array("danger",e("Please enter a valid URL.")));

				$array=array(
					"title" => Main::truncate($_POST["title"],50),
					"message" => Main::truncate($_POST["message"],140),
					"product" => $_POST["product"],
					"avatar" => $data->avatar,
					"banner" => $data->banner
					);
				$avatar = $banner = 0;
				// Valid avatar
				if(!empty($_FILES["avatar"]["name"])){
					if(!isset($ext[$_FILES["avatar"]["type"]])) return Main::redirect("user/splash/{$this->id}",array("danger",e("Avatar must be either a PNG or a JPEG.")));

					if($_FILES["avatar"]["size"]>300*1024) return Main::redirect("user/splash/{$this->id}",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));

					list($width, $height) = getimagesize($_FILES["avatar"]["tmp_name"]);
					if($width!=100 && $height!=100)	return Main::redirect("user/splash/{$this->id}",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));

					$name = explode(".", $data->avatar);
					$array["avatar"] = $name[0].".".$ext[$_FILES["banner"]["type"]];
					$avatar = 1;
				}
				// Valid Banner
				if(!empty($_FILES["banner"]["name"])){
					if(!isset($ext[$_FILES["banner"]["type"]])) return Main::redirect("user/splash/{$this->id}",array("danger",e("Banner must be either a PNG or a JPEG.")));

					if($_FILES["banner"]["size"]>500*1024) return Main::redirect("user/splash/{$this->id}",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));

					list($width, $height) = getimagesize($_FILES["banner"]["tmp_name"]);
					if($width < 980 || ($height<250 || $height >500))	return Main::redirect("user/splash/{$this->id}",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));

					$name = explode(".", $data->banner);
					$array["banner"] = $name[0].".".$ext[$_FILES["banner"]["type"]];
					$banner = 1;
				}			
				if($avatar){
		    	move_uploaded_file($_FILES["avatar"]['tmp_name'], $upload_path.$array["avatar"]);				
				}
				if($banner){
					move_uploaded_file($_FILES["banner"]['tmp_name'], $upload_path.$array["banner"]);	
				}
				$array=json_encode($array);
				$data = array(
						":userid" => $this->userid,
						":name" => Main::clean($_POST["name"],3,TRUE),
						":data" => $array,
					);
				if($this->db->update("splash","",array("id"=>$this->id,"userid"=>$this->userid),$data)){
					return Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("success",e("Splash page has been updated.")));
				}
				return Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("danger",e("Security token expired. Please try again.")));				
			}
			Main::set("title",e("Edit Custom Splash Page"));
			
			$header=e("Edit splash page");
			$data->banner = Main::href("content/{$data->banner}");
			$data->avatar = Main::href("content/{$data->avatar}");

			$before="<div class='custom-splash panel panel-default' id='splash'>
									<div class='banner'><a href='{$data->product}' rel='nofollow' target='_blank'><img src='{$data->banner}'></a></div><!-- /.banner -->
									<div class='custom-message'>
										<div class='c-avatar'><img src='{$data->avatar}'></div><!-- /.avatar -->
										<div class='c-message'>
											<h2>{$data->title}</h2>
											{$data->message}
											<p><a href='{$data->product}' rel='nofollow' target='_blank' class='btn btn-primary btn-xs'>".e('View site')."</a></p>
										</div><!-- /.messsage -->
										<div class='c-countdown'><span>5</span>seconds</div><!-- /.c-countdown -->
									</div><!-- /.custom-message -->
								</div><!-- /.custom-splash -->";

		  $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
				$widgets.='<h3>'.e("Info").'</h3>';				
				$widgets.="<p>".e("A custom splash page is a transitional page where you can add a banner and an avatar along with a message to represent your brand or company. You can have up to a maximum of 5 splash pages and you can choose one for each URL.")."</p>";
			$widgets.='</div>';	

			// Upload form
			$content="
					<form action='".Main::href("user/splash/{$this->id}")."' class='form' method='post' enctype='multipart/form-data'>
						<div class='form-group'>
							<label for='name'>".e('Unique name')."</label>
							<input type='text' class='form-control' name='name' id='name' value='{$splash->name}' placeholder='e.g. My Brand'>
						</div>						
						<div class='form-group'>
							<label for='product'>".e('Link to Product')."</label>
							<input type='text' class='form-control' name='product' id='product'  value='{$data->product}' placeholder='e.g. http://mysite.com/'>
						</div>
						<div class='form-group'>
							<label for='avatar'>".e('Upload Avatar')." (100x100, PNG or JPEG, MAX 300KB)</label>
							<input type='file' class='form-control' name='avatar' id='avatar'  placeholder='e.g. http://mysite.com/avatar.jpg'>
						</div>
						<div class='form-group'>
							<label for='banner'>".e('Upload Banner')."</label>
							<input type='file' class='form-control' name='banner' id='banner' placeholder='e.g. http://mysite.com/banner.jpg'>
							<div class='help-block'>".e("The minimum width must be 980px and the height must be between 250 and 500. The format must be a PNG or a JPG. Maximum size is 500KB.")."</div>							
						</div>
						<div class='form-group'>
							<label for='title'>".e('Custom Title')."</label>
							<input type='text' class='form-control' name='title' id='title' value='{$data->title}' placeholder='e.g. Get a $10 discount'>
						</div>
						<div class='form-group'>
							<label for='message'>".e('Custom Message')." (Max: 140 chars)</label>
							<textarea name='message' id='message' cols='30' rows='5' class='form-control' placeholder='e.g. Get a $10 discount with any purchase more than $50'>{$data->message}</textarea>
						</div>
						".Main::csrf_token(TRUE)."	
						<button class='btn btn-primary'>".e('Update Splash Page')."</button>
					</form><!-- /.form -->";

	  	$this->isUser=TRUE;  	
	  	$this->header();
			include($this->t("shared/user_template"));
	  	$this->footer();			
		} 		
		/**
		 * Delete Splash Page
		 * @since 4.0
		 **/
		private function splash_delete(){
			if($this->config["demo"]){
				Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
				return;
			}				
	    // Delete single Splash
	    if(!empty($this->id) && is_numeric($this->id)){
	      // Validated Nonce
	      if(Main::validate_nonce("delete_splash-{$this->id}")){
	      	$splash = $this->db->get("splash",array("id"=>"?","userid"=>"?"),array("limit"=>1),array($this->id,$this->userid));
	      	if($splash){
	      		$data=json_decode($splash->data);
	      		if(is_file(ROOT."/content/{$data->avatar}") && is_file(ROOT."/content/{$data->banner}")){
	      			unlink(ROOT."/content/{$data->avatar}");
	      			unlink(ROOT."/content/{$data->banner}");
	      		}
						$this->db->update("url",array("type"=>"?"),array("userid"=>"?","type"=>"?"),array($splash->id,$this->userid,$this->id));
						$this->db->delete("splash",array("userid"=>"?","id"=>"?"),array($this->userid,$this->id));
						return Main::redirect(Main::href("user/splash","",FALSE),array("success",e("The splash page has been deleted.")));      		
	      	}
	      }     
	    }
	    return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("Security token expired. Please try again.")));
		}
  /**
   * Overlay Pages
   * @since 4.3
   **/
  private function overlay(){
  	if(!$this->pro()) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
			// Edit Splash
				$message = "Your text here";
				$label = "Promo";
				$link = "#";
				$text = "Learn more";
				$bg = "#008aff";
				$color = "#fff";
				$btnbg = "#fff";
				$btncolor = "#000";
				$position = "bl";  	
		 		if($this->user->overlay){
					$overlay = json_decode($this->user->overlay);
					$message = $overlay->message;
					$label = $overlay->label;
					$link = $overlay->link;
					$text = $overlay->text;
					$bg = $overlay->bg;
					$color = $overlay->color;
					$btnbg = $overlay->btnbg;
					$btncolor = $overlay->btncolor;
					$position = $overlay->position;
				}				
			if(isset($_POST["token"])){
				if($this->config["demo"]){
					return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
				}				
				if(!Main::validate_csrf_token($_POST["token"])){
					return Main::redirect(Main::href("user/overlay","",FALSE),array("danger",e("Something went wrong, please try again.")));
				}		

				$_POST["message"]	=	Main::clean($_POST["message"],3,TRUE);
				
				if(empty($_POST["message"])) return Main::redirect(Main::href("user/overlay","",FALSE),array("danger",e("The message field cannot be empty.")));
				if(!empty($_POST["link"]) && !Main::is_url($_POST["link"])) return Main::redirect("user/overlay",array("danger",e("Please enter a valid URL.")));
				
				$_POST["bg"] = Main::clean($_POST["bg"],3,TRUE);
				$_POST["color"] = Main::clean($_POST["color"],3,TRUE);
				$_POST["btncolor"] = Main::clean($_POST["btncolor"],3,TRUE);
				$_POST["btnbg"] = Main::clean($_POST["btnbg"],3,TRUE);

				$array = array(
					"message" => Main::truncate($_POST["message"],140),
					"link" => Main::clean($_POST["link"],3,TRUE),
					"label" => Main::clean($_POST["label"],3,TRUE),
					"text" => Main::clean($_POST["text"],3,TRUE),
					"bg" => (!empty($_POST["bg"]) && strlen($_POST["bg"]) < 8) ? Main::clean($_POST["bg"],3,TRUE) : $bg,
					"color" => (!empty($_POST["color"]) && strlen($_POST["color"]) < 8) ? Main::clean($_POST["color"],3,TRUE) : $color,
					"btnbg" => (!empty($_POST["btnbg"]) && strlen($_POST["btnbg"]) < 8) ? Main::clean($_POST["btnbg"],3,TRUE) : $btnbg,
					"btncolor" => (!empty($_POST["btncolor"]) && strlen($_POST["btncolor"]) < 8) ? Main::clean($_POST["btncolor"],3,TRUE) : $btncolor,
					"position" => Main::clean($_POST["position"],3,TRUE),
				);
				if($this->db->update("user", array("overlay" => json_encode($array, JSON_UNESCAPED_UNICODE)), array("id" => $this->user->id))){
					return Main::redirect("user/overlay",array("success",e("Overlay has been saved.")));
				}
				return Main::redirect(Main::href("user/overlay","",FALSE),array("danger",e("Something went wrong, please try again.")));
		}



  	Main::cdn("spectrum");

  	Main::add('<script type="text/javascript">      
					  		function bgColor(element, color, e) {
						        $(element).css("background-color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }
					  		function Color(element, color, e) {
						        $(element).css("color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }		
						    $("#message").keyup(function(e){
						    	if($(this).val().length > 140) return false;
									$(".custom-message p").text($(this).val());
						    });		
						    $("#label").keyup(function(e){
						    	if($(this).val().length > 8) return false;
									$(".custom-message .custom-label").text($(this).val());
						    });	
						    $("#text").keyup(function(e){
						    	if($(this).val().length > 35) return false;
									$(".custom-message .btn").text($(this).val());
						    });							    						    				    
					  		$("#bg").spectrum({
					        color: "'.$bg.'",
					        move: function (color) { bgColor(".custom-message", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message", color, $(this)); }
					    	}); 
					  		$("#color").spectrum({
					        color: "'.$color.'",
					        move: function (color) { Color(".custom-message p", color, $(this)); },
					        hide: function (color) { Color(".custom-message p", color, $(this)); }
					    	});
					  		$("#btnbg").spectrum({
					        color: "'.$btnbg.'",
					        move: function (color) { bgColor(".custom-message .btn", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message .btn", color, $(this)); }
						    });  
					  		$("#btncolor").spectrum({
					        color: "'.$btncolor.'",
					        move: function (color) { Color(".custom-message .btn", color, $(this)); },
					        hide: function (color) { Color(".custom-message .btn", color, $(this)); }
					    });</script>', "custom", TRUE); 

		Main::set("title",e("Customize your overlay page"));

		Main::set("description","Customize the overlay to attract more customers to your product or site.");
		
		$before="<div class='custom-overlay panel panel-default panel-body' id='overlay'>
								<h3>".e("Live Preview")."</h3>
								<div class='custom-message' style='background-color: $bg;'>
										<div class='custom-label'>Promo</div>
										<p style='color: $color'>$message</p>
										<a href='#' class='btn btn-xs' style='background-color: $btnbg;style='background-color: $btncolor''>$text</a>
								</div><!-- /.custom-message -->
							</div><!-- /.custom-overlay -->";

		$content="
				<form action='".Main::href("user/overlay")."' class='form' method='post' enctype='multipart/form-data'>
					<div class='form-group'>
						<label for='message'>".e('Custom Message')." (Max: 140 chars)</label>
						<textarea name='message' id='message' cols='30' rows='5' class='form-control' placeholder='e.g. Get a $10 discount with any purchase more than $50'>$message</textarea>
					</div>				
					<div class='form-group'>
						<label for='label'>".e('Overlay label')." (leave empty to disable)</label>
						<input type='text' class='form-control' name='label' id='label'  placeholder='e.g. Promo' value='$label'>
					</div>										
					<div class='form-group'>
						<label for='link'>".e('Button Link')." (leave empty to disable)</label>
						<input type='text' class='form-control' name='link' id='link'  placeholder='e.g. http://mysite.com/'>
					</div>
					<div class='form-group'>
						<label for='text'>".e('Button Text')." (leave empty to disable)</label>
						<input type='text' class='form-control' name='text' id='text'  placeholder='e.g. Learn more' value='$text'>
					</div>
					<div class='form-group'>
						<label for='bg'>".e('Overlay Background Color')."</label> <br>
						<input type='input' name='bg' id='bg'>
					</div>			
					<div class='form-group'>
						<label for='color'>".e('Overlay Text Color')."</label><br>
						<input type='input' name='color' id='color'>
					</div>	
					<div class='form-group'>
						<label for='btnbg'>".e('Button Background Color')."</label><br>
						<input type='input' name='btnbg' id='btnbg'>
					</div>		
					<div class='form-group'>
						<label for='btncolor'>".e('Button Text Color')."</label><br>
						<input type='input' name='btncolor' id='btncolor'>
					</div>		
					<div class='form-group'>
						<label for='position'>".e('Overlay Position')."</label>
						<select name='position' id='position' class='form-control'>
							<option value='tl' ".($position == "tl" ? "selected" : "").">Top Left</option>
							<option value='tr' ".($position == "tr" ? "selected" : "").">Top Right</option>
							<option value='bl' ".($position == "bl" ? "selected" : "").">Bottom Left</option>
							<option value='br' ".($position == "br" ? "selected" : "").">Bottom Right</option>							
						</select>
					</div>																								
					".Main::csrf_token(TRUE)."	
					<button class='btn btn-primary'>".e('Save overlay')."</button>
				</form><!-- /.form -->";

		$header = e("Customize your overlay page");
	  $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
			$widgets.='<h3>'.e("Info").'</h3>';				
			$widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can also use this feature to send a message to your users. You can customize the message and the appearance of the overlay right from this page. As soon as you save it, the changes will be applied immediately across all your URLs using this type. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
		$widgets.='</div>';		

  	$this->isUser=TRUE;
  	$this->header();
		include($this->t("shared/user_template"));
  	$this->footer();
  }	
  /**
   * [tools description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function tools(){
  	if(!$this->config["api"]){
  		return Main::href("");
  	}
		// Filter ID
		$this->filter($this->id);		
		// Meta information
		Main::set("title",e("Tools"));
		// Get Template		
		$this->isUser=TRUE;
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();
  }
  /**
   * [cancel description]
   * @author KBRmedia <http://gempixel.com>
   * @version 5.0
   * @return  [type] [description]
   */
  protected function cancel(){
  	if(!$this->pro()) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
  	if($this->admin()) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Wow there. You are an admin. You can't cancel your membership.")));
  	if($this->config["pt"] != "stripe") return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));

  	if(isset($_POST["token"])){
			if(!Main::validate_csrf_token($_POST["token"])) {
				return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
			}		  		
	  	$user = $this->db->get("user", ["id" => $this->user->id], ["limit" => 1]);
	  	$subscription = $this->db->get("subscription", ["userid" => $this->user->id], ["limit" => 1, "order" => "date"]);

			if(!Main::validate_pass($_POST["password"], $user->password)){
				return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Your password is incorrect.")));
			}

	  	include(STRIPE);
			\Stripe\Stripe::setApiKey($this->config["stsk"]);
			
			if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

			try {

				$Sub = \Stripe\Subscription::retrieve($subscription->tid);

			} catch (Exception $e) {

				return Main::redirect("user/settings",array("danger",e("An error has occured, please contact us.")));
			}

			if($Sub->plan->id == "PUS.yearly"){
				$Inv = \Stripe\Invoice::all(["subscription" => $subscription->tid]);
				$Charge = $Inv->data[0]->charge;
				$Amount = $Inv->data[0]->total / 100;

				$start = $Sub->current_period_start;
				$end = $Sub->current_period_end;

				$yStart = date('Y', $start);
				$yEnd = date('Y', $end);

				$mStart = date('m', $start);
				$mEnd = date('m', $end);

				$diff = (($yEnd - $yStart) * 12) + ($mEnd - $mStart);

				$refund = round(($diff - 1) * $Amount / 12, 2);

				$re = \Stripe\Refund::create(array(
				  "charge" => "ch_1C3GnG4rNySdg1DYiPWe1IwO",
				  "amount" => $refund * 100
				));

				$data[":expiry"] = date("Y-m-d H:i:s", strtotime("now"));
				$data[":status"] = "Canceled";
				$data[":reason"] = Main::clean($_POST["reason"], 3, TRUE);
				// Cancel Sub
				$this->db->update("subscription",[], ["id" => $subscription->id], $data);

	    	$PArray = [
						    		":date"  => "NOW()",
						    		":tid"  => "r_{$subscription->uniqueid}",
						    		":amount"  =>  $refund,
						    		":status"  =>  "Refunded",
						    		":userid"  =>  $this->user->id,
						    		":expiry" =>  NULL,
						    		":data" =>  NULL
						    		];			
				$this->db->insert("payment", $PArray);
				// Downgrade user
				$this->db->update("user", ["expiration" => $data[":expiry"], "pro" => "0"], ["id" => $user->id]);
				$Sub->cancel();

			}else{

				$data[":reason"] = Main::clean($_POST["reason"], 3, TRUE);	
				$this->db->update("subscription",[], ["id" => $subscription->id], $data);		
				$Sub->cancel(["at_period_end" => true]);
			}
			return Main::redirect("user/settings",array("success",e("Your subscription has been canceled.")));
		}
		return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
  }
	/**
	 * Settings
	 * @since 4.3
	 **/
	protected function settings(){
		// Update settings
		if(isset($_POST["token"])){
			if($this->config["demo"]){
				Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
				return;
			}			
			if(!Main::validate_csrf_token($_POST["token"])) {
				Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
				return;
			}			
			// Validate email				
			if(empty($_POST["email"]) || !Main::email($_POST["email"])) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Please enter a valid email.")));
			
			// Check if empty is changed
			if($_POST["email"]!==$this->user->email){
				if($this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]))){
					return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("An account is already associated with this email.")));
				}
			}

			// Prepare and clean data
			$data= array(
					":email" => Main::clean($_POST["email"],3,TRUE),
					":media" => in_array($_POST["media"], array("0","1")) ? Main::clean($_POST["media"],3,TRUE) : 0,
					":public" => in_array($_POST["public"], array("0","1")) ? Main::clean($_POST["public"],3,TRUE) : 0
				);

			// Validate username
			if(!empty($_POST["username"]) && empty($this->user->username) && $_POST["username"]!==$this->user->username){
				if(!Main::username($_POST["username"])) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Please enter a valid username.")));
				if($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
					return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("This username has already been used. Please try again.")));
				}
				$data[":username"]=$_POST["username"];				
			}		
			// Check if password is changed
			if(!empty($_POST["password"])){
				if(strlen($_POST["password"])<5) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Password must contain at least 5 characters.")));
				if(empty($_POST["cpassword"]) || $_POST["password"]!==$_POST["cpassword"]) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Passwords don't match.")));
				//Update Password
				$data[":password"]=Main::encode($_POST["password"]);
			}

			if($this->pro() && in_array($_POST["defaulttype"], ["direct","frame","splash","overlay"])){
				$data[":defaulttype"] = Main::clean($_POST["defaulttype"],3,TRUE);
			}
			
			// Update Users
			$this->db->update("user","",array("id"=>$this->userid),$data);
			// Return to settings
			return Main::redirect(Main::href("user/settings","",FALSE),array("success",e("Account has been successfully updated.")));
		}
		// Filter ID
		$this->filter($this->id);		
		// Meta information
		Main::set("title",e("Account Settings"));
		Main::set("description","Edit your account's information.");
		// Get Template		
		$this->isUser=TRUE;
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();
	}
		/**
		 * Show Last Payments
		 * @since 4.2
		 **/
		private function last_payments(){
			if(!$this->config["pro"]) return FALSE;

			if(isset($this->config["pt"]) && $this->config["pt"] == "stripe" && $subscription = $this->db->get("subscription",array("userid"=>"?"),array("order"=>"date"),array($this->userid))){
				
				$html = '<div class="main-content panel panel-default panel-body">';
					$html .="<h3>".e("Subscription History")."</h3>";
					$html .= '<div class="table-responsive">';
						$html .='<table class="table table-striped">
							        <thead>
							          <tr>
							            <th>'.e("Transaction ID").'</th>
							            <th>'.e("Amount").'</th>
							            <th>'.e("Since").'</th>
							            <th>'.e("Next Payment").'</th>
							            <th>'.e("Status").'</th>
							          </tr>
							        </thead>
							        <tbody>';          
	          foreach ($subscription as $payment){
	            	$html .='<tr data-id="'.$payment->id.'">
						              <td>'.$payment->uniqueid.'</td>
						              <td>'.Main::currency($this->config["currency"], $payment->amount).'</td>
						              <td>'.date("d F, Y",strtotime($payment->date)).'</td>
						              <td>'.date("d F, Y",strtotime($payment->expiry)).'</td>
						              <td>'.($payment->status == "Compeleted" ? e("Active") : $payment->status).'</td>
						            </tr>';     
	          }
			        $html .='</tbody>
							      </table>';
					$html .= '</div>';
				$html .='</div>';
				echo $html;				
			}

			$payments = $this->db->get("payment",array("userid"=>"?"),array("order"=>"date"),array($this->userid));

			$html = '<div class="main-content panel panel-default panel-body">';
				$html .="<h3>".e("Latest Transactions")."</h3>";
				$html .= '<div class="table-responsive">';
					$html .='<table class="table table-striped">
						        <thead>
						          <tr>
						            <th>'.e("Transaction ID").'</th>
						            <th>'.e("Amount").'</th>
						            <th>'.e("Date").'</th>
						            <th>'.e("Expiration").'</th>
						          </tr>
						        </thead>
						        <tbody>';          
          foreach ($payments as $payment){
            	$html .='<tr data-id="'.$payment->id.'">
					              <td>'.($payment->status == "Refunded" ? "<span class='label label-success'>".e("Refunded")."</span> ":"").$payment->tid.'</td>
					              <td>'.($payment->status == "Refunded" ? "-" :"").Main::currency($this->config["currency"], $payment->amount).'</td>
					              <td>'.date("d F, Y",strtotime($payment->date)).'</td>
					              <td>'.($payment->status == "Refunded" ? "" : date("d F, Y",strtotime($payment->expiry))).'</td>
					            </tr>';     
          }
		        $html .='</tbody>
						      </table>';
				$html .= '</div>';
			$html .='</div>';
			echo $html;
		}
	/**
	 * User Export URLs
	 * @since v4.0
	 */
	protected function export(){
		if($this->config["demo"]){
			Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
			return;
		}
		if(!$this->pro()) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
		if(!empty($this->id)){
			if($url = $this->db->get("url",array("userid"=>"?","id"=>"?"),array("limit"=>1),array($this->userid,$this->id))){
				return $this->export_data($url->custom.$url->alias);
			}else{
				return Main::redirect("user/edit/{$this->id}",array("danger",e("Data for this url is not available.")));
			}
		}	
		if(!Main::validate_nonce("export_url")) return Main::redirect("user/settings",array("danger",e("Security token expired, please try again.")));
  		header('Content-Type: text/csv');
	  	header('Content-Disposition: attachment;filename=URL_Shortener_URLList.csv');
	 	 	$result = $this->db->get("url",array("userid"=>$this->userid),array("order"=>"id","all"=>1));
			echo "Short URL, Long URL, Date, Clicks\n";
	    foreach ($result as $line) {
	     	echo "{$this->user->domain}/{$line->alias}{$line->custom},{$line->url},{$line->date},{$line->click}\n";
	    }
	    return;
	}	
	/**
	 * User Export URLs
	 * @since v4.0
	 */
	protected function export_data($id){		
		if(!Main::validate_nonce("export_url-{$this->id}")) return Main::redirect("user",array("danger",e("Security token expired, please try again.")));
  		header('Content-Type: text/csv');
	  	header('Content-Disposition: attachment;filename=URL_Shortener_'.$id.'_Stats.csv');
	 	 	$result = $this->db->get("stats",array("urluserid"=>$this->userid,"short"=>$id),array("order"=>"id","all"=>1));
			echo "Short URL, Date, IP, Country, Referrer\n";
	    foreach ($result as $line) {
	     	echo "{$this->user->domain}/{$line->short},{$line->date},{$line->ip},{$line->country},{$line->referer}\n";
	    }
	    return;
	}			
	/**
	 * Tracking Pixels
	 * @since 5.0
	 **/
	protected function pixels(){
		
		if(!$this->pro()) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

		// Update settings
		if(isset($_POST["token"])){
			if($this->config["demo"]){
				Main::redirect("user/pixels",array("danger",e("Feature disabled in demo.")));
				return;
			}			
			if(!Main::validate_csrf_token($_POST["token"])) {
				Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Something went wrong, please try again.")));
				return;
			}	
			// Santize		
			if(!empty($_POST["fbpixel"])){
				if(!is_numeric($_POST["fbpixel"]) || (strlen($_POST["fbpixel"]) > 20)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Facebook pixel ID is not correct. Please double check.")));
			}
			if(!empty($_POST["adwordspixel"])){
				if((strlen($_POST["adwordspixel"]) > 40)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Adwords pixel ID is not correct. Please double check.")));
			}			
			if(!empty($_POST["linkedin"])){
				if((strlen($_POST["linkedin"]) > 10)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("LinkedIn ID is not correct. Please double check.")));
			}						
			// Prepare and clean data
			$data = array(
					":fbpixel" => Main::clean($_POST["fbpixel"],3,TRUE),
					":adwordspixel" => Main::clean($_POST["adwordspixel"],3,TRUE),
					":linkedinpixel" => Main::clean($_POST["linkedinpixel"],3,TRUE)
				);

			// Update Users
			$this->db->update("user","",array("id"=> $this->userid), $data);
			// Return to settings
			return Main::redirect(Main::href("user/pixels","",FALSE),array("success",e("Tracking pixels have been successfully updated.")));
		}
		// Filter ID
		$this->filter($this->id);		
		// Meta information
		Main::set("title",e("Account Settings"));
		Main::set("description","Edit your account's information.");
		// Get Template		
		$this->isUser=TRUE;
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();
	}		
}