<?php

class Utils
{
    
    /**
     * @param string $msg
     * @param int $peerId
     */
    public static function sendMessage(string $msg, int $peerId): void
    {
        $request_params = [
            "message" => $msg,
            "peer_id" => $peerId,
            "access_token" => Config::TOKEN,
            "v" => Config::API_VERSION,
            "random_id" => rand(1, time())
        ];
        $get_params = http_build_query($request_params);
        file_get_contents("https://api.vk.com/method/messages.send?" . $get_params);
    }
    
    /**
     * @param string $text
     * @return string
     */
    public static function clean(string $text): string
    {
        return str_replace("\xc2\xa7", "", preg_replace(["/" . "\xc2\xa7" . "[0123456789abcdefklmnor]/", "/\x1b[\\(\\][[0-9;\\[\\(]+[Bm]/"], "", $text));
    }
    
    /**
     * @param string $id
     * @return mixed|string
     */
    public static function getNumberId(string $id)
    {
        if (str_replace("@", "", $id) != $id) {
            $ex = explode("|", str_replace("[id", "", $id));
            return $ex[0];
        }
        return $id;
    }
    
    public static function echoOk(): void
    {
        ignore_user_abort(true);
        set_time_limit(0);
        
        ob_start();
        
        echo "OK";
        
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        
        ob_end_flush();
        ob_flush();
        flush();
    }
}