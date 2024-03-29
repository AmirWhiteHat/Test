<?php


set_time_limit(0);

define('TOKEN', '947669011:AAH1stZX35WKILKf8JaV30uciQ_qKJDrJI0');
define('UPDATES_OFFSET_FILE', __DIR__.'/'.md5(TOKEN));

function apiRequest($method, $parameters = [])
{
    foreach ($parameters as $key => &$val) {
        if (is_array($val)) {
            $val = json_encode($val);
        }
    }
    $ch = curl_init('https://api.telegram.org/bot'.TOKEN.'/'.$method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

if (!file_exists(UPDATES_OFFSET_FILE)) {
    file_put_contents(UPDATES_OFFSET_FILE, 0);
}

$offset = file_get_contents(UPDATES_OFFSET_FILE);

while (true) {
    $updates = apiRequest('getUpdates', ['offset' => $offset]);
    $updates = json_decode($updates, true);
    $updates = $updates['result'];

    if (!empty($updates)) {
        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;
            $pid = pcntl_fork();
            if ($pid === -1) {
                die();
            } elseif ($pid) {
                // Created child with PID $pid
            } else {
                if (isset($update['message'])) {
                    $message = $update['message'];
                    $message_id = $message['message_id'];
                    $chat_id = $message['chat']['id'];
                    if (isset($message['text'])) {
                        $text = $message['text'];
                        if ($text == '/start') {
                            apiRequest('sendMessage', ['chat_id' => $chat_id, 'text' => "Welcome to @AvailabilityCheckerBot!\n\nThis bot can check availability of domain names in different TLDs.\n\nSend me a domain name to check availability.\n\nMade with ❤ by @Radio_Nima", 'reply_to_message_id' => $message_id]);
                        } 
                    }
                }
                die();
            }
        }

        file_put_contents(UPDATES_OFFSET_FILE, $offset);
    }
}
