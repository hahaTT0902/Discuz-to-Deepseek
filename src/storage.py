"""
storage.py — 已回复帖子 TID 的本地持久化模块

使用 JSON 文件存储已回复的 tid 列表，原子写入（先写临时文件再重命名）。
"""

import json
import logging
import os
import tempfile
from typing import Set

logger = logging.getLogger(__name__)


class RepliedStorage:
    """管理已回复帖子 ID 的持久化存储。"""

    def __init__(self, path: str) -> None:
        """
        :param path: JSON 文件路径，如 "replied.json"
        """
        self.path = path
        self._replied: Set[int] = set()
        self._load()

    def _load(self) -> None:
        """从磁盘加载已回复的 tid 集合。文件不存在时初始化为空集合。"""
        if not os.path.exists(self.path):
            logger.debug("存储文件 %s 不存在，将从空集合开始。", self.path)
            return
        try:
            with open(self.path, "r", encoding="utf-8") as f:
                data = json.load(f)
            tids = data.get("replied_tids", [])
            self._replied = set(int(t) for t in tids)
            logger.info("已加载 %d 条已回复记录（来自 %s）。", len(self._replied), self.path)
        except (OSError, json.JSONDecodeError, ValueError) as exc:
            logger.warning("读取存储文件失败：%s。将从空集合开始。", exc)
            self._replied = set()

    def save(self) -> None:
        """原子写入：将当前集合序列化到临时文件再重命名覆盖目标文件。"""
        data = {"replied_tids": sorted(self._replied)}
        dir_name = os.path.dirname(os.path.abspath(self.path)) or "."
        try:
            fd, tmp_path = tempfile.mkstemp(dir=dir_name, suffix=".tmp")
            try:
                with os.fdopen(fd, "w", encoding="utf-8") as f:
                    json.dump(data, f, ensure_ascii=False, indent=2)
                os.replace(tmp_path, self.path)
            except Exception:
                # 清理临时文件
                try:
                    os.unlink(tmp_path)
                except OSError:
                    pass
                raise
        except OSError as exc:
            logger.error("保存存储文件失败：%s", exc)

    def is_replied(self, tid: int) -> bool:
        """检查指定 tid 是否已回复。"""
        return tid in self._replied

    def mark_replied(self, tid: int) -> None:
        """标记 tid 为已回复，并立即持久化。"""
        self._replied.add(tid)
        self.save()

    def __len__(self) -> int:
        return len(self._replied)
