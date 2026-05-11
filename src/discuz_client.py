"""
discuz_client.py — Discuz 论坛交互模块

负责登录、抓取帖子列表、读取帖子正文、发表回复。
支持账号密码登录和 Cookie 登录两种模式。
所有 HTTP 请求带超时（15s）和最多 3 次指数退避重试。
自动处理 GBK / UTF-8 编码的 Discuz 站点。
"""

import logging
import re
import time
from http.cookiejar import MozillaCookieJar
from typing import Dict, List, Optional
from urllib.parse import urljoin, urlencode

import requests
from bs4 import BeautifulSoup

logger = logging.getLogger(__name__)

# HTTP 请求超时（秒）
_TIMEOUT = 15
# 最大重试次数
_MAX_RETRIES = 3


def _parse_cookies(raw: str) -> Dict[str, str]:
    """将浏览器复制的原始 Cookie 字符串解析为字典。"""
    result: Dict[str, str] = {}
    for part in raw.split(";"):
        part = part.strip()
        if "=" in part:
            k, _, v = part.partition("=")
            result[k.strip()] = v.strip()
    return result


class DiscuzClient:
    """封装 Discuz 论坛的 HTTP 交互逻辑。"""

    def __init__(self, config: dict) -> None:
        """
        :param config: discuz 配置节
        """
        self.base_url: str = config["base_url"].rstrip("/")
        self.login_mode: str = config.get("login_mode", "cookie")
        self.username: str = config.get("username", "")
        self.password: str = config.get("password", "")
        self.raw_cookie: str = config.get("cookie", "")
        self.forum_ids: List[int] = [int(f) for f in config.get("forum_ids", [])]
        self.fetch_pages: int = int(config.get("fetch_pages", 1))
        self.user_agent: str = config.get(
            "user_agent",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        )

        self._session = requests.Session()
        self._session.headers.update({"User-Agent": self.user_agent})
        self._logged_in: bool = False

    # ------------------------------------------------------------------
    # 内部工具方法
    # ------------------------------------------------------------------

    def _request(
        self,
        method: str,
        url: str,
        **kwargs,
    ) -> Optional[requests.Response]:
        """
        带重试的 HTTP 请求。

        :param method: HTTP 方法，如 "GET" 或 "POST"
        :param url: 完整 URL
        :return: Response 对象；所有重试失败后返回 None
        """
        kwargs.setdefault("timeout", _TIMEOUT)
        for attempt in range(1, _MAX_RETRIES + 1):
            try:
                resp = self._session.request(method, url, **kwargs)
                resp.raise_for_status()
                return resp
            except requests.RequestException as exc:
                wait = 2 ** attempt
                logger.warning(
                    "HTTP %s %s 第 %d 次请求失败：%s；%d 秒后重试。",
                    method, url, attempt, exc, wait,
                )
                if attempt < _MAX_RETRIES:
                    time.sleep(wait)
        logger.error("HTTP %s %s 在 %d 次重试后仍失败，放弃。", method, url, _MAX_RETRIES)
        return None

    def _decode_response(self, resp: requests.Response) -> str:
        """
        自动检测响应编码（优先 Content-Type charset，回退 apparent_encoding）。
        常见 Discuz 站点默认 GBK。
        """
        # 尝试从响应头读取 charset
        content_type = resp.headers.get("Content-Type", "")
        match = re.search(r"charset=([^\s;]+)", content_type, re.I)
        if match:
            charset = match.group(1).strip().strip('"').lower()
        else:
            charset = None

        if not charset:
            # 从 HTML <meta> 标签中检测
            raw_start = resp.content[:2048]
            meta_match = re.search(
                rb'charset=["\']?([a-zA-Z0-9_\-]+)', raw_start, re.I
            )
            charset = meta_match.group(1).decode("ascii").lower() if meta_match else None

        if not charset:
            charset = resp.apparent_encoding or "utf-8"

        # 规范化别名
        charset = charset.replace("-", "").lower()
        if charset in ("gbk", "gb2312", "gb18030"):
            charset = "gbk"

        try:
            return resp.content.decode(charset, errors="replace")
        except (LookupError, UnicodeDecodeError):
            return resp.content.decode("utf-8", errors="replace")

    @staticmethod
    def _get_formhash(html: str) -> Optional[str]:
        """从 HTML 中提取 formhash 值。"""
        soup = BeautifulSoup(html, "lxml")
        tag = soup.find("input", {"name": "formhash"})
        if tag and tag.get("value"):
            return tag["value"]
        # 备用：正则
        match = re.search(r'formhash["\']?\s*value\s*=\s*["\']?([a-f0-9]+)', html, re.I)
        if match:
            return match.group(1)
        # 备用2：JS 变量
        match2 = re.search(r'var\s+formhash\s*=\s*["\']([a-f0-9]+)["\']', html)
        if match2:
            return match2.group(1)
        return None

    # ------------------------------------------------------------------
    # 登录
    # ------------------------------------------------------------------

    def login(self) -> bool:
        """
        根据配置选择登录方式。

        :return: 登录成功返回 True，否则 False
        """
        if self.login_mode == "password":
            return self.login_with_password()
        return self.login_with_cookie()

    def login_with_cookie(self) -> bool:
        """
        使用原始 Cookie 字符串登录。

        :return: 设置成功返回 True
        """
        if not self.raw_cookie:
            logger.error("login_mode=cookie 但 cookie 字段为空，请在 config.yaml 中填写 Cookie。")
            return False
        cookies = _parse_cookies(self.raw_cookie)
        self._session.cookies.update(cookies)
        self._logged_in = True
        logger.info("已通过 Cookie 完成登录配置（共 %d 个 cookie）。", len(cookies))
        return True

    def login_with_password(self) -> bool:
        """
        使用用户名和密码登录 Discuz。

        流程：
        1. 访问登录页，获取 loginhash 和 formhash
        2. POST 登录表单
        3. 检查响应中是否包含登录成功标志

        :return: 登录成功返回 True，否则 False
        """
        if not self.username or not self.password:
            logger.error("login_mode=password 但 username 或 password 为空。")
            return False

        login_page_url = (
            f"{self.base_url}/member.php?mod=logging&action=login"
        )
        resp = self._request("GET", login_page_url)
        if resp is None:
            logger.error("无法访问登录页面：%s", login_page_url)
            return False

        html = self._decode_response(resp)

        # 提取 loginhash（URL 参数）
        loginhash_match = re.search(r"loginhash=([a-zA-Z0-9]+)", html)
        loginhash = loginhash_match.group(1) if loginhash_match else ""

        formhash = self._get_formhash(html)
        if not formhash:
            logger.error("登录页未找到 formhash，请检查 base_url 是否正确。")
            return False

        post_url = (
            f"{self.base_url}/member.php?mod=logging&action=login"
            f"&loginsubmit=yes&infloat=yes&inajax=1"
        )
        if loginhash:
            post_url += f"&loginhash={loginhash}"

        payload = {
            "formhash": formhash,
            "referer": self.base_url + "/",
            "loginfield": "username",
            "username": self.username,
            "password": self.password,
            "questionid": "0",
            "answer": "",
            "cookietime": "2592000",
        }

        headers = {
            "Referer": login_page_url,
            "Content-Type": "application/x-www-form-urlencoded",
        }

        resp2 = self._request("POST", post_url, data=payload, headers=headers)
        if resp2 is None:
            logger.error("登录 POST 请求失败。")
            return False

        body = self._decode_response(resp2)

        # 检查登录失败标志
        if any(k in body for k in ("登录失败", "密码错误", "用户名错误", "errorhandle_")):
            logger.error("登录失败，请检查用户名和密码。")
            return False

        # 检查登录成功标志
        if any(k in body for k in ("succeedhandle_", "登录成功", "location.replace", "欢迎您回来")):
            self._logged_in = True
            logger.info("账号密码登录成功，用户名：%s", self.username)
            return True

        # 如果有 session cookie（如 discuz_auth），也视为成功
        for cookie in self._session.cookies:
            if "auth" in cookie.name.lower() or "sid" in cookie.name.lower():
                self._logged_in = True
                logger.info("账号密码登录成功（通过 cookie 检测），用户名：%s", self.username)
                return True

        logger.error("登录状态不明确，响应片段：%s", body[:300])
        return False

    # ------------------------------------------------------------------
    # 抓取帖子列表
    # ------------------------------------------------------------------

    def fetch_forum_threads(self, fid: int, pages: int = 1) -> List[Dict]:
        """
        抓取指定版块的帖子列表。

        :param fid: 版块 ID
        :param pages: 要抓取的页数
        :return: [{"tid": int, "title": str, "url": str}, ...]
        """
        threads: List[Dict] = []
        for page in range(1, pages + 1):
            url = (
                f"{self.base_url}/forum.php?mod=forumdisplay"
                f"&fid={fid}&page={page}"
            )
            resp = self._request("GET", url)
            if resp is None:
                logger.warning("抓取版块 fid=%d 第 %d 页失败，跳过。", fid, page)
                continue

            html = self._decode_response(resp)
            page_threads = self._parse_thread_list(html, fid)
            threads.extend(page_threads)
            logger.debug("版块 fid=%d 第 %d 页抓到 %d 个帖子。", fid, page, len(page_threads))

        logger.info("版块 fid=%d 共抓取 %d 个帖子。", fid, len(threads))
        return threads

    def _parse_thread_list(self, html: str, fid: int) -> List[Dict]:
        """解析版块页面，提取帖子列表。"""
        soup = BeautifulSoup(html, "lxml")
        threads: List[Dict] = []

        # 常见 Discuz 帖子列表结构：<tbody id="normalthread_NNN"> 或 <a id="thread_NNN">
        for tag in soup.find_all("a", id=re.compile(r"^thread_\d+")):
            tid_str = re.search(r"\d+", tag["id"])
            if not tid_str:
                continue
            tid = int(tid_str.group())
            title = tag.get_text(strip=True)
            thread_url = f"{self.base_url}/forum.php?mod=viewthread&tid={tid}"
            if title:
                threads.append({"tid": tid, "title": title, "url": thread_url})

        # 备用选择器：<a href="...tid=NNN...">
        if not threads:
            for tag in soup.find_all("a", href=re.compile(r"tid=\d+")):
                href = tag.get("href", "")
                m = re.search(r"tid=(\d+)", href)
                if not m:
                    continue
                tid = int(m.group(1))
                title = tag.get_text(strip=True)
                if title and len(title) > 2:
                    thread_url = f"{self.base_url}/forum.php?mod=viewthread&tid={tid}"
                    # 去重
                    if not any(t["tid"] == tid for t in threads):
                        threads.append({"tid": tid, "title": title, "url": thread_url})

        return threads

    # ------------------------------------------------------------------
    # 抓取帖子正文
    # ------------------------------------------------------------------

    def fetch_thread_content(self, tid: int) -> str:
        """
        抓取指定帖子的首楼正文纯文本。

        :param tid: 帖子 ID
        :return: 正文纯文本（已去除 HTML 标签、引用块、图片占位等）
        """
        url = f"{self.base_url}/forum.php?mod=viewthread&tid={tid}"
        resp = self._request("GET", url)
        if resp is None:
            logger.warning("抓取帖子 tid=%d 失败。", tid)
            return ""

        html = self._decode_response(resp)
        return self._extract_first_post(html)

    def _extract_first_post(self, html: str) -> str:
        """从帖子页面 HTML 中提取首楼正文纯文本。"""
        soup = BeautifulSoup(html, "lxml")

        # 移除引用块
        for tag in soup.find_all("blockquote"):
            tag.decompose()
        # 移除图片
        for tag in soup.find_all("img"):
            tag.decompose()
        # 移除附件、签名等
        for tag in soup.find_all(class_=re.compile(r"attach|signature|pattach", re.I)):
            tag.decompose()

        # 查找首楼帖子内容容器
        # 常见选择器：td.t_f、div.pcb、div[id^="postmessage_"]
        content_tag = (
            soup.find("td", class_="t_f")
            or soup.find("div", class_="pcb")
            or soup.find("div", id=re.compile(r"^postmessage_"))
        )

        if content_tag:
            text = content_tag.get_text(separator="\n", strip=True)
        else:
            # 备用：取页面可见文本的前 1000 字
            body = soup.find("body")
            text = body.get_text(separator="\n", strip=True) if body else ""
            text = text[:1000]

        # 清理多余空行
        lines = [line.strip() for line in text.splitlines() if line.strip()]
        return "\n".join(lines)

    # ------------------------------------------------------------------
    # 发表回复
    # ------------------------------------------------------------------

    def post_reply(self, tid: int, message: str) -> bool:
        """
        向指定帖子发表回复。

        :param tid: 帖子 ID
        :param message: 回复正文（纯文本）
        :return: 发帖成功返回 True，否则 False
        """
        # 先访问帖子页面，获取 formhash 和 fid
        thread_url = f"{self.base_url}/forum.php?mod=viewthread&tid={tid}"
        resp = self._request("GET", thread_url)
        if resp is None:
            logger.error("发帖前访问帖子 tid=%d 失败，无法获取 formhash。", tid)
            return False

        html = self._decode_response(resp)
        formhash = self._get_formhash(html)
        if not formhash:
            logger.error("帖子 tid=%d 页面未找到 formhash，跳过发帖。", tid)
            return False

        # 从页面提取 fid
        fid = self._extract_fid(html, tid)

        post_url = (
            f"{self.base_url}/forum.php?mod=post&action=reply"
            f"&fid={fid}&tid={tid}&extra=&replysubmit=yes"
            f"&infloat=yes&handlekey=fastpost&inajax=1"
        )

        # 检测编码：GBK 站点需要对 message 编码
        charset = self._detect_charset(html)

        payload = {
            "formhash": formhash,
            "subject": "",
            "message": message,
            "usesig": "1",
            "replysubmit": "yes",
        }

        headers = {
            "Referer": thread_url,
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest",
        }

        # GBK 站点需要将 payload 按 GBK 编码发送
        if charset == "gbk":
            try:
                encoded_body = urlencode(payload).encode("gbk", errors="replace")
                headers["Content-Type"] = "application/x-www-form-urlencoded"
                resp2 = self._request(
                    "POST", post_url,
                    data=encoded_body,
                    headers=headers,
                )
            except Exception as exc:
                logger.error("GBK 编码发帖失败：%s", exc)
                return False
        else:
            resp2 = self._request("POST", post_url, data=payload, headers=headers)

        if resp2 is None:
            logger.error("发帖请求失败，tid=%d。", tid)
            return False

        body = self._decode_response(resp2)
        return self._check_reply_success(body, tid)

    def _extract_fid(self, html: str, tid: int) -> int:
        """从帖子页面 HTML 中提取 fid。"""
        match = re.search(r"[?&]fid=(\d+)", html)
        if match:
            return int(match.group(1))
        # 备用：从 hidden input
        soup = BeautifulSoup(html, "lxml")
        tag = soup.find("input", {"name": "fid"})
        if tag and tag.get("value"):
            return int(tag["value"])
        logger.warning("无法从 tid=%d 页面提取 fid，默认使用 0。", tid)
        return 0

    def _detect_charset(self, html: str) -> str:
        """从 HTML 中检测页面字符集，返回 'gbk' 或 'utf-8'。"""
        match = re.search(r'charset=[\"\']?([a-zA-Z0-9_\-]+)', html[:2048], re.I)
        if match:
            cs = match.group(1).lower().replace("-", "")
            if cs in ("gbk", "gb2312", "gb18030"):
                return "gbk"
        return "utf-8"

    @staticmethod
    def _check_reply_success(body: str, tid: int) -> bool:
        """检测发帖响应体中是否包含成功标志。"""
        success_keywords = [
            "非常感谢",
            "发布成功",
            "succeedhandle_",
            "回复发表成功",
            "succeed",
        ]
        for kw in success_keywords:
            if kw.lower() in body.lower():
                logger.info("帖子 tid=%d 回复发表成功。", tid)
                return True

        # 如果没有明确失败信息，也视为可能成功（某些主题风格不返回标准提示）
        error_keywords = ["errorhandle_", "错误", "抱歉", "失败", "prohibited"]
        for kw in error_keywords:
            if kw.lower() in body.lower():
                logger.error("帖子 tid=%d 回复发表失败，响应片段：%s", tid, body[:200])
                return False

        logger.warning("帖子 tid=%d 回复结果不明确，响应片段：%s", tid, body[:200])
        return False
