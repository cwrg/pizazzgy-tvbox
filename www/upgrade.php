<?php

class Upgrade
{

    const URI = 'https://9877.kstore.space/Market/market.json';

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
        file_put_contents($file, file_get_contents($url));
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
        file_put_contents('./version', $upgrade['version']);
        $static->result(1, '升级成功', ['version' => $upgrade['version']]);
    }

}

Upgrade::run();

