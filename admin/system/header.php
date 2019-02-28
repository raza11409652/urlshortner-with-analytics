<?php if(!defined("APP")) die()?>
<!DOCTYPE html>
<html lang="en">
  <head>    
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0" />  
    <meta name="description" content="<?php echo Main::description() ?>" />
    
    
    <title><?php echo Main::title() ?></title>
    <!-- Bootstrap core CSS -->
    <link href="<?php echo $this->config["url"] ?>/static/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->url ?>/static/style.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->config["url"] ?>/static/css/components.min.css">

    <!-- Javascript Files -->
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/jquery.min.js?v=1.11.0"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/chosen.min.js?v=0.8.5"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/application.fn.js"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/bootstrap.min.js"></script>    
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/jvector.js"></script>
    <script type="text/javascript" src="<?php echo $this->config["url"] ?>/static/js/jvector.world.js"></script>
    <script type="text/javascript">
      var appurl="<?php echo $this->url ?>";
    </script>
    <script type="text/javascript" src="<?php echo $this->url ?>/static/dashboard.js"></script>
    <?php Main::admin_enqueue() ?>    
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body id="main">
    <a href="#main" id="back-to-top">Back to top</a>
    <div class="navbar" role="navigation">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-2">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="glyphicon glyphicon-align-justify"></span>
              </button>
              <a class="navbar-brand" href="<?php echo $this->url ?>"><?php echo $this->config["title"] ?></a>
            </div>            
          </div>
          <div class="navbar-collapse collapse">         
            <form class="navbar-form navbar-left search" action="<?php echo Main::ahref("search") ?>">
              <input type="text" class="form-control" size="80" placeholder="Search for users, urls or payments and press enter." name="q">
            </form>             
            <ul class="nav navbar-nav navbar-right">
              <li><a href="<?php echo $this->config["url"] ?>" target="_blank"><span class="glyphicon glyphicon-globe"></span> View Site</a></li>
              <li><a href="<?php echo Main::href("user/logout") ?>"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
            </ul>           
          </div>        
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row">
        <div class="col-md-2 sidebar">
          <ul class="nav nav-sidebar">
            <li class="active"><a href="<?php echo $this->url ?>/"><span class="glyphicon glyphicon-dashboard"></span> Dashboard</a></li>
            <li><a href="<?php echo Main::ahref("urls") ?>"><span class="glyphicon glyphicon-link"></span> URLs</a></li>
            <li><a href="<?php echo Main::ahref("users") ?>"><span class="glyphicon glyphicon-user"></span> Users</a></li>
            <li><a href="<?php echo Main::ahref("payments") ?>"><span class="glyphicon glyphicon-usd"></span> Payments</a></li>
            <li><a href="<?php echo Main::ahref("subscription") ?>"><span class="glyphicon glyphicon-refresh"></span> Subscriptions</a></li>
            <li><a href="<?php echo Main::ahref("pages") ?>"><span class="glyphicon glyphicon-book"></span> Pages</a></li>
            <li><a href="<?php echo Main::ahref("themes") ?>"><span class="glyphicon glyphicon-eye-open"></span> Themes</a></li>
            <li><a href="<?php echo Main::ahref("languages") ?>"><span class="glyphicon glyphicon-globe"></span> Languages</a></li>
            <li><a href="<?php echo Main::ahref("settings") ?>"><span class="glyphicon glyphicon-cog"></span> Settings</a></li> 
            <li><a href="<?php echo Main::ahref("settings/optimize") ?>"><span class="glyphicon glyphicon-refresh"></span> Optimize Database</a></li>
          </ul>
        </div>
        <div class="col-md-10 main">
          <?php echo Main::message() ?>