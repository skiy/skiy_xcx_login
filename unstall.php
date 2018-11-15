<?php

/**
 * 卸载微信小程序扫码登录
 * Skiychan <dev@skiy.net>
 * https://www.skiy.net/201811165225.html
 */

!defined('DEBUG') AND exit('Forbidden');

$tablepre = $db->tablepre;
$sql = "DROP TABLE IF EXISTS `{$tablepre}skiy_xcx_login`";

db_exec($sql);