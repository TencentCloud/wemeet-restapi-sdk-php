<?php

namespace WeMeetingGateWay;

use WeMeetingGateWay\Credential;

/**
 * 代理发送请求
 * Class HttpProxy
 * @package WeMeetingGateWay
 */
class HttpProxy
{
    // 请求url
    private $url = "";
    // 请求方法
    private $method = "";

    // 请求代理的地址
    private $proxy_url = "";
    //请求头
    private $header = [];
    //请求body
    private $post_data = "";

    //默认超时时间
    private $timeout = 3;

    // 支持的请求方法
    const ALLOW_METHODS = ['GET', 'POST', 'DELETE', 'PUT'];

    /**
     * 设置请求 url
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 设置请求超时
     * @param $timeout
     * @return $this
     * @throws \Exception
     */
    public function setTimeOut($timeout)
    {
        $timeout = (int)$timeout;
        if ($timeout < 0 || $timeout > 30) {
            throw new \Exception("超时时间不合法");
        }
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 设置请求方法
     * @param $method
     * @return $this
     * @throws \Exception
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);
        if (!in_array($method, self::ALLOW_METHODS)) {
            throw new \Exception("不支持的方法");
        }
        $this->method = $method;
        return $this;
    }

    /**
     * 设置请求头参数
     * @param $header
     * @return $this
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 返回请求头参数
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * 设置请求body
     * @param $json_string
     * @return $this
     */
    public function setPostJson($json_string)
    {
        $this->post_data = $json_string;
        return $this;
    }

    /**
     * 配置请求代理
     * @param $proxy_url
     * @return $this
     */
    public function setCurlProxy($proxy_url)
    {
        $this->proxy_url = $proxy_url;
        return $this;
    }

    /**
     * 发送请求
     * @return bool|string
     * @throws \Exception
     */
    private function http()
    {
        $curl_opts = array(
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );
        foreach ($this->header as $k => $v) {
            $curl_opts[CURLOPT_HTTPHEADER][] = $k . ": " . $v;
        }
        $curl_opts[CURLOPT_URL] = $this->url;
        if (!empty($this->proxy_url)) {
            $curl_opts[CURLOPT_PROXY] = $this->proxy_url;
        }
        $curl_opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        if (($this->method == 'POST' || $this->method == 'PUT') && !empty($this->post_data)) {
            $curl_opts[CURLOPT_POSTFIELDS] = $this->post_data;
        }
        switch ($this->method) {
            case 'POST':
                $curl_opts[CURLOPT_POST] = 1;
                break;
            case 'PUT':
                $curl_opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;
            case 'DELETE':
                $curl_opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $curl_opts);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if (!empty($error)) {
            throw new \Exception($error);
        }
        return $response;
    }

    /**
     * 发送请求
     * @param string $url 请求url
     * @param string $method 请求方法
     * @param array $header 请求头参数
     * @param string $post_json 请求body
     * @return bool|string 返回body
     * @throws \Exception
     */
    public function sendRequest($url, $method, array $header=[], $post_json='')
    {
        $this->setUrl($url);
        $this->setMethod($method);
        $this->setHeader($header);
        $this->setPostJson($post_json);
        return $this->http();
    }
}
