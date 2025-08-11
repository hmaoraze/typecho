<?php
// 直接包含配置文件，避免common.php中的重定向和HTML输出
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

if (!@include_once __DIR__ . '/../config.inc.php') {
    echo json_encode([
        'success' => false,
        'token' => '',
        'message' => '系统配置错误'
    ]);
    exit;
}

// 设置响应头为JSON
header('Content-Type: application/json');

// 防止CSRF攻击
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// 会话管理和频率限制
class VerifySecurity {
    private static $instance = null;
    private $session;
    
    private function __construct() {
        // 启动会话
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->session = &$_SESSION;
        
        // 初始化会话数据
        if (!isset($this->session['verify_attempts'])) {
            $this->session['verify_attempts'] = [];
        }
        if (!isset($this->session['verify_blocked'])) {
            $this->session['verify_blocked'] = false;
        }
        if (!isset($this->session['verify_block_time'])) {
            $this->session['verify_block_time'] = 0;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // 检查是否被阻止
    public function isBlocked() {
        if ($this->session['verify_blocked']) {
            $blockDuration = 300; // 5分钟阻止
            if (time() - $this->session['verify_block_time'] < $blockDuration) {
                return true;
            } else {
                // 阻止时间已过，重置状态
                $this->session['verify_blocked'] = false;
                $this->session['verify_attempts'] = [];
            }
        }
        return false;
    }
    
    // 记录验证尝试
    public function recordAttempt($success) {
        $currentTime = time();
        $windowTime = 60; // 1分钟窗口
        
        // 清理过期的尝试记录
        $this->session['verify_attempts'] = array_filter(
            $this->session['verify_attempts'],
            function($time) use ($currentTime, $windowTime) {
                return ($currentTime - $time) < $windowTime;
            }
        );
        
        // 记录当前尝试
        $this->session['verify_attempts'][] = $currentTime;
        
        // 检查频率限制
        $maxAttempts = 5; // 每分钟最多5次尝试
        if (count($this->session['verify_attempts']) > $maxAttempts) {
            $this->session['verify_blocked'] = true;
            $this->session['verify_block_time'] = $currentTime;
            return false;
        }
        
        return true;
    }
    
    // 获取剩余尝试次数
    public function getRemainingAttempts() {
        $maxAttempts = 5;
        return max(0, $maxAttempts - count($this->session['verify_attempts']));
    }
}

// 验证函数
function validateRequest() {
    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['success' => false, 'message' => '无效的请求方法'];
    }
    
    // 检查请求来源
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if (empty($referer) || (strpos($referer, 'login.php') === false && strpos($referer, 'test_verify.php') === false && strpos($referer, 'register.php') === false)) {
        return ['success' => false, 'message' => '无效的请求来源'];
    }
    
    // 检查User-Agent
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if (empty($userAgent)) {
        return ['success' => false, 'message' => '缺少User-Agent'];
    }
    
    // 检查AJAX请求
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        return ['success' => false, 'message' => '仅支持AJAX请求'];
    }
    
    return ['success' => true];
}

// 生成安全TOKEN
function generateSecureToken() {
    // 使用更安全的随机数生成
    $randomPart = bin2hex(random_bytes(4)); // 8位随机字符串
    $timestampPart = base_convert(time() * 1000, 10, 36);
    $token = $randomPart . '|' . $timestampPart;
    
    // 添加服务器端验证签名
    $secret = 'typecho_verify_secret_' . date('Ymd'); // 每日变化的密钥
    $signature = hash_hmac('sha256', $token, $secret);
    
    return $token . '|' . substr($signature, 0, 8); // 添加签名前8位
}

// 验证TOKEN格式
function validateTokenFormat($token) {
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

// 获取客户端IP
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
            return $_SERVER[$key];
        }
    }
    return '127.0.0.1';
}

// 主处理逻辑
try {
    // 获取安全实例
    $security = VerifySecurity::getInstance();
    
    // 检查是否被阻止
    if ($security->isBlocked()) {
        echo json_encode([
            'success' => false,
            'token' => '',
            'message' => '验证尝试过于频繁，请5分钟后再试'
        ]);
        exit;
    }
    
    // 验证请求
    $requestValidation = validateRequest();
    if (!$requestValidation['success']) {
        $security->recordAttempt(false);
        echo json_encode([
            'success' => false,
            'token' => '',
            'message' => $requestValidation['message']
        ]);
        exit;
    }
    
    // 记录验证尝试
    if (!$security->recordAttempt(true)) {
        echo json_encode([
            'success' => false,
            'token' => '',
            'message' => '验证尝试过于频繁，请稍后再试'
        ]);
        exit;
    }
    
    // 生成安全TOKEN
    $token = generateSecureToken();
    
    // 记录客户端信息（可选，用于日志）
    $clientIP = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'token' => $token,
        'message' => '验证成功',
        'remaining_attempts' => $security->getRemainingAttempts()
    ]);
    
} catch (Exception $e) {
    // 异常处理
    echo json_encode([
        'success' => false,
        'token' => '',
        'message' => '服务器内部错误'
    ]);
}
?>
