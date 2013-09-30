<?php

include('simple_html_dom.php');
include('RamblerHoroscopeParser.php');

if(isset($argv[1]) && isset($argv[2]) && isset($argv[3])) {

    $hp = new RamblerHoroscopeParser();
    $hp->parse($argv[1], $argv[2], $argv[3]);
}

