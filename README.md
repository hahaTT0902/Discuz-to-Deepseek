# Discuz-to-DeepSeek

> ⚠️ This repository has been archived.  
> 本项目已归档，不再继续维护。新版已经重构为 **Discuz PHP 插件版本**，不再使用 Python 机器人方式运行。

---

## Archive Notice / 归档说明

This repository is the early Python-based version of Discuz-to-DeepSeek.  
It works as an external bot that logs in to a Discuz forum, fetches posts, sends post content to the DeepSeek API, and publishes AI-generated replies automatically.

However, this version is now archived because the project has been upgraded to a native **Discuz PHP plugin version**.

本仓库是 Discuz-to-DeepSeek 的早期 Python 机器人版本。  
它通过模拟登录 Discuz 论坛、抓取帖子、调用 DeepSeek API，并自动发布 AI 回复来实现功能。

目前该版本已经停止维护，项目已经升级为原生 **Discuz PHP 插件版本**。新版更适合直接安装到 Discuz 后台中使用，不需要额外运行 Python 脚本。

---

## New Version / 新版本

The new version is now a Discuz PHP plugin.

新版已经改为 Discuz PHP 插件版本，主要优势包括：

- 可直接在 Discuz 后台安装和管理
- 不需要单独运行 Python 程序
- 更适合长期部署在论坛环境中
- 配置项可以通过插件后台管理
- 与 Discuz 系统集成度更高
- 更方便后续维护和扩展

Please use the new Discuz PHP plugin version instead of this archived Python bot.

请优先使用新版 Discuz PHP 插件版本，而不是本仓库中的 Python 机器人版本。

---

## Legacy Version / 旧版本说明

The content below is kept only for historical reference.

以下内容仅作为历史版本说明保留。

# Discuz-to-Deepseek

> 使用 DeepSeek AI 自动回复 Discuz 论坛帖子的机器人。

---

## 功能特性

- **自动登录**：支持账号密码登录或直接使用浏览器 Cookie 登录
- **自动抓帖**：定时轮询指定版块，抓取新帖或未回复的帖子
- **AI 回复**：将帖子标题和正文发送给 DeepSeek API，获取自然语言回复
- **自动发帖**：将 AI 生成的回复发表到对应帖子下
- **去重保护**：本地记录已回复的帖子 ID，避免重复回复
- **频控保护**：可配置发帖间隔，防止触发论坛速率限制
- **GBK 兼容**：自动检测并处理 GBK 编码的 Discuz 站点
- **Dry Run 模式**：只生成不发表，方便安全测试
- **优雅退出**：Ctrl+C 时自动保存已回复记录

---

## 安装

**环境要求：** Python 3.9+

```bash
pip install -r requirements.txt
```

---

## 配置

1. 复制配置模板：

```bash
cp config.example.yaml config.yaml
```

2. 编辑 `config.yaml`，填写以下关键字段：

| 字段 | 说明 |
|------|------|
| `discuz.base_url` | Discuz 站点根 URL，如 `https://example.com/bbs` |
| `discuz.login_mode` | `"cookie"` 或 `"password"` |
| `discuz.cookie` | `login_mode=cookie` 时，从浏览器复制的完整 Cookie 字符串 |
| `discuz.username` / `password` | `login_mode=password` 时的账号密码 |
| `discuz.forum_ids` | 要监控的版块 fid 列表，如 `[2, 36]` |
| `deepseek.api_key` | 你的 DeepSeek API Key |
| `bot.poll_interval_seconds` | 轮询间隔（秒），默认 300 |
| `bot.dry_run` | `true` 时只打印拟回复内容，不实际发帖 |

完整配置说明见 [`config.example.yaml`](config.example.yaml)。

---

## 运行

```bash
python main.py
```

程序会持续运行，每隔 `poll_interval_seconds` 秒轮询一次。  
按 **Ctrl+C** 优雅退出，已回复记录会自动保存。

---

## 工作原理

```
启动 → 读取 config.yaml → 初始化日志
  → 登录 Discuz（Cookie 或账号密码）
  → 循环：
      遍历所有 forum_ids
        抓取版块帖子列表
        对每个未回复的帖子：
          读取正文 → 发送给 DeepSeek API → 获取 AI 回复
          dry_run=false → 发表回复 → 记录 tid
      等待 poll_interval_seconds 秒
```

---

## 常见问题

### 如何获取 Cookie？

1. 在浏览器中登录目标 Discuz 论坛
2. 按 F12 打开开发者工具 → Network（网络）面板
3. 刷新页面，找到任意请求，在 **Request Headers** 中复制 `Cookie:` 后面的全部内容
4. 粘贴到 `config.yaml` 的 `discuz.cookie` 字段中

### GBK 编码站点有乱码怎么办？

程序会自动检测页面编码（优先读取响应头和 `<meta charset>`），并在发帖时自动使用 GBK 编码。如果仍有问题，请确认站点确实使用 GBK（旧版 Discuz X2/X3 默认 GBK）。

### 触发论坛频控（发帖太快）怎么办？

增大配置中的 `bot.request_delay_seconds`（两次发帖间隔）和 `bot.poll_interval_seconds`（轮询间隔）。建议发帖间隔不低于 10 秒。

### 如何先测试再正式运行？

在 `config.yaml` 中设置 `bot.dry_run: true`，程序会完整执行「登录 → 抓帖 → 生成回复」流程，但只打印拟回复内容，不实际发帖。

### 程序日志在哪里？

默认同时输出到控制台和 `bot.log` 文件（由 `logging.file` 配置）。

---

## 免责声明

本项目仅供学习和技术研究使用。使用者应自行遵守目标论坛的使用规则和当地相关法律法规，作者不对任何滥用行为承担责任。使用本工具进行自动回复时，请确保回复内容对论坛社区有价值，不要用于垃圾信息或违规行为。
