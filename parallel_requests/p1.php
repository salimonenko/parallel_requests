<?php

$t0 = microtime(true);

print_r($_POST);
die();


while (microtime(true) - $t0 < 1){

    echo 'qwer1';
//flush();

    usleep(500000);
}
