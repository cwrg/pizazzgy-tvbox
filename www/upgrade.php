<?php

class Upgrade
{

    const URI = 'https://9877.kstore.space/Market/market.json';

    /**
     * 直播源
     * @var array[]
     */
    public static $lives = [
        [
            'name' => 'ssili126',
            'type' => 0,
            'url' => 'https://ghproxy.net/raw.githubusercontent.com/ssili126/tv/main/itvlist.txt',
            'playerType' => 1,
            'timeout' => 10
        ]
    ];

    /**
     * 获取版本号
     * @return false|string
     */
    public function getVersion()
    {
        $file = './version';
        if (!file_exists($file)) {
            return 0;
        }
        return file_get_contents($file);
    }

    /**
     * 获取升级信息
     * @return array|mixed
     */
    public function getUpgradeInfo()
    {
        $data = file_get_contents($this->getUpgradeUrl());
        $data = json_decode($data, true);
        $result = [];
        foreach ($data as $item) {
            foreach ($item['list'] as $value) {
                if (strpos($value['url'], '单线路.zip') !== false) {
                    $result = $value;
                    break;
                }
            }
        }
        if (!$result) {
            $this->result(0, '获取升级信息失败');
        }
        if ($this->getVersion() == $result['version']) {
            $this->result(0, '当前已是最新版本', ['version' => $this->getVersion()]);
        }
        return $result;
    }

    /**
     * 获取更新地址
     * @return mixed|string
     */
    public function getUpgradeUrl()
    {
        $url = self::URI;
        if (file_exists('./resource/TVBoxOSC/tvbox/api.json')) {
            $json = file_get_contents('./resource/TVBoxOSC/tvbox/api.json');
            $json = json_decode($json, true);
            $sites = array_column($json['sites'], null, 'api');
            $url = $sites['csp_Market']['ext'] ?? $url;
        }
        return $url;
    }

    /**
     * 下载文件
     * @param $url
     * @param $version
     * @return string
     */
    public function download($url, $version): string
    {
        $file = './upgrade/' . $version . '.zip';
        is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        file_put_contents($file, $data);
        return $file;
    }

    /**
     * 解压文件
     * @param string $file
     * @return void
     */
    private function unzip(string $file)
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            $this->result(0, '升级失败');
        }
        is_dir('./resource') || mkdir('./resource', 0777, true);
        $zip->extractTo('./resource');
        $zip->close();
    }

    /**
     * 合并直播源
     * @param $lives
     * @return void
     */
    private function mergeLives($lives)
    {
        $file = './resource/TVBoxOSC/tvbox/api.json';
        $data = file_get_contents($file);
        $data = json_decode($data, true);
        $data['lives'] = array_merge($data['lives'], $lives);
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * @param $code
     * @param $msg
     * @param array $data
     * @return void
     */
    public function result($code, $msg, array $data = [])
    {
        exit(json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return void
     */
    public static function run()
    {
        $static = new static();
        $upgrade = $static->getUpgradeInfo();
        $file = $static->download($upgrade['url'], $upgrade['version']);
        $static->unzip($file);
        $static->mergeLives(self::$lives);
        file_put_contents('./version', $upgrade['version']);
        $static->result(1, '升级成功', ['version' => $upgrade['version']]);
    }

}

Upgrade::run();

