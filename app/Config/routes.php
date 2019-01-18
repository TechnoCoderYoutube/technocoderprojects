<?php
    return array(
        //Routes
        "UserDetailRoute" => array(
            "/user/@usernameid(/@action)",
            array(
                "prefix"     => "",
                "controller" => "User",
                "action"     => "Index"
            )
        ),
        "BlogDetailRoute" => array(
            "/blog/@seolink",
            array(
                "prefix"     => "",
                "controller" => "Blog",
                "action"     => "BlogDetail"
            )
        ),
        "BayiRoute"    => array(
            "/bayi(/@controller(/@action(/@id)))",
            array(
                "prefix"     => "Bayi",
                "controller" => "Home",
                "action"     => "Index"
            )
        ),
        "AdminRoute"    => array(
            "/admin(/@controller(/@action(/@id)))",
            array(
                "prefix"     => "Admin",
                "controller" => "Home",
                "action"     => "Index"
            )
        ),
        "DefaultRoute"    => array(
            "(/@controller(/@action(/@id)))",
            array(
                "prefix"     => "",
                "controller" => "Home",
                "action"     => "Index"
            )
        )
    );