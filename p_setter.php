<?php
// Скрипт ДОБАВЛЯЕТ строку данных в массив (общую для скриптов память) сокет-сервера
require_once 'errors_exceptions.php';
require_once 'parameters.php';

$file_name__ = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', __FILE__));

$client_NAME = 'CLIENT1'; // Имя клиента, который будет подключаться к сокет-серверу

// Форимруем строку данных для последующей записи в память (массив) сервера
$client_DATA = '';
if(isset($_POST)){
    foreach ($_POST as $item => $req){
        $client_DATA .= $item. '=>'. $req. ";"; // Здесь НЕ ДОЛЖНО быть переносов строк (\n)
    }
}

$client_ACTION = 'set'; // Команда ЗАПИСИ данных в память сокет-сервера

require_once 'socket_client.php';




$t0 = microtime(true);


while (microtime(true) - $t0 < 1){

    $h = microtime(true);
    $rez = socket_client(HOST, PORT, true, $client_NAME, $client_DATA. $h, $client_ACTION, $file_name__);

    if(trim($rez) === 'OK'){

        $mes = 'Скрипт <b>'. $file_name__ .'</b> сообщает: '. "Клиент с именем ". $client_NAME. " успешно отправил запрос на сокет-сервер и записал в память сервера следующие данные: ". $client_DATA. $h. "\n\n";
        echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
    }
//flush();

    usleep(200000);
}
