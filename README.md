# 守护进程

## PHP 守护进程示例

这是一个使用纯 PHP 编写的守护进程（Daemon）示例，支持后台运行任务、日志记录、PID 文件管理等功能。

## 📌 特性

- 守护进程启动（daemonize）
- 支持 PID 文件防重复运行
- 后台运行写入日志任务
- 支持标准输出重定向至 `/dev/null`

## 知识点

成为守护进程必要条件：

1. 创建子进程，父进程退出
2. 子进程创建新会话
3. 改变当前目录
4. 重设文件权限掩码
5. 关闭文件描述符

## ✅ 环境要求

- PHP 7.0+
- 必须安装扩展：
  - `pcntl`
  - `posix`
- 仅支持 CLI 模式运行

## 🚀 运行方式

```bash
php runner.php
```

运行后将创建一个后台进程，每 5 秒写入一次日志到 /tmp/job.log，并记录守护日志到 /tmp/daemon.log。

## 📂 目录结构

```shell
bash
.
├── run.php               # 主程序入口
├── /tmp/daemon.pid       # 存储 PID
├── /tmp/job.log          # 定时任务日志
├── /tmp/daemon.log       # 守护进程运行日志
```

📌 注意事项
请确保未重复运行，可查看 PID 文件或使用 ps aux | grep run.php。

若想停止守护进程，可使用 kill $(cat /tmp/daemon.pid)。

推荐放置到 /usr/local/bin 等目录作为服务运行。

## 📜 授权许可

MIT License
