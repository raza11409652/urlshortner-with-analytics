<?php 
/**
 * ====================================================================================
 *                           Premium URL Shortener (c) KBRmedia
 * ----------------------------------------------------------------------------------
 *  @copyright - This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in illegal activity. You must delete this software immediately or buy a proper
 *  license from http://codecanyon.net/user/KBRmedia/portfolio?ref=KBRmedia.
 *
 *	@license http://gempixel.com/license
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author KBRmedia (http://gempixel.com)
 * @link http://gempixel.com
 * @package Premium URL Shortener
 * @subpackage Admin Class (Admin.class.php)
 */

class Admin{
  /**
   * [$sandbox description]
   * @var boolean
   */
  protected $sandbox = FALSE;
  /**
   * Authorized actions
   * @since 5.0
   **/
  protected $actions = ["users","urls","payments","pages","settings","themes","languages","help","search","subscription"];
  /**
   * Config
   * @since 4.0
   **/
  protected $config;
  /**
   *  DB
   * @since 4.0
   **/
  protected $db;
  /**
   * Admin Info
   * @since 4.0
   **/
  protected $user;
  /**
   * Admin URL
   * @since 4.0
   **/
  protected $url;
  /**
   *  Current Page 
   * @since 4.0
   **/
  protected $page;
  /**
   * Reserved Variable
   * @since 4.0
   **/
  protected $action;
  /**
   * Reserved Variable
   * @since 4.0
   **/  
  protected $do;
  /**
   * Reserved Variable
   * @since 4.0
   **/
  protected $id;
  /**
   * Admin Limit/Page
   * @since 4.0
   **/
  protected $limit = 20;

  /**
   * Construct Admin
   * @since 4.0
   **/
  public function __construct($config,$db){
    $this->config=$config;
    $this->db=$db;     
    $this->db->object=TRUE;
    $this->url="{$this->config["url"]}/admin";
    $this->page=(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"]!="0")?Main::clean($_GET["page"],3,TRUE):"1";  
    $this->check();
  }
  /**
   * Free Memory (don't needed but do it anyway)
   * @since 4.0
   **/
  public function __destruct(){
    unset($this->db,$this->user,$this->config);
  }
  /**
   * Check if user is logged and has admin privileges!
   * @since 4.0
   **/
  public function check(){
    if($info=Main::user()){
      if($user=$this->db->get("user",array("id"=>"?","auth_key"=>"?"),array("limit"=>1),array($info[0],$info[1]))){        
        if(!$user->admin) return Main::redirect("404");
        $this->logged=TRUE;
        $this->user=$user;     
        $user=NULL;
        // Unset sensitive information
        unset($this->user->password);
        unset($this->user->auth_key);          
        return TRUE;
      }
    }
    return Main::redirect("404");
  }  
  /**
   * Run Admin Panel
   * @since 4.0
   **/
  public function run(){
    if(isset($_GET["a"]) && !empty($_GET["a"])){
      $var=explode("/",$_GET["a"]);
      if(in_array($var[0],$this->actions) && method_exists("Admin", $var[0])){
        $this->action=Main::clean($var[0],3,TRUE);
        if(isset($var[1]) && !empty($var[1])) $this->do=Main::clean($var[1],3,TRUE);
        if(isset($var[2]) && !empty($var[2])) $this->id=Main::clean($var[2],3,TRUE);
        return $this->{$var[0]}();
      } 
      return Main::redirect("admin",array("danger","Oups! The page you are looking for doesn't exist."));
    }else{
      return $this->home();
    }
  }  
  /**
   * [isExtended description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @return  boolean [description]
   */
  protected function isExtended(){
    if(isset($this->config["stsk"]) && isset($this->config["stpk"]) && isset($this->config["pt"])) return TRUE;
    return FALSE;
  }
  /**
   * Admin Home Page
   * @since 4.0
   **/
  protected function home(){
    // Chart Data
    $urls=$this->db->get("url","",array("limit"=>8,"order"=>"date"));
    $top_urls=$this->db->get("url","",array("limit"=>8,"order"=>"click"));
    $users=$this->db->get("user","",array("limit"=>8,"order"=>"date"));
    $payments=$this->db->get("payment","",array("limit"=>8,"order"=>"date"));

    $this->charts();
    $topcountries=$this->countries();

    //$payments=$this->db->run("SELECT {$this->db->prefix}payment.*,{$this->db->prefix}user.email FROM {$this->db->prefix}payment INNER JOIN {$this->db->prefix}user ON {$this->db->prefix}payment.userid = {$this->db->prefix}user.id ORDER BY date DESC LIMIT 20",array(),TRUE);
    Main::set("title","Admin cPanel");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
    /**
     *  Dashboard Chart Data Function
     *  Generate data and inject it into the homepage. Also append the flot library.
     *  @since 4.0
     */   
      protected function charts($filter="day",$span=30){
        if(isset($_GET["filter"])) $filter=$_GET["filter"];
        $new_date=array();  
        $new_clicks=array(); 
        $new_urls=array();        
        // Store as Array
        $this->db->object=FALSE;
        // Daily Stats
        if($filter=="monthly"){
          $span=12;

          $usersbydate = Main::cache_get("admin_user_month");
          if($usersbydate == null){
            $usersbydate=$this->db->get(array("count"=>"COUNT(MONTH(date)) as count, DATE(date) as date","table"=>"user"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span MONTH))",array("group_custom"=>"MONTH(date)","limit"=>30));     
            Main::cache_set("admin_user_month", $usersbydate,15);
          }

          $urls=Main::cache_get("admin_url_month");
          if($urls == null){
            $urls=$this->db->get(array("count"=>"COUNT(MONTH(date)) as count, DATE(date) as date","table"=>"url"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span MONTH))",array("group_custom"=>"MONTH(date)","limit"=>30));   
            Main::cache_set("admin_url_month", $urls,15);
          }
          
          $clicks=Main::cache_get("admin_click_month");
          if($clicks == null){
            $clicks=$this->db->get(array("count"=>"COUNT(MONTH(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span MONTH))",array("group_custom"=>"MONTH(date)","limit"=>30));   
            Main::cache_set("admin_click_month", $clicks,15);
          }


          foreach ($usersbydate as $user[0] => $data) {
            $new_date[date("F Y",strtotime($data["date"]))]=$data["count"];
          } 
          foreach ($urls as $urls[0] => $data) {
            $new_urls[date("F Y",strtotime($data["date"]))]=$data["count"];
          }
          foreach ($clicks as $clicks[0] => $data) {
            $new_clicks[date("F Y",strtotime($data["date"]))]=$data["count"];
          }        
          $timestamp = time();
          for ($i = 0 ; $i < $span ; $i++) {
              $array[date('F Y', $timestamp)]=0;
              $timestamp -= 30*24 * 3600;
          }
        }elseif($filter=="yearly"){

          $span=8;


          $usersbydate = Main::cache_get("admin_user_year");
          if($usersbydate == null){
           $usersbydate=$this->db->get(array("count"=>"COUNT(YEAR(date)) as count, DATE(date) as date","table"=>"user"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span YEAR))",array("group_custom"=>"YEAR(date)","limit"=>30));      
            Main::cache_set("admin_user_year", $usersbydate,15);
          }

          $urls=Main::cache_get("admin_url_year");
          if($urls == null){
            $urls=$this->db->get(array("count"=>"COUNT(YEAR(date)) as count, DATE(date) as date","table"=>"url"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span YEAR))",array("group_custom"=>"YEAR(date)","limit"=>30)); 
   
            Main::cache_set("admin_url_year", $urls,15);
          }
          
          $clicks=Main::cache_get("admin_click_year");
          if($clicks == null){
            $clicks=$this->db->get(array("count"=>"COUNT(YEAR(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= DATE_SUB(CURDATE(), INTERVAL $span YEAR))",array("group_custom"=>"YEAR(date)","limit"=>30));  
            Main::cache_set("admin_click_year", $clicks,15);
          }

          foreach ($usersbydate as $user[0] => $data) {
            $new_date[date("Y",strtotime($data["date"]))]=$data["count"];
          } 
          foreach ($urls as $urls[0] => $data) {
            $new_urls[date("Y",strtotime($data["date"]))]=$data["count"];
          }
          foreach ($clicks as $clicks[0] => $data) {
            $new_clicks[date("Y",strtotime($data["date"]))]=$data["count"];
          }        
          $timestamp = time();
          for ($i = 0 ; $i < $span ; $i++) {
              $array[date('Y', $timestamp)]=0;
              $timestamp -= 12*30*24 * 3600;
          }
        }else{

          $usersbydate = Main::cache_get("admin_user_daily");
          if($usersbydate == null){
           $usersbydate=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"user"),"(date >= CURDATE() - INTERVAL $span DAY)",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));        
            Main::cache_set("admin_user_daily", $usersbydate,15);
          }

          $urls=Main::cache_get("admin_url_daily");
          if($urls == null){
            $urls=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"url"),"(date >= CURDATE() - INTERVAL $span DAY)",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));
            Main::cache_set("admin_url_daily", $urls,15);
          }
          
