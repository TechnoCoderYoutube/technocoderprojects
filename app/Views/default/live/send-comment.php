<?php
    /**
     * @var \Wow\Template\View $this
     * @var array              $media
     */
    $user = NULL;
    if($this->has("user")) {
        $user = $this->get("user");
    }
?>
    <div class="container">
        <div class="cl10"></div>
        <div class="row">
            <div class="col-sm-8 col-md-9">
                <h4 style="margin-top: 0;"> Canlı Yayın </h4>
                <p>İzleme gönderme aracı ile, dilediğiniz kullanıcıya, kendi belirlediğiniz adette izlemyi anlık olarak gönderebilirsiniz. Gönderilen takipçilerin tamamı gerçek kullanıcılardır.</p>
                <p>Maximum takipçi krediniz kadar, izleme gönderebilirsiniz!</p>
                <?php if(is_null($user)) { ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            Canlı Yayın Bul
                        </div>
                        <div class="panel-body">
                            <form method="post" action="?formType=findUserID" class="form">
                                <div class="form-group">
                                    <label>Kullanıcı Adı:</label>
                                    <input type="text" name="username" class="form-control" placeholder="asosyetikbiri" required>
                                </div><center>
                 <button type="submit" class="btn btn-success">Canlı yayını Bul</button>

                            </form>
                        </div>
                    </div>
                <?php } else { ?>
<div class="panel panel-default">
                        <div class="panel-heading">
                            Yorum Gönder
                        </div>
                        <div class="panel-body">
                            <form id="formYorum" class="form">
                                <div class="form-group">
                                    <label>Gönderi:</label>
                                    <?php $item = $media["items"][0]; ?>
                                    <img src="<?php echo str_replace("http:", "https:",$user["broadcast"]["cover_frame_url"]); ?>" class="img-responsive"/>
                                </div>
                                <div class="form-group">
                                    <label>Yorumlar:</label>
                                    <?php
                                        $sampleComments = array(
                                            "Woww. Süper görünüyor :)",
                                            "Gerçekten harikaaaa..",
                                            "Çoook güzeeel.",
                                            "Vayy be.",
                                            "Bayıldım buna.",
                                            "Valla ne desem bilemedim, süper."
                                        );
                                    ?>
                                    <div id="commentList">
                                        <?php foreach($sampleComments as $comment) { ?>
                                            <div class="input-group" style="margin-bottom: 5px;">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" onclick="$(this).parent().parent().remove();"><i class="fa fa-remove"></i></button>
                                    </span>
                                                <input type="text" class="form-control" name="yorum[]" value="<?php echo $comment; ?>">
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <span class="help-block"><a href="javascript:void(0);" onclick="addNewComment();">+ Ekle</a></span>
                                    <span class="help-block">Her kutuya 1 yorum gelecek şekilde yorumları yazınız. Her yorum için 1 yorum krediniz eksilecektir.</span>
                                </div>
                                <input type="hidden" name="mediaID" value="<?php echo  $user["broadcast"]["id"]; ?>">
                                <input type="hidden" name="mediaCode" value="<?php echo  $user["broadcast"]["id"]; ?>">

                                <button type="button" id="formYorumSubmitButton" class="btn btn-success" onclick="sendYorum();">Gönderimi Başlat</button>
								<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>

                            </form>
                            <div class="cl10"></div>
                            <div id="userList"></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="col-sm-4 col-md-3">
                <?php $this->renderView("live/sidebar"); ?>
            </div>
        </div>
    </div>
<?php $this->section("section_scripts");
    $this->parent();?>
      <script type="text/javascript">
            var countYorum, countYorumMax, clearCommentedIndex;

            function addNewComment() {
                html = '<div class="input-group" style="margin-bottom: 5px;"><span class="input-group-btn"><button class="btn btn-default" type="button" onclick="$(this).parent().parent().remove();"><i class="fa fa-remove"></i></button></span><input type="text" class="form-control" name="yorum[]" value=""></div>';
                $('#commentList').append(html);
            }

            function sendYorum() {
                countYorumMax = 0;
                $("#formYorum input[name='yorum[]']").each(function() {
                    if($.trim($(this).val()) != '') {
                        countYorumMax++;
                    }
                });
                if(countYorumMax === 0) {
                    alert('En az 1 yorum eklemelisin!');
                    return;
                }
                if(countYorumMax > 329) {
                    return confirm('Girdiğiniz yorum sayısı, yorum kredinizden fazla. İlk 329 tanesi gönderilsin mi?');
                }
                countYorum = 0;
                $('#formYorumSubmitButton').html('<i class="fa fa-spinner fa-spin fa-2x"></i> Gönderimi Başlat');
                $('#formYorum input').attr('readonly', 'readonly');
                $('#formYorum button').attr('disabled', 'disabled');
                $('#userList').html('');
                clearCommentedIndex = 1;
                sendYorumRC();
            }

            function sendYorumRC() {
                $.ajax({type: 'POST', dataType: 'json', url: '?formType=send&clearCommentedIndex=' + clearCommentedIndex, data: $('#formYorum').serialize()}).done(function(data) {
                    clearCommentedIndex = 0;
                    if(data.status == 'error') {
                        $('#userList').prepend('<p class="text-danger">' + data.message + '</p>');
                        sendYorumComplete();
                    }
                    else {
                        for(var i = 0; i < data.users.length; i++) {
                            var user = data.users[i];
                            if(user.status == 'success') {
                                $('#userList').prepend('<p><a href="/user/' + user.instaID + '">' + user.userNick + '</a> kullanıcı denendi. Sonuç: <span class="label label-success">Başarılı</span></p>');
                                countYorum++;
                                $('#yorumKrediCount').html(data.yorumKredi);

                            }
                            else {
                                //$('#userList').prepend('<p><a href="/user/' + user.instaID + '">' + user.userNick + '</a> kullanıcı denendi. Sonuç: <span class="label label-danger">Başarısız</span></p>');
                            }
                        }
                        if(countYorum < countYorumMax) {
                            sendYorumRC();
                        }
                        else {
                            sendYorumComplete();
                        }
                    }
                });
            }

            function sendYorumComplete() {
                $('#formYorumSubmitButton').html('Gönderimi Başlat');
                $('#formYorum input').removeAttr('readonly');
                $('#formYorum button').prop("disabled", false);
                $('#userList').prepend('<p class="text-success">Gönderilen toplam yorum adedi: ' + countYorum + '</p>');
            }
        </script>

    <?php 
    $this->endSection(); ?>