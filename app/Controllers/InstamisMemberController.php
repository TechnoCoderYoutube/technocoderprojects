<?php

    namespace App\Controllers;

    use Settings;
    use Wow;
    use Wow\Net\Response;

    class InstamisMemberController extends BaseController {

        private $uyeID;
        private $deviceID;
        private $auth;

        function onActionExecuting() {
            if(($pass = parent::onActionExecuting()) instanceof Response) {
                return $pass;
            }

            $this->uyeID    = $this->request->data->uyeID ? $this->request->data->uyeID : "";
            $this->deviceID = $_SERVER["HTTP_DEVICEID"] ? $_SERVER["HTTP_DEVICEID"] : "";
            $this->auth     = $_SERVER["HTTP_AUTH"] ? $_SERVER["HTTP_AUTH"] : "";

            if(!empty($this->auth) && !empty($this->uyeID) && !empty($this->deviceID)) {

                $tokenID = $this->db->single("SELECT tokenID FROM token WHERE (uyeID=:uyeid OR tokenID=:tokenid) AND deviceID=:deviceid AND oAuth=:oauth", array(
                    "uyeid"    => $this->uyeID,
                    "tokenid"  => $this->uyeID,
                    "deviceid" => $this->deviceID,
                    "oauth"    => $this->auth
                ));

                if(empty($tokenID)) {

                    return $this->json(array(
                                           "status" => 0,
                                           "error"  => "Yetkilendirme hatası",
                                           "hata"   => 1
                                       ));
                }

            } else {
                return $this->json(array(
                                       "status" => 0,
                                       "error"  => "Yetkilendirme hatası",
                                       "hata"   => 2
                                   ));


            }

        }


        public function GecmisIstekAction() {

            $data = $this->db->query("SELECT talepID,adetMax,gonderilenAdet,talepTip,DATE_FORMAT(talepTarih, '%d-%m-%Y %h:%i') AS talepTarih,durum FROM talepler WHERE uyeID=:uyeid ORDER BY talepTarih DESC", array("uyeid" => $this->uyeID));

            return $this->json($data);

        }


        public function IndexAction() {

            $data = array();

            return $this->partialView($data);
        }


        public function TakipciIsteAction() {

            $data = array("status" => 1);

            $deviceID = $this->request->data->deviceID ? $this->request->data->deviceID : "";

            $sorgu = $this->db->row("SELECT t.csrfToken,u.instaID,u.takipKredi,u.uyeID FROM token AS t LEFT JOIN uye AS u ON t.uyeID=u.uyeID WHERE t.deviceID=:deviceid AND t.uyeID=:uyeid", array(
                "deviceid" => $deviceID,
                "uyeid"    => $this->request->data->uyeID ? $this->request->data->uyeID : "",
            ));


            $csrfToken = $this->request->data->csrfToken ? $this->request->data->csrfToken : "";
            $phoneID   = $this->request->data->phoneID ? $this->request->data->phoneID : "";

            if(empty($sorgu)) {
                $data["status"] = 0;
                $data["error"]  = "Yetkilendirme Hatası";
            } else {

                if($sorgu["takipKredi"] == 0) {
                    $data["status"] = 0;
                    $data["error"]  = "Krediniz kalmamıştır. Uygulamaya yarın yeniden giriş yapın yada Takip Kredisi Yükleyin.";

                    return $this->json($data);
                }
                $talepAdet = $this->request->data->takipciAdet ? intval($this->request->data->takipciAdet) : 0;

                if($sorgu["takipKredi"] == 0 || $talepAdet == 0 || $talepAdet > $sorgu["takipKredi"]) {
                    $data["status"] = 0;
                    $data["error"]  = "Talep ettiğiniz adet kadar krediniz bulunmamaktadır. Max: " . $sorgu["takipKredi"] . " adet takipçi talep edebilirsiniz. Kendinize daha fazla takipçi gönderebilmek için takipçi kredisi satın alınız.";
                } else {

                    $this->db->query("UPDATE uye SET takipKredi=takipKredi-:adet WHERE uyeID=:uyeid", array(
                        "adet"  => $talepAdet,
                        "uyeid" => $sorgu["uyeID"]
                    ));

                    $takipList = $this->db->query("SELECT * FROM uye WHERE isActive=1 ORDER BY sonOlayTarihi ASC LIMIT {$talepAdet}");

                    $data["takipData"] = [];

                    $instagram = new \MobilInstagram();
                    $i         = 0;

                    foreach($takipList AS $s) {

                        $data["takipData"][$i]["login"]  = $instagram->MobileLogin($s["kullaniciAdi"], $s["sifre"], $deviceID, $phoneID, $csrfToken);
                        $data["takipData"][$i]["follow"] = $instagram->follow($sorgu["instaID"], $phoneID, $s["instaID"], $csrfToken);
                        $data["takipData"][$i]["logout"] = $instagram->MobilTakipLogout($phoneID, $csrfToken, $phoneID, $deviceID, $phoneID);
                        $i++;

                        $this->db->query("UPDATE uye SET sonOlayTarihi=now() WHERE uyeID=:uyeid", array("uyeid" => $s["uyeID"]));

                    }


                }
            }

            $uyeData = $this->db->query("SELECT takipKredi,begeniKredi,yorumKredi,storyKredi,bonusKredi FROM uye WHERE uyeID=:uyeid", array("uyeid" => $sorgu["uyeID"]));

            $data["uyeData"] = $uyeData[0];

            return $this->json($data);
        }


        public function BegeniIsteAction() {

            $data = array("status" => 1);

            $deviceID = $this->request->data->deviceID ? $this->request->data->deviceID : "";

            $sorgu = $this->db->row("SELECT t.csrfToken,u.instaID,u.begeniKredi,u.uyeID FROM token AS t LEFT JOIN uye AS u ON t.uyeID=u.uyeID WHERE t.deviceID=:deviceid AND t.uyeID=:uyeid", array(
                "deviceid" => $deviceID,
                "uyeid"    => $this->request->data->uyeID ? $this->request->data->uyeID : "",
            ));


            $csrfToken = $this->request->data->csrfToken ? $this->request->data->csrfToken : "";
            $phoneID   = $this->request->data->phoneID ? $this->request->data->phoneID : "";
            $mediaID   = $this->request->data->mediaID ? $this->request->data->mediaID : "";

            if(empty($sorgu)) {
                $data["status"] = 0;
                $data["error"]  = "Yetkilendirme Hatası";
            } else {

                if($sorgu["begeniKredi"] == 0) {
                    $data["status"] = 0;
                    $data["error"]  = "Krediniz kalmamıştır. Uygulamaya yarın yeniden giriş yapın yada Beğeni Kredisi Yükleyin.";

                    return $this->json($data);
                }
                $talepAdet = $this->request->data->begeniAdet ? intval($this->request->data->begeniAdet) : 0;

                if($sorgu["begeniKredi"] == 0 || $talepAdet == 0 || $talepAdet > $sorgu["begeniKredi"]) {
                    $data["status"] = 0;
                    $data["error"]  = "Talep ettiğiniz adet kadar krediniz bulunmamaktadır. Max: " . $sorgu["begeniKredi"] . " adet beğeni talep edebilirsiniz. Kendinize daha fazla beğeni gönderebilmek için beğeni kredisi satın alınız.";
                } else {

                    $this->db->query("UPDATE uye SET begeniKredi=begeniKredi-:adet WHERE uyeID=:uyeid", array(
                        "adet"  => $talepAdet,
                        "uyeid" => $sorgu["uyeID"]
                    ));

                    $begeniList = $this->db->query("SELECT * FROM uye WHERE isActive=1 AND sifre IS NOT null ORDER BY sonOlayTarihi ASC LIMIT {$talepAdet}");

                    $data["begeniData"] = [];

                    $i = 0;

                    foreach($begeniList AS $s) {
                        $instagram                        = new \Instagram($s["kullaniciAdi"], $s["sifre"], $s["instaID"]);
                        $data["begeniData"][$i]["begeni"] = $instagram->mobilelike($mediaID);
                        $i++;

                        $this->db->query("UPDATE uye SET sonOlayTarihi=now() WHERE uyeID=:uyeid", array("uyeid" => $s["uyeID"]));

                    }


                }
            }

            $uyeData = $this->db->query("SELECT takipKredi,begeniKredi,yorumKredi,storyKredi,bonusKredi FROM uye WHERE uyeID=:uyeid", array("uyeid" => $sorgu["uyeID"]));

            $data["uyeData"] = $uyeData[0];

            return $this->json($data);
        }


        public function YorumIsteAction() {

            $data = array("status" => 1);

            $sorgu = $this->db->row("SELECT t.csrfToken,u.instaID,u.yorumKredi,u.uyeID FROM token AS t LEFT JOIN uye AS u ON t.uyeID=u.uyeID WHERE t.deviceID=:deviceid AND t.uyeID=:uyeid", array(
                "deviceid" => $this->request->data->deviceID ? $this->request->data->deviceID : "",
                "uyeid"    => $this->request->data->uyeID ? $this->request->data->uyeID : "",
            ));

            if(empty($sorgu)) {
                $data["status"] = 0;
                $data["error"]  = "Yetkilendirme Hatası";
            } else {

                if($sorgu["yorumKredi"] == 0) {
                    $data["status"] = 0;
                    $data["error"]  = "Krediniz kalmamıştır. Uygulamaya yarın yeniden giriş yapın yada Yorum Kredisi Yükleyin.";

                    return $this->json($data);
                }
                $y         = $this->request->data->yorumlar ? $this->request->data->yorumlar : "";
                $talepAdet = 1;
                $mediaID   = $this->request->data->mediaID ? $this->request->data->mediaID : 0;

                if(empty($y)) {
                    $data["status"] = 0;
                    $data["error"]  = "Lütfen bir yorum giriniz.";
                } else {
                    if($sorgu["yorumKredi"] == 0 || $talepAdet == 0 || $talepAdet > $sorgu["yorumKredi"]) {
                        $data["status"] = 0;
                        $data["error"]  = "Talep ettiğiniz adet kadar krediniz bulunmamaktadır. Max: " . $sorgu["yorumKredi"] . " adet yorum talep edebilirsiniz. Kendinize daha fazla yorum gönderebilmek için yorum kredisi satın alınız.";
                    } else {
                        $this->db->query("INSERT INTO talepler (uyeID, adetMax,talepTip,yorumText,yorumMediaID) VALUES(:uyeid,:adetmax,'yorum',:yorumtext,:yorummedia)", array(
                            "uyeid"      => $sorgu["uyeID"],
                            "adetmax"    => 1,
                            "yorumtext"  => $y,
                            "yorummedia" => $mediaID
                        ));

                        if($this->db->lastInsertId() > 0) {
                            $this->db->query("UPDATE uye SET yorumKredi=yorumKredi-:adet WHERE uyeID=:uyeid", array(
                                "adet"  => 1,
                                "uyeid" => $sorgu["uyeID"]
                            ));
                            $this->db->query("UPDATE token SET csrfToken=:csrftoken WHERE uyeID=:uyeid", array(
                                "uyeid"     => $sorgu["uyeID"],
                                "csrftoken" => $this->request->data->csrfToken ? $this->request->data->csrfToken : $sorgu["csrfToken"]
                            ));
                        } else {
                            $data["status"] = 0;
                            $data["error"]  = "Sistemsel bir hata oluştu. Lütfen tekrar deneyiniz.";
                        }
                    }
                }


            }

            $uyeData = $this->db->query("SELECT takipKredi,begeniKredi,yorumKredi,storyKredi,bonusKredi FROM uye WHERE uyeID=:uyeid", array("uyeid" => $sorgu["uyeID"]));

            $data["uyeData"] = $uyeData[0];

            return $this->json($data);
        }

        public function StoryGoruntulenmeIsteAction() {

            $data = array("status" => 1);

            $sorgu = $this->db->row("SELECT t.csrfToken,u.instaID,u.storyKredi,u.uyeID FROM token AS t LEFT JOIN uye AS u ON t.uyeID=u.uyeID WHERE t.deviceID=:deviceid AND t.uyeID=:uyeid", array(
                "deviceid" => $this->request->data->deviceID ? $this->request->data->deviceID : "",
                "uyeid"    => $this->request->data->uyeID ? $this->request->data->uyeID : "",
            ));

            if(empty($sorgu)) {
                $data["status"] = 0;
                $data["error"]  = "Yetkilendirme Hatası";
            } else {

                if($sorgu["storyKredi"] == 0) {
                    $data["status"] = 0;
                    $data["error"]  = "Krediniz kalmamıştır. Uygulamaya yarın yeniden giriş yapın yada Story Görüntülenme Kredisi Yükleyin.";

                    return $this->json($data);
                }
                $storyData = $this->request->data->storyData ? $this->request->data->storyData : 0;
                $talepAdet = $this->request->data->storyAdet ? intval($this->request->data->storyAdet) : 0;

                if($sorgu["storyKredi"] == 0 || $talepAdet == 0 || $talepAdet > $sorgu["storyKredi"]) {
                    $data["status"] = 0;
                    $data["error"]  = "Talep ettiğiniz adet kadar krediniz bulunmamaktadır. Max: " . $sorgu["storyKredi"] . " adet yorum talep edebilirsiniz. Kendinize daha fazla yorum gönderebilmek için story görüntülenme kredisi satın alınız.";
                } else {
                    $this->db->query("INSERT INTO talepler (uyeID,instaID, adetMax,talepTip,storyData) VALUES(:uyeid,:instaid,:adetmax,'story',:storydata)", array(
                        "uyeid"     => $sorgu["uyeID"],
                        "instaid"   => $sorgu["instaID"],
                        "adetmax"   => $talepAdet,
                        "storydata" => $storyData,
                    ));

                    if($this->db->lastInsertId() > 0) {
                        $this->db->query("UPDATE uye SET storyKredi=storyKredi-:adet WHERE uyeID=:uyeid", array(
                            "adet"  => $talepAdet,
                            "uyeid" => $sorgu["uyeID"]
                        ));
                        $this->db->query("UPDATE token SET csrfToken=:csrftoken WHERE uyeID=:uyeid", array(
                            "uyeid"     => $sorgu["uyeID"],
                            "csrftoken" => $this->request->data->csrfToken ? $this->request->data->csrfToken : $sorgu["csrfToken"]
                        ));
                    } else {
                        $data["status"] = 0;
                        $data["error"]  = "Sistemsel bir hata oluştu. Lütfen tekrar deneyiniz.";
                    }
                }
            }

            $uyeData = $this->db->query("SELECT takipKredi,begeniKredi,yorumKredi,storyKredi,bonusKredi FROM uye WHERE uyeID=:uyeid", array("uyeid" => $sorgu["uyeID"]));

            $data["uyeData"] = $uyeData[0];

            return $this->json($data);

        }

        public function GetDataAction() {

            $data = array();

            $uye = $this->db->row("SELECT t.tokenID,t.csrfToken,u.instaID,t.deviceID,t.cookieJar from token AS t LEFT JOIN uye AS u ON u.uyeID=t.uyeID WHERE t.uyeID=:uyeid AND t.deviceID=:deviceID", array(
                "uyeid"    => $this->uyeID,
                "deviceID" => $this->deviceID
            ));
            

            $this->db->query("UPDATE token SET cookieJar=:cookie WHERE tokenID=:tokenid", array(
                "cookie"  => isset($_REQUEST["headers"]) && strlen($_REQUEST["headers"]) > 50 ? $_REQUEST["headers"] : $uye["cookieJar"],
                "tokenid" => $uye["tokenID"]
            ));

            $data["userID"]     = $this->uyeID;

            return $this->json($data);
        }


        public function SaveOperationAction() {

            $data = array();

            $talepID    = $this->request->data->talepID ? $this->request->data->talepID : "";
            $returnData = $this->request->data->data ? json_decode($this->request->data->data, TRUE) : "";

            if(!empty($talepID)) {

                $this->db->query("INSERT INTO responses (talepID,responseText,tokenID) VALUES (:talepid,:response,:tokenid)", array(
                    "talepid"  => $talepID,
                    "response" => json_encode($returnData),
                    "tokenid"  => $this->uyeID
                ));

                if(isset($returnData["status"]) && $returnData["status"] == "ok") {

//                    if(isset($returnData["friendship_status"])) {
//
//                        if($returnData["friendship_status"]["following"] == "true") {
//                            $this->db->query("UPDATE talepler SET gonderilenAdet=gonderilenAdet+1,gonderimUyeID=CONCAT(gonderimUyeID,:uyeid) WHERE talepID=:talepid AND gonderimUyeID NOT LIKE :uyeLike", array(
//                                "uyeid"   => $this->uyeID . ",",
//                                "talepid" => $talepID,
//                                "uyeLike" => $this->uyeID . ","
//                            ));
//                            $this->db->query("UPDATE token SET userActive=1,updateDate=now() WHERE tokenID=:tokenid", array("tokenid" => $this->uyeID));
//                        } else {
//                            $this->db->query("UPDATE token SET userActive=2,updateDate=now() WHERE tokenID=:tokenid", array("tokenid" => $this->uyeID));
//                        }
//
//                    } else {

                    $this->db->query("UPDATE talepler SET gonderilenAdet=gonderilenAdet+1,gonderimUyeID=CONCAT(gonderimUyeID,:uyeid) WHERE talepID=:talepid AND gonderimUyeID NOT LIKE :uyeLike", array(
                        "uyeid"   => $this->uyeID . ",",
                        "talepid" => $talepID,
                        "uyeLike" => $this->uyeID . ","
                    ));
                    $this->db->query("UPDATE token SET userActive=1,updateDate=now() WHERE tokenID=:tokenid", array("tokenid" => $this->uyeID));

//                    }

                } else if(isset($returnData["status"]) && $returnData["status"] == "fail" && ($returnData["message"] == "login_required" || $returnData["message"] == "challenge_required")) {
                    $this->db->query("UPDATE token SET userActive='0' WHERE tokenID=:tokenid", array("tokenid" => $this->uyeID));
                }
//                else {
//                    $this->db->query("UPDATE talepler SET gonderimUyeID=CONCAT(gonderimUyeID,:uyeid) WHERE talepID=:talepid AND gonderimUyeID NOT LIKE :uyeLike", array(
//                        "uyeid"   => $this->uyeID . ",",
//                        "talepid" => $talepID,
//                        "uyeLike" => $this->uyeID . ","
//                    ));
//                    $this->db->query("UPDATE token SET userActive=2,deviceActive=2,updateDate=now() WHERE tokenID=:tokenid", array("tokenid" => $this->uyeID));
//                }
            }

            $data["success"]    = 1;
            $data["returnData"] = $returnData;

            return $this->json($data);
        }

        public function BonusAktarAction() {

            $data = array();

            $bonusKredi = $this->db->single("SELECT bonusKredi FROM uye WHERE uyeID=:uyeid", array("uyeid" => $this->uyeID));

            $this->db->query("UPDATE uye SET takipKredi=takipKredi+:bonus,bonusKredi=0 WHERE uyeID=:uyeid", array(
                "bonus" => $bonusKredi,
                "uyeid" => $this->uyeID
            ));

            $uyeData         = $this->db->query("SELECT takipKredi,begeniKredi,yorumKredi,storyKredi,bonusKredi FROM uye WHERE uyeID=:uyeid", array("uyeid" => $this->uyeID));
            $data["uyeData"] = $uyeData[0];

            return $this->json($data);
        }


        public function LogoutAction() {

            $data = array();

            $this->db->query("UPDATE token SET loginStatus=0 WHERE uyeID=:uyeid AND deviceID=:deviceid", array(
                "uyeid"    => $this->uyeID,
                "deviceid" => $this->deviceID
            ));

            $data["uyeID"]    = $this->uyeID;
            $data["deviceID"] = $this->deviceID;

            return $this->json($data);
        }

    }