          $clicks=Main::cache_get("admin_click_daily");
          if($clicks == null){
            $clicks=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= CURDATE() - INTERVAL $span DAY)",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));  
            Main::cache_set("admin_click_daily", $clicks,15);
          }
          foreach ($usersbydate as $user[0] => $data) {
            $new_date[date("d M",strtotime($data["date"]))]=$data["count"];
          } 
          foreach ($urls as $urls[0] => $data) {
            $new_urls[date("d M",strtotime($data["date"]))]=$data["count"];
          }
          foreach ($clicks as $clicks[0] => $data) {
            $new_clicks[date("d M",strtotime($data["date"]))]=$data["count"];
          }        
          $timestamp = time();
          for ($i = 0 ; $i < $span ; $i++) {
              $array[date('d M', $timestamp)]=0;
              $timestamp -= 24 * 3600;
          }            
        }
       
        $this->db->object=TRUE;
        $date=""; $var=""; $date1=""; $var1=""; $date2=""; $var2=""; $i=0; 

        foreach ($array as $key => $value) {
          $i++;
          if(isset($new_date[$key])){
            $var.="[".($span-$i).", ".$new_date[$key]."], ";
            $date.="[".($span-$i).",\"$key\"], ";
          }else{
            $var.="[".($span-$i).", 0], ";
            $date.="[".($span-$i).", \"$key\"], ";
          }
          if(isset($new_urls[$key])){
            $var1.="[".($span-$i).", ".$new_urls[$key]."], ";
            $date1.="[".($span-$i).",\"$key\"], ";
          }else{
            $var1.="[".($span-$i).", 0], ";
            $date1.="[".($span-$i).", \"$key\"], ";
          }  
          if(isset($new_clicks[$key])){
            $var2.="[".($span-$i).", ".$new_clicks[$key]."], ";
            $date2.="[".($span-$i).",\"$key\"], ";
          }else{
            $var2.="[".($span-$i).", 0], ";
            $date2.="[".($span-$i).", \"$key\"], ";
          }             
        }
        $data=array("registered"=>array($var,$date),"urls"=>array($var1,$date1),"clicks"=>array($var2,$date2));
        Main::admin_add("{$this->config["url"]}/static/js/flot.js","script",0);
        Main::admin_add("<script type='text/javascript'>var options = {
              series: {
                lines: { show: true, lineWidth: 2,fill: true},                
                points: { show: true, lineWidth: 2 }, 
                shadowSize: 0
              },
              grid: { hoverable: true, clickable: true, tickColor: 'transparent', borderWidth:0 },
              colors: ['#0da1f5', '#1ABC9C','#F11010'],
              xaxis: {ticks:[{$data["urls"][1]},{$data["clicks"][1]},{$data["registered"][1]}], tickDecimals: 0, color: '#999'},
              yaxis: {ticks:3, tickDecimals: 0, color: '#CFD2E0'},
              xaxes: [ { mode: 'time'} ]
          }; 
          var data = [{
              label: ' URLs ',
              data: [{$data["urls"][0]}]
          },{
              label: ' Clicks',
              data: [{$data["clicks"][0]}]
          },{
              label: ' Users ',
              data: [{$data['registered'][0]}]
          }];
          $.plot('#user-chart', data ,options);</script>",'custom',TRUE);        
      }
    /**
     *  Dashboard Country Function
     *  @since 1.1
     */     
     protected function countries(){
        $this->db->object=FALSE;
        $countries = Main::cache_get("admin_countries");
        if($countries == null){
          $countries=$this->db->get(array("count"=>"COUNT(country) as count, country as country","table"=>"stats"),"",array("group"=>"country","order"=>"count"));
          Main::cache_set("admin_countries",$countries,15);
        }
        $this->db->object=TRUE;
        $i=0;
        $top_countries=array();
        $country=array();
        foreach ($countries as $c) {
          $country[Main::ccode(ucwords($c["country"]),1)]=$c["count"];
          if($i<=10){
            if(!empty($c["country"])) $top_countries[ucwords($c["country"])]=$c["count"];
          }
          $i++;
        }
        Main::admin_add("<script type='text/javascript'>var data=".json_encode($country)."; $('#country-map').vectorMap({
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
        return $top_countries;
     }        
  /**
    * Search
    * @since 4.0 
    **/
  protected function search(){
    if(empty($_GET["q"]) || strlen($_GET["q"])<3) Main::redirect("admin",array("danger","Keyword must be at least 3 characters."));
    $count="";
    $pagination="";
    $hideFilter=FALSE;
    $users=$data=$this->db->search("user",array("email"=>"?","username"=>"?"),array("limit"=>30),array("%{$_GET["q"]}%","%{$_GET["q"]}%"));

    $urls=$this->db->search("url",array("url"=>":q","alias"=>":q","meta_title"=>":q"),array("limit"=>30),array(":q"=>"%{$_GET["q"]}%"));

    $payments=$this->db->search("payment",array("userid"=>":q","tid"=>":q"),array("limit"=>30),array(":q"=>"%{$_GET["q"]}%"));
    $this->header();
    if(!$users && !$urls && !$payments){
      echo "<h3>No results found</h3> <p>Your keyword did not match any results. Please try a different keyword.</p>";
    }
    if($users){
      include($this->t("users"));
    }
    if($urls){
      include($this->t("urls"));
    }
    if($payments){
      include($this->t("payments"));
    }    
    $this->footer();    
  }
  /**
   * URLs
   * @since 4.2
   **/
  protected function urls($limit=""){
    if(in_array($this->do, array("edit","delete","export","inactive","flush"))){
      $fn = "urls_{$this->do}";
      return $this->$fn();
    }
    $where=""; 
    $filter="id";
    $order="";
    $asc=FALSE;       
    $perpage = "";
    // Reset Limit
    if(isset($_GET["perpage"]) && in_array($_GET["perpage"], array("25","50", "100"))) {
      $this->limit = Main::clean($_GET["perpage"], TRUE, 3);
      $perpage = $this->limit;
    }
    // Filters
    if(isset($_GET["filter"]) && in_array($_GET["filter"], array("most","less","old","anon"))){
        if($_GET["filter"]=="most"){
          $filter="click";
          $order="most";
          $asc=FALSE;
        }elseif($_GET["filter"]=="less"){
          $filter="click";
          $order="less";
          $asc=TRUE;
        }elseif($_GET["filter"]=="old"){
          $filter="date";
          $order="old";
          $asc=TRUE;
        }elseif($_GET["filter"]=="anon"){
          $order="anon";
          $where=array("userid"=>0);
        }
    }
    // Get User Info
    if($this->do=="view" && is_numeric($this->id)){
      $where=array("userid"=>$this->id);
    }
    // Get urls from Database
    $urls=$this->db->get("url",$where,array("order"=>$filter,"limit"=>(($this->page-1)*$this->limit).", {$this->limit}","asc"=>$asc));

    if(empty($urls)) Main::redirect("admin/",array("danger","No URLs found."));  

    if(!empty($this->id)){
      $pagination=Main::nextpagination($this->page, Main::ahref("urls/view/{$this->id}")."?page=%d&filter=$order", ($this->db->rowCountAll < $this->limit ? TRUE : FALSE));
    }else{
      $pagination=Main::nextpagination($this->page, Main::ahref("urls")."?page=%d&filter=$order&perpage=$perpage", ($this->db->rowCountAll < $this->limit ? TRUE : FALSE));
    }

    // Set Header
    Main::set("title","Manage URLs");    
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
      /**
       * Edit URL
       * @since 4.0
       **/
      private function urls_edit(){
        // Save Changes
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("urls/edit/{$this->id}","",FALSE),array("danger","Something went wrong, please try again."));
          }
          // Validate URl
          if(empty($_POST["url"]) || !Main::is_url(Main::clean($_POST["url"],3))) return Main::redirect(Main::ahref("urls/edit/{$this->id}","",FALSE),array("danger","Please enter a valid URL."));
          // Encode Geo Data
          $countries='';
          if(!empty($_POST['location'][0]) && !empty($_POST['target'][0])){
            foreach ($_POST['location'] as $i => $country) {
              if(!empty($country) && !empty($_POST['target'][$i]) && Main::is_url($_POST['target'][$i])){
                $countries[strtolower(Main::clean($country,3))]=Main::clean($_POST['target'][$i],3);
              }            
            }
            $countries=json_encode($countries);            
          }
          // Prepare Data
          $data = array(
            ":url" => Main::clean($_POST["url"],3),
            ":meta_title" => Main::clean($_POST["meta_title"],3),
            ":meta_description" => Main::clean($_POST["meta_description"],3),
            ":location" => $countries,
            ":ads" => in_array($_POST["ads"],array("0","1")) ? Main::clean($_POST["ads"],3):"1",
            ":public" => in_array($_POST["public"],array("0","1")) ? Main::clean($_POST["public"],3):"0"
            );
          $this->db->update("url","",array("id"=>$this->id),$data);
          return Main::redirect(Main::ahref("urls/edit/{$this->id}","",FALSE),array("success","URL has been updated."));
        }

        // Get URL Info
        if(!$url=$this->db->get("url",array("id"=>"?"),array("limit"=>1),array($this->id))){
          Main::redirect(Main::ahref("urls","",TRUE),array("danger","This URL doesn't exist."));
        }
        $this->url_chart($url->alias.$url->custom);
        $beforehead="<div class='panel panel-default panel-dark'>     
                      <div class='panel-body'>
                        <div id='user-chart' class='chart' style='width:98%'></div>  
                      </div>
                    </div>";
        $beforehead.="<div class='form-group country hide' style='display:none'>
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
        $header="Edit URL";
        $content="
        <p class='alert alert-info'>
          This URL has been shortened <strong>".Main::timeago($url->date)."</strong> and has received <strong>{$url->click}</strong> clicks since then. This URL is <strong>".(empty($url->location) ? "not geotargeted" : "geotargeted")."</strong>, <strong>".(empty($url->pass) ? "not password-protected" : "password-protected")."</strong> and is owned by <strong>".($url->userid ? "a registered user" : "an anonymous user")."</strong>.
          </p>        
        <form action='".Main::ahref("urls/edit/{$url->id}")."' method='post' class='form-horizontal' role='form'>
          <div class='form-group'>
            <label for='url' class='col-sm-3 control-label'>Long URL</label>
            <div class='col-sm-9'>
              <input type='url' class='form-control' name='url' id='url' value='{$url->url}'>
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
            <label for='meta_title' class='col-sm-3 control-label'>Meta Title</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='meta_title' id='meta_title' value='{$url->meta_title}'>
            </div>
          </div>  

          <div class='form-group'>
            <label for='meta_description' class='col-sm-3 control-label'>Meta Description</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='meta_description' id='meta_description' value='{$url->meta_description}'>
            </div>
          </div>
          <hr>
          <h4>Geotargeting Data <a href='#' class='btn btn-primary btn-xs pull-right add_geo'>Add a Field</a></h4>
          <div id='geo'>
          <p class='small alert alert-info'>After you click <strong>Delete</strong>, the geotargeting  data will be deleted but will remain in the database. It will be permanently deleted once you click <strong>Submit</strong>. If you change your mind or you clicked delete by accident, simply refresh this page <strong>without</strong> clicking <strong>Submit</strong>.</p>";

          if(!empty($url->location)){
            $geo=json_decode($url->location);
            foreach ($geo as $country => $link){
              $content.="<div class='form-group'>
                          <div class='col-sm-6'>
                            <label>Country</label>
                              <select name='location[]'>
                                ".Main::countries($country)."
                              </select>
                          </div>
                          <div class='col-sm-6'>
                          <label>URL</label>
                            <input type='text' class='form-control' name='target[]' id='meta_description' value='$link'>                          
                          </div>
                        </div><p><a href='#' class='btn btn-danger btn-xs delete_geo'>Delete</a></p>";
            }
          } 
        $content.="</div><hr>
        <ul class='form_opt' data-id='ads'>
          <li class='text-label'>Display advertisement for this URL <small>Disabling this option will not show any advertisement for this short URL.</small></li>
          <li><a href='' class='last".(!$url->ads?' current':'')."' data-value='0'>Disable</a></li>
          <li><a href='' class='first".($url->ads?' current':'')."' data-value='1'>Enable</a></li>
        </ul>
        <input type='hidden' name='ads' id='ads' value='".$url->ads."' />

        <ul class='form_opt' data-id='public'>
          <li class='text-label'>URL Access <small>Making this URL private will make the stats inaccessible everyone except the admin and the creator.</small></li>
          <li><a href='' class='last".(!$url->public?' current':'')."' data-value='0'>Private</a></li>
          <li><a href='' class='first".($url->public?' current':'')."' data-value='1'>Public</a></li>
        </ul>
        <input type='hidden' name='public' id='public' value='".$url->public."' />             
        ".Main::csrf_token(TRUE)."
        <input type='submit' value='Update URL' class='btn btn-primary' />
        <a href='{$this->url}/urls/delete/{$url->id}' class='btn btn-danger delete'>Delete</a>";
        if($url->userid){
          $content.="<a href='{$this->url}/users/edit/{$url->userid}' class='btn btn-success pull-right'>View User</a>";
        }
        $content.="</form>";
        Main::set("title","Edit URL");
        $this->header();
        include($this->t("edit"));
        $this->footer();        
      }
      /**
       * Delete URL
       * @since 5.0
       **/
      private function urls_delete(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
        // Mass Delete URLs
        if(isset($_POST["token"]) && isset($_POST["delete-id"]) && is_array($_POST["delete-id"])){
          
          // Validate Token
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("urls","",FALSE),array("danger",e("Invalid token. Please try again.")));
          }     

          $query = "(";
          $query2 =" (";
          $c = count($_POST["delete-id"]);
          $p = [];
          $i = 1;
          foreach ($_POST["delete-id"] as $id) {
            if($i >= $c){
              $query.="(`alias` = :id$i OR `custom`= :id$i)";
              $query2.="`short` = :id$i";
            }else{
              $query.="(`alias` = :id$i OR `custom`= :id$i) OR ";
              $query2.="`short` = :id$i OR ";
            }            
            $p[':id'.$i] = $id;
            $i++;
          }  
          $query .= ")";
          $query2 .= ")";

          $this->db->delete("url", $query, $p);
          $this->db->delete("stats", $query2, $p);
          return Main::redirect(Main::ahref("urls","",FALSE),array("success",e("Selected URLs have been deleted.")));
        }        
        // Delete single URL
        if(!empty($this->id) && is_numeric($this->id)){
          // Validated Nonce
          if(!Main::validate_nonce("delete_url-{$this->id}")) return Main::redirect(Main::ahref("urls","",FALSE),array("danger","Security token expired. Please try again."));

          $url=$this->db->get("url",array("id"=>"?"),array("limit"=>1),array($this->id));
          $this->db->delete("url",array("id"=>"?"),array($this->id));
          $this->db->delete("stats",array("short"=>"?"),array($url->alias.$url->custom));
          return Main::redirect(Main::ahref("urls","",FALSE),array("success",e("URL has been deleted.")));
        } 
        return Main::redirect(Main::ahref("urls","",FALSE),array("danger",e("An unexpected error occurred.")));          
      }
    /**
     *  URL Chart Data Function
     *  Generate data and inject it into the homepage. Also append the flot library.
     *  @since 4.0
     */   
      protected function url_chart($id,$span=25){
        $this->db->object=FALSE;
        $clicks=$this->db->get(array("count"=>"COUNT(DATE(date)) as count, DATE(date) as date","table"=>"stats"),"(date >= CURDATE() - INTERVAL $span DAY) AND short='{$id}'",array("group_custom"=>"DATE(date)","limit"=>"0 , $span"));
       
        $this->db->object=TRUE;

        $new_clicks=array(); 
        foreach ($clicks as $clicks[0] => $data) {
          $new_clicks[date("d M",strtotime($data["date"]))]=$data["count"];
        }        
        $timestamp = time();
        for ($i = 0 ; $i < $span ; $i++) {
            $array[date('d M', $timestamp)]=0;
            $timestamp -= 24 * 3600;
        }
         $date2=""; $var2=""; $i=0; 

        foreach ($array as $key => $value) {
          $i++;
          if(isset($new_clicks[$key])){
            $var2.="[".($span-$i).", ".$new_clicks[$key]."], ";
            $date2.="[".($span-$i).",\"$key\"], ";
          }else{
            $var2.="[".($span-$i).", 0], ";
            $date2.="[".($span-$i).", \"$key\"], ";
          }             
        }
        $data=array("clicks"=>array($var2,$date2));
        Main::admin_add("{$this->config["url"]}/static/js/flot.js","script",0);
        Main::admin_add("<script type='text/javascript'>var options = {
              series: {
                lines: { show: true, lineWidth: 2,fill: true},
                //bars: { show: true,lineWidth: 1 },  
                points: { show: true, lineWidth: 2 }, 
                shadowSize: 0
              },
              grid: { hoverable: true, clickable: true, tickColor: 'transparent', borderWidth:0 },
              colors: ['#0da1f5', '#1ABC9C','#F11010'],
              xaxis: {ticks:[{$data["clicks"][1]}], tickDecimals: 0, color: '#999'},
              yaxis: {ticks:3, tickDecimals: 0, color: '#CFD2E0'},
              xaxes: [ { mode: 'time'} ]
          }; 
          var data = [{
              label: ' Clicks',
              data: [{$data["clicks"][0]}]
          }];
          $.plot('#user-chart', data ,options);</script>",'custom',TRUE);        
      } 
    /**
     * Export User
     * @since v3.0
     */   
    private function urls_export(){
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment;filename=URL_Shortener_URLList.csv');
      $result = $this->db->get("url","",array("order"=>"id","all"=>1));
      echo "Short URL, Long URL, Date, Clicks, User ID\n";
      foreach ($result as $line) {
        echo "{$this->config["url"]}/{$line->alias}{$line->custom},{$line->url},{$line->date},{$line->click},{$line->userid}\n";
      }
      return;
    }
   /**
    * Delete Inactive URLs
    * @since v3.0
    */  
    private function urls_inactive($clicks='0',$days='30'){
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));   
      if(Main::validate_nonce("inactive_urls")){
        $a=$this->db->get(array("count"=>"alias,custom","table"=>"url"),"click='$clicks' AND date < (CURDATE() - INTERVAL $days DAY)",array("all"=>TRUE));
        if(!empty($a)){
          $list="";
          $i=0;
          $count=count($a);
          foreach ($a as $v) {
            $i++;         
            if(!empty($v->custom)){
              $list.="short='{$v->custom}'";
            }else{
              $list.="short='{$v->alias}'";
            }
            if($i<$count) $list.=" AND ";
          }
          $this->db->delete('url',"click='$clicks' AND date<(CURDATE() - INTERVAL $days DAY)");
          $this->db->delete('stats',"($list)");       
        } 
        Main::redirect(Main::ahref("urls","",FALSE),array("success","Inactive URLs have been removed from the database."));
        return;
      }else{
        Main::redirect(Main::ahref("urls","",FALSE),array("danger","An error has occurred."));
        return;     
      }   
    } 
   /**
    * Delete Anonymous URLs
    * @since v4.2
    */  
    private function urls_flush(){
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));   

      if(Main::validate_nonce("flush")){
        $a=$this->db->get(array("count"=>"alias,custom","table"=>"url"),array("userid" => "0") ,array("all"=>TRUE));
        if(!empty($a)){
          $list="";
          $i=0;
          $count=count($a);
          foreach ($a as $v) {
            $i++;         
            if(!empty($v->custom)){
              $list.="short='{$v->custom}'";
            }else{
              $list.="short='{$v->alias}'";
            }
            if($i<$count) $list.=" AND ";
          }
          $this->db->delete('url', array("userid" => "0"));
          $this->db->delete('stats',"($list)");       
        } 
        Main::redirect(Main::ahref("urls","",FALSE),array("success","All URLs by anonymous users have been removed from the database."));
        return;
      }else{
        Main::redirect(Main::ahref("urls","",FALSE),array("danger","An error has occurred."));
        return;     
      }   
    }                       
  /**
   * Users
   * @since 4.0
   **/
  protected function users($limit=""){
    // Toggle
    if(in_array($this->do, array("edit","delete","add","export","inactive"))){
      $fn = "users_{$this->do}";
      return $this->$fn();
    }    
    if(!empty($limit)) $this->limit=$limit;
    // Filters
    $where="";
    $filter="id";
    $order="";
    $asc=FALSE;    
    if(isset($_GET["filter"]) && in_array($_GET["filter"], array("old","admin"))){
        if($_GET["filter"]=="admin"){
          $filter="id";
          $order="admin";
          $where=array("admin"=>1);
        }elseif($_GET["filter"]=="old"){
          $filter="date";
          $order="old";
          $asc=TRUE;
        }
    }   
    // Get urls from Database
    $users=$this->db->get("user",$where,array("count"=>TRUE,"order"=>$filter,"limit"=>(($this->page-1)*$this->limit).", {$this->limit}","asc"=>$asc));
    if($this->page > $this->db->rowCount) Main::redirect("admin/",array("danger","No Users found."));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }     
    $count="({$this->db->rowCount})";
    $pagination=Main::pagination($max, $this->page, Main::ahref("users")."?page=%d&filter=$order");    
    Main::set("title","Manage Users");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
      /**
       * Add user
       * @since 4.0
       **/
      private function users_add(){
        // Add User
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("users/add","",FALSE),array("danger","Something went wrong, please try again."));
          }
          if(!empty($_POST["username"])){
            if(!Main::username($_POST["username"])) return Main::redirect(Main::ahref("users/add","",FALSE),array("danger","Please enter a valid username."));
            if($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
              Main::redirect(Main::ahref("users/add","",FALSE),array("danger","This username has already been used."));
              return;
            }
          }          
          // Get User info
          if(empty($_POST["email"]) || !Main::email($_POST["email"])){
            return Main::redirect(Main::ahref("users/add","",FALSE),array("danger","Please enter a valid email"));
          }   
          if($this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]))){
            Main::redirect(Main::ahref("users/add","",FALSE),array("danger","This email has already been used."));
            return;
          }          
          if(strlen($_POST["password"]) < 5) return Main::redirect(Main::ahref("user","",FALSE),array("danger","Password has to be at least 5  characters."));          
          // Prepare Data
          $data = array(
            ":email" => Main::clean($_POST["email"],3),
            ":username" => Main::clean($_POST["username"],3),
            ":password" => Main::encode($_POST["password"]),
            ":api" => Main::strrand(12),
            ":ads" => in_array($_POST["ads"],array("0","1")) ? Main::clean($_POST["ads"],3):"1",
            ":admin" => in_array($_POST["admin"],array("0","1")) ? Main::clean($_POST["admin"],3):"0",
            ":active" => in_array($_POST["active"],array("0","1")) ? Main::clean($_POST["active"],3):"1",
            ":banned" => "0",
            ":splash_opt" => in_array($_POST["splash_opt"],array("0","1")) ? Main::clean($_POST["splash_opt"],3):"1",
            ":public" => in_array($_POST["public"],array("0","1")) ? Main::clean($_POST["public"],3):"0",
            ":date" => "NOW()",
            ":pro" =>  in_array($_POST["pro"],array("0","1")) ? Main::clean($_POST["pro"],3):"0",
            ":last_payment" => Main::clean($_POST["last_payment"],3),
            ":expiration" => Main::clean($_POST["expiration"],3)            
            );         

          $this->db->insert("user",$data);
          return Main::redirect(Main::ahref("users","",FALSE),array("success","User has been added."));
        }
             
        $header="Add a User";
        $content="       
        <form action='".Main::ahref("users/add")."' method='post' class='form-horizontal' role='form'>

          <div class='form-group'>
            <label for='username' class='col-sm-3 control-label'>Username</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='username' id='username' value=''>
              <p class='help-block'>A username is required for the public profile to be visible. This is however optional.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='email' class='col-sm-3 control-label'>Email</label>
            <div class='col-sm-9'>
              <input type='email' class='form-control' name='email' id='email' value=''>
              <p class='help-block'>Please make sure that email is valid.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='password' class='col-sm-3 control-label'>Password</label>
            <div class='col-sm-9'>
              <input type='password' class='form-control' name='password' id='password' value=''>
              <p class='help-block'>Password needs to be at least 5 characters.</p>
            </div>
          </div>
          <div class='form-group'>
            <label for='pro' class='col-sm-3 control-label'>Premium Member</label>
            <div class='col-sm-9'>
              <select name='pro' id='pro'>
                <option value='1'>Pro</option>
                <option value='0' selected>Free</option>
              </select>
            </div>
          </div> 
          <div class='form-group'>
            <label for='last_payment' class='col-sm-3 control-label'>Last Payment</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='last_payment' id='last_payment' value=''>
              <p class='help-block'>Date in this format: YYYY-MM-DD HH:ii:ss (e.g. 2014-04-01 18:25:00)</p>
            </div>
          </div> 
          <div class='form-group'>
            <label for='expiration' class='col-sm-3 control-label'>Expiration</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='expiration' id='expiration' value=''>
              <p class='help-block'>Date in this format: YYYY-MM-DD HH:ii:ss (e.g. 2014-04-01 18:25:00)</p>
            </div>
          </div>                       
          <hr />
          <ul class='form_opt' data-id='admin'>
            <li class='text-label'>User Status<small>Do you want this user to be admin or just a regular user?</small></li>
            <li><a href='' class='last current' data-value='0'>User</a></li>
            <li><a href='' class='first' data-value='1'>Admin</a></li>
          </ul>
          <input type='hidden' name='admin' id='admin' value='0' />

          <ul class='form_opt' data-id='splash_opt'>
            <li class='text-label'>Enable Custom Splash Page <small>Users will be able to advertise their product and encourage traffic flow.</small></li>
            <li><a href='' class='last current' data-value='0'>Disable</a></li>
            <li><a href='' class='first' data-value='1'>Enable</a></li>
          </ul>
          <input type='hidden' name='splash_opt' id='splash_opt' value='0' />

          <ul class='form_opt' data-id='ads'>
            <li class='text-label'>Display advertisement for this user <small>By default all users will see advertisement.</small></li>
            <li><a href='' class='last' data-value='0'>Disable</a></li>
            <li><a href='' class='first current' data-value='1'>Enable</a></li>
          </ul>
          <input type='hidden' name='ads' id='ads' value='1' />

          <ul class='form_opt' data-id='public'>
            <li class='text-label'>Profile Access <small>Private profiles are not accessible and will throw a 404 error.</small></li>
            <li><a href='' class='last current' data-value='0'>Private</a></li>
            <li><a href='' class='first' data-value='1'>Public</a></li>
          </ul>
          <input type='hidden' name='public' id='public' value='0' />   

          ".Main::csrf_token(TRUE)."
          <input type='submit' value='Add User' class='btn btn-primary' />";

        $content.="</form>";
        Main::set("title","Add a User");
        $this->header();
        include($this->t("edit"));
        $this->footer();       
      }  
      /**
       * Edit user
       * @since 4.0
       **/
      private function users_edit(){
        // Save Changes
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","Something went wrong, please try again."));
          }
          // Get User info
          $user=$this->db->get("user",array("id"=>"?"),array("limit"=>1),array($this->id));
          if($user->auth!="twitter" && (empty($_POST["email"]) || !Main::email($_POST["email"]))){
            Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","Please enter a valid email."));
            return;
          }
          if($_POST["email"]!==$user->email){
            if($this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]))){
              Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","This email has already been used. Please try again."));
              return;
            }
          }   
          if(!empty($_POST["username"]) && $_POST["username"]!==$user->username){
            if($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
              Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","This username has already been used. Please try again."));
              return;
            }
          }
          // Prepare Data
          $data = array(
            ":email" => Main::clean($_POST["email"],3),
            ":username" => Main::clean($_POST["username"],3),
            ":api" => Main::clean($_POST["api"],3),
            ":ads" => in_array($_POST["ads"],array("0","1")) ? Main::clean($_POST["ads"],3):"1",
            ":admin" => in_array($_POST["admin"],array("0","1")) ? Main::clean($_POST["admin"],3):"0",
            ":active" => in_array($_POST["active"],array("0","1")) ? Main::clean($_POST["active"],3):"1",
            ":banned" => in_array($_POST["banned"],array("0","1")) ? Main::clean($_POST["banned"],3):"0",
            ":splash_opt" => in_array($_POST["splash_opt"],array("0","1")) ? Main::clean($_POST["splash_opt"],3):"1",
            ":public" => in_array($_POST["public"],array("0","1")) ? Main::clean($_POST["public"],3):"0",
            ":pro" =>  in_array($_POST["pro"],array("0","1")) ? Main::clean($_POST["pro"],3):"0",
            ":last_payment" => Main::clean($_POST["last_payment"],3),
            ":expiration" => Main::clean($_POST["expiration"],3)
            );         
          if(!empty($_POST["password"])){
            if(strlen($_POST["password"]) < 5) return Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("danger","Password has to be at least 5 characters."));
            $data[":password"]=Main::encode($_POST["password"]);
          }
          $this->db->update("user","",array("id"=>$this->id),$data);
         return Main::redirect(Main::ahref("users/edit/{$this->id}","",FALSE),array("success","User has been updated."));
        }

        // Get URL Info
        if(!$user=$this->db->get("user",array("id"=>"?"),array("limit"=>1),array($this->id))){
          Main::redirect(Main::ahref("users","",TRUE),array("danger","This user doesn't exist."));
        }                 

        $user->last_payment = date("Y-m-d", strtotime($user->last_payment));
        $user->expiration = date("Y-m-d", strtotime($user->expiration));
        $header="Edit User";
        $content="       
        <form action='".Main::ahref("users/edit/{$user->id}")."' method='post' class='form-horizontal' role='form'>
          ".($user->id==$this->user->id?"<p class='alert alert-warning'><strong>This is your account!</strong> Be careful when editing the password or the admin status to prevent locking yourself out.</p>":"")."         
          ".(!empty($user->auth)?"<p class='alert alert-warning'>This user has used ".ucfirst($user->auth)." to login.</p>":"")."         
          <div class='form-group'>
            <label for='username' class='col-sm-3 control-label'>Username</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='username' id='username' value='{$user->username}'>
              <p class='help-block'>A username is required for the public profile to be visible.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='email' class='col-sm-3 control-label'>Email</label>
            <div class='col-sm-9'>
              <input type='email' class='form-control' name='email' id='email' value='{$user->email}'>
            </div>
          </div>  
          <div class='form-group'>
            <label for='password' class='col-sm-3 control-label'>Password</label>
            <div class='col-sm-9'>
              <input type='password' class='form-control' name='password' id='password' value=''>
              <p class='help-block'>Leave this field empty to keep the current password otherwise password needs to be at least 5 characters.</p>
            </div>
          </div>  
          <div class='form-group'>
            <label for='pro' class='col-sm-3 control-label'>Premium Member</label>
            <div class='col-sm-9'>
              <select name='pro' id='pro'>
                <option value='1' ".($user->pro?"selected":"").">Pro</option>
                <option value='0' ".(!$user->pro?"selected":"").">Free</option>
              </select>
            </div>
          </div> 
          <div class='form-group'>
            <label for='last_payment' class='col-sm-3 control-label'>Last Payment</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' data-toggle='datetimepicker' name='last_payment' id='last_payment' value='{$user->last_payment}'>
              <p class='help-block'>Date for free members will be a couple of zeros.</p>
            </div>
          </div> 
          <div class='form-group'>
            <label for='expiration' class='col-sm-3 control-label'>Expiration</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' data-toggle='datetimepicker' name='expiration' id='expiration' value='{$user->expiration}'>
              <p class='help-block'>Date for free members will be a couple of zeros.</p>
            </div>
          </div>                               
          <div class='form-group'>
            <label for='api' class='col-sm-3 control-label'>API Key</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='api' id='api' value='{$user->api}'>
              <p class='help-block'>An API key allows users to shorten URLs from their own app or site. Remove the API key to prevent this user from using the API feature.</p>
            </div>
          </div>           
          <hr />
          <ul class='form_opt' data-id='admin'>
            <li class='text-label'>User Status<small>Do you want this user to be admin or just a regular user?</small></li>
            <li><a href='' class='last".(!$user->admin?' current':'')."' data-value='0'>User</a></li>
            <li><a href='' class='first".($user->admin?' current':'')."' data-value='1'>Admin</a></li>
          </ul>
          <input type='hidden' name='admin' id='admin' value='".$user->admin."' />

          <ul class='form_opt' data-id='active'>
            <li class='text-label'>User Activity <small>Inactive users cannot login anymore but their URLs will still work.</small></li>
            <li><a href='' class='last".(!$user->active?' current':'')."' data-value='0'>Inactive</a></li>
            <li><a href='' class='first".($user->active?' current':'')."' data-value='1'>Active</a></li>
          </ul>
          <input type='hidden' name='active' id='active' value='".$user->active."' />

          <ul class='form_opt' data-id='banned'>
            <li class='text-label'>Ban this user <small>Banning will prevent this user from logging in and all of their URLs will stop working.</small></li>
            <li><a href='' class='last".(!$user->banned?' current':'')."' data-value='0'>Not Banned</a></li>
            <li><a href='' class='first".($user->banned?' current':'')."' data-value='1'>Banned</a></li>
          </ul>
          <input type='hidden' name='banned' id='banned' value='".$user->banned."' />
                              
          <ul class='form_opt' data-id='splash_opt'>
            <li class='text-label'>Enable Custom Splash Page <small>Users will be able to advertise their product and encourage traffic flow.</small></li>
            <li><a href='' class='last".(!$user->splash_opt?' current':'')."' data-value='0'>Disable</a></li>
            <li><a href='' class='first".($user->splash_opt?' current':'')."' data-value='1'>Enable</a></li>
          </ul>
          <input type='hidden' name='splash_opt' id='splash_opt' value='".$user->splash_opt."' />

          <ul class='form_opt' data-id='ads'>
            <li class='text-label'>Display advertisement for this user <small>By default all users will see advertisement.</small></li>
            <li><a href='' class='last".(!$user->ads?' current':'')."' data-value='0'>Disable</a></li>
            <li><a href='' class='first".($user->ads?' current':'')."' data-value='1'>Enable</a></li>
          </ul>
          <input type='hidden' name='ads' id='ads' value='".$user->ads."' />

          <ul class='form_opt' data-id='public'>
            <li class='text-label'>Profile Access <small>Private profiles are not accessible and will throw a 404 error.</small></li>
            <li><a href='' class='last".(!$user->public?' current':'')."' data-value='0'>Private</a></li>
            <li><a href='' class='first".($user->public?' current':'')."' data-value='1'>Public</a></li>
          </ul>
          <input type='hidden' name='public' id='public' value='".$user->public."' />   

          ".Main::csrf_token(TRUE)."
          <input type='submit' value='Update User' class='btn btn-primary' />
          <a href='{$this->url}/users/delete/{$user->id}' class='btn btn-danger delete'>Delete</a>";

        $content.="</form>";
        Main::set("title","Edit User");
        Main::cdn("datepicker", NULL, TRUE);
        $this->header();
        include($this->t("edit"));
        $this->footer();        
      }
      /**
       * Delete user(s)
       * @since 4.0
       **/
      private function users_delete(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));        
        // Mass Delete Users without deleting URLs
        if(isset($_POST["token"]) && isset($_POST["delete-id"]) && is_array($_POST["delete-id"])){
          // Validate Token
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("users","",FALSE),array("danger",e("Invalid token. Please try again.")));
          }             
          $query="(";
          $c=count($_POST["delete-id"]);
          $p="";
          $i=1;
          foreach ($_POST["delete-id"] as $id) {
            $this->db->update("url",array("userid"=>0),array("userid"=>"?"),array($id));
            if($i>=$c){
              $query.="`id` = :id$i";
            }else{
              $query.="`id` = :id$i OR ";
            }                   
            $p[":id$i"]=$id;
            $i++;
          }  
          $query.=")";
          if($query!=="()") $this->db->delete("user",$query,$p);
          return Main::redirect(Main::ahref("users","",FALSE),array("success",e("Selected users have been deleted but their URLs were not deleted.")));
        }        
        // Delete single URL
        if(!empty($this->id) && is_numeric($this->id)){
          // Validated Single Nonce
          if(Main::validate_nonce("delete_user-{$this->id}")){
            $this->db->delete("user",array("id"=>"?"),array($this->id));
            return Main::redirect(Main::ahref("users","",FALSE),array("success",e("User has been deleted.")));
          }
          // Validated Single Nonce
          if(Main::validate_nonce("delete_user_all-{$this->id}")){
            $urls=$this->db->get("url",array("userid"=>"?"),array("limit"=>1),array($this->id));
            foreach ($url as $url) {
              $this->db->delete("stats",array("short"=>"?"),array($url->alias.$url->custom));
            }
            $this->db->delete("url",array("userid"=>"?"),array($this->id));
            $this->db->delete("user",array("id"=>"?"),array($this->id));
            return Main::redirect(Main::ahref("users","",FALSE),array("success",e("This user and everything associated have been successfully deleted.")));
          }          
        } 
        return Main::redirect(Main::ahref("users","",FALSE),array("danger",e("An unexpected error occurred.")));          
      } 
      /**
        * Delete Inactive Users
        * @since v3.0
        */    
      private function users_inactive(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));  
        if(Main::validate_nonce("inactive_users")){
          $this->db->delete('user',array("active"=>'0',"admin"=>'0'));
          Main::redirect(Main::ahref("users","",FALSE),array("success","Inactive users have been removed from the database."));
          return;
        }else{
          Main::redirect(Main::ahref("users","",FALSE),array("danger","An error has occurred."));
          return;     
        }   
      }
      /**
       * Export User
       * @since v2.0
       */   
      protected function users_export(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo.")); 
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=URL_Shortener_UserList.csv');
        $result = $this->db->get("user","",array("order"=>"id","all"=>1));
        echo "Username (empty=none), Email, Registration Date, Auth Method (empty=system), Pro, Expiration\n";
        foreach ($result as $line) {
          echo "{$line->username},{$line->email},{$line->date},{$line->auth},{$line->pro},{$line->expiration}\n";
        }
        return;
      }  
  /**
   * [subscription description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function subscription(){
    
    if(!$this->isExtended()) return $this->setUpExtended();

    // Get urls from Database
    $this->db->object=TRUE;
    $subscriptions = $this->db->get("subscription",[],array("count"=>TRUE,"order"=>"date","limit"=>(($this->page-1)*$this->limit).", {$this->limit}"));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }     

    $count="({$this->db->rowCount})";
    $pagination = Main::pagination($max, $this->page, Main::ahref("subscription")."?page=%d");    
    Main::set("title","Manage Subscriptions");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }           
  /**
   * [setUpExtended description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   */
  private function setUpExtended(){

    if(isset($_POST["token"])){

      $key = Main::clean($_POST["key"], "3", TRUE);

      $Resp = Main::gemCurl("https://cdn.gempixel.com/validator/",[
                      "data" => ["key" => $key, "url" => $this->config["url"]]
                ]);

      if(!$Resp || empty($Resp) || $Resp == "Failed"){
        return Main::redirect("admin/subscription",array("danger","This purchase code is not valid. It is either for another item or has been disabled.")); 
      }elseif($Resp == "Wrong.Item"){
        return Main::redirect("admin/subscription",array("danger","This purchase code is for another item. Please use a Premium URL Shortener extended license purchase code.")); 
      }elseif($Resp == "Wrong.License"){
        return Main::redirect("admin/subscription",array("danger","This purchase code is for a standard license. Please use a Premium URL Shortener extended license purchase code.")); 
      }else{
        return $this->installExtended($Resp);
      }
      return Main::redirect("admin/subscription",array("danger","An error occured. Please try again.")); 
    }

    $header = "Set Up Extended Version - Enable Subscriptions";
    $content = "       
    <form action='".Main::ahref("subscription")."' method='post' class='form-horizontal' role='form'>
      <p>You will need an extended license to enable subscription and stripe payments as per Envato's license. You DO NOT need an extended license if you are using this for yourself, your company or using PayPal one-time payment. To enable subscriptions, enter your extended license key below to unlock. For more info on the license type, <a href='https://codecanyon.net/licenses/standard' target='_blank'>click here</a>.</p>
      <p>If for some reason it is not working and your extended license is valid, the validation server might not be responding. You can try again later or contact us. You can find your license key in the <a href='https://codecanyon.net/downloads'>Downloads</a> section of Codecanyon. Click on Download then on License Certificate.</p>
      <hr>
        <h4>Extended license features</h4>
        <ul>
          <li>Ability to charge customers periodically (automatically)</li>
          <li>Enables stripe</li>
          <li>Enables invoicing</li>
          <li>Automatic payment management</li>
        </ul>
      <hr>
      <div class='form-group'>
        <label for='key' class='col-sm-3 control-label'>Extended License Key</label>
        <div class='col-sm-9'>
          <input type='text' class='form-control' name='key' id='key' value=''>
        </div>
      </div>  
      ".Main::csrf_token(TRUE)."
      <input type='submit' value='Enable Subscriptions' class='btn btn-primary' />
      </form>";

    Main::set("title","Set Up Extended");    
    $this->header();
    include($this->t("edit"));
    $this->footer();       
  }  
  /**
   * Payments
   * @since 4.0
   **/
  protected function payments(){
    // Export
    if($this->do=="export"){
      // Disable if demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo.")); 
      // Validated Nonce
      if(!Main::validate_nonce("export")) return Main::redirect(Main::ahref("payments","",FALSE),array("danger","Security token expired. Please try again."));
      // Export Payments
      header('Content-Type: text/csv');
      header('Content-Disposition: attachment;filename=URL_Shortener_Payments.csv');
      $this->db->object=TRUE;
      $payments = $this->db->get("payment","",array("order"=>"id","all"=>1));
      echo "Transaction ID,Paypal Transaction ID,Status,User ID,Date,Membership Expiration,Amount\n";
      foreach ($payments as $payment) {
        echo "{$payment->id},{$payment->tid},{$payment->status},{$payment->userid},{$payment->date},{$payment->expiry},{$payment->amount}\n";
      }
      exit;
      return Main::redirect(Main::ahref("payments","",FALSE),array("danger","Security token expired. Please try again."));
    }
    // Make a payment as Completed
    if($this->do=="review" && is_numeric($this->id)){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      if($this->db->update("payment",array("status"=>"?"),array("id"=>"?"),array("Completed",$this->id))){
        return Main::redirect(Main::ahref("payments","",FALSE),array("success","Payment has been marked as Completed."));
      }
      return Main::redirect(Main::ahref("payments","",FALSE),array("danger","Security token expired. Please try again."));
    }    
    if($this->do=="view" && is_numeric($this->id)){
      $where=array("userid"=>$this->id);
    }else{
      $where="";
    }    
    // Get urls from Database
    $this->db->object=TRUE;
    $payments=$this->db->get("payment",$where,array("count"=>TRUE,"order"=>"date","limit"=>(($this->page-1)*$this->limit).", {$this->limit}"));
    if($this->page > $this->db->rowCount) Main::redirect("admin/",array("danger","No payments found."));

    if(($this->db->rowCount%$this->limit)<>0) {
      $max=floor($this->db->rowCount/$this->limit)+1;
    } else {
      $max=floor($this->db->rowCount/$this->limit);
    }     
    $count="({$this->db->rowCount})";
    $pagination=Main::pagination($max, $this->page, Main::ahref("payments")."?page=%d");    
    Main::set("title","Manage Payments");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
      /**
       * Edit payment
       * @since 4.0
       **/
      private function payment_delete(){

      }  
  /**
   * Pages
   * @since 4.0
   **/
  protected function pages(){
    // Toggle
    if(in_array($this->do, array("edit","delete","add"))){
      $fn = "pages_{$this->do}";
      return $this->$fn();
    }       
    $pages=$this->db->get("page","",array("order"=>"id"));
    $count=$this->db->rowCountAll;
    Main::set("title","Manage Pages");
    $this->header();
    include($this->t("page"));
    $this->footer();
  }
      /**
       * Add page
       * @since 4.0
       **/
      private function pages_add(){
        // Add User
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("pages/add","",FALSE),array("danger","Something went wrong, please try again."));
          }

          if(!empty($_POST["name"]) && !empty($_POST["content"])){
            if($this->db->get("page",array("seo"=>Main::slug((!empty($_POST["slug"])?$_POST["seo"]:$_POST["name"]))))){
              Main::redirect(Main::ahref("pages/add","",FALSE),array("danger","This slug is already taken, please use another one."));
            }
            // Prepare Data
            $data = array(
              ":name" => Main::clean($_POST["name"],3),
              ":seo" => empty($_POST["slug"]) ? Main::slug($_POST["name"]) : Main::slug($_POST["slug"]),
              ":content" => $_POST["content"],
              ":menu" => in_array($_POST["menu"],array("0","1")) ? Main::clean($_POST["menu"],3):"0"
              );         

            $this->db->insert("page",$data);
            return Main::redirect(Main::ahref("pages","",FALSE),array("success","Page has been added."));        
          }
          Main::redirect(Main::ahref("pages/add","",FALSE),array("danger","Please make sure that you fill everything correctly."));            
        }
        // Add CDN Editor
        Main::cdn("ckeditor","",1);
        Main::admin_add("<script>CKEDITOR.replace( 'editor', {height: 350});</script>","custom",1);
        $header="Add a Custom Page";
        $content="       
        <form action='".Main::ahref("pages/add")."' method='post' class='form-horizontal' role='form'>
          <div class='form-group'>
            <label for='name' class='col-sm-3 control-label'>Name</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='name' id='name' value=''>
            </div>
          </div>  
          <div class='form-group'>
            <label for='seo' class='col-sm-3 control-label'>Slug</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='slug' id='slug' value=''>
              <p class='help-block'>E.g. {$this->config["url"]}/page/<strong>Slug</strong>. Leave this empty to automatically generate it.</p>
            </div>
          </div>
         <ul class='form_opt' data-id='menu'>
            <li class='text-label'>Add to Menu<small>Do you want to add a link to this page in the menu?</small></li>
            <li><a href='' class='last current' data-value='0'>No</a></li>
            <li><a href='' class='first' data-value='1'>Yes</a></li>
          </ul>
          <input type='hidden' name='menu' id='menu' value='0' />          
          
          <textarea class='form-control ckeditor' id='editor' name='content' rows='25'></textarea>
          <br>
          ".Main::csrf_token(TRUE)."
          <input type='submit' value='Add Page' class='btn btn-primary' />";

        $content.="</form>";
        Main::set("title","Add a Custom Page");
        $this->header();
        include($this->t("edit"));
        $this->footer();       
      }  
      /**
       * Edit page
       * @since 4.0
       **/
      private function pages_edit(){
        // Add User
        if(isset($_POST["token"])){
          // Disable if demo
          if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
          // Validate Results
          if(!Main::validate_csrf_token($_POST["token"])){
            return Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("danger","Something went wrong, please try again."));
          }

          if(!empty($_POST["name"])){
            if($this->db->get("page","seo=? AND id!=?","",array(Main::slug(!empty($_POST["slug"])?$_POST["seo"]:$_POST["name"]),$this->id))){
              Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("danger","This slug is already taken, please use another one."));
            }
            // Prepare Data
            $data = array(
              ":name" => Main::clean($_POST["name"],3),
              ":seo" => empty($_POST["slug"]) ? Main::slug($_POST["name"]) : Main::slug($_POST["slug"]),
              ":content" => $_POST["content"],
              ":menu" => in_array($_POST["menu"],array("0","1")) ? Main::clean($_POST["menu"],3):"0"
              );         

            $this->db->update("page","",array("id"=>$this->id),$data);
            return Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("success","Page has been edited."));        
          }
          Main::redirect(Main::ahref("pages/edit/{$this->id}","",FALSE),array("danger","Please make sure that you fill everything correctly."));            
        }
        if(!$page=$this->db->get("page",array("id"=>"?"),array("limit"=>1),array($this->id))){
          return Main::redirect(Main::ahref("pages","",FALSE),array("danger","Page doesn't exist."));
        }
        // Add CDN Editor
        Main::cdn("ckeditor","",1);
        Main::admin_add("<script>CKEDITOR.replace( 'editor', {height: 350});</script>","custom",1);
        $header="Edit Page";
        $content="       
        <form action='".Main::ahref("pages/edit/{$this->id}")."' method='post' class='form-horizontal' role='form'>
          <div class='form-group'>
            <label for='name' class='col-sm-3 control-label'>Name</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='name' id='name' value='{$page->name}'>
            </div>
          </div>  
          <div class='form-group'>
            <label for='seo' class='col-sm-3 control-label'>Slug</label>
            <div class='col-sm-9'>
              <input type='text' class='form-control' name='slug' id='slug' value='{$page->seo}'>
              <p class='help-block'>E.g. {$this->config["url"]}/page/<strong>Slug</strong>. Leave this empty to automatically generate it.</p>
            </div>
          </div>
         <ul class='form_opt' data-id='menu'>
            <li class='text-label'>Add to Menu<small>Do you want to add a link to this page in the menu?</small></li>
            <li><a href='' class='last".(!$page->menu?' current':'')."' data-value='0'>No</a></li>
            <li><a href='' class='first".($page->menu?' current':'')."' data-value='1'>Yes</a></li>
          </ul>
          <input type='hidden' name='menu' id='menu' value='0' />          
          
          <textarea class='form-control ckeditor' id='editor' name='content' rows='25'>{$page->content}</textarea>
          <br>
          ".Main::csrf_token(TRUE)."
          <input type='submit' value='Add Page' class='btn btn-primary' />
          <a href='".Main::href("page/{$page->seo}")."' class='btn btn-success' target='_blank'> View Page</a>";

        $content.="</form>";
        Main::set("title","Edit Page");
        $this->header();
        include($this->t("edit"));
        $this->footer();       
      }
      /**
       * Delete page
       * @since 4.0
       **/
      private function pages_delete(){
        // Disable if demo
        if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));            
        // Delete single URL
        if(!empty($this->id) && is_numeric($this->id)){
          // Validated Single Nonce
          if(Main::validate_nonce("delete_page-{$this->id}")){
            $this->db->delete("page",array("id"=>"?"),array($this->id));
            return Main::redirect(Main::ahref("pages","",FALSE),array("success",e("Page has been deleted.")));
          }        
        } 
        return Main::redirect(Main::ahref("pages","",FALSE),array("danger",e("An unexpected error occurred.")));          
      }   
  /**
   * Settings
   * @since 4.2
   **/
  protected function settings(){    
    // Optimize
    if($this->do=="optimize") return $this->optimize();
    // Update Settings
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
        return Main::redirect(Main::ahref("settings","",FALSE),array("danger","Something went wrong, please try again."));
      }         
      // Upload Logo
      if(isset($_FILES["logo_path"]) && !empty($_FILES["logo_path"]["tmp_name"])) {
        $ext=array("image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg");            
        if(!isset($ext[$_FILES["logo_path"]["type"]])) return Main::redirect(Main::ahref("settings","",FALSE),array("danger",e("Logo must be either a PNG or a JPEG.")));
        if($_FILES["logo_path"]["size"]>100*1024) return Main::redirect(Main::ahref("settings","",FALSE),array("danger",e("Logo must be either a PNG or a JPEG (Max 100KB).")));            
        $_POST["logo"]="auto_site_logo.".$ext[$_FILES["logo_path"]["type"]];
        move_uploaded_file($_FILES["logo_path"]['tmp_name'], ROOT."/content/auto_site_logo.".$ext[$_FILES["logo_path"]["type"]]);                
      }
      // Delete Logo
      if(isset($_POST["remove_logo"])){
        unlink(ROOT."/content/".$this->config["logo"]);
        $_POST["logo"]="";
      }       
      
      if(isset($this->config["pt"]) && $this->config["pt"] == "stripe"){
        
        if(isset($this->config["stsk"]) && empty($this->config["stsk"]) && !empty($_POST["stsk"])){
          $this->config["stpk"] = Main::clean($_POST["stpk"], 3, TRUE);
          $this->config["stsk"] = Main::clean($_POST["stsk"], 3, TRUE);
          $this->getPlans();
        }else{
          if($_POST["pro_monthly"] != $this->config["pro_monthly"] || $_POST["pro_yearly"] != $this->config["pro_yearly"] ||  $_POST["currency"] != $this->config["currency"]){
            $this->updatePlan();
          }
        }
      }

      // Encode SMTP
      $_POST["smtp"] = json_encode($_POST["smtp"]);

      // Update Config
      foreach($_POST as $config => $var){
        if(in_array($config, array("ad728","ad300","ad468"))){
          $this->db->update("settings",array("var"=>"?"),array("config"=>"?"),array($var,$config));
        }else{
          $this->db->update("settings",array("var"=>"?"),array("config"=>"?"),array(Main::clean($var,2,TRUE),$config));
        }
      }

      return Main::redirect(Main::ahref("settings","",FALSE),array("success","Settings have been updated.")); 
    } 


    $lang="<option value='' ".($this->config["default_lang"]==""?" selected":"").">English</option>";
    foreach (new RecursiveDirectoryIterator(ROOT."/includes/languages/") as $path){
      if(!$path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && $path->getFilename()!=="lang_sample.php" && $path->getFilename()!=="index.php" && Main::extension($path->getFilename())==".php"){  
          $data = token_get_all(file_get_contents($path));
          $data = $data[1][1];
          if(preg_match("~Language:\s(.*)~", $data,$name)){
            $name ="".strip_tags(trim($name[1]))."";
          }        
        $code = str_replace(".php", "" , $path->getFilename());
        $lang .= "<option value='".$code."' ".($this->config["default_lang"]==$code ? " selected":"").">$name</option>";
      }
    }        

    $this->config["email"] = ($this->config["demo"])?"Hidden" : $this->config["email"];
    $this->config["apikey"] = ($this->config["demo"])?"Hidden" : $this->config["apikey"];
    $this->config["safe_browsing"] = ($this->config["demo"])?"Hidden" : $this->config["safe_browsing"];
    $this->config["phish_api"] = ($this->config["demo"])?"Hidden" : $this->config["phish_api"];
    $this->config["captcha_public"] = ($this->config["demo"])?"Hidden" : $this->config["captcha_public"];
    $this->config["captcha_private"] = ($this->config["demo"])?"Hidden" : $this->config["captcha_private"];
    $this->config["facebook_secret"] = ($this->config["demo"])?"Hidden" : $this->config["facebook_secret"];
    $this->config["facebook_app_id"] = ($this->config["demo"])?"Hidden" : $this->config["facebook_app_id"];
    $this->config["twitter_key"] = ($this->config["demo"])?"Hidden" : $this->config["twitter_key"];
    $this->config["twitter_secret"] = ($this->config["demo"])?"Hidden" : $this->config["twitter_secret"];

    Main::admin_add("<style>.help-block{font-size:12px;color: #777777;font-weight: 400;}</style>","custom",FALSE);

    Main::set("title","Settings");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }

  /**
   * [getPlans description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function getPlans(){
    if(!$this->isExtended()) return FALSE;
    include(STRIPE);

    \Stripe\Stripe::setApiKey($this->config["stsk"]);
    if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

    try {

      $planMonthly = \Stripe\Plan::retrieve("PUS.monthly");

    } catch (Exception $e) {
      
      if($e->getMessage() == "No such plan: PUS.monthly"){

        if(empty($this->config["pro_monthly"])) $this->config["pro_monthly"] = "5.99";

        $planMonthly = \Stripe\Plan::create(array(
            "amount" => $this->config["pro_monthly"]*100,
            "interval" => "month",
            "nickname" => "Premium Plan - Monthly",
            "product" => array(
              "name" => "Premium Membership - Monthly"
            ),            
            "currency" => strtolower($this->config["currency"]),
            "id" => "PUS.monthly"
          ));

      }
    }

    try {

      $planYearly = \Stripe\Plan::retrieve("PUS.yearly");

    } catch (Exception $e) {
      
      if($e->getMessage() == "No such plan: PUS.yearly"){
        
        if(empty($this->config["pro_yearly"])) $this->config["pro_monthly"] = "49.99";

        $planYearly = \Stripe\Plan::create(array(
            "amount" => $this->config["pro_yearly"]*100,
            "interval" => "year",
            "nickname" => "Premium Plan - Yearly",
            "product" => array(
              "name" => "Premium Membership - Yearly"
            ),                
            "currency" => strtolower($this->config["currency"]),
            "id" => "PUS.yearly"
          ));

      }
    }    

    return \Stripe\Plan::all(array("limit" => 2));
  }
  /**
   * [updatePlan description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @return  [type] [description]
   */
  protected function updatePlan(){
    if(!$this->isExtended()) return FALSE;
    include(STRIPE);

    \Stripe\Stripe::setApiKey($this->config["stsk"]);
    if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

    // Price Changed
    if($_POST["pro_monthly"] != $this->config["pro_monthly"] || $_POST["currency"] != $this->config["currency"]){
      $mPlan = \Stripe\Plan::retrieve("PUS.monthly");
      $mPlan->delete();

      $planMonthly = \Stripe\Plan::create(array(
          "amount" => $_POST["pro_monthly"]*100,
          "interval" => "month",
          "nickname" => "Premium Plan - Monthly",
          "product" => array(
            "name" => "Premium Membership - Monthly"
          ),            
          "currency" => strtolower($_POST["currency"]),
          "id" => "PUS.monthly"
        ));      
    }

    if($_POST["pro_yearly"] != $this->config["pro_yearly"] || $_POST["currency"] != $this->config["currency"]){
      $YPlan = \Stripe\Plan::retrieve("PUS.yearly");
      $YPlan->delete();

      $planMonthly = \Stripe\Plan::create(array(
          "amount" => $_POST["pro_yearly"]*100,
          "interval" => "year",
          "nickname" => "Premium Plan - Yearly",
          "product" => array(
            "name" => "Premium Membership - Yearly"
          ),            
          "currency" => strtolower($_POST["currency"]),
          "id" => "PUS.yearly"
        ));      
    }    
    
  }
  /**
   * Get Theme Styles
   * @since 4.1
   **/
  protected function style(){
    if(!is_dir(TEMPLATE."/styles/")) return FALSE;
    $html = '<div class="form-group">
          <label class="col-sm-3 control-label">Style</label>
          <div class="col-sm-9">
            <ul class="themes-style">
            <li class="dark"><a href="#" data-class="" '.($this->config["style"]==""?"class='current'":'').'>Dark</a></li>';        
    foreach (new RecursiveDirectoryIterator(TEMPLATE."/styles/") as $path){
      if(!$path->isDir() && Main::extension($path->getFilename())==".css"){  
        $name=str_replace(".css", "", $path->getFilename());
        $html.='<li class="'.$name.'"><a href="#" data-class="'.$name.'" '.($this->config["style"]==$name?"class='current'":'').'>'.ucfirst($name).'</a></li>';                  
      }
    }             
    $html.='</ul> 
          <input type="hidden" name="style" value="'.$this->config["style"].'" id="theme_value"> 
          <p class="help-block">The default theme supports these styles.</p>
        </div>
      </div>';
    return $html;
  }      
  /**
   * Themes
   * @since 4.0
   **/
  protected function themes(){
    // Activate Theme
    if($this->do=="activate" && !empty($this->id)){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));      
      // Check Security Token
      if(!Main::validate_nonce("theme-{$this->id}")){
        return Main::redirect(Main::ahref("themes","",FALSE),array("danger",e("Security token expired, please try again.")));
      }       

      if(!file_exists(ROOT."/themes/{$this->id}/style.css")){
        return Main::redirect(Main::ahref("themes","",FALSE),array("danger","Sorry this theme cannot be activated because it is missing the stylesheet.")); 
      }
      if($this->db->update("settings",array("var"=>"?"),array("config"=>"?"),array(Main::clean($this->id,3,TRUE),"theme"))){
        Main::redirect(Main::ahref("themes","",FALSE),array("success","Theme has been activated."));
      }      
      return Main::redirect(Main::ahref("themes","",FALSE),array("danger","An unexpected issue occurred, please try again."));
    }
    // Clone Theme
    if($this->do=="copy" && !empty($this->id)){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));      
      // Check Security Token
      if(!Main::validate_nonce("copy-{$this->id}")){
        return Main::redirect(Main::ahref("themes","",FALSE),array("danger",e("Security token expired, please try again.")));
      }       
      $this->copy_folder(ROOT."/themes/{$this->id}",ROOT."/themes/{$this->id}".rand(0,9));
      return Main::redirect(Main::ahref("themes","",FALSE),array("success","Theme has been successfully cloned."));
    }    

    // Update Theme
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
       return Main::redirect(Main::ahref("themes","",FALSE),array("danger","Something went wrong, please try again."));
      }  
      if($_POST["theme_files"]=="style"){
        $file_path=TEMPLATE."/style.css";
      }else{
        $file_path=TEMPLATE."/".Main::clean($_POST["theme_files"],3,TRUE).".php";
      }
      if(file_exists($file_path)){
        $file = fopen($file_path, 'w') or die( Main::redirect(Main::ahref("themes","",FALSE),array("danger","Cannot open file. Please make sure that the file is writable.")));
        fwrite($file, $_POST["content"]);
        fclose($file);
        return Main::redirect(Main::ahref("themes/".Main::clean($_POST["theme_files"],3,TRUE),"",FALSE),array("success","File has been successfully edited."));
      }
    }  
    // Get Themes
    $theme_list="";
    foreach (new RecursiveDirectoryIterator(ROOT."/themes/") as $path){
      if($path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && file_exists(ROOT."/themes/".$path->getFilename()."/style.css")){          

        $data=token_get_all(file_get_contents(ROOT."/themes/".$path->getFilename()."/style.css"));
        $data=isset($data[0][1])?$data[0][1]:FALSE;
        if($data){
          if(preg_match("~Theme Name:\s(.*)~", $data,$name)){
            $name=strip_tags(trim($name[1]));
          }        
          if(preg_match("~Author:\s(.*)~", $data,$author)){
            $author=strip_tags(trim($author[1]));
          }        
          if(preg_match("~Author URI:\s(.*)~", $data,$url)){
            $url=strip_tags(trim($url[1]));
          }
          if(preg_match("~Version:\s(.*)~", $data,$version)){
            $version=strip_tags(trim($version[1]));
          }
          if(preg_match("~Date:\s(.*)~", $data,$date)){
            $date=strip_tags(trim($date[1]));
          }
        }
        $name=isset($name) && !is_array($name)? $name : "No Name";
        $author=isset($author) && !is_array($author)? $author : "Unknown";
        $url=isset($url) && !is_array($url)? $url : "#none";
        $version=isset($version) && !is_array($version)? $version : "1.0";
        $date=isset($date) && !is_array($date)? $date : "";

        if(file_exists(ROOT."/themes/".$path->getFilename()."/screenshot.png")){
          $screenshot=$this->config["url"]."/themes/".$path->getFilename()."/screenshot.png";
        }else{
          $screenshot=$this->config["url"]."/static/noscreen.png";
        }
        $theme_list.="<div class='theme-list'>";
          $theme_list.="<div class='theme-img'><img src='$screenshot' alt='$name'></div>";
          $theme_list.="<div class='theme-info'>";
          $theme_list.="<strong>$name</strong>";
          if($this->config["theme"]!==$path->getFilename()) {
            $theme_list.="<div class='btn-group btn-group-xs pull-right'><a href='".Main::ahref("themes/activate/{$path->getFilename()}").Main::nonce('theme-'.$path->getFilename())."' class='btn btn-success'>Activate</a><a href='".Main::ahref("themes/copy/{$path->getFilename()}").Main::nonce('copy-'.$path->getFilename())."' class='btn btn-info delete'>Clone</a></div>";
          }else{
            $theme_list.="<div class='btn-group btn-group-xs pull-right'><a class='btn btn-dark'>Active</a><a href='".Main::ahref("themes/copy/{$path->getFilename()}").Main::nonce('copy-'.$path->getFilename())."' class='btn btn-info delete'>Clone</a></div>";
          }
        $theme_list.="<p>By <a href='$url' rel='nofollow' target='_blank'>$author</a> (v$version)</p></div></div>";
      }
    }
    // Get Files
    $themeFiles=$this->themeFiles();
    // Get Current File
    $currentFile=$this->currentFile();
    // Add ACE from CDN
    Main::cdn("ace","",1);
    Main::admin_add('
      <script type="text/javascript">
        var editor = ace.edit("code-editor");
            editor.setTheme("ace/theme/xcode");
            editor.getSession().setMode("ace/mode/'.$currentFile["type"].'");
        $(document).ready(function(){
          $("#form-editor").submit(function(){
            $("#code").val(editor.getSession().getValue());
          });
        });
      </script>',"custom",1);

    Main::set("title","Themes");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }
      /**
       * Theme Files
       * @since 4.0
       **/
      protected function themeFiles(){
        $data="";
        foreach (new RecursiveDirectoryIterator(ROOT."/themes/{$this->config["theme"]}/") as $path){
          if(!$path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && (Main::extension($path->getFilename())==".php" || Main::extension($path->getFilename())==".css")){
            $file=explode(".",$path->getFilename());
            $file=$file[0];
            $name=ucwords(str_replace("_", " ", $file));
            $code=strtolower($file);      
            if($path->getFilename()=="style.css") {
              $name="Main Stylesheet";
              $data.="<option value='$code' ".(empty($this->do) || $this->do=="style" ? "selected":"").">$name ({$path->getFilename()})</option>";
            }elseif($path->getFilename()=="index.php"){
              $name="Home Page";              
              $data.="<option value='$code' ".($this->do==$code ? "selected":"").">$name ({$path->getFilename()})</option>";              
            }else{
              $data.="<option value='$code' ".($this->do==$code ? "selected":"").">$name ({$path->getFilename()})</option>";
            }
          }
        }
        return $data;
      }
      /**
       * Current Theme
       * @since 4.0
       **/
      protected function currentFile(){
        $data=array();
        // Get File
        if(!empty($this->do) && $this->do!=="style"){
          if(!empty($this->do) && file_exists(ROOT."/themes/{$this->config["theme"]}/{$this->do}.php")){
            $data["type"]="html";
            $data["current"]=ucfirst($this->do).".php";
             // Disable if demo
            if($this->config["demo"]){
              $data["content"]="Content is hidden in demo";
            }else{
              $data["content"]=htmlentities(file_get_contents(ROOT."/themes/{$this->config["theme"]}/{$this->do}.php", "r"));
            }            
          }else{
            return Main::redirect(Main::ahref("themes","","FALSE"),array("danger","Theme file doesn't exist."));
          }
        }else{
          $data["type"]="css";
          $data["current"]="Main Stylesheet (style.css)";
          if($this->config["demo"]){
            $data["content"]="Content is hidden in demo";
          }else{          
            $data["content"]=htmlentities(file_get_contents(ROOT."/themes/{$this->config["theme"]}/style.css", "r"));
          }
        }
        return $data;
      }   

  /**
   * Languages
   * @since 4.0
   **/
  protected function languages(){
    // Update Language
    if(isset($_POST["token"])){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
      // Check Token
      if(!Main::validate_csrf_token($_POST["token"])){
       return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Something went wrong, please try again."));
      }  
      if(empty($_POST["language_name"])) return Main::redirect(Main::ahref("languages","",FALSE),array("danger","Language name cannot be empty!"));
      // Update Language
      $file = substr(strtolower(Main::clean(trim($_POST["language_name"]),3,TRUE)), 0, 2).".php";
      $handle = fopen(ROOT."/includes/languages/".$file, 'w') or Main::redirect(Main::ahref("languages","",FALSE),array("danger","Cannot create file. Make sure that the folder is writable."));

      $comment="<?php\n";
      $comment.="/*\n* Language: ".ucfirst(Main::clean($_POST["language_name"],3,TRUE))."\n* Author: You\n* Author URI: {$this->config["url"]}\n* Translator: Premium URL Shortener\n* Date: ".date("Y-m-d H:i:s",time())."\n* ---------------------------------------------------------------\n* Important Notice: Make sure to only change the right-hand side\n* DO NOT CHANGE THE LEFT-HAND SIDE\n* Edit the text between double-quotes \"DONT EDIT\"=> \"\" on the right side\n* Make sure to not forget any quotes \" and the comma , at the end\n* ---------------------------------------------------------------\n*/\n";
      $comment.='$lang=array(';

      fwrite($handle, $comment);
      foreach ($_POST["text"] as $o => $t) {
        fwrite($handle, "\n\"".strip_tags($o,"<b><i><s><u><strong>")."\"".'=>'."\"".strip_tags($t,"<b><i><s><u><strong>")."\",");
      }
      fwrite($handle, "); ?>");
      fclose($handle);    
      return Main::redirect(Main::ahref("languages","",FALSE),array("success","Language file has been successfully."));      
    }      
    // Delete Language
    if($this->do=="delete" && !empty($this->id) && strlen($this->id)=="2" && file_exists(ROOT."/includes/languages/{$this->id}.php")){
      // Disable this for demo
      if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));      
      // Check Security Token
      if(!Main::validate_nonce("delete-lang")){
        return Main::redirect(Main::ahref("languages","",FALSE),array("danger",e("Security token expired, please try again.")));
      }
      unlink(ROOT."/includes/languages/{$this->id}.php");
      return Main::redirect(Main::ahref("languages","",FALSE),array("success","Language file has been deleted."));
    }
    $lang_list="";
    foreach (new RecursiveDirectoryIterator(ROOT."/includes/languages/") as $path){
      if(!$path->isDir() && $path->getFilename()!=="." && $path->getFilename()!==".." && $path->getFilename()!=="index.php" && $path->getFilename()!=="lang_sample.php" && Main::extension($path->getFilename())==".php"){  

        $file=explode(".", $path->getFilename());
        $file=$file[0];
        $code=strtolower($file);
        $data=token_get_all(file_get_contents($path));
        $data=isset($data[1][1])?$data[1][1]:FALSE;
          if($data){
            if(preg_match("~Language:\s(.*)~", $data,$name)){
              $name=Main::truncate(strip_tags(trim($name[1])),10);
            }
            if(preg_match("~Author:\s(.*)~", $data,$author)){
              $author=strip_tags(trim($author[1]));
            }           
            if(preg_match("~Date:\s(.*)~", $data,$date)){
              $date=strip_tags(trim($date[1]));
            }                                      
          }else{
            $name="Unknown";
            $author="Unknown";
            $date="Unknown";
          }
        $lang_list.="<a href='".Main::ahref("languages/edit/{$code}")."' class='list-group-item".($this->id==$code ? " active":"")."'>
            <h4 class='list-group-item-heading'>$name</h4>
            <p class='list-group-item-text'>By $author <small class='pull-right'>(".Main::timeago($date).")</small></p>
          </a>";
      }
    }
    $lang_content=$this->getLang();
    Main::set("title","Manage Translations");
    $this->header();
    include($this->t(__FUNCTION__));
    $this->footer();
  }     
      /**
       * Get Language
       * @since 1.0
       **/
      protected function getLang(){
        // Check if it needs to edited
        if($this->do=="edit" && !empty($this->id)){
          if(strlen($this->id)!="2" || !file_exists(ROOT."/includes/languages/{$this->id}.php")){
            return Main::redirect(Main::ahref("languages","",FALSE),array("danger",e("File doesn't exist!")));
          }
          $data=token_get_all(file_get_contents(ROOT."/includes/languages/{$this->id}.php"));
          $data=isset($data[1][1])?$data[1][1]:FALSE;
            if($data){
              if(preg_match("~Language:\s(.*)~", $data,$name)){
                $name=strip_tags(trim($name[1]));
              }
            }
          // Get File
          include(ROOT."/includes/languages/{$this->id}.php");          
          // Check if properly formated
          if(!isset($lang) || !is_array($lang)){
            return "<p class='alert alert-danger'>The translation file appears to be empty or corrupted. Please verify that it is properly formated!</p>";
          }
          // Generate form
          $data="";
          $data.="<form action='".Main::ahref("languages/")."' method='post' class='form'>";
          $data.='<p class="alert alert-warning">
                  For each of the strings below, write the translated text for the label in the textarea. HTML markup allowed: &lt;b&gt;&lt;i&gt;&lt;s&gt;&lt;u&gt;&lt;strong&gt;. It is highly recommended that you save frequently to prevent loss of data. It does not matter if you do not translate everything, just make sure to save periodically!
                  </p>';
          $data.="<div class='form-group'>
              <label for='language_name' class='control-label'>Edit Language Name (e.g. French)</label>
              <input type='text' class='form-control' name='language_name' id='language_name' value='$name'>                
            </div><h4 class='page-header'>To be translated</h4>";        
          foreach ($lang as $original => $translation){
            $data.="<div class='form-group'>
              <label class='control-label'>$original</label>
              <textarea name='text[$original]' class='form-control' style='min-height:60px;'>$translation</textarea>
            </div><hr />";
          }      
          $data.=Main::csrf_token(TRUE);          
          $data.="<button class='btn btn-primary'>Update Translation</button> ";
          $data.="<a href='".Main::ahref("languages/delete/{$this->id}").Main::nonce("delete-lang")."' class='btn btn-danger delete'>Delete</a></form>";           
          return $data;
        }
        // Add language from Sample
        $data="";
        if(!file_exists(ROOT."/includes/languages/lang_sample.php")){
          $data="<p class='alert alert-danger'>Sample file (lang_sample.php) is not available. Please upload that in the includes/languages/ folder. This editor will not work until that file is properly uploaded there and is accessible!</p>";
        }else{
          // Get File
          include(ROOT."/includes/languages/lang_sample.php");
          // Check if properly formated
          if(!isset($lang) || !is_array($lang)){
            return "<p class='alert alert-danger'>The sample translation file appears to be empty or corrupted. Please verify that it is properly formated!</p>";
          }          
          // Generate Form
          $data.="<form action='".Main::ahref("languages")."' method='post' class='form'>";
          $data.='<p class="alert alert-warning">
                   To create a new language file, write the language in the field below and translate each of the strings in the textarea just below it. The text will appear as they do right now so remember to respect the letter case. Remember that the language code will be the first two letters of the language: for example if the language name is French then the language code will be fr. If for some reason this editor doesn\'t work for you, you may manually translate it by following the documentation. It is highly recommended that you save frequently to prevent loss of data. It does not matter if you do not translate everything, just make sure to save periodically!
                  </p>';
          $data.="<div class='form-group'>
              <label for='language_name' class='control-label'>New Language Name (e.g. French)</label>
              <input type='text' class='form-control' name='language_name' id='language_name' value=''>                
            </div><h4 class='page-header'>To be translated</h4>";        
          foreach ($lang as $original => $translation){
            $data.="<div class='form-group'>
              <label class='control-label'>$original</label>
              <textarea name='text[$original]' class='form-control' style='min-height:60px;'>$translation</textarea>
            </div><hr />";
          }      
          $data.=Main::csrf_token(TRUE);
          $data.="<button class='btn btn-primary'>Create Translation</button></form>";        
        }
        return $data;
      }  

  /**
   * Update Notification  
   * @since v2.1 
   */
  public function update_notification(){
    if($this->config["update_notification"]){
      $c=Main::curl("http://gempixel.com/update.php?p=".md5('shortener'));
      $c=json_decode($c,TRUE);
      if(isset($c["status"]) && $c["status"]=="ok"){
        if(_VERSION < $c["current_version"]){
          return "<div class='alert alert-success'>This script has been updated to version {$c["current_version"]}. Please download it from <a href='http://codecanyon.net/downloads' target='_blank' class='button green small'>CodeCanyon</a></div>";
        }
      }
    }
  }     
  /**
    * Optimize Database
    * @since v3.0
    */    
  public function optimize(){
    // Disable this for demo
    if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}user");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}url");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}stats");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}settings");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}bundle");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}page");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}payment");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}splash");
    $this->db->run("OPTIMIZE TABLE {$this->config["prefix"]}subscription");
    return Main::redirect(Main::ahref("settings","",FALSE),array("success","Database has been optimized. Your site should perform better now."));
  }   

  ################################################################################################      
  ### Admin helper methods: Please don't edit these methods as it might cause instability! ###
  ################################################################################################

  /**
   * Header
   * @since 4.0 
   **/
  protected function header(){
    include($this->t(__FUNCTION__));
  }
  /**
   * Footer
   * @since 4.0 
   **/
  protected function footer(){
    include($this->t(__FUNCTION__));
  }  
  /**
   * Template File
   * @since 4.0
   **/
  protected function t($file){
    if(file_exists(ROOT."/admin/system/$file.php")){
      return ROOT."/admin/system/$file.php";
    }else{
      return ROOT."/admin/system/index.php";
    }
  }
  /**
   * [installExtended description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @param   [type] $R [description]
   * @return  [type]    [description]
   */
  private function installExtended($R){
    $db = str_replace("_PRE_", $this->config["prefix"], $R);
    $queries = explode("|", $db);
    foreach ($queries as $query) {
      if(!$this->db->run($query)){
        return FALSE;
      }
    }
    return Main::redirect(Main::ahref("subscription","",FALSE),array("success","Subscription has been enabled. You may now use Stripe."));  
  }
  /**
   * Copy Folder
   * @since 4.0
   **/  
  protected function copy_folder($src,$dst) { 
    // Disable this for demo
    if($this->config["demo"]) return Main::redirect("admin",array("danger","Feature disabled in demo."));    
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
      if (( $file != '.' ) && ( $file != '..' )) { 
        if ( is_dir($src . '/' . $file) ) { 
         $this->copy_folder($src . '/' . $file,$dst . '/' . $file); 
        } 
        else { 
          copy($src . '/' . $file,$dst . '/' . $file); 
        } 
      } 
    } 
    closedir($dir); 
  }   
}