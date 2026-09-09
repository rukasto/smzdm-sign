<?php

function smzdm(): array
{
    $url = 'https://zhiyou.smzdm.com/user/checkin/jsonp_checkin';

    $resp = [
        'title' => '什么值得买 签到',
        'reason' => '',
        'status' => false,
    ];

    // 1. 获取 Cookie (支持环境变量，也支持本地测试时直接写死)
    $cookie = getenv('COOKIE_SMZDM');
    // 如果本地测试没有配环境变量，请解开下面这行注释，填入你浏览器里最新的 Cookie
    // $cookie = '你的浏览器复制出来的完整Cookie字符串'; 

    if (! $cookie) {
        $resp['reason'] = 'cookie 不存在，请检查 GitHub Secrets';
        return $resp;
    }

    // 2. 获取代理 (如果是本地测试，直接解开下一行注释)
    $proxy = getenv('PROXY_SOCKS5');
    // $proxy = '191.96.254.138:6185:pbskpsxc:8hqnavnp3r20'; // 本地测试解开这行

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

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_ENCODING, ''); 

    // 3. 设置代理
    if (!empty($proxy)) {
        $parts = explode(':', $proxy);
        if (count($parts) === 4) {
            list($ip, $port, $user, $pass) = $parts;
            curl_setopt($ch, CURLOPT_PROXY, "{$ip}:{$port}");
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$user}:{$pass}");
            printf(">>> 正在通过代理 %s 发起请求...\n", $ip);
        }
    } else {
        printf(">>> 未使用代理，正在本地直连...\n");
    }

    // 4. 执行请求并获取结果
    $contents = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $resolvedIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP); // 获取实际连上的IP
    curl_close($ch);

    printf(">>> HTTP状态码: %d\n", $httpCode);
    printf(">>> 实际出口IP: %s\n", $resolvedIp); // 这行非常重要，看你到底走了哪个IP
    printf(">>> 接口原始返回内容: %s\n", $contents);

    if ($curlError) {
        $resp['reason'] = 'cURL 请求错误: ' . $curlError;
        return $resp;
    }

    if (empty($contents)) {
        $resp['reason'] = '请求失败，接口返回为空';
        return $resp;
    }

    $response = json_decode($contents, true);

    if (!is_array($response)) {
        $resp['reason'] = '解析接口数据失败';
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
