#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

use App\Application;

$application = new Application();
$application->init()->boot()->handle();

