"""
reply_bot.py — 主流程模块

实现「抓取帖子 → 生成 AI 回复 → 发表回复」的核心主循环。
"""

import logging
import time
from typing import Optional

from src.deepseek_client import DeepSeekClient
from src.discuz_client import DiscuzClient
from src.storage import RepliedStorage

logger = logging.getLogger(__name__)


class ReplyBot:
    """协调 DiscuzClient、DeepSeekClient 和 RepliedStorage 的主机器人类。"""

    def __init__(self, config: dict) -> None:
        """
        :param config: 完整的配置字典（来自 config.yaml）
        """
        self._cfg = config
        bot_cfg = config.get("bot", {})

        self.poll_interval: int = int(bot_cfg.get("poll_interval_seconds", 300))
        self.max_replies: int = int(bot_cfg.get("max_replies_per_cycle", 5))
        self.min_post_length: int = int(bot_cfg.get("min_post_length", 10))
        self.reply_signature: str = bot_cfg.get("reply_signature", "")
        self.dry_run: bool = bool(bot_cfg.get("dry_run", False))
        self.request_delay: int = int(bot_cfg.get("request_delay_seconds", 8))
        storage_path: str = bot_cfg.get("storage_path", "replied.json")

        self.storage = RepliedStorage(storage_path)
        self.discuz = DiscuzClient(config["discuz"])
        self.deepseek = DeepSeekClient(config["deepseek"])

        if self.dry_run:
            logger.info("*** dry_run 模式已开启：只生成回复，不实际发帖 ***")

    def login(self) -> None:
        """
        执行登录，失败时抛出 RuntimeError 中止程序。
        """
        logger.info("正在登录 Discuz 论坛……")
        success = self.discuz.login()
        if not success:
            raise RuntimeError(
                "Discuz 登录失败，请检查 config.yaml 中的登录配置（用户名/密码/Cookie）。"
            )
        logger.info("登录成功。")

    def run_once(self) -> int:
        """
        执行一轮抓取-生成-发表循环。

        :return: 本轮实际回复（或模拟回复）的帖子数
        """
        discuz_cfg = self._cfg["discuz"]
        forum_ids = [int(f) for f in discuz_cfg.get("forum_ids", [])]
        fetch_pages = int(discuz_cfg.get("fetch_pages", 1))

        replied_count = 0

        for fid in forum_ids:
            if replied_count >= self.max_replies:
                break

            logger.info("正在抓取版块 fid=%d（共 %d 页）……", fid, fetch_pages)
            threads = self.discuz.fetch_forum_threads(fid, pages=fetch_pages)

            for thread in threads:
                if replied_count >= self.max_replies:
                    break

                tid: int = thread["tid"]
                title: str = thread["title"]

                if self.storage.is_replied(tid):
                    logger.debug("帖子 tid=%d 已回复，跳过。", tid)
                    continue

                logger.info("处理帖子 tid=%d，标题：%s", tid, title)

                content = self.discuz.fetch_thread_content(tid)
                if len(content) < self.min_post_length:
                    logger.info(
                        "帖子 tid=%d 正文过短（%d 字符 < %d），跳过。",
                        tid, len(content), self.min_post_length,
                    )
                    continue

                reply = self.deepseek.generate_reply(title, content)
                if not reply:
                    logger.warning("帖子 tid=%d DeepSeek 未能生成回复，跳过。", tid)
                    continue

                # 附加签名
                if self.reply_signature:
                    reply = reply + "\n\n" + self.reply_signature

                if self.dry_run:
                    logger.info(
                        "[dry_run] 帖子 tid=%d 拟回复内容：\n%s",
                        tid, reply,
                    )
                    self.storage.mark_replied(tid)
                    replied_count += 1
                else:
                    success = self.discuz.post_reply(tid, reply)
                    if success:
                        self.storage.mark_replied(tid)
                        replied_count += 1
                        logger.info("帖子 tid=%d 回复成功（本轮第 %d 条）。", tid, replied_count)
                        time.sleep(self.request_delay)
                    else:
                        logger.error("帖子 tid=%d 回复失败，不标记为已回复。", tid)

        logger.info("本轮处理完成，共回复 %d 个帖子。", replied_count)
        return replied_count

    def run_forever(self) -> None:
        """
        持续循环执行 run_once，每轮结束后等待 poll_interval 秒。
        捕获 KeyboardInterrupt 优雅退出。
        """
        logger.info("机器人已启动，轮询间隔 %d 秒，按 Ctrl+C 停止。", self.poll_interval)
        try:
            while True:
                try:
                    self.run_once()
                except Exception as exc:  # noqa: BLE001
                    logger.critical("本轮执行发生严重异常：%s", exc, exc_info=True)

                logger.info("等待 %d 秒后开始下一轮……", self.poll_interval)
                time.sleep(self.poll_interval)
        except KeyboardInterrupt:
            logger.info("收到退出信号，正在保存存储并退出……")
            self.storage.save()
            logger.info("已退出。")
