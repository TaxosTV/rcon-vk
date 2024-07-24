<?php
class Config
{
    
    /** @var string */
    const TOKEN = "";
    /** @var string */
    const CONFIRMATION = "24bfb1f2";
    /** @var string */
    const API_VERSION = "5.131";
    /** @var string */
    const SECRET = "sjhg09541LGa21k1";
    
    /** @var string */
    const MYSQL_IP = "127.0.0.1";
    /** @var string */
    const MYSQL_USERNAME = "";
    /** @var string */
    const MYSQL_PASSWORD = "";
    /** @var string */
    const MYSQL_DATABASE = "";
    
    /** @var array */
    const MODERATOR_IDS = [
        94456799,
        384016589
    ];
    
    /** @var array */
    const COMMANDS = [
        "donate" => [
            "help"
        ],
        "moder" => [
            "list"
        ]
    ];
}