<?php

// 1. Настройки сокет-сервера и сокет-клиентов
define('HOST', '127.0.0.1');
define('PORT', 9999);


// Функция определяет доступный порт из заданного диапазона (пока не используется)
function checkPortWithSocket($host) {

    $ports_Arr = range(7000, 49151); // Целесообразный диапазон выбора портов для сокетных серверов в РНР

    for ($i = 0; $i < sizeof($ports_Arr); $i++) {

        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) { // 'Ошибка создания сокета';
            usleep(10); // Ждем немного, а вдруг потом удастся создать сокет
            continue;
        }

        $result = @socket_bind($socket, $host, $ports_Arr[$i]);
        if ($result === false) {
            $error = socket_last_error($socket);
            socket_close($socket);

            if ($error == 98 || $error == 10048) { // EADDRINUSE
                continue; // 'Порт занят';
            } else {
                return -1; // 'Другая ошибка: ' . socket_strerror($error);
            }
        } else {
            socket_close($socket);
            return $ports_Arr[$i];
        }

    }
// Если дошли досюда - плохо, значит, свободный порт не найден
    return -1;
}

// Использование
//echo checkPortWithSocket('127.0.0.1');
