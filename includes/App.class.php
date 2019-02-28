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
 * @subpackage App Request Handler
 */
class App{
	/**
	 * Maximum number of splash pages allowed
	 * @since 4.0
	 **/
	private $max_splash = 5;	
	/**
	 * Current Language
	 * @since 4.0
	 **/	
 	public $lang="";
	/**
	 * Items Per Page
	 * @since 4.0
	 **/
	public $limit = 15;
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
	 * @since 5.0
	 **/
	protected $page=1, $db, $config=array(),$action="", $do="", $id="", $http="http", $sandbox = FALSE;
	protected $actions = ["user","page","contact","server","pricing","upgrade" ,"analytic","profile","ipn", "q", "jShortener", "webhook"];	
	/**
	 * User Variables
	 * @since 4.0
	 **/
	protected $logged=FALSE;
	protected $admin=FALSE, $user=NULL, $userid="0";		
	/**
	 * Constructor: Checks logged user status
	 * @since 4.0
	 **/
	public function __construct($db,$config){
  	$this->config=$config;
  	$this->db=$db;
  	$this->db->object=TRUE;
  	// Clean Request
  	if(isset($_GET)) $_GET = array_map("Main::clean", $_GET);
		if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"]>0) $this->page=Main::clean($_GET["page"]);
		$this->http=((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)?"https":"http");		
		$this->check();
	}
	/**
	 * Run Script
	 * @since 4.0
	 **/
	public function run(){
		if(isset($_GET["a"]) && !empty($_GET["a"])){
			// Validate Request
			$var=explode("/",$_GET["a"]);
			if(count($var) > 3) return $this->_404();
			$this->action=Main::clean($var[0],3,TRUE);
			// Run Methods
			if(isset($var[1]) && !empty($var[1])) $this->do=Main::clean($var[1],3);
			if(isset($var[2]) && !empty($var[2])) $this->id=Main::clean($var[2],3);			
			if(in_array($var[0],$this->actions)){
				return $this->{$var[0]}();
			}
			// Run Short
			require(ROOT."/includes/Short.class.php");
			$short = new Short($this->db,$this->config);			
			$short->analyze($this->action,$this->do);
			return;
		}else{
			// Run HomePage
			return $this->home();
		}
	}	
	/**
	 * Check if user is logged
	 * @since 4.0
	 **/
	public function check(){
		if($info=Main::user()){
			$this->db->object=TRUE;
			if($user=$this->db->get("user",array("id"=>"?","auth_key"=>"?"),array("limit"=>1),array($info[0],$info[1]))){
				$this->logged=TRUE;		
				$this->user = $user;								
				$this->userid=$this->user->id;				
				if(empty($this->user->domain)) $this->user->domain=$this->config["url"];
				if($this->user->admin) $this->admin=TRUE;
				if($user->auth=="facebook" && !empty($user->auth_id)){
					$this->user->avatar="{$this->http}:graph.facebook.com/".$user->auth_id."/picture?type=large";
				}else{
					$this->user->avatar="{$this->http}://www.gravatar.com/avatar/".md5(trim($this->user->email))."?s=150";		
				}				
				if($this->graceExpired()) {
					$this->db->update("user",array("pro" => 0),array("id" => $user->id));
					$this->user->pro = 0;
				}
				// Unset sensitive information
				unset($this->user->password);
				unset($this->user->auth_key);
			}
		}
	}	
	/**
	 * Returns User info
	 * @since 4.2
	 **/
	protected function logged(){
		return $this->logged;
	}	
	protected function admin(){
		return $this->admin;
	}
	protected function actions(){
		return $this->actions;
	}
	/**
	 * [pro description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @return  [type] [description]
	 */
	protected function pro(){
		// Admin is Always PRO
		if(!$this->logged()) return FALSE;
		if($this->admin() || !$this->config["pro"]) return TRUE;
		return $this->user->pro;
	}
	/**
	 * [isexpired description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @param   integer $grace [description]
	 * @return  [type]         [description]
	 */
	protected function isexpired($grace = 0){
		if(!$this->logged()) return TRUE;
		// Admins don't expire
		if($this->admin()) return FALSE;
		// Check expiration date
		if($this->pro() && strtotime($this->user->expiration) < time()) return TRUE;
		return FALSE;
	}
	/**
	 * [graceExpired description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @return  [type] [description]
	 */
	protected function graceExpired(){
		if(!$this->logged()) return TRUE;
		// Admins don't expire
		if($this->admin()) return FALSE;
		// Check expiration date
		if($this->pro() && strtotime("+3 days", strtotime($this->user->expiration)) < time()) return TRUE;		
		return FALSE;
	}
	/**
	 * [variable description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @param   [type] $var [description]
	 * @return  [type]      [description]
	 */
	protected function variable($var){
		return $this->{$var};
	}
	/**
	 * Generate Home Page
	 * @since 4.0
	 */
	protected function home(){
		// Check if private
		if($this->config["private"] && !$this->logged()) {
			$this->_private();
			return;
		}	  			
		// Check if under maintenance
		if($this->config["maintenance"] && !$this->admin()) {
			$this->_maintenance();
			return;
		}
		// Bookmarklet valid request
		if(isset($_GET["bookmark"])){
			return $this->bookmark();
		}
		// If logged redirect to dashboard
		if($this->logged()) return Main::redirect("user");
		Main::set("body_class","light home");
		Main::cdn("datepicker");
		$this->isHome=TRUE;  	
		$this->header();
		include(TEMPLATE."/index.php");
	 	$this->footer();	
	}
	/**
	 * [q description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function q(){
		if($this->logged() && isset($_GET["u"])){
			$u = Main::clean($_GET["u"], 3, TRUE);
			// Run Short
			require(ROOT."/includes/Short.class.php");
			$short = new Short($this->db,$this->config);  			
			$short = $short->add(["url"=> $u, "type" => $this->user->defaulttype], ["noreturn" => TRUE]);

			if($short["error"]){
				return Main::redirect("?url={$u}", array("danger",$short["msg"]));			
			}else{
				return Main::redirect($short["short"], array("success", e("URL has been successfully shortened")),"",TRUE);
			}			
		}
		return Main::redirect("?quick.error");					
	}
		 /**
			 * Bookmark
			 * @since 5.0
			 **/	
			private function bookmark(){
				if(!isset($_GET["token"]) || $_GET["token"] !== md5($this->config["public_token"])){
					header('HTTP/1.1 400 Bad Request', true, 400);
					return print("{$_GET["callback"]}(".json_encode(array("error"=>1,"msg"=>"Invalid request. Please update bookmarklet.")).")");
				}
				// Run Short
				require(ROOT."/includes/Short.class.php");
				$short = new Short($this->db,$this->config);  			
				$short = $short->add(["url"=> Main::clean($_GET["url"], 3, TRUE)], ["noreturn" => TRUE]);
				if(isset($_GET["callback"])){
					header("Content-type: application/javascript");
					echo "{$_GET["callback"]}(".json_encode($short).")";
					exit;
				}
				if($short["error"]){
					return Main::redirect("?url={$_GET["url"]}",array("danger",$short["msg"]));			
				}else{
					return Main::redirect($short["short"]."+",array("success",e("URL has been successfully shortened")),"",TRUE);
				}
			}
			/**
			 * Anonymous User History
			 * @since 4.1
			 **/
			protected function history(){
				// Check if enabled or logged
				if(!$this->config["user_history"] || $this->logged()) return FALSE;
				// Get Aliases
				$alias = json_decode(Main::cookie("aid"),TRUE);

				// If empty return False
				if(!$alias) return FALSE;						
				// Get URLs
	      $query="(";
	      $c = count($alias);
	      $value = [];
	      $i = 1;
	      foreach ($alias as $id) {
	        if($i>=$c){
	          $query.="(`alias` = :id$i OR `custom`= :id$i)";
	        }else{
	          $query.="(`alias` = :id$i OR `custom`= :id$i) OR ";
	        }

	        $value[':id'.$i] = $id;
	        $i++;
	      }  
	      $value[":user"] = "0";
	      $query .= ") AND userid=:user";				

				if(!$urls = $this->db->get("url",$query, array("limit"=>10, "order"=>"date"), $value)) return FALSE;

				echo "<section id='last_history' class='urls-holder'>";
					echo '<div class="container content">
										'.$this->ads(728,FALSE).'
										<div class="row" id="user-content">
											<div class="col-md-8 addmargin public_list" id="data-container">';
									echo "<h2>".e('Your Last 10 URLs')."</h2>";
										$user = new stdClass();
										$user->domain = $this->config["url"];
										foreach ($urls as $url){
											include(TEMPLATE."/shared/public_url_loop.php");
										}			
							echo "</div>
										<div class='col-md-4 addmargin text-right'>
											".$this->ads(300,FALSE)."
										</div>
									</div>
							</div>
							<br />
						</section>";
				return TRUE;
			}	
			/**
			 * Return Public List
			 * @author KBRmedia
			 * @since  4.2
			 */
			protected function public_list($limit = 15){
				if($this->config["user_history"]) echo "<hr>";

				if(!$this->config["public_dir"]) return FALSE;

				if($urls = $this->db->get("url", array("public" => "1"), array("limit"=> $limit, "order"=>"date"))){
				  echo '<section class="urls-holder">
				    <div class="container content">
				      <div class="row" id="user-content">
				        <div class="col-md-8 addmargin public_list" id="data-container">
									<h2>'.e("Latest Public URLs").'</h2>';
				          $user = new stdClass; $user->domain = $this->config["url"];
				          foreach ($urls as $url){
				           include(TEMPLATE."/shared/public_url_loop.php");
				          }
				        echo '</div>
				        <div class="col-md-4 addmargin text-right">
				          '.$this->ads(300,0).';
				        </div>
				      </div>    
				    </div>
				  </section>';
				}
			}
	/**
	 * User
	 * @since 5.0
	 **/
	protected function user(){
		// Possible actions for user/* when logged and when not logged
		if($this->logged()){
			$action = array("edit","delete","archive","expired","bundles","splash","settings","logout","verify","search","server","export","overlay","pixels", "tools", "cancel");
		}else{
			$action = array("login","register","forgot","activate");
		}
		// Run actions
		if(!empty($this->do)){			
			if(in_array($this->do, $action)){
				require(ROOT."/includes/User.class.php");
			 	if(method_exists("User", $this->do)) {
					$user = new User($this->db,$this->config);
					return $user->initiate($this->do,$this->id);
				}
			}
			return $this->_404();
		}
		// If not logged redirect to login page
		if(!$this->logged()) return Main::redirect(Main::href("user/login","",FALSE));
		// Get URLs
		$order=array("date",FALSE,"newest");
		if(isset($_GET["sort"])){
			if(Main::clean($_GET["sort"],3,TRUE)=="popular"){
				$order=array("click",FALSE,"popular");
			}elseif(Main::clean($_GET["sort"],3,TRUE)=="oldest"){
				$order=array("date",TRUE,"oldest");
			}
		}

		$urls = $this->db->get("url","userid = '{$this->userid}' AND (expiry IS NULL OR expiry > DATE(CURDATE())) AND archived = '0'", array("order"=>$order[0], "limit"=>(($this->page-1)*$this->limit).", {$this->limit}","count"=>TRUE,"asc"=>$order[1]),array($this->userid));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }   
    if($this->page > 1 && $this->page > $max) Main::redirect("user",array("danger","No URLs found."));
    $pagination = Main::pagination($max,$this->page,Main::href("user?filter={$order[2]}&amp;page=%d"));

