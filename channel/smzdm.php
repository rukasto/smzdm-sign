<?php

use Pusher\Channel\Webhook;
use Pusher\Message\WebhookMessage;
use Pusher\Pusher;

function smzdm(): array
{
    $url = 'https://zhiyou.smzdm.com/user/checkin/jsonp_checkin';

    $resp = [
        'title' => '什么值得买 签到',
        'reason' => '',
        'status' => false,
    ];

    // 获取 GitHub Secrets 中传入的 Cookie
    $cookie = getenv('COOKIE_SMZDM');
    if (! $cookie) {
        printf("检测不到 smzdm Cookie\n");
        $resp['reason'] = 'cookie 不存在';
        return $resp;
    }

    // 添加一个随机延迟，模拟真人操作，降低被风控的可能 (0.5秒 ~ 2秒)
    usleep(random_int(500000, 2000000));

    // 使用较新的浏览器 User-Agent
    $headers = [
        'Accept' => '*/*',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
        'Connection' => 'keep-alive',
        'Host' => 'zhiyou.smzdm.com',
        'Referer' => 'https://www.smzdm.com/',
        'Sec-Fetch-Dest' => 'script',
        'Sec-Fetch-Mode' => 'no-cors',
        'Sec-Fetch-Site' => 'same-site',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    ]; 
    $headers['Cookie'] = $cookie;

    $options = [
        'headers' => $headers,
    ];

    try {
        $channel = new Webhook();
        $channel->setReqURL($url)
            ->setMethod(Pusher::METHOD_GET)
            ->setOptions($options);

        $message = new WebhookMessage();

        $channel->request($message);
        
        // 获取返回内容
        $contents = $channel->getContents();
        
        // 打印一下原生的返回内容，方便排错
        printf("接口原始返回内容: %s\n", $contents);

        // 判断返回内容是否为空
        if (empty($contents)) {
            $resp['reason'] = '请求失败，接口返回为空，可能是网络被拦截';
            return $resp;
        }

        $response = json_decode($contents, true);

        // 检查 json_decode 是否成功，以及是否为空
        if (!is_array($response)) {
            $resp['reason'] = '解析接口数据失败，返回的数据不是有效的JSON格式';
            return $resp;
        }

        // 判断是否成功
        // 如果 error_code 不是 0，则提取报错信息
        if (($response['error_code'] ?? -1) !== 0) {
            $resp['reason'] = $response['error_msg'] ?? '未知错误';
            // 如果真的是验证码错误，给出提示
            if (isset($response['error_msg']) && strpos($response['error_msg'], '验证码') !== false) {
                $resp['reason'] .= ' (GitHub服务器IP被风控，需手动登录获取新Cookie或本地运行)';
            }
            return $resp;
        }

        $data = $response['data'];
        $resp['reason'] = sprintf("\n⭐⭐⭐签到成功 %s 天⭐⭐⭐\n🏅🏅🏅金币[%d]\n🏅🏅🏅积分[%d]\n🏅🏅🏅经验[%d]\n🏅🏅🏅等级[%d]\n🏅🏅补签卡[%s]",
            $data['checkin_num'] ?? '未知',
            $data['gold'] ?? 0,
            $data['point'] ?? 0,
            $data['exp'] ?? 0,
            $data['rank'] ?? 0,
            $data['cards'] ?? '无',
        );
        $resp['status'] = true;

    } catch (\Throwable $e) {
        // 捕获网络请求等任何底层异常
        $resp['reason'] = '发生底层异常: ' . $e->getMessage();
    }

    return $resp;
}
