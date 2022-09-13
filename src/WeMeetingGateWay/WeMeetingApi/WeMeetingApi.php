<?php

namespace WeMeetingGateWay\WeMeetingApi;

use WeMeetingGateWay\Credential;
use WeMeetingGateWay\HttpProxy;
use WeMeetingGateWay\WeMeetingApi\WeMeetingUserApi;
use WeMeetingGateWay\Exception\WeMeetingException;

/**
 * 腾讯会议api代理
 * Class WeMeetingApi
 * @package WeMeetingGateWay\WeMeetingApi
 */
class WeMeetingApi
{
    // 腾讯会议api域名
    const API_HOST = "https://api.meeting.qq.com";
    // 会议接口路径
    const MEETING_API_PATH = "/v1/meetings";
    // 会议用户操作接口路径
    const MEETING_USER_API_PATH = "/v1/users";

    // 认证对象
    private $cred = null;
    // appid
    private $appid = "";
    // 用户id
    private $uid = "";
    // 用户的设备类型
    private $instanceid = 0;
    // http请求代理对象
    private $http_proxy = null;
    // 请求公共参数
    private $common_header = [];

    // api请求的参数map
    private $meeting_api_config = [
        'CREATE_MEETING' => [
            'method' => 'POST',
            'uri' => self::MEETING_API_PATH,
        ],
        'QUERY_MEETING' => [
            'method' => 'GET',
        ],
        'CANCEL_MEETING' => [
            'method' => 'POST',
            'uri' => self::MEETING_API_PATH . '/{meeting_id}/cancel',

        ],
        'DISMISS_MEETING' => [
            'method' => 'POST',
            'uri' => self::MEETING_API_PATH . '/{meeting_id}/dismiss',

        ],
        'UPDATE_MEETING' => [
            'method' => 'PUT',
            'uri' => self::MEETING_API_PATH . '/{meeting_id}',

        ],
        'MEETING_PP' => [
            'method' => 'GET',
            'uri' => self::MEETING_API_PATH . '/{meeting_id}/participants',
        ],
        'GET_MEETINGS' => [
            'method' => 'GET',
            'uri' => self::MEETING_API_PATH . '?userid={userid}&instanceid={instanceid}',
        ],
        'UPDATE_LIVE' => [
            'method' => 'PUT',
            'uri' => self::MEETING_API_PATH . '/{meeting_id}/live_play/config',
        ],
        'LIVE_REPLAYS' => [
            'method' => 'GET',
            'uri' => self::MEETING_API_PATH . '/{meeting_id}/live_play/replays?userid={userid}&instanceid={instanceid}',
        ],
        'DEL_LIVE_REPLAYS' => [
            'method' => 'DELETE',
            'uri' => self::MEETING_API_PATH . '/{meeting_id}/live_play/{live_room_id}/replays?userid={userid}&instanceid={instanceid}'
        ],
    ];
    // 会议用户操作的接口
    use WeMeetingUserApi;

    /**
     * WeMeetingApi constructor.
     * @param $cred
     * @param $appid
     */
    public function __construct(Credential $cred, $appid, $uid='')
    {
        if (!empty($uid)) {
            $this->uid = $uid;
        }
        $this->cred = $cred;
        $this->appid = $appid;
    }

    /**
     * 设置请求用户的终端设备类型
     * @param $instanceid
     */
    public function setInstanceId($instanceid)
    {
        $instanceid = (int)$instanceid;
        if ($instanceid < 1 || $instanceid > 8) {
            throw new WeMeetingException("终端设备类型参数错误");
        }
        $this->instanceid = $instanceid;
        return $this;
    }

    /**
     * 设置调用接口使用的uid
     * @param $uid
     */
    public function setUser($uid)
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * 设置其他的公共参数(http头)
     * 具体支持的公共参数参考: https://cloud.tencent.com/document/product/1095/42413#.E5.85.AC.E5.85.B1.E5.8F.82.E6.95.B0
     * @param array $header
     */
    public function setCommonHeader(array $header)
    {
        $this->common_header = $header;
        return $this;
    }

