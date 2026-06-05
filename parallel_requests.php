<?php
// Скрипт запускает параллельно (и асинхронно) сокет-сервер, а также два других скрипта. Последние взаимодействуют с сокет-сервером, обмениваясь с ним данными
// Скрипт прекращает работу либо по таймауту, либо когда окончат работу ВСЕ вызванные им скрипты (точнее, когда закроются все открытые им сокеты)
header('Content-type: text/html; charset=utf-8');

require_once 'errors_exceptions.php';
require_once 'parameters.php';

echo '<pre>';

$file_name__ = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', __FILE__));

// 1. Проверка подключения модуля с параметрами, на всякий случай
if(!defined('HOST') || !defined('PORT')){
    throw new ErrorException("Ошибка в ". $file_name__ .": не получилось задать хост и/или порт. 1"); // 1 - работу прекращаем
}


// 2. Задаем параметры
$path_FILES_Arr = array( // Массив относительных путей к файлам, которые будут запускаться параллельно путем обращения к ним через сокеты
    '/TEST/parallel_requests/socket_server.php',
    '/TEST/parallel_requests/p_setter.php',
    '/TEST/parallel_requests/p_getter.php'
);

$POST_data_Arr = array( // Данные для POST-запросов (соответственно, построчно)
    array('port' => PORT, 'host' => HOST), // Настройки сокет-сервера
    array('param1' => 'value1', 'param2' => 'value2'), // Просто данные для примера
    array()
);

$maxExecutionTime = 10;

// **************************************************************************************

// 3. Создаем лог-файл
file_put_contents('info.log', 'Здесь приведены результаты выводов в файл всех скриптов в режиме реального времени:' . PHP_EOL . PHP_EOL);

// 4. Делаем запрос на асинхронный запуск скриптов
$parallel_requests_Arr = parallel_requests($path_FILES_Arr, $POST_data_Arr, $file_name__, $maxExecutionTime);
$error_mes_Arr = array();

// 5. Разбираем результаты запроса
foreach ($parallel_requests_Arr as $reque) {
    if (is_array($reque)) {
        if ($reque[0] === -1) { // В случае ошибки
            $error_mes_Arr[] = $reque[1];
        }
    }
}

$mes = '';
if ($error_mes_Arr) { // Если были критические ошибки (которые сделали невозможным запуск функции parallel_requests() ), то выводим их
    $mes .= implode(PHP_EOL, $error_mes_Arr) . PHP_EOL;
    $mes .= 'В результате этих ошибок не получилось отправить POST-запросы скриптам ' . implode(PHP_EOL, $path_FILES_Arr);

} else { // Если критич. ошибок не было
    $mes .= "<p style='font-size: 120%; text-decoration: underline'>Результаты запуска указанных скриптов (сводно, в отдельности по каждому из них):</p>\n";

    $i = 0;
    foreach ($parallel_requests_Arr as $item => $reque) {
        $mes .= ++$i . '. Для <b>' . $item . "</b>:\n";
        $mes .= $reque . "\n\n";
    }
}

echo $mes;
file_put_contents('info.log', $mes, FILE_APPEND);
$mes = '';


