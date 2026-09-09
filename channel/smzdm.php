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

    $cookie = getenv('COOKIE_SMZDM');
    if (! $cookie) {
        printf("检测不到 smzdm Cookie\n");
        $resp['reason'] = 'cookie 不存在';
        return $resp;
    }

    $headers = [
        'Accept' => '*/*',
        'Accept-Encoding' => 'gzip, deflate, br',
        'Accept-Language' => 'zh-CN,zh;q=0.9',
        'Connection' => 'keep-alive',
        'Host' => 'zhiyou.smzdm.com',
        'Referer' => 'https://www.smzdm.com/',
        'Sec-Fetch-Dest' => 'script',
        'Sec-Fetch-Mode' => 'no-cors',
        'Sec-Fetch-Site' => 'same-site',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36',
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
            $resp['reason'] = '请求失败，接口返回为空，可能是Cookie失效或网络被拦截';
            return $resp;
        }

        $response = json_decode($contents, true);

        // 检查 json_decode 是否成功，以及是否为空
        if (!is_array($response)) {
            $resp['reason'] = '解析接口数据失败，返回的数据不是有效的JSON格式';
            return $resp;
        }

        if ($response['error_code'] !== 0) {
            $resp['reason'] = $response['error_msg'] ?? '未知错误';
            return $resp;
        }

        $data = $response['data'];
        $resp['reason'] = sprintf("\n⭐⭐⭐签到成功 %s 天⭐⭐⭐\n🏅🏅🏅金币[%d]\n🏅🏅🏅积分[%d]\n🏅🏅🏅经验[%d]\n🏅🏅🏅等级[%d]\n🏅🏅补签卡[%s]",
            $data['checkin_num'],
            $data['gold'],
            $data['point'],
            $data['exp'],
            $data['rank'],
            $data['cards'],
        );
        $resp['status'] = true;

    } catch (\Throwable $e) {
        // 捕获网络请求等任何底层异常
        $resp['reason'] = '发生底层异常: ' . $e->getMessage();
    }

    return $resp;
}
