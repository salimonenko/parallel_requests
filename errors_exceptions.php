<?php

class MyException extends ErrorException {

    public function __construct($message, $code = 0, ErrorException $previous = null) {

        parent::__construct($message, $code, $previous);
    }
}

// todo: По идее, в будущем целесообразно сделать разные исключения для разных случаев  +++

function myExceptionHandler(ErrorException $e) {
//    error_log($e);
//    http_response_code(500); // Целесообразно, если только не было вывода в браузер, иначе будет ошибка

    if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN)) { // Для разработчиков, более подробная информация

        $mess = substr($e->getMessage(), 0) . '<br/>';
        $mess .= $e->getTraceAsString() . '<br/>';
        $mess .= ' в файле ' . $e->getFile();
        $mess .= ', строчка ' . $e->getLine() . '<br/>';
//        $mess .= ' getTrace ' . json_encode($e->getTrace());
    } else { // Для конечных пользователей
        $mess = "<h1>500 Internal Server Error</h1>An internal server error has been occurred.<br>Please try again later.";
    }
//print_r($e);
    if (defined('CONTENT_TYPE_OUR')) {
        if (CONTENT_TYPE_OUR === 'application/json') {
            Exception_response(json_encode(array($mess)), 1); // +++
        } elseif (CONTENT_TYPE_OUR === 'application/x-www-form-urlencoded') {
            Exception_response($mess, 1);
        } else { // Если пока неизвестный тип, то хоть и так, и так ответить, чтобы было заметнее
            Exception_response($mess, 0);
            Exception_response(json_encode(($mess)), 1);
        }
    } else {
        Exception_response($mess, 0);
        Exception_response(json_encode(array($mess)), 1);
    }

}

// задает пользовательскую функцию для обработки всех необработанных исключений
set_exception_handler('myExceptionHandler'); // Обработчик для перехвата исключений (в т.ч. ошибок, превращенных в исключения)

set_error_handler(function ($level, $message, $file = '', $line = 0) {
// Превращаем ошибки в исключения. А они обрабатываются при помощи set_exception_handler()
    throw new ErrorException($message, 0, $level, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last(); // Если ошибки были перехвачены, то будет пустой массив
    if ($error !== null) { // Если была НЕПЕРЕХВАЧЕННАЯ ошибка (если не было, то пустой массив считается равным null и дальше не будет выполняться)
        $e = new ErrorException(
            'From register_shutdown_function: ' . $error['message'], 0, $error['type'], $error['file'], $error['line']
        );
        myExceptionHandler($e);
    }
});


function Exception_response($mess, $flag_die) {
    if ($flag_die) {
        die($mess);
    } else {
        echo $mess;
    }
}

