# PHP 守护进程示例

这是一个使用纯 PHP 编写的守护进程（Daemon）示例，支持后台运行任务、日志记录、PID 文件管理等功能。

## 📌 特性

- 守护进程启动（daemonize）
- 支持信号处理（如优雅停止、重启等）
- 后台运行任务并记录日志，支持 PID 文件防止重复运行。

## ✅ 环境要求

- PHP 7.0+
- 必须安装以下扩展：
  - `pcntl`
  - `posix`
- 仅支持 CLI 模式运行

## 🚀 使用方法

### 启动守护进程

运行以下命令启动守护进程：

```bash
php runner.php start
```

### 停止守护进程

运行以下命令停止守护进程：

```bash
php runner.php stop
```

### 查看守护进程状态

运行以下命令查看守护进程是否正在运行：

```bash
php runner.php status
```

### 重启守护进程

运行以下命令重启守护进程：

```bash
php runner.php restart
```

## 📂 目录结构

```plaintext
.
├── daemon.php            # 守护进程核心逻辑
├── runner.php            # 守护进程控制入口
├── /tmp/daemon.pid       # 存储守护进程的 PID
├── /tmp/job.log          # 守护进程任务日志
├── /tmp/daemon.log       # 守护进程运行日志
```

## 📜 注意事项

1. 请确保未重复运行守护进程，可通过查看 `/tmp/daemon.pid` 文件或使用以下命令检查：
   ```bash
   ps aux | grep runner.php
   ```
2. 若想手动停止守护进程，可使用以下命令：
   ```bash
   kill $(cat /tmp/daemon.pid)
   ```
3. 请确保 `/tmp` 目录有写权限，或修改代码中的日志和 PID 文件路径。

## 📜 授权许可

MIT License
