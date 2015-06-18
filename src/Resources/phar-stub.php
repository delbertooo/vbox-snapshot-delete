#!/usr/bin/env php
<?php
/*
 * This file is part of the VirtualBox Snapshot Delete.
 *
 * (c) Robert Worgul <robert.worgul@scitotec.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400) {
    fwrite(STDERR, "PHP needs to be a minimum version of PHP 5.4.0\n");
    exit(1);
}
set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity & error_reporting()) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});
Phar::mapPhar('vbox-snapshot-delete.phar');
require_once 'phar://vbox-snapshot-delete.phar/vendor/autoload.php';
use Delbertooo\VirtualBox\SnapshotDelete\Console\Application;
$application = new Application();
$application->run();
__HALT_COMPILER();
