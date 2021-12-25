<?php

use App\Bot;
use Dotenv\Dotenv;

require_once 'vendor/autoload.php';

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

date_default_timezone_set('Europe/Moscow');

new Bot(getenv('TOKEN'), getenv('TIKER'), getenv('STRATEGY'));