// Функция делает параллельные HTTP-запросы к заданным файлам-скриптам (при помощи сокетов). С учетом POST-параметров
function parallel_requests($path_FILES_Arr, $POST_data_Arr, $file_name__, $maxExecutionTime) {
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

// 1. Определяем размерности массивов параметров
    $sockets_num = sizeof($path_FILES_Arr);
    $POST_data_Arr_num = sizeof($POST_data_Arr);
// 2. Убеждаемся, что эти размерности совпадают
    if ($sockets_num !== $POST_data_Arr_num) {
        throw new ErrorException("Ошибка в " . $file_name__ . ": неправильно заданы данные: размерности массивов относительных путей к файлам и POST-параметрам к ним не совпадают. 1"); // 1 - работу прекращаем
    }

    $sockets_Arr = array();
    $responses_Arr = array();

// 3. Создаем строки POST-запросов
    $POST_http_query_Arr = array();

    for ($i = 0; $i < $POST_data_Arr_num; $i++) {
        $POST_http_query_Arr[$i] = http_build_query($POST_data_Arr[$i]);
    }

    /*******    4. ОТПРАВЛЯЕМ HTTP‑ЗАПРОС через сокет    *******/
// 4. Создаем неблокирующие сокеты, записываем в них заголовки и POST-параметры и запускаем их. В итоге запустятся скрипты, имена которых заданы в  $path_FILES_Arr
    for ($i = 0; $i < $sockets_num; $i++) {
        $path = $path_FILES_Arr[$i];
        $sockets_Arr[$path] = stream_socket_client('tcp://' . $_SERVER['SERVER_NAME'] . ':80', $errno1, $errstr1, 5);
        if (!$sockets_Arr[$path]) {
            throw new ErrorException("Ошибка в " . $file_name__ . "Ошибка подключения сокета для отправки POST-запроса скрипту " . $path . ": $errstr1 ($errno1). 1"); // 1 - работу прекращаем
        }
// Переводим сокет в неблокирующий режим
        stream_set_blocking($sockets_Arr[$path], false);

        $responses_Arr[$path] = '';

        $request = "POST " . $path . " HTTP/1.0\r\n" . DO_request_COMMON($POST_http_query_Arr[$i]);
// Отправляем HTTP‑запрос
        fwrite($sockets_Arr[$path], $request);
    }


    $mes = "Отправлены POST-запросы через сокеты следующим скриптам:\n<b>" . implode(PHP_EOL, $path_FILES_Arr) . "</b>\n Ожидание ответов...\n\n";
    echo $mes;
    file_put_contents('info.log', $mes, FILE_APPEND);
    $mes = '';

// 5. Основной цикл обработки событий (для чтения данных, присланных сокет-сервером, из сокетов)
    $running = true;

    $startTime = microtime(true);


    while ($running) {
// 5.1. Выявляем валидные (НЕ null) ресурсы
        $read = array_filter($sockets_Arr, function ($sock) {
            return is_resource($sock); // Оставляем только валидные ресурсы
        });

// 5.2. Завершаем цикл, если ВСЕ сокеты закрыты
        if (!sizeof($read)) {
            $running = false;
            break;
        }

        $write = null;
        $except = null;

// 5.3. Проверяем готовность сокетов к чтению
        $ready = stream_select($read, $write, $except, 0, 100000); // 0.1 сек таймаут

        if ($ready === false) { // Если ошибка

            return array(-1, socket_strerror(socket_last_error()));

        } elseif ($ready > 0) {
// 5.3. Обрабатываем каждый готовый сокет
            foreach ($read as $socket) {
                for ($i = 0; $i < $sockets_num; $i++) {
                    $path = $path_FILES_Arr[$i];

                    if (isset($sockets_Arr[$path])) {
                        if ($socket === $sockets_Arr[$path]) {
// 5.4. Получаем вывод вызванного скрипта (то, что он выводит через echo, print_r и т.п.).
                            $chunk = fread($sockets_Arr[$path], 8192);

                            if ($chunk === false) { // Если ошибка
                                $mes .= "\nОшибка в работе сокета, вызвавшего скрипт " . $path . ". Этот сокет закрыт.\n";
                                fclose($sockets_Arr[$path]);
                                unset($sockets_Arr[$path]);

                            } elseif ($chunk === '') { // Если данных больше нет, нужно закрыть соединение
                                $mes .= "\nСокет, вызвавший скрипт " . $path . ", корректно закрыт.\n";
                                fclose($sockets_Arr[$path]);
                                unset($sockets_Arr[$path]);
                            } else {
                                $responses_Arr[$path] .= $chunk;
                            }

                            echo $mes;
                            file_put_contents('info.log', $mes, FILE_APPEND);
                            $mes = '';
                        }
                    }
                }
                flush();
            }
        } else {
//        echo "."; // Визуальный индикатор ожидания (при AJAX все равно не будет выводиться)
        }

// 5.5. Проверка общего таймаута
        if (microtime(true) - $startTime > $maxExecutionTime) {
            $mes .= "\nТаймаут ожидания ответа в ". basename(__FILE__) ."!\n";
            echo $mes;
            file_put_contents('info.log', $mes, FILE_APPEND);
            $mes = '';

            $running = false;
        }

        usleep(1000); // Чтобы сильно не нагружать процессор
    } // Конец цикла  while($running)

// 6. Закрываем оставшиеся сокеты (если вдруг остались)
    for ($i = 0; $i < $sockets_num; $i++) {
        $path = $path_FILES_Arr[$i];
        if (isset($sockets_Arr[$path])) fclose($sockets_Arr[$path]);
        $responses_Arr[$path] = extractBody($responses_Arr[$path]); // Извлекаем тело ответа (без заголовков ответа и пустой строки)
    }

    return $responses_Arr;
}


// Функция убирает из ответа сервера заголовки ответа и пустую строку, оставляет только само сообщение
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
