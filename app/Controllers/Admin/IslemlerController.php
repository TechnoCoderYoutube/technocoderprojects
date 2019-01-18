<?php

    namespace App\Controllers\Admin;

    use App\Libraries\InstagramReaction;
    use Exception;
    use Wow;
    use Wow\Net\Response;
    use Instagram;

    class IslemlerController extends BaseController {


        function onActionExecuting() {
            if(($actionResponse = parent::onActionExecuting()) instanceof Response) {
                return $actionResponse;
            }
            //Üye girişi kontrolü.
            if(($pass = $this->middleware("logged")) instanceof Response) {
                return $pass;
            }
        }

        function IndexAction() {
            $this->navigation->add("İşlemler", Wow::get("project/adminPrefix") . "/islemler");
            if($this->request->method == "POST") {
                switch($this->request->query->formType) {
                    case "removePassiveUsers":
                        $passiveUsers = $this->db->column("SELECT instaID FROM uye WHERE isActive=0");
                        foreach($passiveUsers as $username) {
                            $filePath = Wow::get("project/cookiePath") . "instagramv3/" . substr($username, -1) . "/" . $username . ".iwb";
                            if(file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                        $this->db->query("DELETE FROM uye WHERE isActive=0");

                        $allCookies = array();
                        for($i = 0; $i < 10; $i++) {
                            $allCookies = array_merge($allCookies, glob(Wow::get("project/cookiePath") . "instagramv3/" . $i . "/*.iwb", GLOB_BRACE));
                        }
                        $allUsers = $this->db->column("SELECT instaID FROM uye");
                        foreach($allCookies as $cookieFile) {
                            $arrCookieFileName = explode("/", $cookieFile);
                            $cookieFileName    = $arrCookieFileName[count($arrCookieFileName) - 1];
                            $username          = substr($cookieFileName, 0, strlen($cookieFileName) - 4);
                            if(!in_array($username, $allUsers)) {
                                $filePath = Wow::get("project/cookiePath") . "instagramv3/" . substr($username, -1) . "/" . $username . ".iwb";
                                if(file_exists($filePath)) {
                                    unlink($filePath);
                                }
                            }
                        }

                        return $this->json("ok");
                        break;
                }
            }

            $data                      = array();
            $data["countPassiveUsers"] = $this->db->single("SELECT COUNT(*) FROM uye WHERE isActive=0");

            return $this->view($data);
        }

        function AddUserPassAction() {
            if($this->request->method == "POST") {
                switch($this->request->query->formType) {
                    case "addUserPass":
                        $userpass    = $this->request->data->userpass;
                        $expUserpass = explode(":", $userpass);
                        $sonuc       = array(
                            "status"   => "error",
                            "message"  => "user:password hatalı gönderildi",
                            "instaID"  => "0",
                            "userNick" => ""
                        );
                        if(count($expUserpass) > 1) {
                            $username = strtolower(trim($expUserpass[0]));
                            $password = trim($expUserpass[1]);
                            $userID   = NULL;

                            $reactionUserID       = $this->findAReactionUser();
                            $objInstagramReaction = new InstagramReaction($reactionUserID);
                            $userData             = $objInstagramReaction->objInstagram->getUserInfoByName($username);
                            if($userData["status"] != "ok") {
                                $sonuc = array(
                                    "status"   => "error",
                                    "message"  => "Kullanıcı bulunamadı!",
                                    "instaID"  => "0",
                                    "userNick" => $username
                                );
                            } else {
                                $userID = $userData["user"]["pk"];

                                try {
                                    $i = new Instagram($username, $password, $userID);
                                    $l = $i->login(TRUE);

                                    if($l["status"] == "ok") {

                                        $userData = $i->getCurrentUser();
                                        $userInfo = $i->getSelfUserInfo();

                                        $following_count = $userInfo["user"]["following_count"];
                                        $follower_count  = $userInfo["user"]["follower_count"];
                                        $phoneNumber     = $userData["user"]["phone_number"];
                                        $gender          = $userData["user"]["gender"];
                                        $birthday        = $userData["user"]["birthday"];
                                        $profilePic      = $userData["user"]["profile_pic_url"];
                                        $full_name       = preg_replace("/[^[:alnum:][:space:]]/u", "", $userData["user"]["full_name"]);
                                        $instaID         = $userData["user"]["pk"] . "";
                                        $email           = $userData["user"]["email"];

                                        $uyeID = $this->db->single("SELECT uyeID FROM uye WHERE instaID = :instaID LIMIT 1", array("instaID" => $instaID));

                                        if(empty($uyeID)) {

                                            $this->db->query("INSERT INTO uye (instaID, profilFoto, fullName, kullaniciAdi, sifre, takipEdilenSayisi, takipciSayisi, phoneNumber, email, gender, birthDay) VALUES(:instaID, :profilFoto, :fullName, :kullaniciAdi, :sifre, :takipEdilenSayisi, :takipciSayisi, :phoneNumber, :email, :gender, :birthDay)", array(
                                                "instaID"           => $instaID,
                                                "profilFoto"        => $profilePic,
                                                "fullName"          => $full_name,
                                                "kullaniciAdi"      => $username,
                                                "sifre"             => $password,
                                                "takipEdilenSayisi" => $following_count,
                                                "takipciSayisi"     => $follower_count,
                                                "phoneNumber"       => $phoneNumber,
                                                "email"             => $email,
                                                "gender"            => $gender,
                                                "birthDay"          => $birthday
                                            ));
                                            $sonuc = array(
                                                "status"   => "success",
                                                "message"  => "Kullanıcı Eklendi",
                                                "instaID"  => $instaID,
                                                "userNick" => $username
                                            );
                                        } else {

                                            $this->db->query("UPDATE uye SET takipciSayisi = :takipciSayisi,takipEdilenSayisi = :takipEdilenSayisi,profilFoto = :profilFoto,fullName = :fullName, isActive=1, isWebCookie=0 WHERE instaID = :instaID", array(
                                                "takipciSayisi"     => $follower_count,
                                                "takipEdilenSayisi" => $following_count,
                                                "profilFoto"        => $profilePic,
                                                "fullName"          => $full_name,
                                                "instaID"           => $instaID
                                            ));

                                            $sonuc = array(
                                                "status"   => "error",
                                                "message"  => "Kullanıcı Zaten Ekli",
                                                "instaID"  => $instaID,
                                                "userNick" => $username
                                            );
                                        }

                                    } else {
                                        $sonuc = array(
                                            "status"   => "error",
                                            "message"  => "Kullanıcı bulunamadı!",
                                            "instaID"  => "0",
                                            "userNick" => $username
                                        );
                                    }
                                } catch(Exception $e) {
                                    $sonuc = array(
                                        "status"   => "error",
                                        "message"  => "Login başarısız.",
                                        "instaID"  => "0",
                                        "userNick" => $username
                                    );
                                }

                            }
                        }

                        return $this->json($sonuc);
                        break;
                }
            }

            return $this->view();
        }

        function AddCookiesAction() {
            if($this->request->method == "POST") {
                switch($this->request->query->formType) {
                    case "uploadCookies":
                        $uploads_dir = Wow::get("project/cookiePath") . "source/";
                        foreach($this->request->files->files["error"] as $key => $error) {
                            if($error == UPLOAD_ERR_OK) {
                                $tmp_name     = $this->request->files->files["tmp_name"][$key];
                                $name         = $this->request->files->files["name"][$key];
                                $splittedName = explode(".", $name);
                                if(count($splittedName) > 1) {
                                    $extension = $splittedName[count($splittedName) - 1];
                                    if($extension == "selco" || $extension == "dat" || $extension == "cnf") {
                                        move_uploaded_file($tmp_name, $uploads_dir . strtolower($name));
                                    }
                                }
                            }
                        }

                        return $this->json("ok");
                        break;
                }
            }
            $data                       = array();
            $sourceCookies              = glob(Wow::get("project/cookiePath") . "source/*.{selco,dat}", GLOB_BRACE);
            $data["countSourceCookies"] = count($sourceCookies);

            return $this->view($data);
        }



        function CinsiyetTespitAction() {
            if($this->request->method == "POST") {
                $lastUserID    = intval($this->request->data->lastUserID);
                $lastUserIDNew = NULL;
                $uUsers        = $this->db->query("SELECT uyeID,SUBSTRING_INDEX(SUBSTRING_INDEX(fullName, ' ', 1), ' ', -1) AS ad FROM uye WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(fullName, ' ', 1), ' ', -1)<>'' AND (gender IS NULL OR gender=3) AND uyeID>:uyeID LIMIT 80", array("uyeID" => $lastUserID));
                foreach($uUsers as $u) {
                    $lastUserIDNew = $u["uyeID"];
                    $fIsim         = $this->db->row("SELECT * FROM isimler WHERE isimler=:isimler AND cinsiyet<>'U'", array("isimler" => $u["ad"]));
                    if(!empty($fIsim)) {
                        $fGender = $fIsim["cinsiyet"] == "K" ? 2 : 1;
                        $this->db->query("UPDATE uye SET gender = :gender WHERE uyeID=:uyeID", array(
                            "uyeID"  => $u["uyeID"],
                            "gender" => $fGender
                        ));
                    }
                }
                $lastUserID  = $lastUserIDNew;
                $isCompleted = 0;
                if(empty($lastUserIDNew)) {
                    $lastUserID  = 0;
                    $isCompleted = 1;
                }

                $data = [
                    "lastUserID"  => $lastUserID,
                    "isCompleted" => $isCompleted
                ];

                return $this->json($data);
            }

            $data = $this->db->query("SELECT gender,COUNT(uyeID) 'toplamSayi' FROM uye GROUP BY gender ORDER BY toplamSayi DESC");

            return $this->view($data);
        }


    }