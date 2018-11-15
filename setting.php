<?php

/**
 * 微信登录插件配置
 * Skiychan <dev@skiy.net>
 * https://www.skiy.net/201811165225.html
 */

!defined('DEBUG') AND exit('Access Denied.');

if ($method == 'GET') {
	$kv = kv_get('skiy_xcx_login');
	
	$input = array();
	$input['qrcode_expiry'] = form_text('qrcode_expiry', $kv['qrcode_expiry']);
	
	include _include(APP_PATH.'plugin/skiy_xcx_login/setting.htm');
	
} else {

	$kv = array();
	$qrcode_expiry = param('qrcode_expiry');
	$kv['qrcode_expiry'] = (int)$qrcode_expiry;
	
	kv_set('skiy_xcx_login', $kv);
	
	message(0, '修改成功');
}
