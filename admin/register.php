<?php
include 'common.php';

if ($user->hasLogin() || !$options->allowRegister) {
    $response->redirect($options->siteUrl);
}
$rememberName = htmlspecialchars(\Typecho\Cookie::get('__typecho_remember_name') ?? '');
$rememberMail = htmlspecialchars(\Typecho\Cookie::get('__typecho_remember_mail') ?? '');
\Typecho\Cookie::delete('__typecho_remember_name');
\Typecho\Cookie::delete('__typecho_remember_mail');

$bodyClass = 'body-100';

include 'header.php';
?>
<div class="typecho-login-wrap">
    <div class="typecho-login">
        <h1><a href="https://typecho.org" class="i-logo">Typecho</a></h1>
        <form action="<?php $options->registerAction(); ?>" method="post" name="register" role="form">
            <p>
                <label for="name" class="sr-only"><?php _e('用户名'); ?></label>
                <input type="text" id="name" name="name" placeholder="<?php _e('用户名'); ?>" value="<?php echo $rememberName; ?>" class="text-l w-100" autofocus />
            </p>
            <p>
                <label for="mail" class="sr-only"><?php _e('Email'); ?></label>
                <input type="email" id="mail" name="mail" placeholder="<?php _e('Email'); ?>" value="<?php echo $rememberMail; ?>" class="text-l w-100" />
            </p>
            <p class="verify-section" style="display: none;">
                <button type="button" id="verify-btn" class="btn btn-l w-100" style="background-color: #778899; color: white;"><?php _e('确定您是真人'); ?></button>
                <input type="hidden" id="verify-token" name="verify_token" value="" />
                <span id="verify-timer" style="display: none; margin-left: 10px; color: #666;"></span>
            </p>
            <p class="submit">
                <button type="submit" id="register-btn" class="btn btn-l w-100 primary" disabled><?php _e('注册'); ?></button>
            </p>
        </form>
        
        <p class="more-link">
            <a href="<?php $options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
            &bull;
            <a href="<?php $options->adminUrl('login.php'); ?>"><?php _e('用户登录'); ?></a>
        </p>
    </div>
</div>
<?php 
include 'common-js.php';
?>
<script>
$(document).ready(function () {
    $('#name').focus();
    
    // 显示验证区域
    $('.verify-section').show();
    
    // 点击验证按钮
    $('#verify-btn').click(function() {
        var btn = $(this);
        var registerBtn = $('#register-btn');
        var tokenInput = $('#verify-token');
        var timer = $('#verify-timer');
        
        // 如果正在验证中，不重复点击
        if (btn.prop('disabled')) return;
        
        // 显示验证中状态
        btn.prop('disabled', true);
        btn.text('<?php _e('验证中...'); ?>');
        btn.css('background-color', '#ffc107');
        
        // 发送AJAX请求到后端验证
        $.ajax({
            url: 'verify.php',
            type: 'POST',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    // 验证成功
                    tokenInput.val(response.token);
                    btn.text('<?php _e('成功！'); ?>');
                    btn.css('background-color', '#009688');
                    
                    // 启用注册按钮
                    registerBtn.prop('disabled', false);
                    
                    // 显示剩余尝试次数（如果有）
                    if (response.remaining_attempts !== undefined) {
                        console.log('剩余验证次数: ' + response.remaining_attempts);
                    }
                    
                    // 开始倒计时
                    var timeLeft = 60; // 60秒 = 1分钟
                    timer.show();
                    timer.text('验证有效期: ' + timeLeft + '秒');
                    
                    var countdown = setInterval(function() {
                        timeLeft--;
                        if (timeLeft > 0) {
                            timer.text('验证有效期: ' + timeLeft + '秒');
                        } else {
                            clearInterval(countdown);
                            timer.text('验证已过期');
                            timer.css('color', 'red');
                            
                            // 重置验证状态
                            btn.prop('disabled', false);
                            btn.text('<?php _e('确定您是真人'); ?>');
                            btn.css('background-color', '#778899');
                            registerBtn.prop('disabled', true);
                            tokenInput.val('');
                            timer.hide();
                        }
                    }, 1000);
                } else {
                    // 验证失败
                    btn.text(response.message || '<?php _e('验证失败，请重试'); ?>');
                    btn.css('background-color', '#dc3545');
                    
                    // 根据错误类型设置不同的重置时间
                    var resetTime = 3000; // 默认3秒
                    if (response.message && response.message.includes('5分钟')) {
                        resetTime = 5000; // 5秒，让用户看清错误信息
                    }
                    
                    // 重置按钮状态
                    setTimeout(function() {
                        btn.prop('disabled', false);
                        btn.text('<?php _e('确定您是真人'); ?>');
                        btn.css('background-color', '#778899');
                    }, resetTime);
                }
            },
            error: function(xhr, status, error) {
                // 请求失败
                btn.text('<?php _e('网络错误'); ?>');
                btn.css('background-color', '#dc3545');
                
                // 3秒后重置按钮状态
                setTimeout(function() {
                    btn.prop('disabled', false);
                    btn.text('<?php _e('确定您是真人'); ?>');
                    btn.css('background-color', '#778899');
                }, 3000);
                
                console.error('验证请求失败:', error);
            }
        });
    });
    
    // 表单提交前验证TOKEN
    $('form[name="register"]').submit(function(e) {
        var token = $('#verify-token').val();
        if (!token) {
            e.preventDefault();
            alert('<?php _e('请先进行点击验证'); ?>');
            return false;
        }
    });
});
</script>
<?php
include 'footer.php';
?>