    /**
     * 设置请求对象
     * @param $http_proxy
     */
    public function setHttpProxy(HttpProxy $http_proxy)
    {
        $this->http_proxy = $http_proxy;
        return $this;
    }

    /**
     * 设置http头参数
     * @param $uri
     * @param $post_json
     * @param $method
     * @return array
     */
    private function getApiHeader($uri, $post_json, $method)
    {
        $sign_header = $http_header = array(
            'X-TC-Key' => $this->cred->getSecretId(),
            'X-TC-Timestamp' => time(),
            'X-TC-Nonce' => rand(1, 999999999),
            'AppId' => $this->appid
        );
        $sign_header['URI'] = $uri;
        $post_json_str = '';
        if (!empty($post_json)) {
            $post_json_str = json_encode($post_json);
        }
        // sha256加密认证参数
        $http_header['X-TC-Signature'] = $this->cred->sign($sign_header, $post_json_str, $method);
        if ($this->user_registered) {
            $http_header['X-TC-Registered'] = 1;
        }
        $http_header = array_merge($this->common_header, $http_header);
        return $http_header;
    }

    /**
     * 创建会议，参数说明参考：https://cloud.tencent.com/document/product/1095/42417
     * @param $meeting_config
     * @return mixed|null 返回会议信息
     * @throws WeMeetingException
     */
    public function createMeeting(array $meeting_config)
    {
        if (empty($meeting_config)) {
            throw new WeMeetingException("缺少创建会议的参数");
        }
        $meeting_config['userid'] = !isset($meeting_config['userid']) ? $this->uid : $meeting_config['userid'];
        $meeting_config['instanceid'] = !isset($meeting_config['instanceid']) ? $this->instanceid : $meeting_config['instanceid'];

        $request_param = $this->meeting_api_config['CREATE_MEETING'];
        // 请求
        return $this->getMeetingApiResponse($request_param['uri'], $request_param['method'], $meeting_config);
    }

    /**
     * 查询会议,会议ID或者会议Code参数至少一个必须
     * 参考文档：https://cloud.tencent.com/document/product/1095/42420
     * @param string $meeting_id 会议ID
     * @param string $meeting_code 会议Code
     * @return mixed|null 返回会议信息
     * @throws WeMeetingException
     */
    public function getMeeting($meeting_id=null, $meeting_code=null)
    {
        if (empty($meeting_id) && empty($meeting_code)) {
            throw new WeMeetingException("缺少会议ID参数或者会议Code");
        }
        if (!empty($meeting_id)) {
            $uri = self::MEETING_API_PATH . '/' . $meeting_id . "?userid={$this->uid}&instanceid={$this->instanceid}";
        } else {
            $uri = self::MEETING_API_PATH . "?meeting_code={$meeting_code}&userid={$this->uid}&instanceid={$this->instanceid}";
        }
        $method = $this->meeting_api_config['QUERY_MEETING']['method'];

        return $this->getMeetingApiResponse($uri, $method);
    }

    /**
     * 取消会议 参考文档：https://cloud.tencent.com/document/product/1095/42422
     * @param $meeting_id 会议id
     * @param array $cancel_reason [
     * 'reason_code' => 1,  //必须,原因代码，可为用户自定义
     * 'reason_detail' => '详细取消原因描述'
     * ]
     * @return mixed|null 无输出参数，成功返回空消息体，失败返回 错误码 和错误信息
     * @throws WeMeetingException
     */
    public function cancelMeeting($meeting_id, array $cancel_reason)
    {
        return $this->cancelOrDismissMeeting($meeting_id, $cancel_reason, 0);
    }

    /**
     * 结束会议，cancel_param参数参考文档：https://cloud.tencent.com/document/product/1095/47659
     * @param $meeting_id  会议id
     * @param array $cancel_param 取消原因  eg: ['reason_code' => 1 ,'reason_detail' => '时间冲突']
     * @return mixed|null
     * @throws WeMeetingException
     */
    public function dismissMeeting($meeting_id, array $cancel_param)
    {
        return $this->cancelOrDismissMeeting($meeting_id, $cancel_param, 1);
    }

