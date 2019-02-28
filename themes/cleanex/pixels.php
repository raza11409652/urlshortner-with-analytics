<?php defined("APP") or die() // Settings Page ?>
<div class="row">	
  <div id="user-content" class="col-md-8">  	
  	<?php echo $this->ads(728) ?>
		<?php echo Main::message() ?>  			
		<div class="main-content panel panel-default panel-body">
			<h3><?php echo e("Tracking Pixels") ?></h3>

			<form action="<?php echo Main::href("user/pixels") ?>" role="form" class="form-horizontal" method="post">
        <div class="form-group">
					<label class="col-sm-3 control-label"><?php echo e("Facebook Pixel")?></label>			
					<div class="col-sm-9">
						<input type="text" value="<?php echo $this->user->fbpixel ?>" name="fbpixel" class="form-control" placeholder="e.g. 1234567890123456" />
					</div>
        </div>
				<hr>
        <div class="form-group">
					<label class="col-sm-3 control-label"><?php echo e("Google Adwords Pixel")?></label>			
					<div class="col-sm-9">
						<input type="text" value="<?php echo $this->user->adwordspixel ?>" name="adwordspixel" class="form-control" placeholder="e.g. AW-12345678901/ABCDEFGHIJKLMOPQRST" />
					</div>
        </div>	
        <hr>
        <div class="form-group">
					<label class="col-sm-3 control-label"><?php echo e("LinkedIn Insight Tag")?></label>			
					<div class="col-sm-9">
						<input type="text" value="<?php echo $this->user->linkedinpixel ?>" name="linkedinpixel" class="form-control" placeholder="e.g. 123456" />
					</div>
        </div>	        			
				<?php echo Main::csrf_token(TRUE) ?>
				<p><button type="submit" class="btn btn-primary"><?php echo e("Save pixels")?></button></p>
			</form>
		</div>	
  </div><!--/#user-content-->
  <div id="widgets" class="col-md-4">
  	<?php echo $this->sidebar() ?>
		<div class="panel panel-default panel-body">
			<h3><?php echo e("What are tracking pixels?") ?></h3>
			<p><?php echo e("Ad platforms such as Facebook and Adwords provide a conversion tracking tool to allow you to gather data on your customers and how they behave on your website. By adding your pixel ID from either of the platforms, you will be able to optimize marketing simply by using short URLs.") ?></p>
		</div>
		<div class="panel panel-default panel-body">
			<h3><?php echo e("Facebook Pixel") ?></h3>
			<p><?php echo e("Facebook pixel makes conversion tracking, optimization and remarketing easier than ever. The Facebook pixel ID is usually composed of 16 digits. Please make sure to add the correct value otherwise events will not be tracked!") ?> </p>
			<p><code>e.g. 1234567890123456</code></p>
			<a href="https://www.facebook.com/business/a/facebook-pixel" target="_blank" class="btn btn-primary btn-xs"><?php echo e("Learn more") ?></a>
		</div>		
		<div class="panel panel-default panel-body">
			<h3><?php echo e("Google Adwords Conversion Pixel") ?></h3>
			<p><?php echo e("With AdWords conversion tracking, you can see how effectively your ad clicks lead to valuable customer activity. The Adwords pixel ID is usually composed of AW followed by 11 digits followed by 19 mixed characters. Please make sure to add the correct value otherwise events will not be tracked!") ?></p>
			<p><code>e.g. AW-12345678901/ABCDEFGHIJKLMOPQRST</code></p>
			<a href="https://support.google.com/adwords/answer/1722054?hl=en" target="_blank" class="btn btn-primary btn-xs"><?php echo e("Learn more") ?></a>
		</div>	
		<div class="panel panel-default panel-body">
			<h3><?php echo e("LinkedIn Insight Tag") ?></h3>
			<p><?php echo e("The LinkedIn Insight Tag is a piece of lightweight JavaScript code that you can add to your website to enable in-depth campaign reporting and unlock valuable insights about your website visitors.You can use the LinkedIn Insight Tag to track conversions, retarget website visitors, and unlock additional insights about members interacting with your ads.!") ?></p>
			<p><code>e.g. 123456</code></p>
			<a href="https://www.linkedin.com/help/linkedin/answer/65521" target="_blank" class="btn btn-primary btn-xs"><?php echo e("Learn more") ?></a>
		</div>				
  </div><!--/#widgets-->
</div><!--/.row-->