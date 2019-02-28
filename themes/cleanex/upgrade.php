<?php defined("APP") or die() ?>
<section id="plan">
  <div class="container">
    <?php echo Main::message() ?>
    <div class="row">
      <div class="col-md-4">
        <h1><?php echo e("Choose your premium plan") ?></h1>
        <br>
        <div class="toggle-container cf">
          <div class="switch-toggles">
            <div class="individual"><?php echo e("Monthly") ?></div>
            <div class="company"><?php echo e("Yearly") ?></div>
          </div>
        </div>
        <?php if ($discount): ?>
          <div class="faq-list clearfix">
            <h2><i class="glyphicon glyphicon-gift"></i> <?php echo e("If I pay yearly, do I get a discount?") ?></h2>
            <p class="info"><?php echo e("Definitely! If you choose to pay yearly, not only will you make great use of premium features but also you will get a discount of up to $discountAmount%.") ?></p>
          </div>                  
        <?php endif ?>      
        <div class="faq-list clearfix">
          <h2><i class="glyphicon glyphicon-flash"></i> <?php echo e("Can I upgrade my account at any time?") ?></h2>
          <p class="info"><?php echo e("Yes! You can start with our free package and upgrade anytime to enjoy premium features.") ?></p>
        </div>
        <?php if (isset($this->config["pt"]) && $this->config["pt"] == "stripe"): ?>
          <div class="faq-list clearfix">
            <h2><i class="glyphicon glyphicon-credit-card"></i> <?php echo e("How will I be charged?") ?></h2>
            <p class="info"><?php echo e("You will be charged at the beginning of each period automatically until canceled.") ?></p>
          </div>           
        <?php else: ?>
          <div class="faq-list clearfix">
            <h2><i class="glyphicon glyphicon-credit-card"></i> <?php echo e("How will I be charged?") ?></h2>
            <p class="info"><?php echo e("You will be reminded to renew your membership 7 days before your expiration.") ?></p>
          </div>          
        <?php endif ?>
        <?php if (isset($this->config["pt"]) && $this->config["pt"] == "stripe"): ?>
          <div class="faq-list clearfix">
            <h2><i class="glyphicon glyphicon-log-in"></i> <?php echo e("How do refunds work?") ?></h2>
            <p class="info">
              <?php echo e("Upon request, we will issue a refund at the moment of the request for all <strong>upcoming</strong> periods. If you are on a monthly plan, we will stop charging you at the end of your current billing period. If you are on yearly plan, we will refund amounts for the remaining months.") ?>            
            </p>
          </div>          
        <?php else: ?>
        <div class="faq-list clearfix">
          <h2><i class="glyphicon glyphicon-log-in"></i> <?php echo e("How do refunds work?") ?></h2>
          <p class="info">
            <?php echo e("Upon request, we will issue a refund at the moment of the request for all <strong>upcoming</strong> periods. You will just need to contact us and we will take of everything.") ?>            
          </p>
        </div>       
        <?php endif ?>                              
      </div>
      <div id="price_tables" class="col-md-7 col-md-offset-1">
        <div class="individual cf">
          <div class="price-table">
            <div class="table-inner text-center">
              <h3><?php echo e("Starter") ?></h3>
              <span class="price"><?php echo e("Free") ?></span>
              <ul class="feature-list">
                <li><?php echo e("Basic Features") ?></li>
                <li><?php echo e("Basic Redirection Filters") ?></li>
                <?php if($this->config["freeurls"] > 0): ?>
                  <li><?php echo $this->config["freeurls"] ?> <?php echo e("URLs allowed") ?></li>
                <?php endif; ?>                
                <li><?php echo e("Limited URL Customization") ?></li>                
                <li><?php echo e("Advertisement") ?></li>          
                <li><?php echo e("Limited Support") ?></li>
                <li>&nbsp;</li>
              </ul>
              <?php if($this->logged()): ?>
                <?php if (!$this->pro()): ?>
                  <a class="btn btn-primary btn-round"><?php echo e("Current Plan") ?></a> 
                <?php endif ?>
              <?php else: ?>
                <a href="<?php echo Main::href("user/register") ?>" class="btn btn-secondary btn-round"><?php echo e("Get Started") ?></a> 
              <?php endif ?>               
            </div>
          </div>
          <div class="price-table highlighted">
            <div class="table-inner text-center">
              <h3><?php echo e("Professional") ?></h3>
              <span class="price"><?php echo Main::currency($this->config["currency"],$this->config["pro_monthly"]) ?></strong></span>
              <ul class="feature-list">
                <li><?php echo e("Premium Features") ?></li>
                <li><?php echo e("Custom Splash Pages"); ?></li>
                <li><?php echo e("Custom Overlay Pages"); ?></li>
                <li><?php echo e("Event Tracking"); ?></li>
                <?php if($this->config["freeurls"] > 0): ?>
                  <li><?php echo e("Unlimited URLs") ?></li>
                <?php endif; ?>                  
                <li><?php echo e("URL Customization") ?></li>                      
                <li><?php echo e("Export Data") ?></li>                
                <li><?php echo e("No Advertisements") ?></li>
                <li><?php echo e("Prioritized Support") ?></li>
              </ul>
              <?php if(!$this->pro()): ?>
                <a href="<?php echo Main::href("upgrade/monthly") ?>" class="btn btn-secondary btn-round"><?php echo e("Go Pro") ?></a>     
              <?php endif ?>                         
            </div>
          </div>
        </div>

        <div class="company cf">
          <div class="price-table">
            <div class="table-inner text-center">
              <h3><?php echo e("Starter") ?></h3>
              <span class="price"><?php echo e("Free") ?></span>
              <ul class="feature-list">
                <li><?php echo e("Basic Features") ?></li>
                <li><?php echo e("Basic Redirection Filters") ?></li>
                <?php if($this->config["freeurls"] > 0): ?>
                  <li><?php echo $this->config["freeurls"] ?> <?php echo e("URLs allowed") ?></li>
                <?php endif; ?>
                <li><?php echo e("Limited URL Customization") ?></li>
                <li><?php echo e("Advertisement") ?></li>          
                <li><?php echo e("Limited Support") ?></li>
                <li>&nbsp;</li>
              </ul>
              <?php if($this->logged()): ?>
                <?php if (!$this->pro()): ?>
                  <a class="btn btn-primary btn-round"><?php echo e("Current Plan") ?></a> 
                <?php endif ?>
              <?php else: ?>
                <a href="<?php echo Main::href("user/register") ?>" class="btn btn-secondary btn-round"><?php echo e("Get Started") ?></a> 
              <?php endif ?>               
            </div>
          </div>
          <div class="price-table highlighted">
            <?php echo $discount ?>
            <div class="table-inner text-center">
              <h3><?php echo e("Professional") ?></h3>
              <span class="price"><?php echo Main::currency($this->config["currency"],$this->config["pro_yearly"]) ?></strong></span>
              <ul class="feature-list">
                <li><?php echo e("Premium Features") ?></li>
                <li><?php echo e("Custom Splash Pages"); ?></li>
                <li><?php echo e("Custom Overlay Pages"); ?></li>
                <li><?php echo e("Event Tracking"); ?></li>
                <?php if($this->config["freeurls"] > 0): ?>
                  <li><?php echo e("Unlimited URLs") ?></li>
                <?php endif; ?>                
                <li><?php echo e("Limited URL Customization") ?></li>
                <li><?php echo e("Export Data") ?></li>                
                <li><?php echo e("No Advertisements") ?></li>
                <li><?php echo e("Prioritized Support") ?></li>
              </ul>
              <?php if(!$this->pro()): ?>
                <a href="<?php echo Main::href("upgrade/yearly") ?>" class="btn btn-secondary btn-round"><?php echo e("Go Pro") ?></a>     
              <?php endif ?>                
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<hr>
<section>
  <div class="container">
    <div class="feature">
      <div class="row">
        <div class="col-sm-7 rand image">
          <div class="rand1"> <i class="glyphicon glyphicon-link"></i> <h3><?php echo e("Link Controls") ?></h3></div>
          <div class="rand2"> <i class="glyphicon glyphicon-lock"></i> <h3><?php echo e("Privacy Control") ?></h3></div>
          <div class="rand3"> <i class="glyphicon glyphicon-briefcase"></i> <h3><?php echo e("Link Management") ?></h3></div>
          <div class="rand4"> <i class="glyphicon glyphicon-dashboard"></i> <h3><?php echo e("Powerful Dashboard") ?></h3></div>
          <div class="rand5"> <i class="glyphicon glyphicon-star"></i> <h3><?php echo e("Premium Features") ?></h3></div>
          <div class="rand6"> <i class="glyphicon glyphicon-stats"></i> <h3><?php echo e("Statistics") ?></h3></div>
        </div>
        <div class="col-sm-5 info">
          <h2>
            <i class="glyphicon glyphicon-tasks"></i>
            <small><?php echo e("Control on each and everything.") ?></small>
            <?php echo e("Complete control on your links") ?>
          </h2>
          <p>
            <?php echo e("With our premium membership, you will have complete control on your links. This means you can change the destination anytime you want. Add, change or remove any filters, anytime.") ?>
          </p>
        </div>  
      </div>    
    </div>      
  </div>
