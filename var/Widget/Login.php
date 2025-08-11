<?php

namespace Widget;

use Typecho\Cookie;
use Typecho\Validate;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 登录组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Login extends Users implements ActionInterface
{
    /**
     * 验证TOKEN有效性
     * 
     * @param string $token 前端传递的TOKEN
     * @return bool TOKEN是否有效
     */
    private function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }
        
        // TOKEN格式: 随机字符串 + 分隔符 + 时间戳(36进制) + 分隔符 + 签名
        $parts = explode('|', $token);
        if (count($parts) != 3) {
            return false;
        }
        
        $randomPart = $parts[0];
        $timestampPart = $parts[1];
        $signaturePart = $parts[2];
        
        // 验证随机字符串格式
        if (!ctype_xdigit($randomPart) || strlen($randomPart) != 8) {
            return false;
        }
        
        // 验证时间戳格式
        if (empty($timestampPart) || strlen($timestampPart) < 5) {
            return false;
        }
        
        // 将36进制时间戳转换为10进制（毫秒时间戳）
        $timestampMs = base_convert($timestampPart, 36, 10);
        if (!is_numeric($timestampMs)) {
            return false;
        }
        
        // 转换为秒时间戳
        $tokenTime = intval($timestampMs / 1000);
        $currentTime = time();
        
        // 验证时间是否在1分钟内
        $timeDiff = $currentTime - $tokenTime;
        if ($timeDiff < 0 || $timeDiff > 60) {
            return false;
        }
        
        // 验证签名
        $secret = 'typecho_verify_secret_' . date('Ymd', $tokenTime);
        $expectedSignature = hash_hmac('sha256', $randomPart . '|' . $timestampPart, $secret);
        $expectedSignatureShort = substr($expectedSignature, 0, 8);
        
        return hash_equals($expectedSignatureShort, $signaturePart);
    }
    
    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        // protect
        $this->security->protect();

        /** 如果已经登录 */
        if ($this->user->hasLogin()) {
            /** 直接返回 */
            $this->response->redirect($this->options->index);
        }

        /** 验证点击TOKEN */
        $verifyToken = $this->request->get('verify_token');
        if (empty($verifyToken)) {
            // 如果GET方式获取不到，尝试POST方式
            $verifyToken = $this->request->post('verify_token');
        }
        
        if (!$this->validateToken($verifyToken)) {
            Cookie::set('__typecho_remember_name', $this->request->name);
            Notice::alloc()->set(_t('验证已过期或无效，请重新点击验证'), 'error');
            $this->response->goBack('?referer=' . urlencode($this->request->referer));
        }

        /** 初始化验证类 */
        $validator = new Validate();
        $validator->addRule('name', 'required', _t('请输入用户名'));
        $validator->addRule('password', 'required', _t('请输入密码'));
        $expire = 30 * 24 * 3600;

        /** 记住密码状态 */
        if ($this->request->remember) {
            Cookie::set('__typecho_remember_remember', 1, $expire);
        } elseif (Cookie::get('__typecho_remember_remember')) {
            Cookie::delete('__typecho_remember_remember');
        }

        /** 截获验证异常 */
        if ($error = $validator->run($this->request->from('name', 'password'))) {
            Cookie::set('__typecho_remember_name', $this->request->name);

            /** 设置提示信息 */
            Notice::alloc()->set($error);
            $this->response->goBack();
        }

        /** 开始验证用户 **/
        $valid = $this->user->login(
            $this->request->name,
            $this->request->password,
            false,
            1 == $this->request->remember ? $expire : 0
        );

        /** 比对密码 */
        if (!$valid) {
            /** 防止穷举,休眠3秒 */
            sleep(3);

            self::pluginHandle()->loginFail(
                $this->user,
                $this->request->name,
                $this->request->password,
                1 == $this->request->remember
            );

            Cookie::set('__typecho_remember_name', $this->request->name);
            Notice::alloc()->set(_t('用户名或密码无效'), 'error');
            $this->response->goBack('?referer=' . urlencode($this->request->referer));
        }

        self::pluginHandle()->loginSucceed(
            $this->user,
            $this->request->name,
            $this->request->password,
            1 == $this->request->remember
        );

        /** 跳转验证后地址 */
        if (!empty($this->request->referer)) {
            /** fix #952 & validate redirect url */
            if (
                0 === strpos($this->request->referer, $this->options->adminUrl)
                || 0 === strpos($this->request->referer, $this->options->siteUrl)
            ) {
                $this->response->redirect($this->request->referer);
            }
        } elseif (!$this->user->pass('contributor', true)) {
            /** 不允许普通用户直接跳转后台 */
            $this->response->redirect($this->options->profileUrl);
        }

        $this->response->redirect($this->options->adminUrl);
    }
}
