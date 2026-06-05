<?php
// Функция делает сокет-запрос на сокет-сервер (для записи или извлечения данные из общей памяти сервера)


$file_name__ = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', __FILE__));

if(!defined('HOST') || !defined('PORT')){ // Запрет непосредственного запуска
    throw new ErrorException("Ошибка в ". $file_name__ .": не получилось задать хост и/или свободный порт. 1"); // 1 - работу прекращаем
}


// Функция-клиент направляет запрос на сокетный сервер
function socket_client($host, $port, $flag_mess_echo, $client_NAME, $client_DATA, $client_ACTION, $file_name__) {
    /*  $port           - порт для сокетов (например: 9999)
        $flag_mess_echo - true (если выводить вспомогательные сообщения)
                          false (если НЕ выводить)
        $client_NAME    - имя-идентификатор клиента, который через эту функцию будет делать запрос к сокетному серверу
        $client_DATA    - данные, которые клиент передает серверу
        $client_ACTION  - set (записать данные в кэш-память сервера)
                        - get (прочитать данные из кэш-памяти сервера)
    */

    if ($flag_mess_echo){
        ob_implicit_flush(true);
    }

    if ($flag_mess_echo) {
        $mes = "Попытка подключения клиента с именем ". $client_NAME. " к сокет-серверу $host:$port...\n";
        echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
    }

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        throw new ErrorException("Ошибка создания клиентского сокета: " . mb_convert_encoding(socket_strerror(socket_last_error()), 'utf-8', 'cp1251'). " 1"); // 1 - работу прекращаем
    }

    $error = 'Ошибка соединения в клиенте с именем '. $client_NAME .': в функции socket_connect(): '. mb_convert_encoding(socket_strerror(socket_last_error()), 'utf-8', 'cp1251'). '. 1';
    try{
    $result = @socket_connect($socket, $host, $port);
    if ($result === false) {
        throw new ErrorException($error); // 1 - работу прекращаем
    }
    }catch (ErrorException $e){
        throw new ErrorException($error); // 1 - работу прекращаем
    }

    if ($flag_mess_echo) {
        $mes = "Подключение установлено. Отправка данных...\n";
        echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
    }

// Устанавливаем таймаут: 10 секунд на чтение
    $timeout_sec = 10;
    $timeout_usec = 0; // микросекунды (опционально)
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array(
        'sec' => $timeout_sec,
        'usec' => $timeout_usec
    ));

    $message = $client_NAME . ':' . $client_DATA . ':' . $client_ACTION . "\n"; // Данные передаем в формате ИМЯКЛИЕНТА:ДАННЫЕ:to_do

    $bytesWritten = socket_write($socket, $message, strlen($message));
    if ($bytesWritten === false) {
        throw new ErrorException('Ошибка отправки данных в клиенте с именем '. $client_NAME. ': в функции socket_write().'. mb_convert_encoding(socket_strerror(socket_last_error($socket)), 'utf-8', 'cp1251' ). '. 1'); // 1 - работу прекращаем
    }

    if ($flag_mess_echo) {
        $mes = "Отправлено $bytesWritten байт данных.\n";
        echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
    }

// Теперь socket_read() будет ждать не дольше 10 секунд
    $response = socket_read($socket, 2048, PHP_NORMAL_READ);

    if ($response === false) {
        $error = socket_last_error($socket);
        if ($error === SOCKET_EAGAIN || $error === SOCKET_EWOULDBLOCK) {

            if ($flag_mess_echo) {
                $mes = "Таймаут: сервер не ответил за $timeout_sec секунд.\n";
                echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
            }

        } else {
            if ($flag_mess_echo) {
                 // +++  cp1251
                throw new ErrorException( "Ошибка чтения ответа сокет-сервера (в клиенте с именем ". $client_NAME. "): !" . mb_convert_encoding(socket_strerror($error), 'utf-8', 'cp1251'). ' 1'); // 1 - работу прекращаем
            }
        }
    } else {

        if ($flag_mess_echo) {
            $mes = "Клиентом получен ответ от сервера: '". trim($response). "'\n";
            echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
        }
    }

    socket_close($socket);

    if ($flag_mess_echo) {
        $mes = "Соединение клиента закрыто. Окончание работы ". $client_NAME . ' (файл '. $file_name__ .")\n";
        echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
    }
    /*  Возможные возвращаемые значения:
            OK\n        - Если данные успешно сохранились в массив сокетного сервера (set)
            0\n         - Если для заданного клиента нет сохраненных данных (get)
            строка\n    - Если данные есть и извлечены, то - строка (get).
    */
    return $response; // Ответ сервера
}
