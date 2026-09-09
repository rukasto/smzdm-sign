<?php

// 注意：这里不需要 use Pusher 库了，我们直接使用 PHP 原生 cURL 来绕过验证码

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

    // 获取代理配置 (强烈建议在 GitHub Secrets 中存为 PROXY_SOCKS5 格式: ip:port:user:pass)
    // 如果本地测试，可以临时把下面这行改为： $proxy = '191.96.254.138:6185:pbskpsxc:8hqnavnp3r20';
    $proxy = getenv('PROXY_SOCKS5');

    $headers = [
        'Accept: */*',
        'Accept-Encoding: gzip, deflate, br',
        'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
        'Connection: keep-alive',
        'Host: zhiyou.smzdm.com',
        'Referer: https://www.smzdm.com/',
        'Sec-Fetch-Dest: script',
        'Sec-Fetch-Mode: no-cors',
        'Sec-Fetch-Site: same-site',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Cookie: ' . $cookie,
    ];

    // 初始化 cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过 SSL 证书验证，防止报错
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // 自动处理 gzip 压缩

    // 如果是本地测试，且没有配置 Secrets，可以直接解开下面这一行来测试代理
    // $proxy = '191.96.254.138:6185:pbskpsxc:8hqnavnp3r20';

    if (!empty($proxy)) {
        // 解析代理字符串格式: ip:port:user:pass
        $parts = explode(':', $proxy);
        if (count($parts) === 4) {
            list($ip, $port, $user, $pass) = $parts;
            
            // 设置 SOCKS5 代理
            curl_setopt($ch, CURLOPT_PROXY, "{$ip}:{$port}");
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            // 设置代理用户名和密码
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$user}:{$pass}");
        } else {
            printf("代理格式错误，应为 ip:port:user:pass\n");
        }
    } else {
        printf("未检测到 PROXY_SOCKS5 环境变量，将直连（大概率会被风控）\n");
    }

    // 执行请求
    $contents = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 打印 HTTP 状态码和原始返回内容
    printf("HTTP状态码: %d\n", $httpCode);
    printf("接口原始返回内容: %s\n", $contents);

    if ($curlError) {
        $resp['reason'] = 'cURL 请求错误: ' . $curlError;
        return $resp;
    }

    if (empty($contents)) {
        $resp['reason'] = '请求失败，接口返回为空，可能是代理连不上或网络被拦截';
        return $resp;
    }

    $response = json_decode($contents, true);

    if (!is_array($response)) {
        $resp['reason'] = '解析接口数据失败，返回的数据不是有效的JSON格式';
        return $resp;
    }

    if (($response['error_code'] ?? -1) !== 0) {
        $resp['reason'] = $response['error_msg'] ?? '未知错误';
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

    return $resp;
}
