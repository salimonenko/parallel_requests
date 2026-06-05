<?php
// Скрипт ИЗВЛЕКАЕТ строку данных из массива (общей для скриптов памяти) сокет-сервера
require_once 'errors_exceptions.php';
require_once 'parameters.php';

$file_name__ = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', __FILE__));

$client_NAME = 'CLIENT1'; // Имя клиента, который будет подключаться к сокет-серверу

$client_DATA = '';
$client_ACTION = 'get'; // Команда ИЗВЛЕЧЕНИЯ данных из памяти сокет-сервера

require_once 'socket_client.php';




$t0 = microtime(true);


while (microtime(true) - $t0 < 1) {

    $rez = socket_client(HOST, PORT, true, $client_NAME, $client_DATA . microtime(true), $client_ACTION, $file_name__);

    if (trim($rez) !== '0') {

        $mes = 'Скрипт <b>' . $file_name__ . '</b> сообщает: ' . "Клиент с именем " . $client_NAME . " успешно отправил запрос на сокет-сервер и извлек из памяти сервера следующие данные: " . trim($rez) . "\n\n";
    } else {
        $mes = 'Скрипт <b>' . $file_name__ . '</b> сообщает: ' . "Клиент с именем " . $client_NAME . " успешно отправил запрос на сокет-сервер, но извлечь данные из памяти сервера не получилось, т.к. их там не оказалось\n\n";
    }

    echo $mes;
    file_put_contents('info.log', $mes, FILE_APPEND);
//flush();

    usleep(500000);
}
