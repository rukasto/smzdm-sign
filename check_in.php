<?php
// 开启严格错误报告，防止报错被吞掉
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once 'vendor/autoload.php';
include_once 'notify.php';

// 引入具体签到逻辑
include_once 'channel/smzdm.php';

function notify_message(array $resp): string
{
    return sprintf("【%s%s】\r\n%s", $resp['title'], $resp['status'] ? '成功' : '失败', $resp['reason']);
}

$list = [
    [
        'name' => 'smzdm',
        'title' => '【什么值得买】签到'
    ]
];

foreach ($list as $value) {
    $resp = '';
    $title = '';

    // 根据配置执行签到
    switch ($value['name']) {
        case 'smzdm':
            $title = $value['title'];
            // 注意：这里调用的是 channel/smzdm.php 里的 smzdm() 函数
            // 请确保 channel/smzdm.php 内部通过 getenv('COOKIE_SMZDM') 获取了 Cookie
            try {
                $resp = notify_message(smzdm());
            } catch (\Throwable $e) {
                // 捕获异常，防止因为网络问题导致直接静默退出
                $resp = notify_message([
                    'title' => $title, 
                    'status' => false, 
                    'reason' => '代码执行异常: ' . $e->getMessage()
                ]);
            }
            break;
    }

    // 只要 $resp 不为空，说明程序走完了签到逻辑
    if ($resp !== '') {
        printf("%s 正在签到...\n", $title);
        printf("最终结果: %s\n", $resp); // 打印最终结果，方便在 Actions 里看
        
        $check_in = new Notify($resp, $title);
        
        // 发送通知，如果没配置对应的 Token 可能不会推送，但不会报错
        $check_in->pusher();
        $check_in->dingtalk();
    } else {
        // 如果 $resp 是空的，说明代码出问题了，直接在 GitHub 报错
        printf("%s 签到失败：没有获取到返回结果，请检查 channel/smzdm.php 中的 Cookie 获取逻辑！\n", $title);
        exit(1); // 非0退出码，让 GitHub Actions 显示为失败（红色）
    }
}

// 全部执行完毕，退出成功
echo "所有签到流程结束。\n";
exit(0);
