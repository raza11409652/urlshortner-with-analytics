<?php if(!defined("APP")) die()?>
<p class="alert alert-info">
  All themes are located in the "themes" folder. All you have to do is to upload your theme in that folder then come here to activate it. Make sure to name your main stylesheet style.css otherwise the theme will not show up!
</p>      
<div class="row themes">
  <?php echo $theme_list ?> 
</div>
<div class='editor'>
  <form action="<?php echo Main::ahref("themes/update") ?>" method="post" class="form" id="form-editor">
    <textarea name="content" id="code" class="form-control hidden" rows="1"></textarea>
    <div class='header'>
      <div class="row">
        <div class="col-sm-6">
          Currently editing: <?php echo $currentFile["current"] ?>
        </div>
        <div class="col-sm-6">
          <select name="theme_files" id="theme_files" style="max-width: 250px" class="pull-right">
            <?php echo $themeFiles ?>
          </select>
        </div>
      </div>
    </div>
    <div id="code-editor"><?php echo $currentFile["content"] ?></div>
    <br class="clear">
    <?php echo Main::csrf_token(TRUE); ?>
    <button class="btn btn-primary btn-lg">Update File</button>
  </form>  
</div>