    /**
     * 取消或者结束会议操作
     * @param $meeting_id
     * @param array $cancel_param
     * @param int $opt_flag
     * @return mixed|null
     * @throws WeMeetingException
     */
    private function cancelOrDismissMeeting($meeting_id, array $cancel_param, $opt_flag=0)
    {
        if (empty($meeting_id)) {
            throw new WeMeetingException("缺少会议ID参数");
        }
        if (!isset($cancel_param['reason_code'])) {
            throw new WeMeetingException("缺少原因代码");
        }
        $opt_param['userid'] = !isset($opt_param['userid']) ? $this->uid : $opt_param['userid'];
        $opt_param['instanceid'] = $this->instanceid;
        $opt_param['reason_code'] = $cancel_param['reason_code'];
        $opt_param = array_merge($opt_param, $cancel_param);

        // 判断操作是取消还是结束
        if ($opt_flag) {  // 结束
            $request_param = $this->meeting_api_config['DISMISS_MEETING'];
        } else {  // 取消
            $request_param = $this->meeting_api_config['CANCEL_MEETING'];
        }
        $uri = str_replace('{meeting_id}', $meeting_id, $request_param['uri']);

        return $this->getMeetingApiResponse($uri, $request_param['method'], $opt_param);
    }

    /**
     * 修改会议 参考文档：https://cloud.tencent.com/document/product/1095/42424
     * @param $meeting_id 会议id
     * @param array $meeting_config 更新的会议设置
     * @return mixed|null
     * @throws WeMeetingException
     */
    public function updateMeeting($meeting_id, array $meeting_config)
    {
        if (empty($meeting_id)) {
            throw new WeMeetingException("缺少会议ID参数");
        }
        if (empty($meeting_config)) {
            throw new WeMeetingException("缺少会议设置参数");
        }

        $request_param = $this->meeting_api_config['UPDATE_MEETING'];
        $uri = str_replace('{meeting_id}', $meeting_id, $request_param['uri']);
        $meeting_config['userid'] = $this->uid;
        $meeting_config['instanceid'] = $this->instanceid;

        return $this->getMeetingApiResponse($uri, $request_param['method'], $meeting_config);
    }

    /**
     * 获取参会成员列表 参考文档：https://cloud.tencent.com/document/product/1095/42701
     * @param $meeting_id  会议id
     * @param array $page_param 分页参数 page,page_size,start_time,end_time
     * @return mixed|null
     * @throws WeMeetingException
     */
    public function getParticiPants($meeting_id, array $page_param=[])
    {
        if (empty($meeting_id)) {
            throw new WeMeetingException("缺少会议ID参数");
        }
        $request_param = $this->meeting_api_config['MEETING_PP'];
        $uri = str_replace('{meeting_id}', $meeting_id, $request_param['uri']);
        $uri .= "?userid={$this->uid}";
        if (!isset($page_param['size'])) {
            $uri .= '&size=' . $page_param['size'];
        }
        if (!isset($page_param['pos'])) {
            $uri .= '&pos=' . $page_param['pos'];
        }
        if (!isset($page_param['start_time'])) {
            $uri .= '&start_time=' . $page_param['start_time'];
        }
        if (!isset($page_param['end_time'])) {
            $uri .= '&end_time=' . $page_param['end_time'];
        }

        return $this->getMeetingApiResponse($uri, $request_param['method']);
    }

    /**
     * 查询用户的会议列表 参考文档：https://cloud.tencent.com/document/product/1095/42421
     * 用户必须为腾讯会议注册用户，否则返回的会议列表为空
     * @param int $pos 分页获取用户会议列表的查询起始时间值
     * @return mixed|null
     * @throws WeMeetingException
     */
    public function getMeetings($pos=0)
    {
        $request_param = $this->meeting_api_config['GET_MEETINGS'];
        $uri = str_replace('{userid}', $this->uid, $request_param['uri']);
        $uri = str_replace('{instanceid}', $this->instanceid, $uri);
        if (intval($pos) > 0) {
            $uri = $uri . "&pos=" . intval($pos);
        }
        return $this->getMeetingApiResponse($uri, $request_param['method']);
    }

