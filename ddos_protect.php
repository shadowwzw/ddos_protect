<?php
const DDOS_PROTECT_MAX_COUNT = 15;
const DDOS_PROTECT_MAX_TIME = 1; // in seconds (Максимальное время между запросами)
const DDOS_PROTECT_RESET_COUNT_TIME = 15; // in seconds (Время сброса счетчика) (DDOS_PROTECT_RESET_COUNT_TIME > DDOS_PROTECT_MAX_TIME)

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
var_dump($redis->ping());
$client_ip = $_SERVER['REMOTE_ADDR'];
echo $client_ip;
//echo $_SERVER['REQUEST_URI'];
$now_unix_time = time();

$redis->hSetNx($client_ip, 'last', $now_unix_time);
$redis->hSetNx($client_ip, 'count', 0);

$current_last = $redis->hGet($client_ip, 'last');
if($now_unix_time - $current_last > DDOS_PROTECT_RESET_COUNT_TIME){
    $redis->hSet($client_ip, 'last', $now_unix_time);
    $redis->hSet($client_ip, 'count', 0);
    goto go_next;
}

$current_count = $redis->hGet($client_ip, 'count');
if($current_count > DDOS_PROTECT_MAX_COUNT){
    RenderPageNoticeAndExit(DDOS_PROTECT_RESET_COUNT_TIME);
}

if($now_unix_time - $current_last > DDOS_PROTECT_MAX_TIME){
    $redis->hSet($client_ip, 'count', ++$current_count);
}

function RenderPageNoticeAndExit($reset_count_time) {
    echo <<<DOC
    <html>
        <head>
            <meta charset='UTF-8'>
        </head>
        <body>
            <h1>Вы посылаете слишком много запросов! Подождите <span id="seconds">{$reset_count_time}</span> секунд.</h1>
        <script>
            setInterval(function() {
                if(document.getElementById('seconds').innerHTML <= 0) location.reload();
                else{
                    console.log(document.getElementById('seconds').innerHTML);
                    document.getElementById('seconds').innerHTML = document.getElementById('seconds').innerHTML - 1;    
                }
            }, 1000);
        </script>
        </body>
    </html>
DOC;
    exit();
}

go_next:
// do something