<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once 'vendor/autoload.php';
include_once 'notify.php';
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

    switch ($value['name']) {
        case 'smzdm':
            $title = $value['title'];
            $resp = notify_message(smzdm());
            break;
    }

    if ($resp !== '') {
        printf("%s 正在签到...\n", $title);
        printf("最终结果: %s\n", $resp);
        
        $check_in = new Notify($resp, $title);
        $check_in->pusher();
        $check_in->dingtalk();
    } else {
        printf("%s 签到失败：没有获取到返回结果！\n", $title);
        exit(1);
    }
}

echo "所有签到流程结束。\n";
exit(0);
