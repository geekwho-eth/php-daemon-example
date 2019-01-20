<?php

/**
 * 用于创建一个标准的 PHP 守护进程
 * 
 * @Author: GeekWho
 * @Date:   2019-01-19 17:31:43
 * @Last Modified by:   GeekWho
 * @Last Modified time: 2019-01-19 23:33:58
 */
class Daemon
{
    private $pidFile = '/tmp/daemon.pid';
    private $logFile = '/tmp/daemon.log';
    private $jobFile = '/tmp/job.log';
    private $workDir = '/tmp/';

    /**
     * 守护进程运行入口
     */
    public function run()
    {
        $this->checkEnvironment();
        $this->checkAlreadyRunning();
        $this->daemonize();
        $this->writePidFile();
        $this->mainLoop();
    }

    /**
     * 环境检查：扩展 + CLI 模式
     */
    private function checkEnvironment()
    {
        if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
            die("缺少必要扩展：pcntl 或 posix\n");
        }
        if (php_sapi_name() !== "cli") {
            die("只能在 CLI 模式下运行\n");
        }
    }

    /**
     * 检查是否已有运行中的守护进程
     */
    private function checkAlreadyRunning()
    {
        if (file_exists($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);
            if ($pid && posix_kill((int)$pid, 0)) {
                die("守护进程已在运行，PID: $pid\n");
            }
        }
    }

    /**
     * 执行 daemon 化
     * 1. 复制一个子进程出来
     * 2. 创建新的会话
     * 3. 改变当前目录
     * 4. 重设文件权限掩码
     * 5. 关闭文件描述符
     *
     * @see http://php.net/manual/zh/function.pcntl_fork.php
     * @see http://php.net/manual/zh/function.posix_setsid.php
     * @see http://php.net/manual/zh/function.posix_setegid.php
     * @see http://php.net/manual/zh/function.posix_seteuid.php
     * @see http://php.net/manual/zh/function.chdir.php
     * @see http://php.net/manual/zh/function.umask.php
     * @see http://php.net/manual/zh/function.fopen.php
     * @see http://php.net/manual/zh/function.fwrite.php
     * @see http://php.net/manual/zh/function.fclose.php
     */
    private function daemonize()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("创建子进程失败\n");
        }
        if ($pid > 0) {
            exit(0); // 父进程退出
        }

        if (posix_setsid() == -1) {
            die("创建会话失败\n");
        }

        chdir($this->workDir);
        umask(0);

        // 关闭并重定向标准输入输出
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        fopen('/dev/null', 'r');
        fopen('/dev/null', 'a');
        fopen('/dev/null', 'a');
    }

    /**
     * 写入 PID 文件
     */
    private function writePidFile()
    {
        file_put_contents($this->pidFile, posix_getpid());
        $this->log("守护进程启动，PID: " . posix_getpid());
    }

    /**
     * 守护进程主循环
     */
    private function mainLoop()
    {
        while (true) {
            $timestamp = microtime(true);
            file_put_contents($this->jobFile, "$timestamp job\n", FILE_APPEND);
            $this->log("运行中: $timestamp");
            sleep(5);
        }
    }

    /**
     * 日志记录函数
     * @param string $msg
     */
    private function log(string $msg)
    {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . " $msg\n", FILE_APPEND);
    }
}

(new Daemon())->run();
