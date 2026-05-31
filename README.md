# parallel_requests
The parallel_requests() function can run multiple scripts (processes) in parallel. Relative paths to these scripts, as well as POST parameters (as arrays), are passed as arguments to this function.
For example, this function calls scripts with the relative paths '/TEST/parallel_requests/p0.php' and '/TEST/parallel_requests/p1.php'. They will be called with the following POST parameters:
array('param1' => 'value1', 'param2' => 'value2'),
array('param3' => 'value3', 'param4' => 'value4')

That is, The following URLs will be called in parallel:
/TEST/parallel_requests/p0.php?param1=value1&param2=value2
/TEST/parallel_requests/p1.php?param3=value3&param4=value4

These URLs are called (launched) using PHP sockets (similar to sockets in the C language).

ОДНОВРЕМЕННЫЙ ЗАПУСК НЕСКОЛЬКИХ СКРИПТОВ ПАРАЛЛЕЛЬНО

Функция parallel_requests() может запустить несколько скриптов (процессов) параллельно. Относительные пути к таким скриптам, а также POST-параметры (в виде массивов) задаются в качестве аргументов этой функции.
Для примера, эта функция вызывает скрипты, имеющие относительные пути 
'/TEST/parallel_requests/p0.php', 
'/TEST/parallel_requests/p1.php'. 
Они будут вызываться с POST-параметрами, соответственно: 
array('param1' => 'value1', 'param2' => 'value2'), 
array('param3' => 'value3', 'param4' => 'value4')

Т.е. параллельно будут вызваны следующие URL:
/TEST/parallel_requests/p0.php?param1=value1&param2=value2
/TEST/parallel_requests/p1.php?param3=value3&param4=value4

Вызовы (запуски) этих URL осуществляются при помоще сокетов PHP (аналог сокетов из языка С). 
