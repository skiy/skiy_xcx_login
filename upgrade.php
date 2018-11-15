<?php

/**
 * 微信小程序扫码登录插件更新
 * Skiychan <dev@skiy.net>
 * https://www.skiy.net/201811165225.html
 */

$kv1 = kv_get('skiy_xcx_login');

$kv = array();
$kv['qrcode_expiry'] = isset($kv1['qrcode_expiry']) ? (int)$kv1['qrcode_expiry'] : 120;

kv_set('skiy_xcx_login', $kv);