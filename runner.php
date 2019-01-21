<?php

require_once 'Daemon.php';

$daemon = new Daemon();

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'start':
        $daemon->run();
        break;
    case 'stop':
        $daemon->stop();
        break;
    case 'restart':
        $daemon->restart();
        break;
    case 'status':
        $daemon->status();
        break;
    default:
        echo "用法: php runner.php {start|stop|restart|status}\n";
}
