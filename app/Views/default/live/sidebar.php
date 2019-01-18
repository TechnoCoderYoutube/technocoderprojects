<?php

    /**

     * @var \Wow\Template\View $this

     */

    $bulkTasks      = array();

    $bulkTasks[]    = array(

        "link"   => "/tools/story-view",

        "text"   => "Hikaye izlenmesi Gönder",

        "action" => "SendLike",

        "icon"   => "fa fa-youtube-play"

    );

    $bulkTasks[]    = array(

        "link"   => "/tools/send-follower",

        "text"   => "Takipçi Gönder",

        "action" => "SendFollower",

        "icon"   => "fa fa-user-plus"

    );

    $bulkTasks[]    = array(

        "link"   => "/tools/send-comment",

        "text"   => "Yorum Gönder",

        "action" => "SendComment",

        "icon"   => "fa fa-comment"

    );
	
	
	$bulkTasks[]    = array(

        "link"   => "/bayi-tx/send-follower",

        "text"   => "Username ile Takipçi Gönder",

        "action" => "SendFollower1",

        "icon"   => "fa fa-user-plus"

    );
	
	 $bulkTasks[]    = array(

        "link"   => "/bayi-tx/send-comment",

        "text"   => "Link ile Yorum Gönder",

        "action" => "SendComment1",

        "icon"   => "fa fa-comment"

    );
	
	 $bulkTasks[] = array(

        "link"   => "/tools/auto-like-packages",

        "text"   => "Oto Beğeni Paketleri",

        "action" => "AutoLikePackages",

        "icon"   => "fa fa-heartbeat"

    );

    $bulkTasks[] = array(

        "link"   => "/tools/nonfollow-users",

        "text"   => "Non Followers",

        "action" => "NonfollowUsers",

        "icon"   => "fa fa-users"

    );

    $premiumTools   = array();

    $premiumTools[] = array(

        "link"   => "/live/send-comment",

        "text"   => "Canlı Yayına Yorum Gönder",

        "action" => "AutoLikePackages",

        "icon"   => "fa fa-heartbeat"

    );

    $premiumTools[] = array(

        "link"   => "/live/send-view",

        "text"   => "Canlı Yayına İzleyici Gönder",

        "action" => "NonfollowUsers",

        "icon"   => "fa fa-users"

    );

?>


<div class="panel panel-info">

    <div class="panel-heading">En Yeni Araçlar</div>

    <div class="panel-body" style="padding: 0;">

        <div class="list-group" style="margin-bottom: 0;">

            <?php foreach($premiumTools as $menu) { ?>

                <a href="<?php echo $menu["link"]; ?>" class="list-group-item<?php echo $this->route->params["action"] == $menu["action"] ? ' active' : ''; ?>">

                    <i class="<?php echo $menu["icon"]; ?>"></i> <?php echo $menu["text"]; ?>

                </a>

            <?php } ?>

        </div>

    </div>

</div>

<div class="panel panel-success">

    <div class="panel-heading">Toplu İşlemler</div>

    <div class="panel-body" style="padding: 0;">

        <div class="list-group" style="margin-bottom: 0;">

            <?php foreach($bulkTasks as $menu) { ?>

                <a href="<?php echo $menu["link"]; ?>" class="list-group-item<?php echo $this->route->params["action"] == $menu["action"] ? ' active' : ''; ?>">

                    <i class="<?php echo $menu["icon"]; ?>"></i> <?php echo $menu["text"]; ?>

                </a>

            <?php } ?>

        </div>

    </div>

</div>




