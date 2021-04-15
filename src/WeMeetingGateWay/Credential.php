<?php

namespace WeMeetingGateWay;

use WeMeetingGateWay\Exception\WeMeetingException;

/**
 * 生成http头中的签名 X-TC-Signature
 * Class Credential
 * @package WeMeetingGateWay
 */
class Credential
{
    /*
     * 签名加密方法
     */
    const SIGN_METHOD = 'sha256';
    /**
     * @var string secretId
     */
    private $secretId;

    /**
     * @var string secretKey
     */
    private $secretKey;

    /**
     * Credential constructor.
     * @param string $secretId secretId
     * @param string $secretKey secretKey
     */
    public function __construct($secretId, $secretKey)
    {
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
    }

    /**
     * 返回secretId
     * @return string
     */
    public function getSecretId()
    {
        return $this->secretId;
    }

    /**
     * @param $header = [
     * 'X-TC-Nonce' => '',  // 必须， X-TC-Nonce请求头，随机数
     * 'X-TC-Timestamp' => '', //必须， X-TC-Timestamp请求头，当前时间的秒级时间戳
     * 'URI' => ''  //必须，请求uri，eg：/v1/meetings,
     * ];
     * @param $body  请求体，没有的设为空串
     * @param $method 请求方式 字符串 "POST","GET"
     * @return string
     */
    public function sign($header, $body, $method)
    {
        if (empty($this->secretId) || empty($this->secretKey)) {
            throw new WeMeetingException("缺少secretId或者secretKey");
        }
        $header_string = "X-TC-Key={$this->secretId}&X-TC-Nonce={$header['X-TC-Nonce']}&X-TC-Timestamp={$header['X-TC-Timestamp']}";
        $str_to_sign = "{$method}\n{$header_string}\n{$header['URI']}\n{$body}";
        $hash = hash_hmac(self::SIGN_METHOD, $str_to_sign, $this->secretKey);
        return base64_encode($hash);
    }
}