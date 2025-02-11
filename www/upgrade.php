<?php

class Upgrade
{

    const URI = 'http://www.fish2018.us.kg/p/jsm.json';


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
        $sites = array_column($data['sites'], null, 'key');
        $key = 'https://github.com/fish2018/PG';
        if (!isset($sites[$key])) {
            $this->result(0, '升级信息获取失败');
        }
        $upgrade = $sites[$key];
        if ($this->getVersion() == $upgrade['name']) {
            $this->result(0, '当前已是最新版本', ['version' => $this->getVersion()]);
        }
        return $upgrade;
    }

    /**
     * 获取更新地址
     * @return mixed|string
     */
    public function getUpgradeUrl()
    {
        return self::URI;
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
        $file = $static->download($upgrade['zip'], $upgrade['name']);
        $static->unzip($file);
        file_put_contents('./version', $upgrade['name']);
        $static->result(1, '升级成功', ['version' => $upgrade['name']]);
    }

}

Upgrade::run();

