"""
main.py — 程序入口

加载 config.yaml，初始化日志，启动主循环。
"""

import os
import sys
import logging

try:
    import yaml
except ImportError:
    print("缺少依赖：请先运行 pip install -r requirements.txt", file=sys.stderr)
    sys.exit(1)

from src.logger import setup_logger
from src.reply_bot import ReplyBot

_CONFIG_PATH = "config.yaml"
_CONFIG_EXAMPLE = "config.example.yaml"


def load_config(path: str) -> dict:
    """
    从 YAML 文件加载配置。

    :param path: 配置文件路径
    :return: 配置字典
    :raises SystemExit: 文件不存在或解析失败时退出
    """
    if not os.path.exists(path):
        print(
            f"错误：配置文件 {path!r} 不存在。\n"
            f"请复制 {_CONFIG_EXAMPLE!r} 为 {path!r} 并填写相关配置。",
            file=sys.stderr,
        )
        sys.exit(1)

    try:
        with open(path, "r", encoding="utf-8") as f:
            config = yaml.safe_load(f)
    except yaml.YAMLError as exc:
        print(f"错误：配置文件 {path!r} 解析失败：{exc}", file=sys.stderr)
        sys.exit(1)

    _validate_config(config, path)
    return config


def _validate_config(config: dict, path: str) -> None:
    """检查必需配置项是否存在，缺失时给出明确错误并退出。"""
    required = {
        "discuz": ["base_url", "login_mode", "forum_ids"],
        "deepseek": ["api_key"],
        "bot": [],
        "logging": [],
    }
    for section, keys in required.items():
        if section not in config:
            print(
                f"错误：配置文件 {path!r} 缺少顶级节 [{section}]。",
                file=sys.stderr,
            )
            sys.exit(1)
        for key in keys:
            if key not in config[section]:
                print(
                    f"错误：配置文件 {path!r} 中 [{section}] 缺少必需字段 '{key}'。",
                    file=sys.stderr,
                )
                sys.exit(1)


def main() -> None:
    """程序主入口。"""
    config = load_config(_CONFIG_PATH)

    # 初始化日志
    log_cfg = config.get("logging", {})
    setup_logger(
        level=log_cfg.get("level", "INFO"),
        log_file=log_cfg.get("file") or None,
    )

    logger = logging.getLogger(__name__)
    logger.info("Discuz-to-Deepseek 机器人启动中……")

    try:
        bot = ReplyBot(config)
        bot.login()
        bot.run_forever()
    except RuntimeError as exc:
        logger.error("%s", exc)
        sys.exit(1)
    except Exception as exc:  # noqa: BLE001
        logger.exception("未预期的错误：%s", exc)
        sys.exit(1)


if __name__ == "__main__":
    main()