    // Show Template		
		$this->isUser=TRUE;
		Main::cdn("datepicker");
		Main::set("title",e("User Account"));
		$this->header();
		include($this->t("user"));
	 	$this->footer();
	}
	/**
	 * [upgrade description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @return  [type] [description]
	 */
	protected function upgrade(){
		// Pricing
		if(!$this->logged()) {
			if(in_array($this->do, ["monthly", "yearly"])) $_SESSION["redirect"] = "upgrade/{$this->do}";
			return Main::redirect(Main::href("user/register","",FALSE));
		}

		// Disable Pro membership
		if(!$this->config["pro"]) return $this->_404();
		
		// Process Payment
		if(isset($_POST["token"])){
			if(!Main::validate_csrf_token($_POST["token"])) {
				return Main::redirect(Main::href("checkout/{$this->do}","",FALSE),array("danger",e("Something went wrong, please try again.")));
			}		

			if(isset($this->config["pt"]) && $this->config["pt"] == "stripe") return $this->ProcessStripe();
			return $this->ProcessPayPal();			
		}

		if($this->do == "yearly" || $this->do == "monthly") return $this->checkout();
		if($this->do == "renew") $_SESSION["renew"] = TRUE;

		return Main::redirect("pricing");
	}
	/**
	 * [ProcessStripe description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 */
	private function ProcessStripe(){
		include(STRIPE);
		\Stripe\Stripe::setApiKey($this->config["stsk"]);
		
		if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

		$term = e("Monthly Subscription");
		$text = e("First month");
		$price = $this->config["pro_monthly"];
		$plan = "PUS.monthly";

		if($this->do == "yearly"){
			$term = e("Yearly Subscription");
			$text = e("First year");
			$price = $this->config["pro_yearly"];
			$plan = "PUS.yearly";				
		}

		if(!isset($_POST["stripeToken"])) return Main::redirect("",array("warning",e("An error ocurred, please try again. You have not been charged.")));

	  $token  = $_POST['stripeToken'];

	  $customerID = $this->user->customerid;

		if(!$this->user->customerid){
			$customer = \Stripe\Customer::create(array(
				"email" => $this->user->email,
			  "description" => "$term - $text for {$this->user->email}",
			  "source" => $token
			));						
			if(!isset($customer->id)) return Main::redirect("",array("warning",e("An error ocurred, please try again. You have not been charged.")));		
		  $customerID = $customer->id;
			$this->db->update("user",["customerid" => $customerID], ["id" => $this->user->id]);			  
		}
		try {
			$subscription = \Stripe\Subscription::create(array(
			  "customer" => $customerID,
			  "items" => array(
			    array(
			      "plan" => $plan,
			    ),
			  )
			));					
		} catch (Exception $e) {
			error_log($e->getMessage());
			return Main::redirect("",array("warning",e("An error ocurred, please try again. You have not been charged.")));	
		}			

		if($subscription->status != "active"){
			return Main::redirect("",array("warning",e("Your credit card was declined. Please check your credit card and try again later.")));	
		}

		$uniqueid = Main::strrand(16);
		$SArray = [
								":tid" => $subscription->id,
								":userid" => $this->user->id,
								":plan" => str_replace("PUS.", "", $plan),
								":status" => "Pending",
								":amount" => "0",
								":date" => "NOW()",
								":expiry" => date("Y-m-d h:i:s", $subscription->current_period_start),
								":lastpayment" => "NOW()",
								":data" => json_encode($subscription->items->data),
								":uniqueid" => $uniqueid
							];
		$this->db->insert("subscription", $SArray);

 		//  	$PArray = [
		// 		    		":date"  => "NOW()",
		// 		    		":tid"  => $uniqueid,
		// 		    		":amount"  =>  $price,
		// 		    		":status"  =>  "Completed",
		// 		    		":userid"  =>  $this->user->id,
		// 		    		":expiry" =>  date("Y-m-d h:i:s", $subscription->current_period_end),
		// 		    		":data" =>  json_encode($subscription->items->data)
		// 		    		];
		
		// $this->db->insert("payment", $PArray);

  	// Update database
  	$UArray = [
  							":last_payment" => date("Y-m-d h:i:s", $subscription->current_period_start),
  							":expiration" =>  date("Y-m-d h:i:s", $subscription->current_period_start),
  							":pro" => "1",
  							":address" => Main::clean($_POST["address"].", ".$_POST["city"].", ".$_POST["state"].". ".$_POST["zip"].". ".$_POST["country"], 3, TRUE),
  							":name" => Main::clean($_POST["name"])
  					];

  	if($this->db->update("user",[],array("id" => $this->user->id), $UArray)){
  		return Main::redirect(Main::href("user/settings","",FALSE),array("success",e("You were successfully subscribed. Thank you!")));
		}
  	return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("An unexpected issue occurred. Please contact us for more info.")));		
	}
	/**
	 * Upgrade 
	 * @since 5.0
	 **/
	protected function pricing(){
		// Disable Pro membership
		if(!$this->config["pro"]) return $this->_404();

		// Verify Price
		if(empty($this->config["pro_monthly"])) $this->config["pro_monthly"] = "0.00";
		if(empty($this->config["pro_yearly"])) $this->config["pro_yearly"] = "0.00";

    if($this->config["pro_monthly"]){
      $discountAmount = round((($this->config["pro_monthly"]*12)-$this->config["pro_yearly"])*100/($this->config["pro_monthly"]*12),0);
      $discount = ($discountAmount < 1) ? "" : ("<div class='corner-ribbon top-left'>".e("Save")." $discountAmount%</div>");
    }else{
      $discount = "";
    }

		Main::set("title",e("Premium Plan Pricing"));
		$this->header();
		include($this->t("upgrade"));
	 	$this->footer();
	}	
			/**
			 * [checkout description]
			 * @author KBRmedia <http://gempixel.com>
			 * @version 5.0
			 * @return  [type] [description]
			 */
			private function checkout(){
				// Require Login
				if(!$this->logged()) {
					$_SESSION["redirect"] = "upgrade";
					return Main::redirect(Main::href("user/register","",FALSE));
				}

				if($this->pro()){
					return Main::redirect(Main::href("user/settings","",FALSE));
				}

				$logo = ($this->config["logo"] ? "{$this->config["url"]}/content/{$this->config["logo"]}" : "");

				$term = e("Monthly Subscription");
				$text = e("First month");
				$price = $this->config["pro_monthly"];

				if($this->do == "yearly"){
					$term = e("Yearly Subscription");
					$text = e("First year");
					$price = $this->config["pro_yearly"];
				}

				Main::set("title",e("Complete your subscription"));
				$this->header();
				include($this->t("checkout"));
			 	$this->footer();				
			}
			/**
			 * Membership Payment
			 * @since 4.0
			 **/
			private function ProcessPayPal($array=array()){
				// If demo mode is on disable this feature
				if($this->config["demo"]){
					Main::redirect(Main::href("user","",FALSE),array("danger",e("Feature disabled in demo.")));
					return;
				}		
				// Require Login
				if(!$this->logged()) {
					return Main::redirect(Main::href("user/register","",FALSE));
				}

				// Check if already pro
				if($this->pro() && !isset($_SESSION["renew"])) return Main::redirect("",array("warning",e("You are already a pro member.")));

				// Determine Fee
				if(!empty($this->do) && $this->do=="yearly"){
					$fee=$this->config["pro_yearly"];
					$period="Yearly";
				}else{
					$fee=$this->config["pro_monthly"];
					$period="Monthly";
				}
				$renew = isset($_SESSION["renew"]) ? "1" : "0";
				// Generate Paypal link
				$options = array(
						"cmd" => "_xclick",
						"business" => "{$this->config["paypal_email"]}",
		   			"currency_code" => "{$this->config["currency"]}",
		   			"item_name" => "{$this->config["title"]} $period Membership (Pro)",
		   			"custom"  =>  json_encode(array("userid" => $this->userid,"period" => $period,"renew" => $renew)),
		   			"amount" => $fee,
		   			"return" => Main::href("ipn/".md5($this->config["security"].$this->do)),
		   			"notify_url" => Main::href("ipn"),
		   			"cancel_return" => Main::href("ipn/cancel")
				);
				// Build Query
				// $options=array_replace($default,$array);		
				if(empty($options["business"])) Main::redirect("",array("danger","PayPal is not set up correctly. Please contact the administrator."));
				// Get URL
				if($this->sandbox){
					$paypal_url="https://www.sandbox.paypal.com/cgi-bin/webscr?";
				}else{
					$paypal_url="https://www.paypal.com/cgi-bin/webscr?";
				}
		    $q = http_build_query($options);
		    $paypal_url = $paypal_url.$q;
				header("Location: $paypal_url");
				exit;
			}	
	/**
	 * Verify Payment
	 * @since 4.2
	 **/		
	private function ipn(){
		// If demo mode is on disable this feature
		if($this->config["demo"]){
			Main::redirect(Main::href("user","",FALSE),array("danger",e("Feature disabled in demo.")));
			return;
		}	
		// Disable Pro membership
		if(!$this->config["pro"]) return $this->_404();

		if($this->do=="cancel") return Main::redirect("user/",array("warning",e("Your payment has been canceled.")));

   	// instantiate the IPN listener
    include(ROOT.'/includes/library/Paypal.class.php');
    $listener = new IpnListener();

    // tell the IPN listener to use the PayPal test sandbox
    $listener->use_sandbox = $this->sandbox;

    // try to process the IPN POST
    try {
      $listener->requirePostMethod();
      $verified = $listener->processIpn();   
    } catch (Exception $e) {
      error_log($e->getMessage());
      return Main::redirect("user/",array("info",e("Payment complete. We will upgrade your account as soon as the payment is verified.")));
    }
    // If Verified Purchase
    if ($verified){
    	if(isset($_POST["custom"])){
    		$data=json_decode($_POST["custom"]);
    		$this->userid=$data->userid;
    	}
    	if($data->renew === "1"){
    		$user = $this->db->get("user",array("id" => "?"),array("limit" => 1),array($this->userid));
	    	if($data->period == "Yearly"){
	    		$expires=date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($user->expiration)) . " + 1 year"));
	    		$info["duration"]="1 Year";
	    	}else{
	    		$expires=date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($user->expiration)) . " + 1 month"));
	    		$info["duration"]="1 Month";
	    	}
    	}else{
	    	if($data->period == "Yearly"){
	    		$expires=date("Y-m-d H:i:s", strtotime("+1 year"));
	    		$info["duration"]="1 Year";
	    	}else{
	    		$expires=date("Y-m-d H:i:s", strtotime("+1 month"));
	    		$info["duration"]="1 Month";
	    	}    		
    	}
    	// Save info for future needs
    	if(isset($_POST["pending_reason"])){
    		$info["pending_reason"]=$_POST["pending_reason"];
    	}
    	$info["payer_email"]=$_POST["payer_email"];
    	$info["payer_id"]=$_POST["payer_id"];
    	$info["payment_date"]=$_POST["payment_date"];

    	$insert=array(
    		":date" =>"NOW()",
    		":tid" =>$_POST["txn_id"],
    		":amount" => $_POST["mc_gross"],
    		":status" => $_POST["payment_status"],
    		":userid" => $this->userid,
    		":expiry"=> $expires,
    		":data"=> json_encode($info)
    		);
    	if($this->db->get("payment",array("tid"=>$_POST["txn_id"]))) {	
    		$this->db->update("payment",array("status"=>$_POST["payment_status"]),array("tid"=>$_POST["txn_id"]));
				return Main::redirect("user");
    	}
    	// Update database
    	if($this->db->insert("payment",$insert) && $this->db->update("user",array("last_payment"=>"NOW()","expiration"=>$expires,"pro"=>"1"),array("id"=>$this->userid))){
    		Main::redirect(Main::href("user/settings","",FALSE),array("success",e("Your payment was successfully made. Thank you.")));
    	}else{
    		Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("An unexpected issue occurred. Please contact us for more info.")));
    	}
    }
    // Return to settings page
    return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("An unexpected issue occurred. Please contact us for more info.")));
	}	
	/**
	 * [webhook description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @return  [type] [description]
	 */
	protected function webhook(){

		if(!isset($this->config["pt"]) || $this->config["pt"] != "stripe") return FALSE;
		include(STRIPE);
		
		\Stripe\Stripe::setApiKey($this->config["stsk"]);

		$payload = @file_get_contents("php://input");

		if(!$payload || empty($payload)) {
			http_response_code(400);
			exit;
		}

		if(!empty($this->config["stripesig"])){
			$sig_header = $_SERVER["HTTP_STRIPE_SIGNATURE"];
			$event = null;			
			try {
			  $event = \Stripe\Webhook::constructEvent(
			    $payload, $sig_header, $this->config["stripesig"]
			  );
			} catch(\UnexpectedValueException $e) {
			  // Invalid payload
			  error_log($e->gerMessage());
			  http_response_code(400);
			  exit();
			} catch(\Stripe\Error\SignatureVerification $e) {
			  // Invalid signature				
				error_log($e->gerMessage());
			  http_response_code(400);
			  exit();
			}			
		}
		
		$e = json_decode($payload);
		$ey = $e->data->object;

		if($ey->object == "charge"){	

			$user = $this->db->get("user", ["customerid" => $ey->customer], ["limit" => "1"]);

			$subscription = $this->db->get("subscription", ["userid" => $user->id], ["limit" => "1", "order" => "date"]);

			if($ey->paid == true && $ey->status == "succeeded"){

				if($subscription->plan == "yearly"){

					$new_expiry = date("Y-m-d H:i:s", strtotime("+1 year", strtotime($subscription->expiry)));

				}else{

					$new_expiry = date("Y-m-d H:i:s", strtotime("+1 month", strtotime($subscription->expiry)));
				}

				$PArray = [
	    		":date"  => "NOW()",
	    		":cid" => $ey->id,
	    		":tid"  => Main::strrand(16),
	    		":amount"  =>  $ey->amount / 100,
	    		":userid"  =>  $user->id,
	    		":status" => "Completed",
	    		":expiry" =>  $new_expiry,
	    		":data" =>  json_encode($ey)
	    		];

				$this->db->insert("payment", $PArray);		

				$amount = $subscription->amount + ($ey->amount / 100);

				$this->db->update("subscription",["amount" => $amount, "expiry" => $new_expiry, "status" => "Active"], ["userid" => $user->id]);	

				$this->db->update("user",["expiration" => $new_expiry, "pro" => "1"], ["id" => $user->id]);	 

				// $mail["to"] = $user->email;
				// $mail["subject"] = "[{$this->config["title"]}] Payment successfully charged.";
				// $mail["message"] = '<tr>
				// 												<td>Subscription - '.$subscription->plan.'</td>
				// 												<td class="alignright">'.Main::currency($this->config["currency"], $ey->amount / 100).'</td>
				// 											</tr>
				// 											<tr class="soustotal">
				// 												<td class="alignright" width="80%">Subtotal</td>
				// 												<td class="alignright">'.Main::currency($this->config["currency"], $ey->amount / 100).'</td>
				// 											</tr>																												
				// 											<tr class="total">
				// 												<td class="alignright" width="80%">Charged on '.$ey->source->brand.' ('.$ey->source->last4.')</td>
				// 											</tr>';

	   //    Main::send($mail);				   			    		

			}elseif ($ey->status == "failed") {
 				$PArray = [
		    		":date"  => "NOW()",
		    		":cid" => $ey->id,
		    		":tid"  => Main::strrand(16),
		    		":amount"  =>  $ey->amount / 100,
		    		":status"  =>  "Failed",
		    		":userid"  =>  $user->id,
		    		":data" =>  json_encode($ey)
		    		];

					$this->db->insert("payment", $PArray);		
				$mail["to"] = $user->email;
				$mail["subject"] = "[{$this->config["title"]}] Payment failed!";
				$mail["message"] = '<tr>
																<td>Subscription - '.$subscription->plan.'</td>
																<td class="alignright">'.Main::currency($this->config["currency"], $ey->amount / 100).'</td>
															</tr>
															<tr class="soustotal">
																<td class="alignright" width="80%">Subtotal</td>
																<td class="alignright">'.Main::currency($this->config["currency"], $ey->amount / 100).'</td>
															</tr>																												
															<tr class="total">
																<td class="alignright" width="80%">Failed on '.$ey->source->brand.' ('.$ey->source->last4.')</td>
															</tr>';

	      Main::send($mail);									
			}
		}
		http_response_code(200);
	}
	/**
	 * Profile
	 * @since 4.1
	 **/
	protected function profile(){
		// Check if user is valid and profile is public
		if(!$user = $this->db->get("user",array("username"=>"?"),array("limit" => 1),array($this->do))) return $this->_404();
		if($this->logged() && $this->userid == $user->id && !$user->public) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("You have to make your profile public for this page to be accessible.")));
		// Check if profile is public
		if(!$user->public) return $this->_404();

		// Format user info
		if(empty($user->domain)) $user->domain=$this->config["url"];
		if($user->auth=="facebook" && !empty($user->auth_id)){
			$user->avatar="{$this->http}:graph.facebook.com/".$user->auth_id."/picture?type=large";
		}else{
			$user->avatar="{$this->http}://www.gravatar.com/avatar/".md5(trim($user->email))."?s=150";		
		}
	
		$id = explode("-",$this->id);
		$id = array_reverse($id);	

		if(!empty($this->id) && is_numeric($id[0]) && $bundle = $this->db->get("bundle",array("id"=>"?","access"=>"?"),array("limit"=>1),array($id[0],"public"))){
			// Get URLs
			$urls=$this->db->get("url",array("userid"=>"?","public"=>"?","bundle"=>"?"),array("order"=>"date","limit"=>(($this->page-1)*$this->limit).", {$this->limit}","count"=>TRUE),array($user->id,"1",$bundle->id));	
			// Update view 
			$this->db->update("bundle","view= view + 1",array("id"=>$bundle->id));
			// Set Meta data	
			Main::set("title",$bundle->name." ".e("Bundle URLs"));
			Main::set("description","{$bundle->name} is a bundle that includes a series of grouped URLs shared with everyone.");
			// Pagination
			$bundle->view++;
			$heading="<em>{$bundle->name}</em> ".e("Bundle URLs")." <span class='label label-primary pull-right'>{$bundle->view} ".e("Views")."</label>";		
			$page="profile/{$user->username}/".Main::slug($bundle->name)."-".Main::slug($bundle->id);			
		}else{
			// Get URLs
			$urls=$this->db->get("url",array("userid"=>$user->id,"public"=>1,"bundle"=>$id[0]),array("order"=>"date","limit"=>(($this->page-1)*$this->limit).", {$this->limit}","count"=>TRUE));			
			Main::set("title",e("Public profile of ")." ".ucfirst($user->username));
			Main::set("description","The public profile of {$user->username} includes all of his URLs and bundles shared with everyone.");
			$heading=e("Public URLs");		
			$page="profile/{$user->username}";				
		}		

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }   
    if($this->page > 1 && $this->page > $max) Main::redirect("profile/{$user->username}",array("danger","No URLs found."));
		$pagination = Main::pagination($max,$this->page,Main::href("$page?page=%d"));	

		$this->header();
		include($this->t("profile"));
	 	$this->footer();		
	}
	/**
	* Custom Page
	* @since v2.0	
	*/
	private function page(){
		if(!empty($this->do)){
			if($this->lang!="en"){
				if(!$page=$this->db->get("page",array("seo"=>"?"),array("limit"=>1),array($this->do."_".$this->lang))){
					$page=$this->db->get("page",array("seo"=>"?"),array("limit"=>1),array($this->do));
				}				
			}else{
				$page=$this->db->get("page",array("seo"=>"?"),array("limit"=>1),array($this->do));
			}
			if(!$page){
				return $this->_404();
			}
			$page->content=$this->page_replace($page->content);

			Main::set("title",e($page->name));
			Main::set("description",Main::truncate(Main::clean(str_replace(array("\r","\n","	"),"",$page->content),3,TRUE),100));	
			Main::set("url","{$this->config["url"]}/page/{$page->seo}");		
			$this->header(); 
			include($this->t("page"));	
			$this->footer();
			return;			
		}
		return $this->_404();
	}	
	/**
	 * Contact page
	 * @since 3.1
	 */
	protected function contact(){
		if(isset($_POST["token"])){
			// Kill the bot
			if(Main::bot()) return $this->_404();
			// Validate Token
			if(!Main::validate_csrf_token($_POST["token"])){
				return Main::redirect("contact",array("danger",e("Something went wrong, please try again.")));
			}		
			if(empty($_POST["email"]) || !Main::email($_POST["email"]) || empty($_POST["message"]) || strlen($_POST["message"]) < 5){
				return Main::redirect("contact",array("danger",e("Please fill everything")."!"));			
			}
			// Check Captcha
			if($this->config["captcha"]){
				$captcha=Main::check_captcha($_POST);
				if($captcha!='ok'){
					return Main::redirect("contact",array("danger",$captcha));
				}
			}	
			$email=Main::clean($_POST["email"],3,TRUE);
			$name=Main::clean($_POST["name"],3,TRUE);			
			$mail["to"]=$this->config["email"];
			$mail["subject"]="[{$this->config["title"]}] You have been contacted!";
			$mail["message"]="From: $name ($email)<br><br>".Main::clean($_POST["message"],3,TRUE);
			Main::send($mail);
			return Main::redirect("contact",array("success",e("Your message has been sent. We will reply you as soon as possible.")));	
		}
		Main::set("title",e("Contact Us"));
		Main::set("description",e("If you have any questions, feel free to contact us on this page."));
		Main::set("url","{$this->config["url"]}/contact");
		
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();
	}
	/**
	 * Analytics
	 * @since 4.2
	 **/
	protected function analytic(){
		if(!isset($_GET["token"]) || $_GET["token"]!==$this->config["public_token"] || empty($this->do)) return $this->server_die();
		header("content-type: application/javascript");
		$decode=explode(":", base64_decode($this->do));
		$alias=str_replace("'", "", str_replace('"', "", Main::clean($decode[0],3,TRUE)));
		if(!$this->db->get("url","custom=:q OR alias=:q","",array(":q"=>$alias))) return $this->server_die();

	  $total=Main::clean(is_numeric($decode[1])?$decode[1]:1,3,TRUE);		
	  // if($this->config["pixels"]=="0"){
	  //   echo "$('.analytics').hide();";
	  //   return;
	  // }	
	  if($this->config["tracking"]=="1" || $this->config["tracking"]=="2"){
	  	$clicks=$this->stats_chart($alias,$total);
	  	$countries=$this->stats_countries($alias);	  	
	  	$country=$countries[0];
	  	$top_country=$countries[1];
	  	$data=$this->stats_referrers($alias);
	  	$referrers=$data[0];
	  	$fb=$data[1];
	  	$tw=$data[2];
	  	$gl=$data[3];
	  	$browsers=$data[4];
	  	$os=$data[5];  		  	
	  }	  
		include(ROOT."/includes/analytics.php");
	}
			/**
			 * Get Chart
			 * @since 4.0
			 **/
			private function stats_chart($id,$click,$span = 30){
				$this->db->object=TRUE;
		    $clicks=array();

				$timestamp = time();
		    for ($i = 0 ; $i < $span ; $i++) {
		        $clicks[date('Ymd', $timestamp)]=0;
		        $timestamp -= 24 * 3600;
		    }      
        $data=Main::cache_get("url_click_daily_$id");		        
        if($data == null){
          $data=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"stats"),"short='$id' AND (date >= CURDATE() - INTERVAL $span DAY)",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));  
          if($click > 1000){
          	Main::cache_set("url_click_daily_$id", $data,15);
          }
        }

		    foreach ($data as $url) {  
		      $clicks[date("Ymd", strtotime($url->date))]=$url->count;
		    }   
		    ksort($clicks);
		    unset($url,$data); 
				return $clicks;
			}
			/**
			 * Get Countries
			 * @since 4.0
			 **/
			private function stats_countries($id,$span = 14){
    		$country=array();
    		$top_country=array();				
				$this->db->object=TRUE;
      	$data=Main::cache_get("url_country_$id");		        
        if($data == null){
          $data=$this->db->get(array("count"=>"country AS country, COUNT(country) AS count","table"=>"stats"),array("short"=>"?"),array("group"=>"country","order"=>"count"),array($id));  
          	Main::cache_set("url_country_$id", $data,15);
        }				
		    $i=0;
		    foreach ($data as $url) {
		    	$code = Main::ccode(ucwords($url->country),TRUE);
	        if($code) $country[$code]=$url->count;
	        if(!empty($url->country) && $i<=9){
	          $top_country[ucwords($url->country)]=$url->count;
	        }
	        $i++;
		    }
		    arsort($country);
		    arsort($top_country);
				return array($country,$top_country);
			}
			/**
			 * Referrers
			 * @since 4.0
			 **/
			private function stats_referrers($id,$span = 14){
				$domains=array();
		    $data=$this->db->get(array("count"=>"domain AS domain, COUNT(domain) AS count","table"=>"stats"),array("short"=>"?"), array('group' => "domain","limit"=>10),array($id));
		    $browser = $this->db->get(array("count"=>"browser as browser, COUNT(browser) AS count","table"=>"stats"),array("short"=>"?"), array('group' => "browser","limit"=>10, "order" => "count"),array($id));
		    $os = $this->db->get(array("count"=>"os as os, COUNT(os) AS count","table"=>"stats"),array("short"=>"?"), array('group' => "os","limit"=>10,"order" => "count"),array($id));		    
		    $fb = $this->db->count("stats","short='$id' AND (domain LIKE '%facebook.%' OR domain LIKE '%fb.%')");
		    $tw = $this->db->count("stats","short='$id' AND (domain LIKE '%twitter.%' OR domain LIKE '%t.co%')");
		    $gl = $this->db->count("stats","short='$id' AND (domain LIKE '%plus.url.google%')");
		    foreach ($data as $url) {
		    	if(empty($url->domain)) $url->domain=e("Direct, email and other");
		    	if(!preg_match("~facebook.~", $url->domain) && !preg_match("~fb.~", $url->domain) && !preg_match("~t.co~", $url->domain) && !preg_match("~twitter.~", $url->domain) && !preg_match("~plus.url.google.~", $url->domain)){
		    		$domains[$url->domain]=$url->count;
		    	}
		    }  
		    arsort($domains);
				return array($domains,$fb,$tw,$gl,$browser,$os);
			}
	/**
	 * [_404 description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function custom404(){
		// 410 Header
		header('HTTP/1.0 410 Gone');
		// Set Meta Tags
		Main::set("title",e("Expired Resource"));
		Main::set("description","The resource your are looking for has expired.");
		Main::set("body_class","light");
		$content="<section class='red rounded wshadow'><h1>410</h1>
							<h2>Resource Expired</h2></section>";							
		$this->header();
		include($this->t("shared/template"));
		$this->footer();
	}			
	/**
	 * 404 Page
	 * @since 4.0
	 **/
	protected function _404(){
		// 404 Header
		header('HTTP/1.0 404 Not Found');
		// Set Meta Tags
		Main::set("title",e("Page not found"));
		Main::set("description","The page you are looking for cannot be found anywhere. Please try again or contact us for more info.");
		Main::set("body_class","light");
		$content="<section class='blue rounded wshadow'><h1>404</h1>
							<h2>Not Found</h2></section>";							
		$this->header();
		include($this->t("shared/template"));
		$this->footer();
	}
	/**
	 * Private Page
	 * @since 5.0
	 */		
	public function _private(){

		if(!empty($this->config["home_redir"])){
			header("Location: {$this->config["home_redir"]}");
			exit;
		}

		Main::set("title","Private URL Shortener");			
		Main::set("description","This URL shortener is private and internal-use only.");
		Main::set("body_class","light");					
		$content="<section class='blue rounded wshadow'><h1>Hello</h1>
							<h3>This service is meant to be private.</h3></section>";
		$this->header();
		include($this->t("shared/template"));
		$this->footer();		
	}		
	/**
	 * Maintenance Page
	 * @since v2.0
	 */		
	public function _maintenance(){
		Main::set("title","Under Maintenance");			
		Main::set("description","We are currently under maintenance.");
		Main::set("body_class","light");					
		$content="<section class='blue rounded wshadow'><h1><i class='glyphicon glyphicon-cog'></i></h1>
							<h3>We are currently under maintenance.</h3></section>";
		$this->header();
		include($this->t("shared/template"));
		$this->footer();		
	}	
	/**
	 * Header
	 * @since 4.5.6
	 **/
	protected function header(){

		if(!empty($this->config["style"]) && file_exists(TEMPLATE."/styles/{$this->config["style"]}.css")){
			$css="styles/{$this->config["style"]}.css";
			Main::add("{$this->config["url"]}/themes/{$this->config["theme"]}/styles/{$this->config["style"]}.css","style",false);
		}

		if($this->sandbox == TRUE) {
			// Developement Stylesheets
			Main::add("<link rel='stylesheet/less' type='text/css' href='{$this->config["url"]}/themes/{$this->config["theme"]}/style.less'>","custom",false);
			Main::cdn("less");
		}

		// Use CDN for better performance
		if($this->config["cdn"]){
			Main::cdn("chosen");
			Main::cdn("icheck");
			Main::cdn("clipboard");			
			Main::cdn("consent");			
		}else{
			Main::add($this->config["url"]."/static/js/chosen.min.js","script",0);
			Main::add($this->config["url"]."/static/js/icheck.min.js","script",0);
		}
		if(!empty($this->config["font"])) {
			Main::add("https://fonts.googleapis.com/css?family=".str_replace(' ', '+', ucwords($this->config["font"])),"style",FALSE);
			Main::add("<style type='text/css'>body{font-family: {$this->config["font"]} }</style>","custom",FALSE);
		}
		if(!empty($this->config["analytic"])){					
			Main::add("<script type='text/javascript'>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create', '{$this->config["analytic"]}','auto');ga('send', 'pageview');</script>","custom",FALSE);
		}				

		Main::cdn("pace");	
		include($this->t(__FUNCTION__));
	}
	/**
	 * Footer
	 * @since 4.0
	 **/
	protected function footer(){
		$pages=$this->db->get("page",array("menu"=>1),array("limit"=>10));
		include($this->t(__FUNCTION__));
	}		
	/**
	 * Shortener Form
	 * @since 4.0
	 **/
	protected function shortener($option=array()){
		// Override Options
		if(!isset($option["advanced"])) $option["advanced"]=1;
		if(!isset($option["multiple"])) $option["multiple"]=1;
		if(!isset($option["autohide"])) $option["autohide"]=1;

		include(TEMPLATE."/shared/shortener.php");
		return;
	}
			/**
			 * Option
			 * @since 4.0 
			 **/
			protected function shortener_option($form=FALSE){
				$html="";
				if($this->config["multiple_domains"]){
					$html='<select name="domain" class="form-control">';
					$html.='<optgroup label="'.e('Choose Domain').'" />';
					$domains=explode("\n", $this->config["domain_names"]);
					$html.='<option value="'.strtolower($this->config["url"]).'">'.ucfirst(str_replace("https://","",str_replace("http://", "",$this->config["url"]))).'</option>';
					foreach ($domains as $domain) {
						if(!empty($domain)) $html.='<option value="'.strtolower(trim($domain)).'"'.(($this->logged() && $this->user->domain==$domain)?' selected':'').'>'.ucfirst(str_replace("https://","",str_replace("http://", "", trim($domain)))).'</option>';
					}
					$html.='</select>';
				}						
				if($this->config["frame"] == "3" && !$this->pro()){
					$html .= '<select name="type" class="form-control">
										<optgroup label="'.e('Redirection').'">
							        <option value="direct">'.e("Direct").'</option>
							        <option value="frame">'.e("Frame").'</option>
							        <option value="splash">'.e("Splash").'</option>
						        </optgroup>
						      </select>';
				}
				if($this->logged() && $this->pro()){
					$splash = $this->db->get("splash",array("userid"=>"?"),array("order"=>"date"),array($this->userid));
					$html .= '<select name="type" class="form-control">
										<optgroup label="'.e('Redirection').'">
							        <option value="direct"'.($this->user->defaulttype == "direct" || $this->user->defaulttype== "" ?" selected":"").'>'.e("Direct").'</option>
							        <option value="frame"'.($this->user->defaulttype == "frame"?" selected":"").'>'.e("Frame").'</option>
							        <option value="splash"'.($this->user->defaulttype == "splash"?" selected":"").'>'.e("Splash").'</option>
							        <option value="overlay"'.($this->user->defaulttype == "overlay"?" selected":"").'>'.e("Overlay").'</option>
						        </optgroup>';
					if($splash){
						$html.='<optgroup label="'.e('Custom Splash').'">';
						foreach ($splash as $type) {
							$html.='<option value="'.$type->id.'">'.ucfirst($type->name).'</option>"';
						}				
						$html.="</optgroup>";
					}
					$html .= '</select>';					
				}
				return $html;
			}
	/**
	 * Header Menu
	 * To add a custom menu, send an array of urls with a text and href index e.g. array(array("href"=>"","text"=>""),array("href"=>"","text"=>""))
	 * @since 4.0
	 **/
	protected function menu($option=array()){
		$menu='<div class="navbar-collapse collapse">';
			$menu.='<ul class="nav navbar-nav navbar-right">';
	      if(!$this->logged()){
					if($this->config["user"] && !$this->config["private"] && !$this->config["maintenance"]){
		      	$menu.='<li><a href="'.$this->config["url"].'/pricing">'.e("Pricing").'</a></li>';
		      }
					$menu.='<li><a href="'.Main::href("user/login").'">'.e("Login").'</a></li>';
					if($this->config["user"] && !$this->config["private"] && !$this->config["maintenance"]){
						$menu.='<li><a href="'.Main::href("user/register").'" class="active">'.e("Get Started").'</a></li>';
					}
	      }else{
          if ($this->admin()){
          	$menu.='<li><a href="'.$this->config["url"].'/admin" class="active">'.e("Admin").'</a></li>';
          }
          if(!$this->pro() && $this->config["pro"]){
          	$menu.='<li><a href="'.$this->config["url"].'/pricing" class="active">'.e("Upgrade").'</a></li>';
          }
          $menu.="<li><a href='".Main::href('user')."'>".e('My Account')."</a></li>";
          if(!empty($option) && is_array($option)){
          	foreach ($option as $item) {
          		if(isset($item["href"]) && isset($item["text"])){
          			$menu.='<li><a href="'.Main::clean($item["href"],3,TRUE).'" rel="custom">'.Main::clean($item["text"],3,TRUE).'</a></li>';
          		}
          	}
          }
          $menu.='<li><a href="'.Main::href("user/logout").'">'.e("Logout").'</a></li>';
	      }		
			$menu.='</ul>';

		$menu.='</div>';
		return $menu;
	}
	/**
	 * User Menu
	 * To add a custom menu, send an array of urls with a text and href index e.g. array(array("href"=>"","text"=>""),array("href"=>"","text"=>""))
	 * @since 4.3
	 **/
	protected function user_menu($option=array()){
		$menu='<ul class="nav nav-sidebar">';
			$menu.='<li><a href="'.Main::href("user").'" class="active"><span class="glyphicon glyphicon-home"></span> '.e('Dashboard').'</a></li>';
			$menu.='<li><a href="'.Main::href("user/archive").'"><span class="glyphicon glyphicon-briefcase"></span> '.e('Archived Links').'</a></li>';
			$menu.='<li><a href="'.Main::href("user/expired").'"><span class="glyphicon glyphicon-calendar"></span> '.e('Expired Links').'</a></li>';
			$menu.='<li><a href="'.Main::href("user/bundles").'"><span class="glyphicon glyphicon-folder-open"></span> '.e('Bundles').'</a></li>';			
			$menu.='<li'.(!$this->pro() ? ' class="locked"': '').'><a href="'.Main::href("user/splash").'"><span class="glyphicon glyphicon-transfer"></span> '.e('Splash Pages').''.(!$this->pro() ? '<span class="label label-secondary pull-right">'.e('Pro').'</span>': '').'</a></li>';
			$menu.='<li'.(!$this->pro() ? ' class="locked"': '').'><a href="'.Main::href("user/overlay").'"><span class="glyphicon glyphicon-record"></span> '.e('Overlay Pages').''.(!$this->pro() ? '<span class="label label-secondary pull-right">'.e('Pro').'</span>': '').'</a></li>';
			$menu.='<li'.(!$this->pro() ? ' class="locked"': '').'><a href="'.Main::href("user/pixels").'"><span class="glyphicon glyphicon-screenshot"></span> '.e('Tracking Pixels').''.(!$this->pro() ? '<span class="label label-secondary pull-right">'.e('Pro').'</span>': '').'</a></li>';			
			$public = $this->user->public ?"<span class='label label-primary pull-right'>".e("Online")."</span>"  : "<span class='label label-danger pull-right'>".e("Offline")."</span>";
			$menu.='<li><a href="'.Main::href("profile/{$this->user->username}").'"><span class="glyphicon glyphicon-cloud'.($this->user->public ?" icon-green": " icon-red").'"></span> '.e('Public Profile').'</a></li>';
			if($this->config["api"]){
				$menu.='<li><a href="'.Main::href("user/tools").'"><span class="glyphicon glyphicon-wrench"></span> '.e('Tools').'</a></li>';
			}

			$menu.='<li><a href="'.Main::href("user/settings").'"><span class="glyphicon glyphicon-cog"></span> '.e('Settings').'</a></li>';

      if(!empty($option) && is_array($option)){
      	foreach ($option as $item) {
      		if(isset($item["href"]) && isset($item["text"])){
      			$menu.='<li><a href="'.Main::clean($item["href"],3,TRUE).'" rel="custom">'.Main::clean($item["text"],3,TRUE).'</a></li>';
      		}
      	}
      }			
		$menu.='</ul>';
			$menu.='<h3>'.e("Account Info");
							if (!$this->config["pro"] || $this->pro()){
              	$menu.='<span class="label label-primary pull-right">'.e("Pro").'</span>';
              }else{
              	$menu.='<span class="label label-primary pull-right">'.e("Free").'</span>';
              }	              	
	  	$menu.='</h3>';
	    $menu.='<div class="side-stats hidden-xs">
			          <p><span>'.$this->count("user_urls").' '.(!$this->pro() && $this->config["freeurls"] > 0 ? e("out of")." {$this->config["freeurls"]}": "").'</span> '.e('URLs').'</p>
			          <p><span>'.$this->count("user_clicks").'</span> '.e('Clicks').'</p>    
			          <p><span>'.$this->count("user_bundles").'</span> '.e('Bundles').'</p>			         
			          <p><span>'.$this->db->count("bundle","userid='{$this->userid}'","view").'</span> '.e('Bundles Views').'</p>';
	    $menu.='</div>';
			if($this->pro() && $this->config["pro"]){
				$menu.="<h3>".e("Next Payment")."</h3>";
	    	$menu.='<div class="side-stats"><p><span>'.date("F d, Y",strtotime($this->user->expiration)).'</span> </p></div>';								
			}	 			    
		return $menu;
	}	
	/**
	 * Server Requests
	 * @since 4.0
	 **/
	protected function server(){
		// Make sure that the request is valid!
		if(!isset($_POST["request"]) || !isset($_POST["token"]) || $_POST["token"]!==$this->config["public_token"]) return $this->server_die();		

		$server = Main::clean($_POST["request"],3,TRUE);
		// Swtich requests
		$system=array("unlock","lock","bundle","edit","archive","unarchive","activities","bundle_urls","url_bundle_add","bundle_create","bundle_edit", "cancel");	
		$public=array("chart","bundles");
		$fn = "server_$server";

		if(in_array($server, $public) && method_exists("App",$fn)){
			return $this->$fn();
		}		
		// Make sure that user is logged to access protected server requests
		if(!$this->logged()) return $this->_404();		

		if(in_array($server, $system) && method_exists("App",$fn)){
			return $this->$fn();
		}
		return $this->server_die();		
	}	
		/**
		 * Server Error
		 * @since 4.0
		 **/
		private function server_die(){
			return die(header('HTTP/1.1 400 Bad Request', true, 400));
		}
			/**
			 * Lock a URL
			 * @since 4.0
			 */
			private function server_lock(){
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				if($this->db->update("url",array("public"=>"?"),array("userid"=>"?","id"=>"?"),array("0",$this->userid,Main::clean($_POST["id"])))){
					echo '<a href="#public?" class="ajax_call" data-id="'.Main::clean($_POST["id"]).'" data-action="unlock" data-class="lock-url-'.Main::clean($_POST["id"]).'"><i class="glyphicon glyphicon-eye-close"></i> '.e('Private').'</a>';
					return;
				}
			}
			/**
			 * Unlock a URL
			 * @since 4.0
			 */
			private function server_unlock(){
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				if($this->db->update("url",array("public"=>"?"),array("userid"=>"?","id"=>"?"),array("1",$this->userid,Main::clean($_POST["id"])))){
					echo '<a href="#private?" class="ajax_call" data-id="'.Main::clean($_POST["id"]).'" data-action="lock" data-class="lock-url-'.Main::clean($_POST["id"]).'"><i class="glyphicon glyphicon-eye-open"></i> '.e('Public').'</a>';
					return;
				}
			}		
			/**
			 * URL Archive 
			 * @since v3.0
			 */
			private function server_archive(){
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				if($this->db->update("url",array("archived"=>"?"),array("id"=>"?","userid"=>"?"),array("1",Main::clean($_POST["id"],3,TRUE),$this->userid))){
					echo "<div class='alert alert-success'>".e("URL successfully archived.")."</div>";
					echo "<script type='text/javascript'>$('#url-container-".Main::clean($_POST["id"],3,TRUE)."').fadeOut('slow');</script>";
				}
			}
			/**
			 * URL Unrchive 
			 * @since v3.0
			 */
			private function server_unarchive(){
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				if($this->db->update("url",array("archived"=>"?"),array("id"=>"?","userid"=>"?"),array("0",Main::clean($_POST["id"],3,TRUE),$this->userid))){
					echo "<div class='alert alert-success'>".e("URL successfully unarchived.")."</div>";
					echo "<script type='text/javascript'>$('#url-container-".Main::clean($_POST["id"],3,TRUE)."').fadeOut('slow');</script>";
				}
			}	
			/**
			 * Realtime Activities
			 * @since v4.0
			 */			
			private function server_activities(){
				// Check request
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				// Get data
				$data = $this->db->get("stats",array("urluserid"=>"?"),array("limit"=>10,"order"=>"date"),array($this->userid));
				$html = "";
    		foreach ($data as $item) {
    			$url = $this->db->get(array("count"=>"meta_title","table"=>"url"),"BINARY alias=:q OR BINARY custom=:q",array("limit"=>1),array(":q"=>$item->short));
						// Get Domain
        	$domain=(empty($item->referer) || $item->referer=="direct") ? e("directly ") : e("referred by ")."<a href='".Main::clean($item->referer,3,TRUE)."' target='_blank'>".Main::domain($item->referer,0)."</a>";

    		  $html.="<li data-id='{$item->id}'>".sprintf(e("%s from %s %s visited %s %s"),($item->os?"<strong>{$item->os}</strong> user":e("Someone")),"<strong>".ucwords($item->country)."</strong>",$domain,"<a href='{$this->user->domain}/{$item->short}+' target='_blank'>".($url?Main::truncate($url->meta_title,15):e("Undefined Title"))."</a>","<span>".Main::timeago($item->date)."</span>")."</li>";
    		}  
				echo $html;
				return FALSE;
			}
			/**
			 * Bundle URLs
			 * @since v4.0
			 */			
			private function server_bundle_urls(){
				// Check request
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				// Get data
				$urls = $this->db->get("url",array("bundle"=>"?","userid"=>"?"),array("limit"=>50,"order"=>"date"),array(Main::clean($_POST["id"],3,TRUE),$this->userid));
				if(!$urls) return print("<p class='center'>".e("No URLs found.")."</p>");

    		foreach ($urls as $url) {
    			include(TEMPLATE."/shared/url_loop.php");
    		}
    		echo "<script>loadall();</script>";
			}
			/**
			 * Add to Bundle
			 * @since v4.0
			 */			
			private function server_url_bundle_add(){
				// Check request
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				// Get Data
				if(!$url=$this->db->get("url",array("id"=>"?","userid"=>"?"),array("limit"=>1),array(Main::clean($_POST["id"],3,TRUE),$this->userid))) return $this->server_die();

					if($bundles=$this->db->get("bundle",array("userid"=>"?"),"",array($this->userid))){
						echo '<form role="form" action="'.Main::href("user/bundles/update").'" method="post">
							  <div class="form-group">
							    <label>'.e("URL").'</label>
							    <input type="text" class="form-control" value="'.$url->url.'" disabled>
							  </div>
							  <div class="form-group">
									<label class="label-block">'.e("Choose Bundle").' <a href="#" data-action="bundle_create" data-title="'.e("Create Bundle").'" class="btn btn-xs btn-primary pull-right ajax_call">'.e("Create Bundle").'</a></label>
									<select name="bundle_id">';
									echo "<option value=''>".e("Remove from Bundle")."</option>";
									foreach ($bundles as $bundle) {
										echo '<option value="'.$bundle->id.'" '.($url->bundle==$bundle->id?'selected':'').'>'.$bundle->name.'</option>';
									}
							echo '</select>
							  </div>							  
								'.Main::csrf_token(TRUE).'
								<input type="hidden" name="url_id" value="'.$url->id.'">
								<button type="submit" class="btn btn-primary">'.e("Add to bundle").'</button>
								<script>$("select").chosen();</script>';
					}		
			}
			/**
			 * Create Bundle
			 * @since v4.0
			 */			
			private function server_bundle_create(){
				echo '<form action="'.Main::href("user/bundles/add").'" method="post" class="form">
							<div class="form-group">
								<label>'.e("Bundle Name").' ('.e("required").')</label>			
								<input type="text" value="" name="name" class="form-control" />
							</div>
								<ul class="form_opt" data-id="access">
									<li class="text-label">'.e("Bundle Access").'
									<small>'.e("If you set it to private, only you can access the URLs").'.</small>
									</li>
									<li><a href="" class="last current" data-value="private">'.e("Private").'</a></li>
									<li><a href="" class="first" data-value="public">'.e("Public").'</a></li>
								</ul>
								<input type="hidden" name="access" id="access" value="private">	

								'.Main::csrf_token(TRUE).'
								<button type="submit" class="btn btn-primary">'.e("Create Bundle").'</button>							
						</form>';
			}
			/**
			 * Edit Bundle
			 * @since v4.0
			 */			
			private function server_bundle_edit(){
				// Check request
				if(!isset($_POST["id"]) || !is_numeric($_POST["id"])) return $this->server_die();
				if(!$bundle=$this->db->get("bundle",array("userid"=>"?","id"=>"?"),array("limit"=>1),array($this->userid,Main::clean($_POST["id"],3,TRUE)))) return $this->server_die();

				echo '<form action="'.Main::href("user/bundles/edit").'" method="post" class="form">
							<div class="form-group">
								<label>'.e("Bundle Name").' ('.e("required").')</label>			
								<input type="text" value="'.$bundle->name.'" name="name" class="form-control" />
							</div>
								<ul class="form_opt" data-id="access">
									<li class="text-label">'.e("Bundle Access").'
									<small>'.e("If you set it to private, only you can access the URLs").'.</small>
									</li>
									<li><a href="" class="last'.($bundle->access=="private"?" current":"").'" data-value="private">'.e("Private").'</a></li>
									<li><a href="" class="first'.($bundle->access=="public"?" current":"").'" data-value="public">'.e("Public").'</a></li>
								</ul>
								<input type="hidden" name="access" id="access" value="'.$bundle->access.'">	

								'.Main::csrf_token(TRUE).'
								<input type="hidden" name="id" value="'.$bundle->id.'" />
								<button type="submit" class="btn btn-primary">'.e("Update Bundle").'</button>							
						</form>';
			}	
			/**
			 * Update Chart
			 * @since 4.0
			 **/
			private function server_chart(){
				$this->db->object=TRUE;
		    $clicks=array();
		    if(!isset($_POST["id"])) return $this->server_die();
		    header("content-type: application/javascript");
		    $data=json_decode($_POST["id"],TRUE);
		    $var=Main::clean($data[0],3,TRUE);
		    $id=Main::clean($data[1],3,TRUE);
		    $click=Main::clean($data[2],3,TRUE);
		    if(!in_array($var,array("m","y"))) $this->server_die();
				if($var=="m"){   					
					$span = 11;
					$timestamp = time();
			    for ($i = 0 ; $i < $span ; $i++) {
			        $clicks[date('Y-m', $timestamp)]=0;
			        $timestamp -= 24 * 3600 * 30;
			    }
					
	        $data=Main::cache_get("url_click_monthly_$id");		        
	        if($data == null){
	          $data=$this->db->get(array("count"=>"COUNT(MONTH(date)) as count, DATE(date) as date","table"=>"stats"),"short=? AND (date >= DATE_SUB(CURDATE(), INTERVAL $span MONTH))",array("group_custom"=>"MONTH(date)","order"=>"date","limit"=>30),array($id)); 
	          	//Main::cache_set("url_click_monthly_$id", $data,15);
	        }
			    foreach ($data as $url) {  
			      $clicks[date('Y-m', strtotime($url->date))]=$url->count;
			    }		
			    $d="";
			    foreach ($clicks as $date => $count) {
			    	$d .= "[".(strtotime($date)*1000).",$count],";
			    }
				}elseif($var=="y"){
					$span = 8;
					$timestamp = time();
			    for ($i = 0 ; $i < $span ; $i++) {
			        $clicks[$timestamp]=0;
			        $timestamp -= 365*24*60*60;
			    }					
	        $data=Main::cache_get("url_click_yearly_$id");		        
	        if($data == null){
	          $data=$this->db->get(array("count"=>"COUNT(YEAR(date)) as count, DATE(date) as date","table"=>"stats"),"short=? AND (date >= DATE_SUB(CURDATE(), INTERVAL $span YEAR))",array("group_custom"=>"YEAR(date)","order"=>"date","limit"=>$span),array($id)); 
	          	Main::cache_set("url_click_yearly_$id", $data,15);
	        }
			    foreach ($data as $url) {  
			      $clicks[strtotime($url->date)]=$url->count;
			    }
			    $d="";
			    foreach ($clicks as $date => $count) {
			    	$d .= "[".($date*1000).",$count],";
			    }			
				}
				$d=rtrim($d,",");
		    unset($url,$data); 
		    echo '{"data": ['.$d.']}';
				return;				
			}	
			/**
			 * Get Public Bundles
			 * @since 4.0
			 **/				
			private function server_bundles(){
				$id=Main::clean(substr(base64_decode($_POST["id"]), 3),3,TRUE);
				if(!$user = $this->db->get("user",array("id"=>"?","public"=>"?"),array("limit"=>"1"),array($id,"1"))) return $this->server_die();

				$bundles=$this->db->get("bundle",array("userid"=>"?","access"=>"?"),array("order"=>"date","limit"=>50),array($user->id,"public"));
				$html="<h3>".e("Public Bundles")."</h3>";
				$html.='<ul class="list-group bundles">';
				foreach ($bundles as $bundle){
					$url=$this->config["url"].'/profile/'.$user->username.'/'.Main::slug($bundle->name).'-'.$bundle->id;
					$html.='<li class="list-group-item">';
						$html.='<a href="'.$url.'"><h4 class="list-group-item-heading">'.$bundle->name.'</h4></a>';
						$html.='<p>'.$url.' <a href="#" class="copy inline-copy" data-value="'.$this->config["url"].'/profile/'.$user->username.'/'.Main::slug($bundle->name).'-'.$bundle->id.'">'.e("Copy").'</a></p>';

						$html.='<p class="list-group-item-text">
								    	<strong>'.$this->count("user_public_bundle_urls",$bundle->id).' '.e("URLs").'</strong>
								    	&nbsp;&nbsp;&bullet;&nbsp;&nbsp;	
											'.Main::timeago($bundle->date).'
											&nbsp;&nbsp;&bullet;&nbsp;&nbsp;
            					<a href="https://twitter.com/share?url='.$url.'&amp;text=Check+out+this+bundle" class="u_share">'.e("Share on").' Twitter</a>
											&nbsp;&nbsp;&bullet;&nbsp;&nbsp;
											<a href="https://www.facebook.com/sharer.php?u='.$url.'" class="u_share">'.e("Share on").' Facebook</a>											
								    </p>';
					$html.='</li>';	
				}				
				$html.='</ul>';
				echo $html;
				return;
			}
	/**
	 * [server_cancel description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @return  [type] [description]
	 */
	protected function server_cancel(){
				echo '<form action="'.Main::href("user/cancel").'" method="post" class="form">
								<p>'.e("Although we respect your decision, we are still sorry to see you go. If you want to share anything with us, please use the box below and we will do our best to improve our service.").'</p>

								<div class="form-group">
									<label>'.e("Password").'</label>			
									<input type="password" name="password" class="form-control" />
								</div>				
								<div class="form-group">
									<label>'.e("Reason for cancellation").'</label>			
									<textarea name="reason" class="form-control"></textarea>
								</div>

								'.Main::csrf_token(TRUE).'
								<button type="submit" class="btn btn-primary">'.e("Cancel my membership").'</button>							
						</form>';
	}
	/**
	 * Notice
	 * @since 4.2
	 **/
	protected function sidebar(){
		if($this->isExpired()){
			echo "<p class='alert alert-info no-round'>".e("Please note that your premium membership is about to expire. You can renew it right now by clicking the button below.")." <br><br><a href='{$this->config["url"]}/upgrade/renew' class='btn btn-primary btn-sm'>".e("Renew")."</a></p>";
		}
		// Plug in sidebar
		Main::plug("sidebar");
	}
	/**
	 * Widgets
	 * @since 4.0
	 **/
	protected function widgets($widget,$option=array()){
		$system=array("activities","top_urls","countries","news","tools","social_count","export");
		$fn = "widget_$widget";
		## if(in_array($widget, $system) && method_exists("App",$fn)){
		if(method_exists("App",$fn)){
			return $this->$fn($option);
		}
		return FALSE;
	}
		/**
		 * Recent Activity Widgets
		 * @since 4.3		 
		 **/
		protected function widget_activities($option=array()){
			// Only works with system stats
			if($this->config["tracking"]!=="1") return FALSE;
			if(!$this->logged()) return FALSE;

			if(!isset($option["limit"]) || !is_numeric($option["limit"]) || $option["limit"]<=0) $option["limit"]=10;
			if(!isset($option["refresh"]) || !is_numeric($option["refresh"]) || $option["refresh"]<=0) $option["refresh"]=10000;

			// Get data
			$data = $this->db->get("stats",array("urluserid"=>"?"),array("limit"=>$option["limit"],"order"=>"date"),array($this->userid));			
			$html="<div class='panel panel-default panel-body activities' id='".__FUNCTION__."' data-refresh='{$option["refresh"]}'>";
      	$html.="<h3>".e("Recent Activities")." <small class='pull-right'>".e("Realtime")."</small></h3>";
      	if(empty($data)){
      		$html.="<p class='center'>".e("No activities yet")."...</p>";
      	}else{
        	$html.="<ul>";
        		foreach ($data as $item) {
        			$url = $this->db->get(array("count" => "meta_title", "table" => "url"),"BINARY alias=:q OR BINARY custom=:q",array("limit"=>1),array(":q"=>$item->short));
        			
							// Get Domain
        			$domain=(empty($item->referer) || $item->referer=="direct") ? e("directly ") : e("referred by ")."<a href='".Main::clean($item->referer,3,TRUE)."' target='_blank'>".Main::domain($item->referer,0)."</a>";

        		  $html.="<li data-id='{$item->id}'>".sprintf(e("%s from %s %s visited %s %s"),($item->os?"<strong>{$item->os}</strong> user":e("Someone")),"<strong>".ucwords($item->country)."</strong>",$domain,"<a href='{$this->user->domain}/{$item->short}+' target='_blank'>".($url?Main::truncate($url->meta_title,15):e("Undefined Title"))."</a>","<span>".Main::timeago($item->date)."</span>")."</li>";
        		}       
       		$html.="</ul>";    		
      	}
			$html.="</div>";
			return $html;
		}
		/**
		 * Recent URLs Widgets
		 * @since 5.0
		 **/
		protected function widget_top_urls($option=array()){
			if(!isset($option["limit"]) || !is_numeric($option["limit"]) || $option["limit"]<=0) $option["limit"]=10;
			if(!$this->logged()) return FALSE;
			// Get data
			$data = $this->db->get("url","userid = '{$this->userid}' AND (expiry IS NULL OR expiry > DATE(CURDATE())) AND archived = '0'",array("limit" => $option["limit"],"order"=> "click"));

			$html="<div class='panel panel-default panel-body' id='".__FUNCTION__."'>";
      	$html.="<h3>".e("Top URLs")."</h3>";
      	if(empty($data)){
      		$html.="<p class='center'>".e("No URLs found")."...</p>";
      	}else{
        	$html.="<ul>";
        		foreach ($data as $url) {
        		  $html.="<li>
        		  <a href='{$this->user->domain}/{$url->alias}{$url->custom}+' target='_blank'>
        		  &nbsp;<img src='{$this->http}://www.google.com/s2/favicons?domain={$url->url}' alt='favicon'>
        		  ".(empty($url->meta_title)?"{$this->user->domain}/{$url->alias}{$url->custom}":Main::truncate($url->meta_title,30))."
        		  </a> - <strong>{$url->click} ".e("Click")."</strong> <span>".Main::timeago($url->date)."</span>
        		  </li>";
        		}       
       		$html.="</ul>";    		
      	}
			$html.="</div>";
			return $html;
		}
		/**
		 * Countries
		 * @since 4.0
		 **/
		protected function widget_countries($option=array()){
			// Only works with system stats
			if($this->config["tracking"]!=="1") return FALSE;
			if(!$this->logged()) return FALSE;

			if(isset($option["urlid"])) {
				$where=array("short"=>Main::clean($option["urlid"],3,TRUE));
			}else{
				$option["urlid"]="";
				$where=array("urluserid"=>$this->userid);
			}
			$countries = Main::cache_get("user_chart_{$option["urlid"]}");
      if($countries == null){
      	$countries=$this->db->get(array("count"=>"COUNT(country) as count, country as country","table"=>"stats"),$where,array("group"=>"country","order"=>"count","limit"=>199));
      	Main::cache_set("user_chart_{$option["urlid"]}",$countries,30);
      }
      $i=0;
      $top_countries=array();
      $country=array();
      foreach ($countries as $c) {
        $country[Main::ccode(ucwords($c->country),1)]=$c->count;
        if($i<=10){
          if(!empty($c->country)) $top_countries[ucwords($c->country)]=$c->count;
        }
        $i++;
      }
      Main::add("{$this->config["url"]}/static/js/jvector.js");
      Main::add("{$this->config["url"]}/static/js/jvector.world.js");
      Main::add("<script type='text/javascript'>var data=".json_encode($country)."; $('#country-map').vectorMap({
        map: 'world_mill_en',
        backgroundColor: 'transparent',
        series: {
          regions: [{
            values: data,
            scale: ['#74CBFA', '#0da1f5'],
            normalizeFunction: 'polynomial'
          }]
        },
        onRegionLabelShow: function(e, el, code){
          if(typeof data[code]!='undefined') el.html(el.html()+' ('+data[code]+' Clicks)');
        }     
      });</script>","custom");
			$html="<div class='panel panel-dark panel-body' id='".__FUNCTION__."'>";
 				$html.="<div id='country-map' style='width:100%;height:300px;'></div>";
			$html.="</div>";
			return $html;                
  	}
  	/**
  	 * Last news
  	 * @since 4.0
  	 **/
  	protected function widget_news($option=array()){
  		if(empty($this->config["news"])) return FALSE;
  		$html="<div class='panel panel-default panel-body' id='".__FUNCTION__."'>";
      	$html.="<h3>".e("Announcement")."</h3>";
      	$html.=Main::clean($this->config["news"]);
      $html.="</div>";
      return $html;
  	}
  	/**
  	 * Tools widget
  	 * @since 4.0
  	 **/
  	protected function widget_tools(){  		
			$html='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
				$html.='<h3>'.e("Tools").'</h3>';
				$html.='<p>'.e("You can use our bookmarklet tool to instantaneously shorten any site you are currently viewing and if you are logged in on our site, it will be automatically saved to your account for future access. Simply drag the following link to your bookmarks bar or copy the link and manually add it to your favorites.").'</p>';
				$html.="<a class='btn btn-block btn-primary' href=\"javascript:void((function(){if(window.location.protocol=='https:'){window.location='".$this->config["url"]."/?bookmark=true&amp;token=".md5($this->config["public_token"])."&amp;url='+encodeURIComponent(document.URL);}else{var e=document.createElement('script');e.setAttribute('data-url','".$this->config["url"]."');e.setAttribute('data-token','".md5($this->config["public_token"])."');e.setAttribute('id','gem_bookmarklet');e.setAttribute('type','text/javascript');e.setAttribute('src','".$this->config["url"]."/static/bookmarklet.js?v=".time() ."');document.body.appendChild(e)}})());\" rel='nofollow' title='".e('Drag me to your Bookmark Bar')."' style='cursor:move'>".e('Bookmarklet')."</a>";
			$html.='</div>';
			return $html;
  	}
  	/**
  	 * Export widget
  	 * @since 4.0
  	 **/
  	protected function widget_export($id = ""){  		  		
			$html='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
				if(!empty($id) && $this->config["tracking"]=="1"){
					$html.='<h3>'.e("Export URL Statistics").'</h3>';
					$html.='<p>'.e("You can export visit data as CSV. Simply click the following button to create it.").'</p>';			
					$html.="<a class='btn btn-block btn-primary' href='".Main::href("user/export/$id").Main::nonce("export_url-$id")."' rel='nofollow' title='".e("Export Data")."'>".e("Export Data")."</a>";					
				}else{
					$html.='<h3>'.e("Export URLs").'</h3>';
					$html.='<p>'.e("You can export your URLs along with a summary of the stats as CSV. Simply click the following button to create it.").'</p>';			
					$html.="<a class='btn btn-block btn-primary' href='".Main::href("user/export").Main::nonce("export_url")."' rel='nofollow' title='".e("Export URLs")."'>".e("Export URLs")."</a>";							
				}
			$html.='</div>';
			return $html;
  	}  	
		/**
  	 * Social Count
  	 * @since 4.2
  	 **/
  	protected function widget_social_count(){
  		if(empty($this->config["facebook"]) && empty($this->config["twitter"])) return FALSE;
			$html='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
				$html.='<h3>'.e("We are social").'</h3>';	
				if($this->config["facebook"]){
					$html.="<p><em>".Main::facebook_likes($this->config["facebook"])."</em> Facebook ".e("Likes")."</p>";
					$html.="<a href='{$this->config["facebook"]}' target='blank' class='btn-block btn btn-facebook'>".e("Like us on")." Facebook</a>";
				}
				if($this->config["twitter"]){
					$html.="<a href='{$this->config["twitter"]}' target='blank' class='btn-block btn btn-twitter'>".e("Follow us on")." Twitter</a>";
				}
			$html.='</div>';
			return $html;
  	}   	  	
	/**
	 * Counts
	 * @since 4.0
	 **/  	
  protected function count($count,$option=""){
		$system=array("urls","users","clicks","user_urls","user_bundles","user_clicks","user_bundle_urls","user_public_urls","user_public_bundles","user_public_bundle_urls");
		$fn = "count_$count";
		if(in_array($count, $system) && method_exists("App",$fn)){
			return $this->$fn($option);
		}
		return FALSE;
  }
  		/**
  		 * Count URLs
  		 * @since 4.0
  		 **/
  		protected function count_urls(){
  			return $this->db->count("url");
  		}
			/**
  		 * Count Users
  		 * @since 4.0
  		 **/
  		protected function count_users(){
  			return $this->db->count("user");
  		}
  		/**
  		 * Count Clicks
  		 * @since 4.0
  		 **/
  		protected function count_clicks(){
  			return $this->db->count("url","","click");
  		}
  		/**
  		 * Count User URLs
  		 * @since 4.0
  		 **/
  		protected function count_user_urls(){
  			return $this->db->count("url","userid='{$this->userid}'");
  		}		
  		/**
  		 * Count User Clicks
  		 * @since 4.0
  		 **/
  		protected function count_user_clicks(){
  			return $this->db->count("url","userid='{$this->userid}'","click");
  		}
  		/**
  		 * Count Bundles URLs
  		 * @since 4.0
  		 **/
  		protected function count_user_bundles(){
  			return $this->db->count("bundle","userid='{$this->userid}'");
  		}
  		/**
  		 * Count Bundle URLs
  		 * @since 4.0
  		 **/
  		protected function count_user_bundle_urls($id){
  			if(!is_numeric($id) || $id == 0) return 0;
  			return $this->db->count("url","bundle='$id'");
  		}   
  		/**
  		 * Count Public URLs
  		 * @since 4.0
  		 **/  		
  		protected function count_user_public_urls($id){
  			return $this->db->count("url","userid='$id' AND public='1'");
  		}
  		/**
  		 * Count Public URLs
  		 * @since 4.0
  		 **/  		
  		protected function count_user_public_bundle_urls($id){
  			return $this->db->count("url","public='1' AND bundle='$id'");
  		}  		
  		/**
  		 * Count Public Bundles
  		 * @since 4.0
  		 **/  		
  		protected function count_user_public_bundles($id){
  			return $this->db->count("bundle","userid='$id' AND access='public'");
  		}  
  /**
   * [jShortener description]
   * @author KBRmedia <http://gempixel.com>
   * @version 5.0
   * @return  [type] [description]
   */
  protected function jShortener(){
  	header("Content-type: text/javascript");
    ob_start("compress");
  	$js = file_get_contents(ROOT."/static/jShortener.js");
  	$js = str_replace("__URL__", $this->config["url"], $js);

  	echo $js;
		ob_end_flush();  	
  }
	/**
	 * Display advertisement
	 * @since 4.0	 
	 */	
	public function ads($size,$text=TRUE,$breadcrumb=""){
		if($this->pro()) return FALSE;
		if($this->logged() && !$this->user->ads) return FALSE;		
		
		if(in_array($size, array("728","468","300"))){
			if($this->config["ads"]){
				return "<div class='ads ad_$size clearfix'>".($text?"<p class='text'><small class='pull-left'>".e('Advertisment')."</small><a href='{$this->config["url"]}/upgrade' class='pull-right'><small>(".e("Remove Ads").")</small></a></p>":"")."{$this->config["ad$size"]}</div>";				
			}
		}
		return;		
	}
	/**
	 * Filter
	 * @since 4.0 
	 **/
	protected function filter($filter=null){
		if(is_null($filter)){
			if(!empty($this->do) || !empty($this->id)) die($this->_404());
		}else{
			if(!empty($filter)) die($this->_404());
		}
	}
	/**
	 * Validate multiple domain names
	 * @since 4.1
	 */	
	protected function validate_domain_names($domain,$return=TRUE){
		if($this->config["multiple_domains"]){
			$domains=explode("\n", $this->config["domain_names"]);
			$domains=array_map("rtrim", $domains);
			$domains[]=$this->config["url"];
			if(in_array($domain, $domains)) {
		  	if($return) return $domain;
		  	return TRUE;
		  }
		}
		return FALSE;
	}
	/**
	 * Page replace function
	 */	
	protected function page_replace($text){
	  $text=str_replace("{URL}",$this->config["url"],$text);
	  if($this->config["ads"]){
	    $text=str_replace("{AD728}",$this->ads('728'),$text);
	    $text=str_replace("{AD468}",$this->ads('468'),$text);
	    $text=str_replace("{AD300}",$this->ads('ad300'),$text);
	  }else{
	    $text=str_replace("{AD728}","",$text);
	    $text=str_replace("{AD468}","",$text);
	    $text=str_replace("{AD300}","",$text);
	  }
	  return $text;
	} 		
	/**
	 * Get Template
	 * @since 4.0
	 **/
	protected function t($template){
    if(!file_exists(TEMPLATE."/$template.php")) die("<p class='alert alert-danger'>File ($template.php) is missing in the theme folder.</p>");
    return TEMPLATE."/$template.php";
 	}	
 	/**
 	 * 	Device
 	 * @author KBRmedia <http://gempixel.com>
 	 * @version 4.3
 	 * @return  [type] [description]
 	 */
  public static function device() { 
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform    =   "Unknown OS";
    $os_array       =   array(
                            '/iphone/i'             =>  'iphone',
                            '/ipad/i'               =>  'ipad',
                            '/android/i'            =>  'android',
                            '/blackberry/i'         =>  'blackberry',
                            '/webos/i'              =>  'webos'
                        );
    foreach ($os_array as $regex => $value) { 
        if (preg_match($regex, $user_agent)) {
            $os_platform    =   $value;
        }
    }   
    return $os_platform;
  } 	
	/**
	 * Get country from IP now with GeoIP
	 * @since 5.0
	 */	
	public function country($ip=NULL, $api=''){
		if(is_null($ip)) $ip = Main::ip();
		// Get it from database first
		require_once ROOT."/includes/library/geoip/autoload.php";
			$reader = new MaxMind\Db\Reader(ROOT."/includes/library/geoip/GeoIP.mmdb");
			$country = $reader->get($ip);
			$reader->close();
		return strtolower($country["country"]["names"]["en"]);			
	}
/**
 * Languages
 * @since 4.0
 **/	
  private function lang($form=TRUE){
		if($form){
			$lang="<option value='en'".(($this->lang=="" || $this->lang=="en")?"selected":"").">English</option>";
		}else{
			$lang="<a href='?lang=en'>English</a>";
		}
    foreach (new RecursiveDirectoryIterator(ROOT."/includes/languages/") as $path){
      if(!$path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && $path->getFilename()!=="lang_sample.php" && $path->getFilename()!=="index.php" && Main::extension($path->getFilename())==".php"){  
          $data=token_get_all(file_get_contents($path));
          $data=$data[1][1];
          if(preg_match("~Language:\s(.*)~", $data,$name)){
            $name="".strip_tags(trim($name[1]))."";
          }                  
        $code=str_replace(".php", "" , $path->getFilename());
        if($form){
					$lang.="<option value='".$code."'".($this->lang==$code?"selected":"").">$name</option>";
        }else{
					$lang.="<a href='?lang=$code'>$name</a>";	
        }
      }
    }  
    return $lang;	
  }	  
  /**
   * Theme URL
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function theme($path){
  	return "{$this->config["url"]}/themes/{$this->config["theme"]}/$path";
  }
}