</section>
<hr>
<section>
  <div class="container">
    <div class="featurette">
      <h3 class="text-center featureH"><?php echo e("Premium Features. All Yours.") ?></h3>
      <div class="row">
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-globe"></i>
          <h3><?php echo e("Target Customers") ?></h3>
          <p><?php echo e("Target your users based on their location and device and redirect them to specialized pages to increase your conversion.") ?></p>
        </div>    
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-star"></i>
          <h3><?php echo e("Custom Landing Page") ?></h3>
          <p><?php echo e("Create a custom landing page to promote your product or service on forefront and engage the user in your marketing campaign.") ?></p>
        </div>      
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-asterisk"></i>
          <h3><?php echo e("Overlays") ?></h3>
          <p><?php echo e("Use our overlay tool to display unobtrusive notifications on the target website. A perfect way to send a message to your customers or run a promotion campaign.") ?></p>
        </div>
      </div>    
      <br> 
      <div class="row">
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-th"></i>
          <h3><?php echo e("Event Tracking") ?></h3>
          <p><?php echo e("Add your custom pixel from providers such as Facebook and track events right when they are happening.") ?></p>
        </div>        
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-glass"></i>
          <h3><?php echo e("Premium Aliases") ?></h3>
          <p><?php echo e("As a premium membership, you will be able to choose a premium alias for your links from our list of reserved aliases.") ?></p>
        </div>     
        <div class="col-sm-4">
          <i class="glyphicon glyphicon-cloud"></i>
          <h3><?php echo e("Robust API") ?></h3>
          <p><?php echo e("Use our powerful API to build custom applications or extend your own application with our powerful tools.") ?></p>
        </div>         
      </div>
    </div>    
  </div>       
</section>