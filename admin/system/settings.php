<?php if(!defined("APP")) die()?>
<?php echo $this->update_notification() ?>
<div class="panel panel-default">
  <div class="panel-heading">
    Application Settings
  </div>      
  <div class="panel-body settings">
  	<div class="row">
  		<div class="col-md-3 sub-sidebar">
        <ul class="nav tabs">
          <li class="active"><a href="#general">General Settings</a></li>
					<li><a href="#app">Application Settings</a></li>
          <li><a href="#adv">Advanced Settings</a></li>					
          <li><a href="#themes">Theme Settings</a></li>					
					<li><a href="#security">Security Settings</a></li>
          <li><a href="#payment">Membership Settings</a></li>
          <li><a href="#user">Users Settings</a></li>
          <li><a href="#ads">Advertisement</a></li>
          <li><a href="#tools">Extra Settings</a></li>
        </ul>
  		</div>
  		<div class="col-md-9">
				<form class="form-horizontal" role="form" id="setting-form" action="<?php echo Main::ahref("settings") ?>" method="post" enctype="multipart/form-data">
					<div id="general" class="tabbed">
						<div class="form-group">
					    <label for="url" class="col-sm-3 control-label">Site URL</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="url" id="url" value="<?php echo $this->config['url'] ?>">
					      <p class="help-block">Please make sure to include http:// (or https://) and remove the last slash</p>
					    </div>
					  </div>				
						<div class="form-group">
					    <label for="title" class="col-sm-3 control-label">Site Title</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="title" id="title" value="<?php echo $this->config['title'] ?>">
					      <p class="help-block">This is your site name as well as the site meta title.</p>
					    </div>
					  </div>				
						<div class="form-group">
					    <label for="description" class="col-sm-3 control-label">Site Description</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="description" id="description" value="<?php echo $this->config['description'] ?>">
					      <p class="help-block">This your site description as well as the site meta description.</p>
					    </div>
					  </div>
						<div class="form-group">
					    <label for="keywords" class="col-sm-3 control-label">Site Keywords</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="keywords" id="keywords" value="<?php echo $this->config['keywords'] ?>">
					      <p class="help-block">This your site keywords as well as the site meta keywords (only some important keywords).</p>
					    </div>
					  </div>					  
						<div class="form-group">
					    <label for="logo" class="col-sm-3 control-label">Logo
					    	<?php if(!empty($this->config["logo"])):  ?>
					    	<span class="help-block"><a href="#" id="remove_logo" class="btn btn-info btn-xs">Remove Logo</a></span>
					    	<?php endif ?>
					    </label>
					    <div class="col-sm-9">
								<?php if(!empty($this->config["logo"])):  ?>
									<img src="<?php echo $this->config["url"] ?>/content/<?php echo $this->config["logo"] ?>" height="80" alt=""> <br />
								<?php endif ?>					    	
					      <input type="file" name="logo_path" id="logo">
					      <p class="help-block">Please make sure that the logo is of adequate size and format.</p>
					    </div>
					  </div>		
						<div class="form-group">
					    <label for="default_lang" class="col-sm-3 control-label">Default Language</label>
					    <div class="col-sm-9">
					      <select name="default_lang" id="default_lang" class="selectized">
					      	<?php echo $lang ?>
					      </select>
					      <p class="help-block">To add a new language, you may use the sample file "sample_lang.php" in includes/languages/ and then rename to a two letter code.</p>
					    </div>
					  </div>
						<div class="form-group">
					    <label for="timezone" class="col-sm-3 control-label">Timezone</label>
					    <div class="col-sm-9">
					      <select name="timezone" id="timezone" class="selectized">
									<?php
										$timezone_identifiers = DateTimeZone::listIdentifiers();
										foreach($timezone_identifiers as $tz){
										    echo "<option value='$tz' ".($this->config["timezone"] == $tz ? "selected":"").">$tz</option>";
										}
									?>		    
								</select> 
					      <p class="help-block">To add a new language, you may use the sample file "sample_lang.php" in includes/languages/ and then rename to a two letter code.</p>
					    </div>
					  </div>
						<div class="form-group">
					    <label for="font" class="col-sm-3 control-label">Google Font</label>
					    <div class="col-sm-9">
					      <input class="form-control" name="font" id="font" value="<?php echo $this->config['font'] ?>">
					      <p class="help-block">Please add the exact name of the <a href="https://www.google.com/fonts" target="_blank">Google Font</a>: e.g. <strong>Open Sans</strong>.</p>
					    </div>
					  </div>
						<div class="form-group">
					    <label for="news" class="col-sm-3 control-label">Announcement</label>
					    <div class="col-sm-9">
					      <textarea class="form-control" name="news" id="news"><?php echo $this->config['news'] ?></textarea>
					      <p class="help-block">This will be shown in the user dashboard. You can use html. Empty it to remove the announcement.</p>
					    </div>
					  </div>					  			  
						<div class="form-group">
					    <label for="email" class="col-sm-3 control-label">Email</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="email" id="email" value="<?php echo $this->config['email'] ?>">
					      <p class="help-block">This email will be used to send emails and to receive emails.</p>
					    </div>
					  </div>
					  <hr>
						<div class="form-group">
					    <label for="facebook" class="col-sm-3 control-label">Facebook Page</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="facebook" id="facebook" value="<?php echo $this->config['facebook'] ?>">
					      <p class="help-block">Link to your Facebook page e.g. http://facebook.com/gempixel</p>
					    </div>
					  </div>	
						<div class="form-group">
					    <label for="twitter" class="col-sm-3 control-label">Twitter Page</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="twitter" id="twitter" value="<?php echo $this->config['twitter'] ?>">
					      <p class="help-block">Link to your Twitter profile e.g. http://www.twitter.com/kbrmedia</p>
					    </div>
					  </div>						  					  			  		  												
					</div><!-- /#main.tabbed -->
					<div id="app" class="tabbed">
						<ul class="form_opt" data-id="maintenance">
							<li class="text-label">Site Online/Offline <small>Setting offline will make your website inaccessible for all users but admins.</small></li>
							<li><a href="" class="last<?php echo (($this->config["maintenance"])?' current':'')?>" data-value="1">Offline for Maintenance</a></li>
							<li><a href="" class="first<?php echo ((!$this->config["maintenance"])?' current':'')?>" data-value="0">Online</a></li>
						</ul>
						<input type="hidden" name="maintenance" id="maintenance" value="<?php echo $this->config["maintenance"]?>">	

						<ul class="form_opt" data-id="private">
							<li class="text-label">Private Service <small>Enabling this will prevent users from shortening and registering. Only you can create accounts.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["private"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["private"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="private" id="private" value="<?php echo $this->config["private"]?>">	

						<div class="form-group">
					    <label for="home_redir" class="col-sm-3 control-label">Home Page Redirect</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="home_redir" id="home_redir" value="<?php echo $this->config['home_redir'] ?>">
					      <p class="help-block">If you enable private mode and you want to redirect users to a custom page, add the URL above including http://.</p>
					    </div>
					  </div>	

						<ul class="form_opt" data-id="frame">
							<li class="text-label">Redirection<small>Choose the type of redirection mechanism. "None" will directly redirect while "Auto" will add an option to let the user choose for each URL.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["frame"])?' current':'')?>" data-value="0">None</a></li>
							<li><a href="" class="<?php echo (($this->config["frame"]==1)?' current':'')?>" data-value="1">Frame</a></li>
							<li><a href="" class="<?php echo (($this->config["frame"]==2)?' current':'')?>" data-value="2">Splash</a></li>
							<li><a href="" class="first<?php echo (($this->config["frame"]==3)?' current':'')?>" data-value="3">Auto</a></li>
						</ul>
						<input type="hidden" name="frame" id="frame" value="<?php echo $this->config["frame"]?>">		
						<div class="form-group">
							<div class="col-md-10">
								<label for="timer" class="control-label">Redirect Timer</label>								
								<p class="help-block">Users will be automatically redirected once the timer reaches zero. This only works on the splash page and the time should be in seconds.</p>
							</div>					    
					    <div class="col-md-2">
					      <input type="text" class="form-control" name="timer" id="timer" value="<?php echo $this->config['timer'] ?>">
					    </div>
					  </div>	

						<ul class="form_opt" data-id="show_media">
							<li class="text-label">Media Gateway <small>Enabling this will create automatically media pages for URLs such as Youtube, Vine, Dailymotion. Registered users can override this option from user settings.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["show_media"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["show_media"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="show_media" id="show_media" value="<?php echo $this->config["show_media"]?>">				

						<ul class="form_opt" data-id="geotarget">
							<li class="text-label">Geotargeting<small>Redirects user according to their country (if set by user).</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["geotarget"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["geotarget"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="geotarget" id="geotarget" value="<?php echo $this->config["geotarget"]?>">	

						<ul class="form_opt" data-id="devicetarget">
							<li class="text-label">Device targeting<small>Redirects user according to their device (if set by user).</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["devicetarget"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["devicetarget"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="devicetarget" id="devicetarget" value="<?php echo $this->config["devicetarget"]?>">	

						<ul class="form_opt" data-id="api">
							<li class="text-label">Developer API <small>Allow registered users to shorten URLs from their site using an API.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["api"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["api"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="api" id="api" value="<?php echo $this->config["api"]?>">							

						<ul class="form_opt" data-id="sharing">
							<li class="text-label">Sharing <small>Allow users to share their shorten URL through social networks such as facebook and twitter.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["sharing"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["sharing"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="sharing" id="sharing" value="<?php echo $this->config["sharing"]?>">					

						<ul class="form_opt" data-id="update_notification">
							<li class="text-label">Update Notification <small>Be notified when an update is available.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["update_notification"])?' current':'')?>" data-value="0">Disable</a></li>
							 <li><a href="" class="first<?php echo (($this->config["update_notification"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="update_notification" id="update_notification" value="<?php echo $this->config["update_notification"]?>">								
					</div><!-- /#app.tabbed -->
					<div id="adv" class="tabbed">
						<ul class="form_opt" data-id="tracking">
							<li class="text-label">Choose Advanced Tracking System <small> "System" will use built-in tracking system and "Disable" will disable advanced tracking but clicks will still be counted.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["tracking"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="<?php echo (($this->config["tracking"]==='1')?' current':'')?>" data-value="1">System</a></li>
						</ul>
						<input type="hidden" name="tracking" id="tracking" value="<?php echo $this->config["tracking"]?>">
						<div class="form-group">
					    <label for="analytic" class="col-sm-3 control-label">Google Analytics Account ID</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="analytic" id="analytic" value="<?php echo $this->config['analytic'] ?>">
					      <p class="help-block">Your Google Analytics account id e.g. UA-12345678-1. This will be used to collect data separately for your information only.</p>
					    </div>
					  </div>	
					  <hr>
						<ul class="form_opt" data-id="multiple_domains">
							<li class="text-label">Multiple Domain Names <small>If enabled users will have the choice to select their preferred domain name from the list below. Make sure that all these point to the script.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["multiple_domains"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["multiple_domains"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="multiple_domains" id="multiple_domains" value="<?php echo $this->config["multiple_domains"]?>">
						<div class="form-group">
					    <label for="domain_names" class="col-sm-3 control-label">Domains</label>
					    <div class="col-sm-9">
					      <textarea name="domain_names" id="domain_names" rows="5" class="form-control"><?php echo $this->config["domain_names"]?></textarea>	
					      <p class="help-block">One domain per line including http://, do not include your main domain name (read documentation).</p>
					    </div>
					  </div>												  
					</div><!-- /#adv.tabbed -->
					<div id="themes" class="tabbed">
						<ul class="form_opt" data-id="user_history">
							<li class="text-label">Anonymous User History <small>If enabled, anonymous users can view their personal history of URLs on the home page.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["user_history"])?' current':'')?>" data-value="0">Disable</a></li>
							 <li><a href="" class="first<?php echo (($this->config["user_history"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="user_history" id="user_history" value="<?php echo $this->config["user_history"]?>">				
						<ul class="form_opt" data-id="public_dir">
							<li class="text-label">Public URL List <small>Enabling this will display a list of new public URLs on the home page. Only the last 25 URLs will be shown there.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["public_dir"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["public_dir"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="public_dir" id="public_dir" value="<?php echo $this->config["public_dir"]?>">	
						
						<ul class="form_opt" data-id="homepage_stats">
							<li class="text-label">Show Stats on Homepage <small>Enabling this will display stats at the bottom of the homepage.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["homepage_stats"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["homepage_stats"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="homepage_stats" id="homepage_stats" value="<?php echo $this->config["homepage_stats"]?>">									
					</div>
					<div id="security" class="tabbed">

						<ul class="form_opt" data-id="adult">
							<li class="text-label">Blacklisting URLs <small>Once enabled, any url containing the keywords below (or an internal list) will not be allowed. This will also prevent links to executable files to be shortened.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["adult"])?' current':'')?>" data-value="0">Disable</a></li>	
							<li><a href="" class="first<?php echo (($this->config["adult"])?' current':'')?>" data-value="1">Enable</a></li>			
						</ul>
						<input type="hidden" name="adult" id="adult" value="<?php echo $this->config["adult"]?>">			
						<div class="form-group">												
					    <label for="keyword_blacklist" class="col-sm-3 control-label">Blacklist Keywords</label>
					    <div class="col-sm-9">
					      <textarea name="keyword_blacklist" class="form-control" rows="5"><?php echo $this->config["keyword_blacklist"] ?></textarea>
					      <p class="help-block">Each URL shortener will be matched with list of keywords below and if matched it will not allowed. Separate each keyword by a comma e.g. keyword1,keyword2</p>
					    </div>
					  </div>
						<div class="form-group">
					    <label for="domain_blacklist" class="col-sm-3 control-label">Blacklist Domains</label>
					    <div class="col-sm-9">
					      <textarea name="domain_blacklist" class="form-control" rows="5"><?php echo $this->config["domain_blacklist"] ?></textarea>
					      <p class="help-block">To blacklist domain names (or tlds), simply add them in the field below in the following format (separated by a comma): domain.com,domain2.com,domain3.com,.tld</p>
					    </div>
					  </div>				  
						<hr>
						<div class="form-group">
					    <label for="safe_browsing" class="col-sm-3 control-label">Google Safe Browsing</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="safe_browsing" id="safe_browsing" value="<?php echo $this->config['safe_browsing'] ?>">
					      <p class="help-block">You can get your API key for free from <a href="https://developers.google.com/safe-browsing" target="_blank">Google</a></p>
					    </div>
					  </div>
						<div class="form-group">
					    <label for="phish_api" class="col-sm-3 control-label">Phishtank API</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="phish_api" id="phish_api" value="<?php echo $this->config['phish_api'] ?>">
					      <p class="help-block">Note that an API key is not required however the number of requests will be limited. You can get your API key for free from <a href="https://www.phishtank.com/developer_info.php" target="_blank">here</a></p>
					    </div>
					  </div>						  	
						<hr>	
						<ul class="form_opt" data-id="captcha" data-callback="solvemedia">
							<li class="text-label">Captcha<small>Users will be prompted to answer a captcha before processing their request. If you enable any of the captcha make sure to add your keys as well.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["captcha"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="<?php echo (($this->config["captcha"]=="1")?' current':'')?>" data-value="1">reCaptcha</a></li>
							<li><a href=""class="first<?php echo (($this->config["captcha"]=="2")?' current':'')?>" data-value="2">Solvemedia</a></li>
						</ul>
						<input type="hidden" name="captcha" id="captcha" value="<?php echo $this->config["captcha"]?>">					  
						<p class="solvemedia alert alert-info" style="display: none;">
							To set up Solvemedia captcha, you must open the file includes/library/Solvemedia.php and fill in your keys where it says <strong>The solvemedia API Keys</strong>. Please note that the script will not work if you enable this and don't add your keys!
						</p>
						<div class="form-group">
					    <label for="captcha_public" class="col-sm-3 control-label">reCaptcha Public Key</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="captcha_public" id="captcha_public" value="<?php echo $this->config['captcha_public'] ?>">
					      <p class="help-block">You can get your public key for free from <a href="https://www.google.com/recaptcha" target="_blank">Google</a></p>
					    </div>
					  </div>				
						<div class="form-group">
					    <label for="captcha_private" class="col-sm-3 control-label">reCaptcha Private Key</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="captcha_private" id="captcha_private" value="<?php echo $this->config['captcha_private'] ?>">
					      <p class="help-block">You can get your private key for free from <a href="https://www.google.com/recaptcha" target="_blank">Google</a></p>
					    </div>
					  </div>										
					</div><!-- /#security.tabbed -->
					<div id="payment" class="tabbed">
						<ul class="form_opt" data-id="pro">
							<li class="text-label">Premium Module <small>Enabling this module will allow you to charge users for premium features. Disable this if you want to offer these for free.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["pro"])?' current':'')?>" data-value="0">Disable</a></li>	
							<li><a href="" class="first<?php echo (($this->config["pro"])?' current':'')?>" data-value="1">Enable</a></li>			
						</ul>
						<input type="hidden" name="pro" id="pro" value="<?php echo $this->config["pro"]?>">		
						<?php if ($this->isExtended()): ?>
							<ul class="form_opt" data-id="pt">
								<li class="text-label">Payment Processor <small>Choose between paypal or stripe. Subscription only possible with Stripe.</small></li>
								<li><a href="" class="last<?php echo (($this->config["pt"] == "stripe")?' current':'')?>" data-value="stripe">Stripe</a></li>	
								<li><a href="" class="first<?php echo (($this->config["pt"] == "paypal")?' current':'')?>" data-value="paypal">Paypal</a></li>			
							</ul>
							<input type="hidden" name="pt" id="pt" value="<?php echo $this->config["pt"]?>">															
						<?php endif ?>														
						<hr>						
						<div class="form-group">
					    <label for="paypal_email" class="col-sm-3 control-label">PayPal Email</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="paypal_email" placeholder="myemail@host.com"  id="paypal_email" value="<?php echo $this->config['paypal_email'] ?>">
					      <p class="help-block">Payments will be sent to this address. Please make sure that you enable IPN and enable notification. Your IPN URL is <strong><?php echo $this->config["url"] ?>/ipn</strong> For more info <a href="https://developer.paypal.com/webapps/developer/docs/classic/products/instant-payment-notification/" target="_blank">click here</a></p>
					    </div>
					  </div>	
					  <hr>
					  <?php if ($this->isExtended()): ?>
							<div class="form-group">
						    <label for="stpk" class="col-sm-3 control-label">Stripe Publishable Key</label>
						    <div class="col-sm-9">
						      <input type="text" class="form-control" name="stpk" id="stpk" value="<?php echo $this->config['stpk'] ?>">
						      <p class="help-block">Get your stripe keys from here once logged in <a href="https://dashboard.stripe.com/account/apikeys" target="_blank">click here</a></p>
						    </div>
						  </div>	
							<div class="form-group">
						    <label for="stsk" class="col-sm-3 control-label">Stripe Secret Key</label>
						    <div class="col-sm-9">
						      <input type="text" class="form-control" name="stsk"  id="stsk" value="<?php echo $this->config['stsk'] ?>">
						      <p class="help-block">Get your stripe keys from here once logged in <a href="https://dashboard.stripe.com/account/apikeys" target="_blank">click here</a></p>
						    </div>
						  </div>			
							<div class="form-group">
						    <label for="stripesig" class="col-sm-3 control-label">Webhook Signature Key</label>
						    <div class="col-sm-9">
						      <input type="text" class="form-control" name="stripesig" placeholder="whsec_..."  id="stripesig" value="<?php echo $this->config['stripesig'] ?>">
						      <p class="help-block">Webhook signature is a security measure to verify the authenticity of the data incoming from Stripe. It is highly recommended that you add this for safety measure. You can find your key after adding a webhook. <a href="https://dashboard.stripe.com/account/webhooks" target="_blank">Click here to find your signature key.</a></p>
						    </div>
						  </div>							  	
						  <hr>				
					  <?php endif; ?>	  	 					  
						<div class="form-group">
					    <label for="currency" class="col-sm-3 control-label">Currency</label>
					    <div class="col-sm-9">
					      <?php $currencies = Main::currency() ?>
					     <select name="currency" id="currency">
					      <?php foreach ($currencies as $code => $info): ?>
					      	<option value="<?php echo $code ?>" <?php if($this->config["currency"]==$code) echo "selected" ?>><?php echo $info["label"] ?></option>
					      <?php endforeach ?>
					      </select>
					  		<p class="help-block"><strong>Notice</strong> If you already have subscribed members, it is highly recommend you <u>do not change</u> the currency or the membership fees because Stripe does not allow modifcation of these parameters. The script will delete the plan and create another one!</p>	 					      
					    </div>
					  </div>		 			
						<div class="form-group">
					    <label for="pro_monthly" class="col-sm-3 control-label">Monthly Membership Fee</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="pro_monthly" id="pro_monthly" placeholder="e.g. 4.99" value="<?php echo $this->config['pro_monthly'] ?>">
					  		<p class="help-block"><strong>Notice</strong> If you already have subscribed members, it is highly recommend you <u>do not change</u> the currency or the membership fees because Stripe does not allow modifcation of these parameters. The script will delete the plan and create another one!</p>	 
					    </div>
					  </div>		
						<div class="form-group">
					    <label for="pro_yearly" class="col-sm-3 control-label">Yearly Membership Fee</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="pro_yearly" id="pro_yearly" placeholder="e.g. 54.99" value="<?php echo $this->config['pro_yearly'] ?>">
					  		<p class="help-block"><strong>Notice</strong> If you already have subscribed members, it is highly recommend you <u>do not change</u> the currency or the membership fees because Stripe does not allow modifcation of these parameters. The script will delete the plan and create another one!</p>	 
					    </div>
					  </div>	
					  <hr>
						<div class="form-group">
					    <label for="freeurls" class="col-sm-3 control-label">Free Users URL</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="freeurls" id="freeurls" value="<?php echo $this->config['freeurls'] ?>">
					      <p class="help-block">Number of URLs for free URLs. Set 0 for unlimited.</p>
					    </div>
					  </div>						  
						<div class="form-group">
					    <label for="aliases" class="col-sm-3 control-label">Premium Aliases</label>
					    <div class="col-sm-9">
					      <textarea name="aliases" class="form-control" rows="5"><?php echo $this->config["aliases"] ?></textarea>
					      <p class="help-block">To reserve an alias for pro members only, add it to the list above (separated by a comma without space between each): google,apple,microsoft,etc. Only admins and pro users can select these.</p>
					    </div>
					  </div>						  		  		  					
					</div><!-- /#payment.tabbed -->	
					<div id="user" class="tabbed">
						<ul class="form_opt" data-id="user_r">
							<li class="text-label">User Registration <small>Allow users to register and to bookmark their URLs. If disable registration links will be hidden.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["user"])?' current':'')?>" data-value="0">Disable</a></li>
							 <li><a href="" class="first<?php echo (($this->config["user"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="user" id="user_r" value="<?php echo $this->config["user"]?>">	

						<ul class="form_opt" data-id="user_activate">
							<li class="text-label">User Activation <small>If enabled, an email containing an activation link will be sent to the user.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["user_activate"])?' current':'')?>" data-value="0">Disable</a></li>
							 <li><a href="" class="first<?php echo (($this->config["user_activate"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="user_activate" id="user_activate" value="<?php echo $this->config["user_activate"]?>">	

						<ul class="form_opt" data-id="require_registration">
							<li class="text-label">Require Registration <small>If enabled, user will be required to create an account before being able to shorten a URL.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["require_registration"])?' current':'')?>" data-value="0">Disable</a></li>
							 <li><a href="" class="first<?php echo (($this->config["require_registration"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="require_registration" id="require_registration" value="<?php echo $this->config["require_registration"]?>">	

						<hr>
						<ul class="form_opt" data-id="fb_connect">
							<li class="text-label">Enable Facebook Connect <small>Users can login and get registered using their facebook account.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["fb_connect"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["fb_connect"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="fb_connect" id="fb_connect" value="<?php echo $this->config["fb_connect"]?>">
						<div class="form-group">
					    <label for="facebook_app_id" class="col-sm-3 control-label">Facebook App ID</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="facebook_app_id" id="facebook_app_id" value="<?php echo $this->config['facebook_app_id'] ?>">
					    </div>
					  </div>
						<div class="form-group">
					    <label for="facebook_secret" class="col-sm-3 control-label">Facebook Secret</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="facebook_secret" id="facebook_secret" value="<?php echo $this->config['facebook_secret'] ?>">
					    </div>
					  </div>					  
						<hr>
						<ul class="form_opt" data-id="tw_connect">
							<li class="text-label">Enable Twitter Connect <small>Users can login and get registered using their twitter account.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["tw_connect"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["tw_connect"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="tw_connect" id="tw_connect" value="<?php echo $this->config["tw_connect"]?>">											
						<div class="form-group">
					    <label for="twitter_key" class="col-sm-3 control-label">Twitter Public Key</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="twitter_key" id="twitter_key" value="<?php echo $this->config['twitter_key'] ?>">
					    </div>
					  </div>
						<div class="form-group">
					    <label for="twitter_secret" class="col-sm-3 control-label">Twitter Secret Key</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="twitter_secret" id="twitter_secret" value="<?php echo $this->config['twitter_secret'] ?>">
					    </div>
					  </div>
					  <hr>
						<ul class="form_opt" data-id="gl_connect">
							<li class="text-label">Enable Google Authentication <small>Users can login and get registered using their Google account. Make sure to fill the fields below!</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["gl_connect"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["gl_connect"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="gl_connect" id="gl_connect" value="<?php echo $this->config["gl_connect"]?>">

						<div class="form-group">
					    <label for="google_cid" class="col-sm-3 control-label">Google Client ID</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="google_cid" id="google_cid" value="<?php echo $this->config['google_cid'] ?>">
					    </div>
					  </div>
						<div class="form-group">
					    <label for="google_cs" class="col-sm-3 control-label">Google Client Secret</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="google_cs" id="google_cs" value="<?php echo $this->config['google_cs'] ?>">
					    </div>
					  </div>											  					
					</div><!-- /#user.tabbed -->
					<div id="ads" class="tabbed">
						<ul class="form_opt" data-id="ads">
							<li class="text-label">Advertisement <small>Enable or disable advertisement throughout the site.</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["ads"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["ads"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="ads" id="ads" value="<?php echo $this->config["ads"]?>">				

						<ul class="form_opt" data-id="detectadblock">
							<li class="text-label">Adblock Detection <small>Enable or disable adblock detection on redirection (splash and frame - does not work for pro users).</small></li>
							<li><a href="" class="last<?php echo ((!$this->config["detectadblock"])?' current':'')?>" data-value="0">Disable</a></li>
							<li><a href="" class="first<?php echo (($this->config["detectadblock"])?' current':'')?>" data-value="1">Enable</a></li>
						</ul>
						<input type="hidden" name="detectadblock" id="detectadblock" value="<?php echo $this->config["detectadblock"]?>">	

						<div class="form-group">
					    <label for="a7" class="col-sm-3 control-label">Advertisement (728x90)</label>
					    <div class="col-sm-9">
					      <textarea class="form-control" name="ad728" id="a7" rows="5"><?php echo $this->config['ad728'] ?></textarea>
					      <div class="help-block">You can display this ad in any of the theme files by using this code: <strong><?php echo htmlentities('<?php echo $this->ad(728) ?>') ?></strong></div>
					    </div>
					  </div>				
						<div class="form-group">
					    <label for="a4" class="col-sm-3 control-label">Advertisement (468x60)</label>
					    <div class="col-sm-9">
					      <textarea class="form-control" name="ad468" id="a4" rows="5"><?php echo $this->config['ad468'] ?></textarea>
					      <div class="help-block">You can display this ad in any of the theme files by using this code: <strong><?php echo htmlentities('<?php echo $this->ad(468) ?>') ?></strong></div>
					    </div>
					  </div>				
						<div class="form-group">
					    <label for="a3" class="col-sm-3 control-label">Advertisement (300x250)</label>
					    <div class="col-sm-9">
					      <textarea class="form-control" name="ad300" id="a3" rows="5"><?php echo $this->config['ad300'] ?></textarea>
					      <div class="help-block">You can display this ad in any of the theme files by using this code: <strong><?php echo htmlentities('<?php echo $this->ad(300) ?>') ?></strong></div>
					    </div>
					  </div>							
					</div><!-- /#ads.tabbed -->			
					<div id="tools" class="tabbed">
					  <div class="alert alert-info"><strong>Tip:</strong> SMTP is recommend because it is much more reliable than the system mail module.</div>
						<div class="form-group">
					    <label for="smtp" class="col-sm-3 control-label">SMTP Host</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="smtp[host]" value="<?php echo $this->config['smtp']['host'] ?>">
					    </div>
					  </div>				
						<div class="form-group">
					    <label for="smtp" class="col-sm-3 control-label">SMTP Port</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="smtp[port]" value="<?php echo $this->config['smtp']['port'] ?>">
					    </div>
					  </div>		
						<div class="form-group">
					    <label for="smtp" class="col-sm-3 control-label">SMTP User</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="smtp[user]" value="<?php echo $this->config['smtp']['user'] ?>">
					    </div>
					  </div>		
						<div class="form-group">
					    <label for="smtp" class="col-sm-3 control-label">SMTP Pass</label>
					    <div class="col-sm-9">
					      <input type="password" class="form-control" name="smtp[pass]" value="<?php echo $this->config['smtp']['pass'] ?>">
					    </div>
					  </div>										 
					</div><!-- /#tools.tabbed -->

				  <div class="form-group">
				    <div class="col-sm-12">
				    	<?php echo Main::csrf_token(TRUE) ?>
				    	<br>
				      <button type="submit" class="btn btn-primary">Save Settings</button>
				    </div>
				  </div>
				</form>  			
  		</div>
  	</div>
  </div>
</div>