    /**
     * 修改直播配置 参考文档：https://cloud.tencent.com/document/product/1095/49222
     * @param $meeting_id 会议id
     * @param $meeting_code 会议Code
     * @param array $live_config 直播配置对象
     * @return mixed|null  成功则返回空消息体，失败返回错误码和错误信息。
     * @throws WeMeetingException
     */
    public function updateLivePlay($meeting_id, $meeting_code, array $live_config)
    {
        if (empty($meeting_id) && empty($meeting_code)) {
            throw new WeMeetingException("缺少会议ID参数或者会议Code");
        }
        if (empty($live_config)) {
            throw new WeMeetingException("缺少直播信息参数");
        }
        $request_param = $this->meeting_api_config['UPDATE_LIVE'];
        if (!empty($meeting_id)) {
            $uri = str_replace('{meeting_id}', $meeting_id, $request_param['uri']);
        } else {
            $uri = str_replace('{meeting_id}/', '', $request_param['uri']);
            $live_setting['meeting_code'] = $meeting_code;
        }
        $live_setting['userid'] = $this->uid;
        $live_setting['live_config'] = $live_config;
        $live_setting['instanceid'] = $this->instanceid;

        return $this->getMeetingApiResponse($uri, $request_param['method'], $live_setting);
    }

    /**
     * 获取直播回看地址 参考文档：https://cloud.tencent.com/document/product/1095/49245
     * @param $meeting_id 会议id
     * @param $meeting_code 会议Code
     * @return mixed|null 返回直播回看信息
     * @throws WeMeetingException
     */
    public function getLiveReplays($meeting_id=null, $meeting_code=null)
    {
        if (empty($meeting_id) && empty($meeting_code)) {
            throw new WeMeetingException("缺少会议ID参数或者会议Code");
        }
        $request_param = $this->meeting_api_config['LIVE_REPLAYS'];
        $uri = str_replace('{userid}', $this->uid, $request_param['uri']);
        $uri = str_replace('{instanceid}', $this->instanceid, $uri);
        if (!empty($meeting_id)) {
            $uri = str_replace('{meeting_id}', $meeting_id, $uri);
        } else {
            $uri = str_replace('{meeting_id}/', '', $uri);
            $uri .= '&meeting_code=' . $meeting_code;
        }
        return $this->getMeetingApiResponse($uri, $request_param['method']);
    }

    /**
     * 删除直播回看文件
     * @param $meeting_id 会议id
     * @param $live_room_id 用户的终端设备类型
     * @return mixed|null 成功返回空消息体，失败返回错误码和错误信息。
     * @throws WeMeetingException
     */
    public function delLiveReplays($meeting_id, $live_room_id)
    {
        if (empty($meeting_id)) {
            throw new WeMeetingException("缺少会议ID参数");
        }
        if (empty($live_room_id)) {
            throw new WeMeetingException("缺少房间ID参数");
        }
        $request_param = $this->meeting_api_config['DEL_LIVE_REPLAYS'];
        $uri = str_replace('{userid}', $this->uid, $request_param['uri']);
        $uri = str_replace('{instanceid}', $this->instanceid, $uri);
        $uri = str_replace('{live_room_id}', $live_room_id, $uri);
        $uri = str_replace('{meeting_id}', $meeting_id, $uri);

        return $this->getMeetingApiResponse($uri, $request_param['method']);
    }

    /**
     * 发送请求，返回结果，返回类型一般为array
     * @param $uri
     * @param $method
     * @param array $post_json
     * @return mixed|null
     */
    private function getMeetingApiResponse($uri, $method, array $post_json=[])
    {
        if ($this->instanceid == 0) {
            throw new WeMeetingException("未设置用户的终端设备类型");
        }
        $header = $this->getApiHeader($uri, $post_json, $method);
        $url = self::API_HOST . $uri;
        $post_json_str = '';
        if (!empty($post_json)) {
            $post_json_str = json_encode($post_json);
        }

        $http_proxy = empty($this->http_proxy) ? (new HttpProxy) : $this->http_proxy;
        $response = $http_proxy->sendRequest($url, $method, $header, $post_json_str);
        if (!empty($response)) {
            return json_decode($response, true);
        }
        return null;
    }
}
