"""
deepseek_client.py — DeepSeek API 调用模块

使用 openai 兼容接口调用 DeepSeek，生成论坛帖子的 AI 回复。
"""

import logging
from typing import Optional

from openai import OpenAI, OpenAIError

logger = logging.getLogger(__name__)


class DeepSeekClient:
    """封装 DeepSeek API 的调用逻辑。"""

    def __init__(self, config: dict) -> None:
        """
        :param config: deepseek 配置节，包含 api_key、base_url、model 等字段
        """
        self.model: str = config.get("model", "deepseek-chat")
        self.system_prompt: str = config.get(
            "system_prompt",
            "你是一位友善、专业的论坛回复助手。请用中文针对帖子内容给出有帮助、不超过200字的回答。",
        )
        self.max_tokens: int = int(config.get("max_tokens", 512))
        self.temperature: float = float(config.get("temperature", 0.7))

        api_key: str = config.get("api_key", "")
        base_url: str = config.get("base_url", "https://api.deepseek.com")

        if not api_key or api_key == "sk-xxx":
            raise ValueError("deepseek.api_key 未配置，请在 config.yaml 中填写有效的 API Key。")

        self._client = OpenAI(api_key=api_key, base_url=base_url)
        logger.debug("DeepSeekClient 已初始化，模型=%s，base_url=%s", self.model, base_url)

    def generate_reply(self, title: str, content: str) -> Optional[str]:
        """
        根据帖子标题和正文生成 AI 回复。

        :param title: 帖子标题
        :param content: 帖子正文纯文本
        :return: 生成的回复字符串；API 失败时返回 None
        """
        user_message = f"标题：{title}\n\n内容：{content}"
        try:
            response = self._client.chat.completions.create(
                model=self.model,
                messages=[
                    {"role": "system", "content": self.system_prompt},
                    {"role": "user", "content": user_message},
                ],
                max_tokens=self.max_tokens,
                temperature=self.temperature,
            )
            reply_text: str = response.choices[0].message.content or ""
            reply_text = reply_text.strip()
            logger.debug("DeepSeek 生成回复（前50字）：%s", reply_text[:50])
            return reply_text if reply_text else None
        except OpenAIError as exc:
            logger.error("DeepSeek API 调用失败：%s", exc)
            return None
        except Exception as exc:  # noqa: BLE001
            logger.error("DeepSeek 生成回复时发生未知异常：%s", exc)
            return None
