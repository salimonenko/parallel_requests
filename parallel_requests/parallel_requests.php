<?php

header('Content-type: text/html; charset=utf-8');


$path_FILES_Arr = array( // Массив относительных путей к файлам, которые будут запускаться параллельно путем обращения к ним через сокеты
                        '/TEST/parallel_requests/p0.php',
                        '/TEST/parallel_requests/p1.php'
);

$POST_data_Arr = array(
                     array('param1' => 'value1', 'param2' => 'value2'), // Данные для POST-запросов (соответственно построчно)
                     array('param3' => 'value3', 'param4' => 'value4')
);


echo '<pre>';

print_r(parallel_requests($path_FILES_Arr, $POST_data_Arr));


// Функция делает параллельные HTTP-запросы к заданным файлам-скриптам (при помощи сокетов). С учетом POST-параметров
function parallel_requests($path_FILES_Arr, $POST_data_Arr) {
/*  $path_FILES_Arr - массив относительных путей к запускаемым файлам
    $POST_data_Arr - массив POST-параметров
*/

// Функция создает заголовки запроса
    function DO_request_COMMON($postData) {
        $request_COMMON = "Host: " . $_SERVER['SERVER_NAME'] . "\r\n";
        $request_COMMON .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request_COMMON .= "Content-Length: " . strlen($postData) . "\r\n";
        $request_COMMON .= "Connection: Close\r\n\r\n";
        $request_COMMON .= $postData . "\r\n\r\n";

        return $request_COMMON;
    }

    $sockets_num = sizeof($path_FILES_Arr);

    $POST_data_Arr_num = sizeof($POST_data_Arr);

    if($sockets_num !== $POST_data_Arr_num){
        return array(-1, 'Неправильно заданы данные: размерности массивов относительных путей к файлам и POST-параметрам к ним не совпадают');
    }

    $sockets = array();
    $responses = array();


    $POST_Data_Arr = array();

    for($i=0; $i < $POST_data_Arr_num; $i++){ // Сооздаем строки POST-запросов
        $POST_Data_Arr[$i] = http_build_query($POST_data_Arr[$i]);
    }

/*******   ОТПРАВЛЯЕМ HTTP‑ЗАПРОС    *******/
// Создаем неблокирующие сокеты, записываем в них заголовки и POST-параметры и запускаем их
    for ($i = 0; $i < $sockets_num; $i++) {
        $sockets[$i] = stream_socket_client('tcp://' . $_SERVER['SERVER_NAME'] . ':80', $errno1, $errstr1, 5);
        if (!$sockets[$i]) {
            die("Ошибка подключения сокета " . $i . ": $errstr1 ($errno1)\n");
        }
// Переводим сокет в неблокирующий режим
        stream_set_blocking($sockets[$i], false);

        $responses[$i] = '';

        $request = "POST " . $path_FILES_Arr[$i] . " HTTP/1.0\r\n" . DO_request_COMMON($POST_Data_Arr[$i]);
// Отправляем HTTP‑запрос
        fwrite($sockets[$i], $request);
    }


    echo "Запрос отправлен. Ожидание ответа...\n";

// Основной цикл обработки событий
    $running = true;


    $startTime = microtime(true);
    $maxExecutionTime = 10;

    while ($running) {

        $read = array_filter($sockets, function ($sock) {
            return is_resource($sock); // Оставляем только валидные ресурсы
        });

// Завершаем цикл, если оба сокета закрыты
        if (!sizeof($read)) {
            $running = false;
            break;
        }

        $write = null;
        $except = null;

// Проверяем готовность сокетов к чтению
        $ready = stream_select($read, $write, $except, 0, 100000); // 0.1 сек таймаут

        if ($ready === false) { // Если ошибка

            return array(-1, socket_strerror(socket_last_error()));

        } elseif ($ready > 0) { // Обрабатываем каждый готовый сокет

            foreach ($read as $socket) {
                for ($i = 0; $i < $sockets_num; $i++) {

                    if (isset($sockets[$i])) {
                        if ($socket === $sockets[$i]) {
                            $chunk = fread($sockets[$i], 1024);

                            if ($chunk === false || $chunk === '') { // Соединение закрыто или ошибка
                                echo "\nСокет " . $i . " закрыт\n";
                                fclose($sockets[$i]);
                                unset($sockets[$i]);

                            } else {
                                $responses[$i] .= $chunk;
                            }
                        }
                    }
                }
                flush();
            }
        } else {
//        echo "."; // Визуальный индикатор ожидания
        }

        // Проверка общего таймаута
        if (microtime(true) - $startTime > $maxExecutionTime) {
            echo "\nТаймаут ожидания ответа!\n";
            $running = false;
        }

        usleep(10);
    }

// Закрываем оставшиеся сокеты
    for ($i = 0; $i < $sockets_num; $i++) {
        if (isset($sockets[$i])) fclose($sockets[$i]);
        $responses[$i] = extractBody($responses[$i]); // Извлекаем тело ответа (без заголовков ответа и пустой строки)
    }

    return $responses;
}


// Функция убирает заголовки ответа и пустую строку, оставляет только само сообщение
function extractBody($response) {
    // Ищем разделитель \r\n\r\n (конец заголовков)
    $separator = "\r\n\r\n";
    $pos = strpos($response, $separator);

    if ($pos !== false) {
        // Возвращаем всё после разделителя
        return substr($response, $pos + strlen($separator));
    }

    // Если разделителя нет, ищем \n\n (альтернативный вариант)
    $altSeparator = "\n\n";
    $altPos = strpos($response, $altSeparator);
    if ($altPos !== false) {
        return substr($response, $altPos + strlen($altSeparator));
    }

    return -1; // Если ничего не найдено
}
