<?php

require __DIR__.'/simple-whois-lookup.php';

set_time_limit(0);

define('TOKEN', '529646499:AAGy3cu9mZFd_37a5VyzW5YbxIpJkiCkvHI');
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
                        elseif (preg_match('/^([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])(\.([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]))*$/', $text)) {
                            $sent = apiRequest('sendMessage', ['chat_id' => $chat_id, 'text' => 'Checking availability of domain name...', 'reply_to_message_id' => $message_id]);
                            $sent = json_decode($sent, true);
                            $sent = $sent['result']['message_id'];
                            apiRequest('editMessageText', ['chat_id' => $chat_id, 'message_id' => $sent, 'text' => $text.'.ir: '.isDomainAvailable($text.'.ir')."\n".$text.'.com: '.isDomainAvailable($text.'.com')."\n".$text.'.net: '.isDomainAvailable($text.'.net')."\n".$text.'.org: '.isDomainAvailable($text.'.org')."\n".$text.'.info: '.isDomainAvailable($text.'.info')."\n".$text.'.co: '.isDomainAvailable($text.'.co')."\n".$text.'.biz: '.isDomainAvailable($text.'.biz')."\n".$text.'.xyz: '.isDomainAvailable($text.'.xyz')."\n".$text.'.tk: '.isDomainAvailable($text.'.tk')]);
                        }
                        else {
                            apiRequest('sendMessage', ['chat_id' => $chat_id, 'text' => 'Invalid domain name!', 'reply_to_message_id' => $message_id]);
                        }
                    }
                }
                die();
            }
        }

        file_put_contents(UPDATES_OFFSET_FILE, $offset);
    }
}
