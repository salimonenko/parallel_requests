<?php
// Этот скрипт будет запущен параллельно с другим скриптом (p1.php)

$t0 = microtime(true);
/*
// p1.php — сервер, принимающий соединения

// Создаём серверный сокет
$serverSocket = stream_socket_server(
    'tcp://0.0.0.0:8080',
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
);

if (!$serverSocket) {
    die("Ошибка создания сервера: $errstr ($errno)\n");
}

echo "Сервер запущен на порту 8080. Ожидание подключений...\n";

// Основной цикл обработки подключений
//while (true)
{
    // Принимаем новое подключение
    $clientSocket = stream_socket_accept($serverSocket, -1, $peerName);

    if ($clientSocket) {
        echo "Новое подключение от: $peerName\n";

        // Читаем данные от клиента
        $request = '';
        while (!feof($clientSocket)) {
            $chunk = fread($clientSocket, 1024);
            if ($chunk === false) break;
            $request .= $chunk;
        }

        echo "Получен запрос:\n$request\n";

        // Формируем ответ
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: text/plain; charset=utf-8\r\n";
        $response .= "Connection: close\r\n\r\n";
        $response .= "Ответ от p1.php: запрос обработан!\n";
        $response .= "Полученные данные:\n$request";

        // Отправляем ответ клиенту
        fwrite($clientSocket, $response);

        // Закрываем соединение с клиентом
        fclose($clientSocket);
        echo "Ответ отправлен, соединение закрыто.\n";
    }
}

// Закрываем серверный сокет (недостижимо в этом примере)
fclose($serverSocket);
*/

print_r($_POST);

while (microtime(true) - $t0 < 1){

echo 'qwer0';
//flush();

usleep(500000);
}
