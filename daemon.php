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

    private $gracefulStopSignal = false; // 优雅停止标识

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
            die("缺少必要扩展：pcntl 或 posix" . PHP_EOL);
        }
        if (php_sapi_name() !== "cli") {
            die("只能在 CLI 模式下运行" . PHP_EOL);
        }
    }

    /**
     * 检查是否已有运行中的守护进程
     */
    private function checkAlreadyRunning()
    {
        if (!file_exists($this->pidFile)) {
            return;
        }
        $pid = $this->getPid();
        if ($pid) {
            // posix_kill((int)$pid, 0)
            die("守护进程已在运行，PID: $pid" . PHP_EOL);
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
        $this->setupSignalHandlers();

        $pid = pcntl_fork();
        if ($pid == -1) {
            die("创建子进程失败" . PHP_EOL);
        }
        if ($pid > 0) {
            exit(0); // 父进程退出
        }

        if (posix_setsid() == -1) {
            die("创建会话失败" . PHP_EOL);
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
        $pid = posix_getpid();
        file_put_contents($this->pidFile, $pid);
        $this->log("守护进程启动，PID: " . $pid);
    }

    /**
     * 守护进程主循环
     *
     * @see https://www.php.net/manual/zh/function.pcntl-signal-dispatch.php
     */
    private function mainLoop()
    {
        while (true) {
            /**
             * ✅ 必考知识点：关于 PHP 信号处理和 pcntl_signal_dispatch 的重要说明
             *
             * 1. 如果你使用 `posix_kill` 向 PHP 进程发送信号（如 SIGTERM），
             *    你必须手动调用 `pcntl_signal_dispatch()` 才能触发注册的信号处理器。
             *    否则，信号回调不会执行，进程可能直接被系统终止（因为 SIGTERM 的默认行为是强制退出）。
             *
             * 2. 若直接在命令行中使用 `kill` 命令（如 `kill -15 PID`），
             *    通常会自动触发信号处理流程，但**仍推荐主动调用 `pcntl_signal_dispatch()` 以确保兼容性**。
             *
             * 3. `pcntl_signal_dispatch()` 执行非常快，性能开销可以忽略，
             *    通常远小于 `sleep(1)` 带来的等待成本，建议在主循环中每轮调用一次。
             */
            pcntl_signal_dispatch();

            if ($this->gracefulStopSignal) {
                $pid = $this->getPid();
                $this->log("进程 (PID: {$pid}) 收到优雅停止信号，正在退出..." . PHP_EOL);
                unlink($this->pidFile);
                exit(0);
            }
            $timestamp = microtime(true);
            file_put_contents($this->jobFile, "$timestamp job" . PHP_EOL, FILE_APPEND);
            $this->log("运行中: $timestamp");
            sleep(3);
        }
    }

    /**
     * 日志记录函数
     * @param string $msg
     */
    private function log(string $msg)
    {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . " $msg" . PHP_EOL, FILE_APPEND);
    }

    private function getPid()
    {
        $pid = (int)trim(file_get_contents($this->pidFile));
        return $pid;
    }

    public function stop()
    {
        if (!file_exists($this->pidFile)) {
            echo "守护进程未运行" . PHP_EOL;
            return;
        }

        $pid = $this->getPid();
        if ($pid && posix_kill($pid, SIGTERM)) {
            echo "守护进程已停止" . PHP_EOL;
            return ;
        }
        echo "停止失败，进程可能不存在" . PHP_EOL;
    }

    public function status()
    {
        if (!file_exists($this->pidFile)) {
            echo "守护进程未运行" . PHP_EOL;
            return;
        }

        $pid = $this->getPid();
        if ($pid) {
            echo "守护进程正在运行，PID: $pid" . PHP_EOL;
            return;
        }
        echo "守护进程 PID 文件存在，但进程不活跃" . PHP_EOL;
    }

    public function restart()
    {
        $this->stop();
        sleep(1);
        $this->run();
    }

    /**
     * 注册信号处理
     */
    private function setupSignalHandlers()
    {
        // 注册信号处理函数
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP, [$this, 'signalHandler']);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
    }

    /**
     * 信号处理函数
     */
    private function signalHandler($signo)
    {
        switch ($signo) {
            case SIGTERM:
                $this->handleSigterm();
                break;
            case SIGHUP:
                $this->handleSighup();
                break;
            case SIGUSR1:
                $this->handleSigusr1();
                break;
            default:
                $this->log("父进程 (PID: {$this->parentPid}) 或子进程 (PID: {$this->childPid}) 收到未知信号: {$signo}，退出中..." . PHP_EOL);
                break;
        }
    }

    /**
     * @return void
     */
    private function handleSigterm()
    {
        $pid = $this->getPid();
        $this->log("进程 (PID: {$pid}) 收到 SIGTERM 信号，优雅停止..." . PHP_EOL);
        $this->gracefulStopSignal = true;
    }

    private function handleSighup()
    {
        $pid = $this->getPid();
        $this->log("进程 (PID: {$pid}) 收到 SIGHUP 信号，重启任务..." . PHP_EOL);
        // 添加重启逻辑
    }

    private function handleSigusr1()
    {
        $pid = $this->getPid();
        $this->log("进程 (PID: {$pid}) 收到 SIGUSR1 信号，自定义操作..." . PHP_EOL);
        // 添加自定义逻辑
    }
}
