<?php
    return array(

        //Project Variables
        "project"  => array(
            "cookiePath"        => "./app/Cookies/",
            "licenseKey"        => "SCRİPTCİYİZ-MERTCAN0233",
            "cronJobToken"      => "Cron-Job",
            "videoToken"        => "SCRİPTCİYİZ-MERTCAN0233",
            "saveToken"         => "SCRİPTCİYİZ-MERTCAN0233",
            "apiToken"          => "SCRİPTCİYİZ-MERTCAN0233",
            "commentLikeToken"  => "SCRİPTCİYİZ-MERTCAN0233",
            "canliToken"        => "SCRİPTCİYİZ-MERTCAN0233",
            "onlyHttps"         => FALSE,
            "adminPrefix"       => "/admin",
            "resellerPrefix"    => "/bayi",
            "memberLoginPrefix" => "/member"
        ),

        //App Variables
        "app"      => array(
            "theme"                 => "default",
            "layout"                => "layout/default",
            "language"              => "en",
            "base_url"              => NULL,
            "handle_errors"         => TRUE,
            "log_errors"            => FALSE,
            "router_case_sensitive" => TRUE
        ),


        //Database Variables
        "database" => array(
            "DefaultConnection"  => array(
                //mysql, sqlsrv, pgsql are tested connections and work perfect.
                "driver"   => "mysql",
                "host"     => "localhost",
                "port"     => "3306",
                "name"     => "DB_NAME",
                "user"     => "DB_USER",
                "password" => "DB_PASS"
            )
        )
    );