<?php

require_once "utils/Rcon.php";
require_once "utils/Config.php";
require_once "utils/Utils.php";

mb_internal_encoding("UTF-8");
mb_http_output("UTF-8");

$data = json_decode(file_get_contents("php://input"), true);
if ($data["secret"] !== Config::SECRET) return;

switch ($data["type"]) {
    case "confirmation":
        echo Config::CONFIRMATION;
        break;
    
    case "message_new":
        Utils::echoOk();
        
        $base = new mysqli(Config::MYSQL_IP, Config::MYSQL_USERNAME, Config::MYSQL_PASSWORD, Config::MYSQL_DATABASE);
        $base->query("SET NAMES utf8 COLLATE utf8_general_ci");
        
        $userId = $data["object"]["message"]["from_id"];
        $peerId = $data["object"]["message"]["peer_id"];
        
        $msg = $data["object"]["message"]["text"];
        $cmds = explode(" ", $msg);
        
        switch ($cmds[0]) {
            case "!config":
                switch ($cmds[1] ?? -1) {
                    case "new":
                    case "add":
                        if (!in_array($userId, Config::MODERATOR_IDS)) return;
                        
                        if (!isset($cmds[3])) {
                            Utils::sendMessage("🔥RCON-BOT🔥\nВводите: !config add @guest <role>", $peerId);
                            return;
                        }
                        $type = mb_strtolower($cmds[3]);
                        if ($type !== "donate" && $type !== "moder" && $type !== "admin") {
                            Utils::sendMessage("🔥RCON-BOT🔥\nУказанный тип пользователя не существует! Возможные: donate, moder, admin", $peerId);
                            return;
                        }
                        
                        $id = Utils::getNumberId($cmds[2]);
                        $info = $base->query("SELECT * FROM `users` WHERE id = '" . $id . "'");
                        if ($info->num_rows !== 0) {
                            Utils::sendMessage("🔥RCON-BOT🔥\nЭтот пользователь и так имеет доступ, сначала нужно их забрать: !config del", $peerId);
                            return;
                        }
                        
                        $result = $base->prepare("INSERT INTO `users` (id, type) VALUES (?, ?)");
                        $result->bind_param("is", $id, $type);
                        $result->execute();
                        
                        Utils::sendMessage("🔥RCON-BOT🔥\nПользователю успешно выданы права с доступом " . $type, $peerId);
                        break;
                    
                    case "delete":
                    case "remove":
                    case "del":
                        if (!in_array($userId, Config::MODERATOR_IDS)) return;
                        
                        if (!isset($cmds[2])) {
                            Utils::sendMessage("🔥RCON-BOT🔥\nВводите: !config del @guest", $peerId);
                            return;
                        }
                        
                        $id = Utils::getNumberId($cmds[2]);
                        $info = $base->query("SELECT * FROM `users` WHERE id = '" . $id . "'");
                        if ($info->num_rows < 1) {
                            Utils::sendMessage("🔥RCON-BOT🔥\nУ этого пользователя нету прав, чтобы их забирать!", $peerId);
                            return;
                        }
                        
                        $result = $base->prepare("DELETE FROM `users` WHERE id = ?");
                        $result->bind_param("i", $id);
                        $result->execute();
                        
                        Utils::sendMessage("🔥RCON-BOT🔥\nВы успешно забрали доступ у пользователя", $peerId);
                        break;
                    
                    case "list":
                        if (!in_array($userId, Config::MODERATOR_IDS)) return;
                        
                        $msg = "";
                        $result = $base->query("SELECT * FROM `users`");
                        while (($info = $result->fetch_array(MYSQLI_ASSOC)))
                            $msg .= "\n* @id" . $info["id"] . " - " . $info["type"];
                        
                        if (!$msg) {
                            Utils::sendMessage("🔥RCON-BOT🔥\nНе найдено никаких пользователей с доступом!", $peerId);
                            return;
                        }
                        Utils::sendMessage("🔥RCON-BOT🔥\nСписок пользователей с доступом:" . $msg, $peerId);
                        break;
                    
                    default:
                        Utils::sendMessage("🔥RCON-BOT🔥\nУказанное действие не найдено, вводите: !config add/del/list", $peerId);
                        break;
                }
                break;
            
            case "!addserver":
                if (!in_array($userId, Config::MODERATOR_IDS)) return;
                
                if (!isset($cmds[4])) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nВводите: !addserver <server> <ip> <port> <passsword>", $peerId);
                    return;
                }
                if (strlen($cmds[4]) < 6) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nПароль должен состоять минимум из 6 символов, подумайте о безопасности!", $peerId);
                    return;
                }
                unset($cmds[0]);
                $cmds[1] = str_replace("#", "", $cmds[1]);
                
                $result = $base->prepare("INSERT INTO `servers` (server, ip, port, password) VALUES (?, ?, ?, ?)");
                $result->bind_param("ssis", ...$cmds);
                $result->execute();
                
                Utils::sendMessage("🔥RCON-BOT🔥\nСервер " . $cmds[1] . " добавлен!", $peerId);
                break;
            
            case "!remserver":
                if (!in_array($userId, Config::MODERATOR_IDS)) return;
                
                if (!isset($cmds[1])) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nВводите !remserver <server>", $peerId);
                    return;
                }
                $cmds[1] = str_replace("#", "", $cmds[1]);
                
                $result = $base->query("SELECT * FROM `servers` WHERE server = '" . $cmds[1] . "'");
                if ($result->num_rows < 1) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nДанный сервер не существует!", $peerId);
                    return;
                }
                
                $result = $base->prepare("DELETE FROM `servers` WHERE server = ?");
                $result->bind_param("s", $cmds[1]);
                $result->execute();
                
                Utils::sendMessage("🔥RCON-BOT🔥\nСервер " . $cmds[1] . " удален!", $peerId);
                break;
            
            case "!servers":
                if (!in_array($userId, Config::MODERATOR_IDS)) return;
                
                $msg = "";
                $result = $base->query("SELECT * FROM `servers`");
                while (($server = $result->fetch_array(MYSQLI_ASSOC))) {
                    $pass = $server["password"];
                    $pass = str_repeat("*", strlen($pass) - 4) . "" . substr($pass, strlen($pass) - 4);
                    $msg .= "\n#" . $server["server"] . " - " . $server["ip"] . ":" . $server["port"] . ", " . $pass;
                }
                if (!$msg) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nНе найдено никаких серверов!", $peerId);
                    return;
                }
                
                Utils::sendMessage("🔥RCON-BOT🔥\nСписок серверов:" . $msg, $peerId);
                break;
            
            case "!peer":
                if (!in_array($userId, Config::MODERATOR_IDS)) return;
                Utils::sendMessage("PEED ID: " . $peerId, $peerId);
                break;
            
            case "!help":
                Utils::sendMessage("🔥RCON-BOT🔥\n!help - узнать справку о командах\n#1 <command> - отправить команду на сервер" . (in_array($userId, Config::MODERATOR_IDS) ? "\n!config add - выдать права пользователю\n!config del - забрать права у пользователя\n!config list - список пользователей\n!addserver <server> <ip> <port> <password> - добавить сервер\n!remserver <server> - удалить сервер\n!servers - узнать список серверов" : ""), $peerId);
                break;
            
            default:
                $user = $base->query("SELECT * FROM `users` WHERE id = '" . $userId . "'");
                if ($user->num_rows < 1 && !in_array($userId, Config::MODERATOR_IDS))
                    break;
                
                if ($cmds[0]{0} !== "#") return;
                
                $alias = str_replace("#", "", $cmds[0]);
                
                $result = $base->prepare("SELECT * FROM `servers` WHERE server = ?");
                $result->bind_param("s", $alias);
                $result->execute();
                $result = $result->get_result();
                if ($result->num_rows < 1) return;
                
                if (!isset($cmds[1])) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nВводите " . $cmds[0] . " <command>", $peerId);
                    break;
                }
                
                $user = $user->fetch_array(MYSQLI_ASSOC);
                if ($user["type"] !== "admin" && !in_array($cmds[1], Config::COMMANDS[$user["type"]]) && !in_array($userId, Config::MODERATOR_IDS)) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nНету прав!", $peerId);
                    break;
                }
                $args = $cmds;
                unset($args[0]);
                
                $temp = implode(" ", $args);
                
                $result = $result->fetch_array(MYSQLI_ASSOC);
                $rcon = new Rcon($result["ip"], $result["port"], $result["password"], 3);
                if (!$rcon->connect()) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nНе удалось подключиться к серверу " . $cmds[0] . " (Попытайтесь позже)", $peerId);
                    break;
                }
                if (!($result = $rcon->sendCommand($temp))) {
                    Utils::sendMessage("🔥RCON-BOT🔥\nОшибка отправления!", $peerId);
                    break;
                }
                $result = Utils::clean($result);
                Utils::sendMessage("🔥RCON-BOT🔥\nКоманда '" . $temp . "' отправлена!\n" . $result, $peerId);
                break;
        }
        break;
}