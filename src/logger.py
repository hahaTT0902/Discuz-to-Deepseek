"""
logger.py — 统一日志模块

同时输出到控制台（带颜色）和日志文件，格式包含时间戳、级别、模块名。
"""

import logging
import sys
from typing import Optional


def setup_logger(level: str = "INFO", log_file: Optional[str] = None) -> logging.Logger:
    """
    初始化并返回根日志记录器。

    :param level: 日志级别字符串，如 "INFO"、"DEBUG"
    :param log_file: 日志文件路径；为空或 None 时仅输出到控制台
    :return: 配置好的 Logger 实例
    """
    numeric_level = getattr(logging, level.upper(), logging.INFO)

    fmt = "%(asctime)s [%(levelname)s] %(name)s: %(message)s"
    datefmt = "%Y-%m-%d %H:%M:%S"
    formatter = logging.Formatter(fmt, datefmt=datefmt)

    root_logger = logging.getLogger()
    root_logger.setLevel(numeric_level)

    # 避免重复添加 handler（多次调用时）
    if root_logger.handlers:
        root_logger.handlers.clear()

    # 控制台 handler
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setLevel(numeric_level)
    console_handler.setFormatter(formatter)
    root_logger.addHandler(console_handler)

    # 文件 handler（可选）
    if log_file:
        try:
            file_handler = logging.FileHandler(log_file, encoding="utf-8")
            file_handler.setLevel(numeric_level)
            file_handler.setFormatter(formatter)
            root_logger.addHandler(file_handler)
        except OSError as exc:
            root_logger.warning("无法创建日志文件 %s：%s", log_file, exc)

    return root_logger


def get_logger(name: str) -> logging.Logger:
    """获取指定名称的子日志记录器。"""
    return logging.getLogger(name)
