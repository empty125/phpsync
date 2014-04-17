<?php
/**
 * nameserver 简单web管理 文件信息
 * @todo 添加driver 分页支持
 * @author xilei
 */
header("WWW-Authenticate:Basic realm='a key'");
header('HTTP/1.0 401 Unauthorized');
echo "You are unauthorized to enter this page.";

