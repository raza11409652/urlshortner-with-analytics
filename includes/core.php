<?php
/**
 * ====================================================================================
 *                           Premium URL Shortener (c) KBRmedia
 * ----------------------------------------------------------------------------------
 *  LICENSE: This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in illegal activity. You must delete this software immediately or buy a proper
 *  license from http://codecanyon.net/user/KBRmedia/portfolio?ref=KBRmedia.
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @package Premium_URL_Shortener
 * @subpackage App_Request_Handler 
 * @author KBRmedia (http://gempixel.com)
 * @copyright 2014 KBRmedia
 * @license http://gempixel.com/license
 * @link http://gempixel.com  
 * @since v4.2.2
 */
  // Defined Constants
	define("_VERSION","5.0");
	define("APP", 1);

	define("ROOT",dirname(dirname(__FILE__)));
	
	define("STRIPE",ROOT."/includes/library/Stripe.load.php");

	// Compress Page
	if($config["gzip"]){
	  ob_start("ob_gzhandler"); 
	}
	// Starts a session
	if(!isset($_SESSION)){
	  session_start();
	}
	// Error Reporting
	if(!isset($config["debug"]) || $config["debug"]==0) {
	  error_reporting(0);
	}else{
		ini_set('error_reporting', E_ALL);		
	  error_reporting(1);
	}

	// If Magic Quotes is ON then Remove Slashes
	if(get_magic_quotes_gpc()) {
	  if($_GET) $_GET = array_map('stripslashes', $_GET);  
	  if($_POST) $_POST = array_map('stripslashes', $_POST);  
	  if($_COOKIE) $_COOKIE = array_map('stripslashes', $_COOKIE);
	} 

	if(!isset($config["secret_key"]) || $config["secret_key"] == "RKEY"){
	  $config["secret_key"] = "";
	}

	if(!empty($config["timezone"])){
		date_default_timezone_set($config["timezone"]);
	}
	// Connect to database
	include(ROOT."/includes/Database.class.php");	
	$db = new Database($config, $dbinfo);
	$config=$db->get_config();	
	$config["smtp"]=json_decode($config["smtp"],TRUE);
	$config["prefix"] = $dbinfo["prefix"];
	
	// Defines Template
	define("TEMPLATE",ROOT."/themes/{$config["theme"]}");

	// phpFastCache
	if($config["cache"]){
		include(ROOT."/includes/library/Cache.class.php");
		phpFastCache::$storage = "auto";		
	}

	// Application Helper
	include(ROOT."/includes/Main.class.php");
	Main::set("config",$config);

  	// Start Application		
	include(ROOT."/includes/App.class.php");
		$app = new App($db,$config);	
	
	// Default Language
	$_language=$config["default_lang"];
	// Set Language from Cookie
	if(isset($_COOKIE["lang"])) $_language=Main::clean($_COOKIE["lang"],3,TRUE);	
	// Set Language
	if(isset($_GET["lang"]) && strlen($_GET["lang"])=="2"){
		setcookie("lang",strip_tags($_GET["lang"]), strtotime('+30 days'), '/', NULL, 0);
		$_language = Main::clean($_GET["lang"],3,TRUE);
	}		
	// Get Language File
	if(isset($_language) && $_language!="en" && file_exists(ROOT."/includes/languages/".Main::clean($_language,3,TRUE).".php")) {
  	include(ROOT."/includes/languages/".Main::clean($_language).".php");
  	if(isset($lang) && is_array($lang)) {
  		Main::set("lang",$lang);
  		$app->lang = $_language;
  	}
	}
	// Read string function
	function e($text){
		return Main::e($text);
	}
  function compress($buffer) {      
      $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
      return $buffer;
  }