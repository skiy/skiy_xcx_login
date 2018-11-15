<?php

/**
 * 微信小程序扫码登录
 * Skiychan <dev@skiy.net>
 * https://www.skiy.net/201811165225.html
 */

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);
$action_2 = param(2);

$home_url = http_url_path();

if (empty($action)) {
    http_location($home_url);
}

include _include(APP_PATH . 'plugin/skiy_xcx_login/model/xcx_login.func.php');

$wxlogin = kv_get('skiy_xcx_login');
$expiry_time = $wxlogin['qrcode_expiry']; //二维码有效时长(秒)

if ($action == 'bind') {
    $qrcode_bind_pre = 'xcx_bd_';
    $qrcode_session_name = 'qrcode_xcx_bind';

    //如果不是在微信中，而是在PC端则
    if (!is_weixin()) {
        if (empty($user)) {
            $message['errmsg'] = '用户未登录';
            message(-1, $message);
        }

        //创建绑定二维码
        if ($action_2 == 'create_qrcode') {
            $code = -1;
            $message = array(
                'errmsg' => '未知错误',
            );

            $uid_binded = xcx_had_bind_user_by_uid($user['uid']);
            if (!empty($uid_binded)) {
                message(1, '该帐号已经被他人绑定微信');
            }

            if (isset($_SESSION[$qrcode_session_name])) {
                $cache_exist = cache_get($qrcode_bind_pre . $_SESSION[$qrcode_session_name]);
                //从session获取码
                if (!empty($cache_exist)) {
                    $code_number = $_SESSION[$qrcode_session_name];
                }
            }

            //SESSION 的code已失效，重新生成随机码
            empty($code_number) && $code_number = strtolower(xn_rand(16));

            $qrcode_key = $qrcode_bind_pre . $code_number;

            $code = 0;
            $message['qrcode'] = $code_number;
            $message['errmsg'] = '获取二维码成功';

            $data = array(
                'uid' => $user['uid'],
                'status' => 0,
            );
            cache_set($qrcode_key, $data, $expiry_time);
            $_SESSION[$qrcode_session_name] = $code_number;

            message($code, $message);

            //定时检测扫码
        } else if ($action_2 == 'check_qrcode') {
            $qrcode = isset($_SESSION[$qrcode_session_name]) ? $_SESSION[$qrcode_session_name] : '';

            $code = -1;
            $message = array(
                'errmsg' => '二维码无效',
            );

            if (empty($qrcode)) {
                message($code, $message);
            }

            $cache_key = $qrcode_bind_pre . $qrcode;
            $data = cache_get($cache_key);

            if (empty($data)) {
                $message['errmsg'] = '二维码已失效';
                $message['qrcode'] = $qrcode;
            } else {
                if ($data['status'] == 0) {
                    $code = 1;
                    $message['errmsg'] = '未扫码';
                    $message['time'] = $time;
                } else if ($data['status'] == 2) {
                    if (isset($data['errmsg'])) {
                        $code = 2;
                        $message['errmsg'] = $data['errmsg'];
                    }
                } else if (($data['status'] == 1) && !empty($data['uid'])) {
                    $code = 0;
                    $message['errmsg'] = '已扫码绑定微信';

                    $user = user_read($data['uid']);

                    $uid = $user['uid'];

                    $last_login = array(
                        'login_ip' => $longip,
                        'login_date' => $time,
                    );
                    user_update($user['uid'], $last_login);

                    $_SESSION['uid'] = $uid;
                    user_token_set($uid);

                    //删除此次二维码
                    unset($_SESSION[$qrcode_session_name]);
                    cache_delete($cache_key);
                }
            }

            message($code, $message);
        }
    }

    $ajax = TRUE;
    if (!is_weixin()) {
        message(-1, '请在微信内打开');
    }

    //检测是否已绑定
    if ($action_2 == 'check') {
        $openid = param('openid');

        $code = -1;
        $message = array(
            'errmsg' => '查询失败',
        );

        if (empty($openid)) {
            message($code, $message);
        }

        //判断微信是否已绑定其它帐号
        $wx_binded = xcx_had_bind_user_by_openid($openid);
        if (!empty($wx_binded)) {
            $message['errmsg'] = '该微信已经绑定他人帐号';
            message(1, $message);
        }

        $message['errmsg'] = '该微信未绑定帐号';
        message(2, $message);
    }

    //PC页面 - 微信扫码绑定
    if ($action_2 == 'scan_qrcode') {
        $code = -1;
        $message = array(
            'errmsg' => '二维码无效',
        );

        $qrcode = param('qrcode');
        $cache_key = $qrcode_bind_pre . $qrcode;

        $data = cache_get($cache_key);

        //如果缓存的数据无效 且 状态不为未扫码 ($data['status'] != 0)
        if (empty($data) || empty($data['uid'])) {
            $message['errmsg'] = '二维码已失效';
            message($code, $message);
        }

        if ($data['status'] == 2) {
            $message['errmsg'] = '二维码已使用';
            message($code, $message);
        }

        //微信登录用户
        $user = user_read($data['uid']);
        if (empty($user)) {
            $message['errmsg'] = '用户不存在';
            message($code, $message);
        }

        $uid = $user['uid'];

        $binding_data = array(
            'uid' => $user['uid'],
            'status' => 2,
            'errmsg' => '未知错误',
        );

        $uid_binded = xcx_had_bind_user_by_uid($uid);
        if (!empty($uid_binded)) {
            $binding_data['errmsg'] = '该帐号已经被他人绑定';
            cache_set($cache_key, $binding_data, $expiry_time);

            $message['errmsg'] = '该帐号已经被他人绑定';
            message($code, $message);
        }

        $openid = param('openid');

        //判断微信是否已绑定其它帐号
        $wx_binded = xcx_had_bind_user_by_openid($openid);
        if (!empty($wx_binded)) {
            $binding_data['errmsg'] = '该微信已经绑定他人帐号';
            cache_set($cache_key, $binding_data, $expiry_time);

            $message['errmsg'] = '该微信已经绑定他人帐号';
            message($code, $message);
        }

        //此微信与此帐号是否已经成功绑定
        $bind = xcx_bind_uid($uid, $openid);
        if (empty($bind)) {
            $binding_data['errmsg'] = '该帐号与微信绑定失败';
            cache_set($cache_key, $binding_data, $expiry_time);

            $message['errmsg'] = '该帐号与微信绑定失败';
            message($code, $message);
        }

        //绑定成功
        $data = array(
            'uid' => $user['uid'],
            'status' => 1,
        );
        cache_set($cache_key, $data, $expiry_time);

        $code = 0;
        $message['errmsg'] = '绑定成功';
        message($code, $message);
    }

//扫描二维码登录
} else if ($action == 'scan') {
    $qrcode = param(3);
    $qrcode_login_pre = 'xcx_dl_';
    $qrcode_session_name = 'qrcode_xcx_login';

    //创建登录二维码
    if ($action_2 == 'create_qrcode') {
        if (isset($_SESSION[$qrcode_session_name])) {
            $code_exist = cache_get($qrcode_login_pre . $_SESSION[$qrcode_session_name]);
            if (!empty($code_exist)) {
                $code_number = $_SESSION[$qrcode_session_name];
            }
        }

        empty($code_number) && $code_number = strtolower(xn_rand(16));

        $qrcode_key = $qrcode_login_pre . $code_number;

        $data = array(
            'status' => 0, //未扫码
        );
        cache_set($qrcode_key, $data, $expiry_time);

        //如果存在旧二维码,删除
        if (!empty($qrcode)) {
            cache_delete('qrcode_' . $qrcode);
        }

        //将创建的code保存到session
        $_SESSION[$qrcode_session_name] = $code_number;

        $message = array(
            'qrcode' => $code_number
        );

        message(0, $message);

        //定时检测微信扫码状态    
    } else if ($action_2 == 'check_qrcode') {
        $qrcode = isset($_SESSION[$qrcode_session_name]) ? $_SESSION[$qrcode_session_name] : '';

        $code = -1;
        $message = array(
            'errmsg' => '二维码无效',
        );

        if (empty($qrcode)) {
            message($code, $message);
        }

        $qrcode_key = $qrcode_login_pre . $qrcode;
        $data = cache_get($qrcode_key);

        if (empty($data)) {
            $message['errmsg'] = '二维码已失效';
            $message['qrcode'] = $qrcode;
        } else {
            if ($data['status'] == 0) {
                $code = 1;
                $message['errmsg'] = '未扫码';
                $message['time'] = $time;
            } else if (($data['status'] == 1) && !empty($data['openid'])) {
                $code = 0;
                $message['errmsg'] = '已扫码登录';

                $user = xcx_login_read_user_by_openid($data['openid']);
                $uid = $user['uid'];

                $last_login = array(
                    'login_ip' => $longip,
                    'login_date' => $time,
                    'logins+' => 1 //微信扫码登录(本次不增加登录次数)
                );
                user_update($user['uid'], $last_login);

                $_SESSION['uid'] = $uid;
                user_token_set($uid);

                //删除此次二维码
                unset($_SESSION[$qrcode_session_name]);
                cache_delete($qrcode_key);
            } else if ($data['status'] == 2) {
                if (isset($data['errmsg'])) {
                    $code = 2;
                    $message['errmsg'] = $data['errmsg'];
                }
            }
        }

        message($code, $message);

        //微信扫码该地址    
    } else if ($action_2 == 'scan_qrcode') {
        $ajax = TRUE;
        if (!is_weixin()) {
            message(-1, '请在微信内打开');
        }

        $code = -1;
        $message = array(
            'errmsg' => '二维码无效',
        );

        $qrcode = param('qrcode');
        if (empty($qrcode)) {
            $message['errmsg'] = '二维码无效';
            message($code, $message);
        }

        $data = cache_get($qrcode_login_pre . $qrcode);

        //如果缓存的数据无效 且 状态不为未扫码 ($data['status'] != 0)
        if (empty($data) || ($data['status'] != 0)) {
            $message['errmsg'] = '二维码已失效';
            message($code, $message);
        }

        $openid = param('openid');
        //判断微信是否已绑定其它帐号
        $wx_binded = xcx_had_bind_user_by_openid($openid);
        if (empty($wx_binded)) {
            $message['errmsg'] = '该微信小程序未绑定用户';
            message($code, $message);
        }

        $data = array(
            'status' => 1, //更新状态为已扫码
            'openid' => $openid,
        );
        cache_set($qrcode_login_pre . $qrcode, $data, $expiry_time);

        $code = 0;
        $message['errmsg'] = '登陆成功!';
        message($code, $message);
    }
//解除绑定
} else if ($action == 'unbind') {
    if (is_weixin()) {
        message(1, '请PC端操作');
    }

    $ajax = TRUE;
    //执行 SQL 语句删除绑定
    xcx_unbind_user_by_uid($uid);

    //用微信创建的帐号,将需要绑定新邮箱
    message(0, '解除微信绑定成功');
} else if ($action == 'openid') {
    if (! is_weixin()) {
        message(1, '请在微信内操作');
    }

    $ajax = TRUE;
    $code = param('code');
    if (empty($code)) {
        message(1, 'code不存在');
    }

    $openidUrl = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code';
    $kv = kv_get('skiy_xcx_login');

    $openidUrl = sprintf($openidUrl, $kv['appid'], $kv['appsecret'], $code);
    $result = http_get($openidUrl);

    exit($result);
}

//未知页面直接转跳至首页
http_location($home_url);