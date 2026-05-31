Функция parallel_requests() может запустить несколько скриптов (процессов) параллельно. Относительные пути к таким скриптам, а также POST-параметры (в виде массивов) задаются в качестве аргументов этой функции.
Для примера, эта функция вызывает скрипты, имеющие относительные пути '/TEST/parallel_requests/p0.php', '/TEST/parallel_requests/p1.php'. Они будут вызываться с POST-параметрами, соответственно: 
array('param1' => 'value1', 'param2' => 'value2'), 
array('param3' => 'value3', 'param4' => 'value4')

Т.е. параллельно будут вызваны следующие URL:
/TEST/parallel_requests/p0.php?param1=value1&param2=value2
/TEST/parallel_requests/p1.php?param3=value3&param4=value4

Вызовы (запуски) этих URL осуществляются при помоще сокетов PHP (аналог сокетов из языка С). 
