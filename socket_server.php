<?php
// Сокет-сервер создает массив, в который другие скрипты могут записывать данные или извлекать оттуда данные. Этот сервер работает либо до момента исключения, либо до истечения таймаута

header('Content-type: text/html; charset=utf-8');


require_once 'errors_exceptions.php';
require_once 'parameters.php';


echo '<pre>';


/*
function curl_post_socket_server($url, array $post = NULL, array $options = array()) {
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
//        CURLOPT_FRESH_CONNECT => 1,
//        CURLOPT_RETURNTRANSFER => 1,
//        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POSTFIELDS => http_build_query($post)
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));

    if( ! $result = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}*/


echo socket_server(HOST, PORT, true, 1); // Задаем время работы сервера, равное 1 сек.


function socket_server($host, $port, $flag_mess_echo, $time_MAX = null) {
    /*  $port           - номер порта (например, 9999)
        $flag_mess_echo - true (если выводить вспомогательные сообщения)
                          false (если НЕ выводить)
        $time_MAX       - Максимальное время работы этого сервера (в секундах). НЕ БОЛЕЕ времени, установленного в php.ini
    */

    $mes = '';

// 2. Если время выполнения не задано, то берем максимальное время, уменьшенное на 1 сек., чтобы эта функция вернула свое значение и, главное, чтобы НЕ БЫЛО аварийного прерывания. Иначе информация в сокетах может быть некорректной.
    if ($time_MAX === null || $time_MAX >= (int)ini_get('max_execution_time')) {
        $max_execution_time = ini_get('max_execution_time');
        $time_MAX = (int)$max_execution_time - 1; // Чтобы сервер завершил свою работу САМ, а не вследствие ошибки таймаута РНР
    }


    ob_implicit_flush(true);

    if ($flag_mess_echo) {
        $mes = "Запуск неблокирующего TCP-сервера " . $_SERVER['PHP_SELF'] . " на $host:$port... - " . microtime(true) . "\n";
        echo $mes;
        flush();
        file_put_contents('info.log', $mes, FILE_APPEND);
    }

// 3. Создаём сокет
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        throw new ErrorException("Ошибка создания сокета: " . socket_strerror(socket_last_error($socket)) . " 1"); // 1 - работу прекращаем
    }

// 4. Разрешаем перепривязку порта (даже в TIME_WAIT)
    $result = socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
    if ($result === false) {
        throw new ErrorException("Ошибка установки SO_REUSEADDR: " . socket_strerror(socket_last_error($socket)) . " 1"); // 1 - работу прекращаем
    }

// 5. Привязываем сокет к адресу и порту
    $result = socket_bind($socket, $host, $port);
    if ($result === false) {
        throw new ErrorException("Ошибка привязки сокета: " . socket_strerror(socket_last_error($socket)) . " 1"); // 1 - работу прекращаем
    }

// 6. Начинаем слушать порт
    $result = socket_listen($socket, 5); // До 5 клиентов одновременно
    if ($result === false) {
        throw new ErrorException("Ошибка запуска прослушивания: " . socket_strerror(socket_last_error($socket)) . " 1"); // 1 - работу прекращаем
    }

// 7. Устанавливаем неблокирующий режим для серверного сокета
    socket_set_nonblock($socket);
    if ($flag_mess_echo) {
        $mes = "Сервер запущен в неблокирующем режиме. Ожидание подключений..." . "\n";
        echo $mes;
        file_put_contents('info.log', $mes, FILE_APPEND);
    }


    $clients = array(); // Массив для хранения клиентских соединений
    $data_Arr = array(); // Массив для хранения set/get-сообщений клиентов (кэш, типа оперативной памяти). Этот кэш доступен клиентам, пока работает данный сокет-сервер
    /*  Будет что-то типа:
    Array
    (
        [CLIENT1] => Array        	<- Имя клиента, записавшего данные (указано в socket_client_set.php)
            (
                [0] => 1234			<- Число 1234 было записано этим клиентом при первом его запуске
                [1] => 4984765		<- Число 4984765 было записано этим клиентом при втором его запуске
                [2] => 34554562     <- ...
                [3] => 32545		<- ...
            )

        [CLIENT2] => Array			<- Имя другого клиента, также записавшего данные
            (
                [0] => 4354395
                [1] => 1295465666
                [2] => 43509
            )
    )
    */


    $time = microtime(true);

    $data_Arr_Str = '';

