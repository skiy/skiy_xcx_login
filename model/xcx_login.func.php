<?php
!defined('DEBUG') AND exit('Access Denied.');

/**
 * 微信小程序登录
 * Skiychan <dev@skiy.net>
 * https://www.skiy.net/201811165225.html
 */

/**
 * 获取用户信息
 * @param $openid 微信openid
 * @return array|bool
 */
function xcx_login_read_user_by_openid($openid) {
    $arr = db_find_one('skiy_xcx_login', array('openid' => $openid));
    if ($arr) {
        $arr2 = user_read($arr['uid']);
        if ($arr2) {
            $arr = array_merge($arr, $arr2);
        } else {
            db_delete('skiy_xcx_login', array('openid' => $openid));
            return FALSE;
        }
    }
    return $arr;
}

/**
 * 微信小程序 openid 已绑定用户
 * @param $openid
 * @return bool
 */
function xcx_had_bind_user_by_openid($openid) {
    $arr = db_find_one('skiy_xcx_login', array('openid' => $openid));
    if ($arr) {
        return $arr;
    }
    return FALSE;
}

/**
 * UID 已绑定微信小程序
 * @param $uid
 * @return bool
 */
function xcx_had_bind_user_by_uid($uid) {
    $arr = db_find_one('skiy_xcx_login', array('uid' => $uid));
    if ($arr) {
        return $arr;
    }
    return FALSE;
}

/**
 * 根据 UID 解除微信小程序绑定
 * @param $uid
 * @return bool
 */
function xcx_unbind_user_by_uid($uid) {
    db_delete('skiy_xcx_login', array('uid' => $uid));
    return TRUE;
}

/**
 * 绑定小程序
 * @param $uid
 * @param $openid
 * @return bool
 */
function xcx_bind_uid($uid, $openid) {
    global $time;

    $bind = array(
        'uid' => $uid,
        'openid' => $openid,
        'create_date' => $time
    );

    $r = db_insert('skiy_xcx_login', $bind);
    if (empty($r)) {
        return FALSE;
    };

    return TRUE;
}

/**
 * 判断微信客户端
 * @return bool
 */
function is_weixin() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'MicroMessenger')) {
        return true;
    }
    return false;
}