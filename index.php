<?php

header('Content-type: text/html; charset=utf-8');


?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>Демонстрация асинхронного запуска скриптов и обмена данными между ними</title>

<style type="text/css">
* {font-size: 16px}

#id_RESPONSE {display: inline-block; width: 70%; min-height: 300px; border: solid 3px; vertical-align: top; margin-left: 20px}
button {padding: 15px; display: inline-block; vertical-align: top}
pre {white-space: pre-wrap}

</style>
</head>

<body>

<p style="max-width: 900px">В результате запроса будет запущен скрипт, запускающий, в свою очередь, сервер на сокетах и еще два скрипта. Эти скрипты будут обмениваться данными друг с другом в процессе работы через динамическую общую память, реализуемую указанным сервером.</p>

<button title="Сделать запрос на сервер" onclick="request();">Сделать запрос</button>
<div id="id_RESPONSE"></div>

<script type="text/javascript">

// Функция делает запрос
function request() {

    var data_Obj1 = {par: null};

    var route = 'parallel_requests.php';
    sender('POST', route, 'id_RESPONSE', data_Obj1, 'JSON', false, false);
}

// Функция отправляет сообщение на сервер  и ждет того или иного ответа, выводя потом его в alert
function sender(method, route, id_to_RESPONSE, data_Obj1, format, flag_ALERT, flag_RESPONSE_ADD, Function_AFTER = false, Function_AFTER_args_Arr = false) {
/*  Можно отправить GET или POST запрос
    Можно отправить сообщение в формате JSON или HTML
    Можно указать имя функции и ее аргументы, которая будет выполняться после получения ответа сервера
*/

    if (!flag_RESPONSE_ADD) { // Если в блок для ответа содержимое не добавляется, а заменяется, то сразу удаляем старое содержимое
        document.getElementById(id_to_RESPONSE).innerHTML = '';
    }

    var xhr = new XMLHttpRequest();

    if (format === 'JSON') {
// 1. Готовим тело сообщения для отправки
        data_Obj1["route"] = encodeURIComponent(route);

        var body_FINAL = JSON.stringify(data_Obj1);

        var xhrHeader_Content_Type;
            xhrHeader_Content_Type = "application/json; charset=utf-8";

    } else if (format === 'HTML') { // HTML
        xhrHeader_Content_Type = 'application/x-www-form-urlencoded';

        body_FINAL = data_Obj1 + '&route=' + encodeURIComponent(route); // Предполагается, что data_Obj1 (при формате 'HTML') теперь представляет собой обычную строку HTML-запроса с соединительными амперсандами
    } else {
        alert('Формат сообщений, отправляемых на сервер, может быть либо JSON, либо HTML. Нужно задать тот или иной формат или доработать программу');
        return;
    }

    var GET_reque = '', POST_reque = '';
    if (method === "GET") {
        GET_reque = '?json_str=' + body_FINAL;
    } else if (method === 'POST') {
        POST_reque = body_FINAL;
    } else { // Можно доработать с учетом других методов (PUT, DELETE и т.д.) +++
        method = 'POST';
        POST_reque = body_FINAL;
    }

    console.log('Итак, вот что отправляем на сервер методом ' + method + ', в формате "' + format + '":');
    console.log(POST_reque ? POST_reque : GET_reque);

    xhr.open(method, route + GET_reque, true); // Имена всех методов посылаем заданным методом
// 2. например, xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.setRequestHeader('Content-Type', xhrHeader_Content_Type);
    xhr.onreadystatechange = function xhr_state() {
        if (xhr.readyState != 4) return;
        if (xhr.status <= 300) {
// 3. После подтверждения получения сообщения сервером выдаем оповещение
//                if(flag_ALERT) alert('Операция '+ method + ' выполнена правильно.');
        } else {
            if (flag_ALERT)
                alert('xhr message: ' + xhr.statusText); // Сообщение об ошибке на транспортном (ТСР) уровне. Обычно вызвано проблемами  с доступом к сети или неправильной работой РНР на сервере, т.п.
        }
// Ответ придет в блок с id=id_RESPONSE

/*  В случае фатальных ошибок или т.п. Дело в том, что тогда РНР по своей инициативе выдает сообщение в ТЕКСТОВОМ виде, не в JSON. Проблема в том, что функция РНР  register_shutdown_function() также выдаст сообщение, а вот оно будет json_encode(array('... текст...'). Поэтому придется ОБРАТНО декодировать JSON (последовательности вида \u0431) в читаемый текст (актуально для кириллицы, например).
*/

// 4.1. Если определено, что ожидался ответ сервера в JSON
        if (format === 'JSON') {

//            document.getElementById(id_to_RESPONSE).innerHTML = decodeURIComponent(xhr.responseText.replace(/[\r\n]+/g, ' \ '));
//                var f = decodeURIComponent(xhr.responseText).replace(/[\r\n]+/g, ' \ ');
            var f = xhr.responseText; // Убрать?... +++

            try {
// Если будет ошибка с JSON, выполнение этой функции остановится, поэтому ниже запись в блок будет делаться уже НЕ в формате JSON
                var obj = JSON.parse(xhr.responseText); // Проверяем, корректный ли JSON пришел с сервера. Если да, то разбираем его

                var str = '';
                for (var t in obj) {
                    if (t !== '0') {
                        str += t + ': ';
                    }
                        str += ( obj[t] + '<br>');
                }
                console.log(str);

                    if (flag_RESPONSE_ADD) {
                        document.getElementById(id_to_RESPONSE).innerHTML += str;
                    } else {
                        document.getElementById(id_to_RESPONSE).innerHTML = str;
                    }

            } catch (er) { // Если JSON некорректный (тут надо доработать, иногда при ответе сервера все же появляются юникод-последовательности вида \u0431) +++
                console.log('Ожидался ответ сервера в виде JSON. Но, ответ оказался некорректным JSON');
// Denwer может в случае ошибки также вставить свой скрипт, а это мешает
                f = f.replace(/<script[^>]*>([^<]*)<\/script>/g, ' ');
                f = f.replace(/\\\"/g, ' * '); // Убираем излишние кавычки
                f = f.replace(/\"/g, '\\"').toString();
// Заменяем последовательности вида \u0431 на читаемые символы (русские или т.п.)
                f = f.replace(/(\\u[\w]{4})/g, function (match, p1) {
                return JSON.parse('"' + p1 + '"');
// return decodeURIComponent(p1.toString()) // Почему-то не работает, хотя должно
                });

                if (flag_RESPONSE_ADD) { // Если true, то добавляем очередной ответ сервера в инфо-блок (предыдущие ответы сохраняются)
                    document.getElementById(id_to_RESPONSE).innerHTML += f;
                } else {
                    document.getElementById(id_to_RESPONSE).innerHTML = f; // Предыдущие ответы НЕ сохраняются
                }
            }

        } else { // 4.2. Если не JSON. Например, если формат был задан как HTML
            if (flag_RESPONSE_ADD) {
                document.getElementById(id_to_RESPONSE).innerHTML += xhr.responseText;
            } else {
                document.getElementById(id_to_RESPONSE).innerHTML = xhr.responseText;
            }
        }

// 5. Если нужно что-то сделать после того, как ответ сервера помещен в соответствующий блок
        if (Function_AFTER && (typeof Function_AFTER) === 'function') { // Если задана функция-обработчик и она существует
            Function_AFTER(Function_AFTER_args_Arr, xhr);
        }
    };

        xhr.send(POST_reque);
        return false;
}

</script>

</body>
</html>