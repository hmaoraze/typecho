<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>验证功能测试页面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .test-button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .verify-btn {
            background-color: #778899;
            color: white;
        }
        .verify-btn:hover {
            background-color: #5a6b7c;
        }
        .verify-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .login-btn {
            background-color: #6c757d;
            color: white;
        }
        .login-btn:hover {
            background-color: #5a6268;
        }
        .login-btn:enabled {
            background-color: #28a745;
        }
        .login-btn:enabled:hover {
            background-color: #218838;
        }
        .result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        .info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        h1, h2 {
            color: #333;
        }
        .test-info {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>验证功能测试页面</h1>
    
    <div class="test-section">
        <h2>1. 基本验证功能测试</h2>
        <p class="test-info">测试验证按钮的基本功能，包括状态变化和TOKEN生成</p>
        
        <button id="verify-btn" class="test-button verify-btn">确定您是真人</button>
        <button id="login-btn" class="test-button login-btn" disabled>登录</button>
        
        <input type="hidden" id="verify-token" value="">
        
        <div id="verify-result" class="result" style="display: none;"></div>
        
        <div class="test-info">
            <strong>预期结果：</strong><br>
            1. 点击验证按钮后显示"验证中..."状态<br>
            2. 验证成功后显示"成功！"并启用登录按钮<br>
            3. 60秒后自动重置验证状态<br>
            4. TOKEN字段包含正确的格式和签名
        </div>
    </div>
    
    <div class="test-section">
        <h2>2. 频率限制测试</h2>
        <p class="test-info">测试验证频率限制功能，防止滥用</p>
        
        <button id="rapid-test-btn" class="test-button verify-btn">快速连续测试（6次）</button>
        
        <div id="rapid-result" class="result" style="display: none;"></div>
        
        <div class="test-info">
            <strong>预期结果：</strong><br>
            1. 前5次请求应该正常处理<br>
            2. 第6次请求应该被拒绝，提示频率限制<br>
            3. 5分钟后应该恢复正常访问
        </div>
    </div>
    
    <div class="test-section">
        <h2>3. 安全机制测试</h2>
        <p class="test-info">测试各种安全防护机制</p>
        
        <button id="security-test-btn" class="test-button verify-btn">测试安全机制</button>
        
        <div id="security-result" class="result" style="display: none;"></div>
        
        <div class="test-info">
            <strong>测试项目：</strong><br>
            1. 非POST请求检测<br>
            2. 请求来源验证<br>
            3. User-Agent检查<br>
            4. AJAX请求验证<br>
            5. TOKEN签名验证
        </div>
    </div>
    
    <div class="test-section">
        <h2>4. 错误处理测试</h2>
        <p class="test-info">测试各种错误情况的处理</p>
        
        <button id="error-test-btn" class="test-button verify-btn">测试错误处理</button>
        
        <div id="error-result" class="result" style="display: none;"></div>
        
        <div class="test-info">
            <strong>测试项目：</strong><br>
            1. 网络错误处理<br>
            2. 服务器错误处理<br>
            3. 无效响应处理<br>
            4. 超时处理
        </div>
    </div>
    
    <script>
        // 基本验证功能测试
        document.getElementById('verify-btn').addEventListener('click', function() {
            const btn = this;
            const loginBtn = document.getElementById('login-btn');
            const resultDiv = document.getElementById('verify-result');
            const tokenInput = document.getElementById('verify-token');
            
            if (btn.disabled) return;
            
            // 设置验证中状态
            btn.disabled = true;
            btn.textContent = '验证中...';
            btn.style.backgroundColor = '#ffc107';
            
            // 清空之前的结果
            resultDiv.style.display = 'none';
            resultDiv.className = 'result';
            
            // 发送验证请求
            fetch('verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Referer': window.location.href
                },
                body: 'action=verify'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('网络响应异常: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                // 显示结果
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '响应数据: ' + JSON.stringify(data, null, 2);
                
                if (data.success) {
                    // 验证成功
                    btn.textContent = '成功！';
                    btn.style.backgroundColor = '#009688';
                    btn.disabled = true;
                    
                    // 存储验证token
                    tokenInput.value = data.token;
                    
                    // 启用登录按钮
                    loginBtn.disabled = false;
                    loginBtn.style.backgroundColor = '#28a745';
                    
                    // 分析TOKEN格式
                    const tokenParts = data.token.split('|');
                    if (tokenParts.length === 3) {
                        resultDiv.className = 'result success';
                        resultDiv.innerHTML += '<br><br><strong>TOKEN分析：</strong><br>' +
                            '随机部分: ' + tokenParts[0] + '<br>' +
                            '时间戳部分: ' + tokenParts[1] + '<br>' +
                            '签名部分: ' + tokenParts[2] + '<br>' +
                            '剩余尝试次数: ' + (data.remaining_attempts || '未知');
                    } else {
                        resultDiv.className = 'result warning';
                        resultDiv.innerHTML += '<br><br><strong>警告：</strong>TOKEN格式不符合预期';
                    }
                    
                    // 设置60秒倒计时
                    let countdown = 60;
                    const countdownInterval = setInterval(() => {
                        countdown--;
                        if (countdown <= 0) {
                            clearInterval(countdownInterval);
                            // 重置验证状态
                            btn.textContent = '确定您是真人';
                            btn.style.backgroundColor = '#778899';
                            btn.disabled = false;
                            
                            // 禁用登录按钮
                            loginBtn.disabled = true;
                            loginBtn.style.backgroundColor = '#6c757d';
                            
                            // 清除token
                            tokenInput.value = '';
                            
                            resultDiv.innerHTML += '<br><br><strong>倒计时结束，状态已重置</strong>';
                        }
                    }, 1000);
                } else {
                    // 验证失败
                    btn.textContent = data.message || '验证失败';
                    btn.style.backgroundColor = '#dc3545';
                    resultDiv.className = 'result error';
                    
                    // 重置按钮状态
                    setTimeout(() => {
                        btn.textContent = '确定您是真人';
                        btn.style.backgroundColor = '#778899';
                        btn.disabled = false;
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('验证请求失败:', error);
                btn.textContent = '网络错误';
                btn.style.backgroundColor = '#dc3545';
                
                resultDiv.style.display = 'block';
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '请求失败: ' + error.message;
                
                // 重置按钮状态
                setTimeout(() => {
                    btn.textContent = '确定您是真人';
                    btn.style.backgroundColor = '#778899';
                    btn.disabled = false;
                }, 3000);
            });
        });
        
        // 频率限制测试
        document.getElementById('rapid-test-btn').addEventListener('click', function() {
            const btn = this;
            const resultDiv = document.getElementById('rapid-result');
            
            if (btn.disabled) return;
            
            btn.disabled = true;
            btn.textContent = '测试中...';
            resultDiv.style.display = 'block';
            resultDiv.className = 'result info';
            resultDiv.innerHTML = '开始快速连续测试...';
            
            let requestCount = 0;
            let successCount = 0;
            let failCount = 0;
            
            const makeRequest = () => {
                if (requestCount >= 6) {
                    resultDiv.className = 'result info';
                    resultDiv.innerHTML = '<strong>测试完成：</strong><br>' +
                        '总请求数: ' + requestCount + '<br>' +
                        '成功数: ' + successCount + '<br>' +
                        '失败数: ' + failCount + '<br>' +
                        (failCount > 0 ? '频率限制功能正常工作' : '频率限制可能未生效');
                    
                    btn.disabled = false;
                    btn.textContent = '快速连续测试（6次）';
                    return;
                }
                
                requestCount++;
                
                fetch('verify.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Referer': window.location.href
                    },
                    body: 'action=verify'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        successCount++;
                    } else {
                        failCount++;
                    }
                    
                    resultDiv.innerHTML += '<br>请求 ' + requestCount + ': ' + 
                        (data.success ? '成功' : '失败 - ' + data.message);
                    
                    // 继续下一个请求
                    setTimeout(makeRequest, 100); // 100ms间隔
                })
                .catch(error => {
                    failCount++;
                    resultDiv.innerHTML += '<br>请求 ' + requestCount + ': 网络错误 - ' + error.message;
                    
                    // 继续下一个请求
                    setTimeout(makeRequest, 100);
                });
            };
            
            // 开始第一次请求
            makeRequest();
        });
        
        // 安全机制测试
        document.getElementById('security-test-btn').addEventListener('click', function() {
            const btn = this;
            const resultDiv = document.getElementById('security-result');
            
            if (btn.disabled) return;
            
            btn.disabled = true;
            btn.textContent = '测试中...';
            resultDiv.style.display = 'block';
            resultDiv.className = 'result info';
            resultDiv.innerHTML = '开始安全机制测试...';
            
            const tests = [
                {
                    name: 'GET请求测试',
                    request: () => fetch('verify.php', { method: 'GET' })
                },
                {
                    name: '无Referer测试',
                    request: () => fetch('verify.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'action=verify'
                    })
                },
                {
                    name: '无AJAX头测试',
                    request: () => fetch('verify.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Referer': window.location.href
                        },
                        body: 'action=verify'
                    })
                }
            ];
            
            let currentTest = 0;
            
            const runTest = () => {
                if (currentTest >= tests.length) {
                    resultDiv.className = 'result info';
                    resultDiv.innerHTML += '<br><strong>所有安全测试完成</strong>';
                    
                    btn.disabled = false;
                    btn.textContent = '测试安全机制';
                    return;
                }
                
                const test = tests[currentTest];
                resultDiv.innerHTML += '<br>正在测试: ' + test.name;
                
                test.request()
                    .then(response => response.json())
                    .then(data => {
                        resultDiv.innerHTML += '<br>结果: ' + 
                            (data.success ? '意外成功' : '预期失败 - ' + data.message);
                        
                        currentTest++;
                        setTimeout(runTest, 500);
                    })
                    .catch(error => {
                        resultDiv.innerHTML += '<br>结果: 请求失败 - ' + error.message;
                        
                        currentTest++;
                        setTimeout(runTest, 500);
                    });
            };
            
            runTest();
        });
        
        // 错误处理测试
        document.getElementById('error-test-btn').addEventListener('click', function() {
            const btn = this;
            const resultDiv = document.getElementById('error-result');
            
            if (btn.disabled) return;
            
            btn.disabled = true;
            btn.textContent = '测试中...';
            resultDiv.style.display = 'block';
            resultDiv.className = 'result info';
            resultDiv.innerHTML = '开始错误处理测试...';
            
            // 测试不存在的接口
            fetch('nonexistent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=verify'
            })
            .then(response => {
                resultDiv.innerHTML += '<br>404测试: 状态码 ' + response.status;
                
                // 测试超时
                return Promise.race([
                    fetch('verify.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Referer': window.location.href
                        },
                        body: 'action=verify'
                    }),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('超时')), 10))
                ]);
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML += '<br>超时测试: 意外成功';
                
                btn.disabled = false;
                btn.textContent = '测试错误处理';
                resultDiv.className = 'result info';
                resultDiv.innerHTML += '<br><strong>错误处理测试完成</strong>';
            })
            .catch(error => {
                resultDiv.innerHTML += '<br>错误测试: ' + error.message;
                
                btn.disabled = false;
                btn.textContent = '测试错误处理';
                resultDiv.className = 'result info';
                resultDiv.innerHTML += '<br><strong>错误处理测试完成</strong>';
            });
        });
    </script>
</body>
</html>
