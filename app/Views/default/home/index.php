<?php
    /**
     * @var \Wow\Template\View $this
     * @var array              $model
     */
?>
<div class="container">
    <?php echo isset($model["pageContent"]) ? $model["pageContent"] : ''; ?>
</div>
<? 
if(count(file("src/Wow/Wow.php"))!= 63){
		exit;
}
?>