// 8. Бесконечный цикл обработки сокетов
    while (true && ((microtime(true) - $time) <= $time_MAX)) {
// 8.1. Пытаемся принять новое подключение (принимаем очередного клиента)
        try {
            $client = @socket_accept($socket);

        } catch (ErrorException $e) { // Возможно, сокет уже создан, но не готов к работе
            $client = false;
        }

        if ($client !== false) {
// 8.2.Новое подключение принято
            socket_set_nonblock($client); // Устанавливаем неблокирующий режим для клиента
            $clients[] = $client;

            if ($flag_mess_echo) {
                $mes = "Новое подключение принято. Всего клиентов: " . count($clients) . "\n";
                echo $mes;
                file_put_contents('info.log', $mes, FILE_APPEND);
            }
        }

// 8.3. Обрабатываем существующие подключения (достаем из них все пришедшие от клиентов данные)
        foreach ($clients as $index => $client) {
            try {
                $name_data = @socket_read($client, 2048, PHP_NORMAL_READ);

            } catch (ErrorException $e) {
                $name_data = false;
            }

            if ($name_data === false) { // Ошибки чтения (хост закрыл соединение) или нет данных — продолжаем
                continue;
            }

            if (strlen($name_data) === 0) { // Клиент отключился

                if ($flag_mess_echo) {
                    $mes = "Клиент отключился. Закрываем соединение с ним." . "\n\n";
                    echo $mes;
                    file_put_contents('info.log', $mes, FILE_APPEND);
                }
                socket_close($client);
                unset($clients[$index]); // Удаляем этого клиента из массива активных клиентов
                continue;
            }

// 8.4. Данные получены
            $name_data = trim($name_data);
            if ($flag_mess_echo) {
                $mes = "Сокет-сервером получены данные (запрос) от сокет-клиента: '$name_data'" . "\n";
                echo $mes;
                file_put_contents('info.log', $mes, FILE_APPEND);
            }

            $response = '';

// 8.5. Обрабатываем данные
            $client_Arr = explode(':', $name_data);

            if (sizeof($client_Arr) !== 3) {
                throw new ErrorException('Ошибочный запрос клиента: был неверный формат данных, полученных от клиента. 1'); // 1 - работу прекращаем

            } else {
                $client_NAME = $client_Arr[0];
                $client_DATA = $client_Arr[1];
                $client_ACTION = $client_Arr[2]; // set или get
// 8.5.1. Если задан set (добавить данные в массив)
                if ($client_ACTION === 'set') {
                    $data_Arr[$client_NAME][] = $client_DATA;
                    $response = "OK\n"; // Запись данных клиента сервером произведена успешно
// 8.5.2. Если задан get (извлечь данные из массива)
                } elseif ($client_ACTION === 'get') {
                    if (isset($data_Arr[$client_NAME]) && isset($data_Arr[$client_NAME][0])) { // Выбираем наиболее ранее записанное значение (метод FIFO)
                        $response = $data_Arr[$client_NAME][0] . "\n";
                        unset($data_Arr[$client_NAME][0]); // И удаляем его из массива

                        $data_Arr[$client_NAME] = array_values($data_Arr[$client_NAME]); // Для нормализации индексов, чтобы первый индекс был 0
                    } else {
                        $response = "0\n"; // Если для заданного имени клиента в массиве не сохранено ни одно значение
                    }
                } else {
                    throw new ErrorException('Ошибочный запрос клиента с именем ' . $client_NAME . '. Был неверный запрос: ни set, ни get. 1'); // 1 - работу прекращаем
                }
            }

// 8.6. Отправляем ответ клиенту
            $bytesWritten = @socket_write($client, $response, strlen($response));
            if ($bytesWritten === false) {

                socket_close($client);
                unset($clients[$index]);
                throw new ErrorException('Ошибка отправки ответа клиенту с именем ' . $client_NAME . ': в функции socket_write(): ' . socket_strerror(socket_last_error($client)) . '. Закрываем соединение. 1'); // 1 - работу прекращаем

            } else {
                $mes = "Сервер отправил ответ клиенту с именем $client_NAME ($bytesWritten байт)" . "\n";
                echo $mes;
                file_put_contents('info.log', $mes, FILE_APPEND);
            }

        }

        $data_Arr_Str_new = md5(strval(print_r($data_Arr, true)));

        if ($data_Arr_Str !== $data_Arr_Str_new) { // Если массив изменился (например, путем добавления, обновления или удаления элементов)
            $data_Arr_Str = $data_Arr_Str_new;


            $mes = 'Общая память (массив), хранимая в массиве скрипта ' . basename($_SERVER['PHP_SELF']) . ":\n";
            $mes .= print_r($data_Arr, true) . PHP_EOL;
            echo $mes; // Выводим массив - общую память
            file_put_contents('info.log', $mes, FILE_APPEND);
        }


// 8.7. Небольшая задержка, чтобы не нагружать CPU
        usleep(10000); // 100 мс
    }

// 7. Закрываем серверный сокет (сюда попадаем, когда истечет таймаут для этого сокет-сервера)
    socket_close($socket);

    if ($flag_mess_echo) {
        $mes = "Сокет-сервер прекратил работу, т.к. истек заданный таймаут (" . $time_MAX . " сек.)." . "\n";
        echo $mes;
        file_put_contents('info.log', $mes, FILE_APPEND);
    }

    return "<b>" . $_SERVER['PHP_SELF'] . "</b> - finish \n";
}
