<?php

    namespace App\Controllers\Admin\Plugins;

    use App\Models\Notification;
    use Wow\Net\Response;
    use App\Controllers\Admin\BaseController;
    use App\Libraries\InstagramReaction;
    use Wow;

    class AutoCommentController extends BaseController {

        function onActionExecuting() {
            if(($actionResponse = parent::onActionExecuting()) instanceof Response) {
                return $actionResponse;
            }
            //Üye girişi kontrolü.
            if(($pass = $this->middleware("logged")) instanceof Response) {
                return $pass;
            }
        }

        function IndexAction($page = 1) {
            if(!intval($page) > 0) {
                $page = 1;
            }

            $limitCount = 50;
            $limitStart = (($page * $limitCount) - $limitCount);

            $sqlWhere = "WHERE 1=1";
            $arrSorgu = array();

            $q = $this->request->query->q;
            if(!empty($q)) {
                $sqlWhere      .= " AND (userName LIKE :q)";
                $arrSorgu["q"] = "%" . $q . "%";
            }


            $isActive = $this->request->query->isActive;
            if(!is_null($isActive) && $isActive !== "") {
                $sqlWhere .= $isActive == 1 ? " AND commentCountLeft > 0" : " AND commentCountLeft = 0";
            }

            $data = $this->db->query("SELECT * FROM plugin_autocomment_gonderi " . $sqlWhere . " ORDER BY id DESC LIMIT :limitStart,:limitCount", array_merge($arrSorgu, array(
                "limitStart" => $limitStart,
                "limitCount" => $limitCount
            )));


            $totalRows    = $this->db->single("SELECT COUNT(id) FROM plugin_autocomment_gonderi " . $sqlWhere, $arrSorgu);
            $previousPage = $page > 1 ? $page - 1 : NULL;
            $nextPage     = $totalRows > $limitStart + $limitCount ? $page + 1 : NULL;
            $totalPage    = ceil($totalRows / $limitCount);
            $endIndex     = ($limitStart + $limitCount) <= $totalRows ? ($limitStart + $limitCount) : $totalRows;
            $pagination   = array(
                "recordCount"  => $totalRows,
                "pageSize"     => $limitCount,
                "pageCount"    => $totalPage,
                "activePage"   => $page,
                "previousPage" => $previousPage,
                "nextPage"     => $nextPage,
                "startIndex"   => $limitStart + 1,
                "endIndex"     => $endIndex
            );
            $this->view->set("pagination", $pagination);

            return $this->view($data);
        }


        function AddAction($id = NULL) {

            if($this->request->method == "POST") {
                $this->instagramReaction = new InstagramReaction($this->findAReactionUser());
                switch($this->request->query->formType) {
                    case "findMediaID":
                        $mediaData = $this->instagramReaction->getMediaData($this->request->data->mediaUrl);
                        if(!$mediaData) {
                            return $this->notFound();
                        } else {
                            $mediaID = $mediaData["media_id"];

                            return $this->redirectToUrl(Wow::get("project/adminPrefix") . "/plugins/auto-comment/add/" . $mediaID);
                        }
                        break;
                    case "send":
                        $arrErrors = array();

                        $yorumlarg = preg_split("/\\r\\n|\\r|\\n/", $this->request->data->yorum);
                        $yorumlar  = [];
                        foreach($yorumlarg as $yorum) {
                            if(!empty($yorum)) {
                                $yorumlar[] = trim($yorum);
                            }
                        }
                        $adet = count($yorumlar);
                        if(!is_array($yorumlar) || empty($yorumlar)) {
                            $arrErrors[] = "En az 1 yorum tanımlamalısınız.";
                        }


                        if(empty($arrErrors)) {
                            $findMedia = $this->instagramReaction->objInstagram->getMediaInfo($this->request->data->mediaID);
                            if($findMedia["status"] == "fail") {
                                $arrErrors[] = "Media bulunamadı! Silinmiş olabilir.";
                            }
                        }

                        if(empty($arrErrors)) {

                            $gender = NULL;
                            if(intval($this->request->data->gender) > 0) {
                                $gender = intval($this->request->data->gender) == 2 ? 2 : 1;
                            }
                            $this->db->query("INSERT INTO plugin_autocomment_gonderi (mediaID,mediaCode,userID,userName,imageUrl,allComments,commentCountTotal,commentCountLeft,minuteDelay,krediDelay,gender) VALUES(:mediaID,:mediaCode,:userID,:userName,:imageUrl,:allComments, :commentCountTotal, :commentCountLeft, :minuteDelay, :krediDelay,:gender)", array(
                                "mediaID"           => $this->request->data->mediaID,
                                "mediaCode"         => $this->request->data->mediaCode,
                                "userID"            => $this->request->data->userID,
                                "userName"          => $this->request->data->userName,
                                "imageUrl"          => $this->request->data->imageUrl,
                                "allComments"       => json_encode($yorumlar),
                                "commentCountTotal" => $adet,
                                "commentCountLeft"  => $adet,
                                "minuteDelay"       => $this->request->data->minuteDelay,
                                "krediDelay"        => $this->request->data->krediDelay,
                                "gender"            => $gender
                            ));

                            $objNotification             = new Notification();
                            $objNotification->type       = $objNotification::PARAM_TYPE_SUCCESS;
                            $objNotification->title      = "Oto Yorum Eklendi.";
                            $objNotification->messages[] = "Durumu aşağıdaki listeden takip edebilirsiniz.";
                            $this->notifications[]       = $objNotification;

                            return $this->redirectToUrl(Wow::get("project/adminPrefix") . "/plugins/auto-comment");
                        }

                        $objNotification           = new Notification();
                        $objNotification->type     = $objNotification::PARAM_TYPE_DANGER;
                        $objNotification->title    = "Ekleme Başarısız !";
                        $objNotification->messages = $arrErrors;
                        $this->notifications[]     = $objNotification;

                        return $this->redirectToUrl($this->request->referrer);
                        break;
                }
            }

            if(!is_null($id)) {
                $this->instagramReaction = new InstagramReaction($this->findAReactionUser());
                $media                   = $this->instagramReaction->objInstagram->getMediaInfo($id);
                if($media["status"] != "ok") {
                    return $this->notFound();
                }
                $this->view->set("media", $media);
            }

            return $this->view();
        }


        function EditAction($id) {
            $data = $this->db->row("SELECT * FROM plugin_autocomment_gonderi WHERE id=:id", ["id" => $id]);
            if(empty($data)) {
                return $this->notFound();
            }

            return $this->partialView($data);
        }

    }