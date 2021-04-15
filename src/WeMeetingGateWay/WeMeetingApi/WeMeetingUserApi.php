<?php

namespace WeMeetingGateWay\WeMeetingApi;

use WeMeetingGateWay\Exception\WeMeetingException;

/**
 * 腾讯会议api代理；用户接口部分
 * Trait WeMeetingUserApi
 * @package WeMeetingGateWay\WeMeetingApi
 */
trait WeMeetingUserApi
{
    // api请求的参数map
    private $user_api_config = [
        'CREATE_USER' => [
            'method' => 'POST',
            'uri' => self::MEETING_USER_API_PATH,

        ],
        'UPDATE_USER' => [
            'method' => 'PUT',
            'uri' => self::MEETING_USER_API_PATH . '/{userid}',
        ],
        'GET_USER_INFO' => [
            'method' => 'GET',
            'uri' => self::MEETING_USER_API_PATH . '/{userid}',
        ],
        'GET_USERS_LIST' => [
            'method' => 'GET',
            'uri' => self::MEETING_USER_API_PATH . '/list?page={page}&page_size={page_size}',
        ],
        'DEL_USER' => [
            'method' => 'DELETE',
            'uri' => self::MEETING_USER_API_PATH . '/{userid}',
        ],
    ];

    /**
     * 是否启用了腾讯会议的企业用户管理功能
     * 腾讯会议的注册用户才可以使用企业用户管理功能
     * 未注册的userid创建的会议，在会议客户端中无法看到会议列表
     */
    private $user_registered = true;

    /**
     * 设置当前用户是否为注册用户
     * @param bool $is_registered
     */
    public function setUserRegistered($is_registered=true)
    {
        $this->user_registered = (bool) $is_registered;
    }

    /**
     * 创建用户，详细参数说明：https://cloud.tencent.com/document/product/1095/43675
     * @param $userid 用户的唯一 ID
     * @param $email 邮箱地址
     * @param $phone 手机号码
     * @param $username 用户昵称
     * @return mixed
     * @throws WeMeetingException
     */
    public function createUser($userid, $email, $phone, $username)
    {
        if (empty($userid) || empty($email) || empty($phone) || empty($username)) {
            throw new WeMeetingException("缺少必要参数");
        }
        $request_param = $this->user_api_config['CREATE_USER'];
        $user_info = [
            'email' => $email,
            'phone' => $phone,
            'username' => $username,
            'userid' => $userid,
        ];
        return $this->getMeetingApiResponse($request_param['uri'], $request_param['method'], $user_info);
    }

    /**
     * 更新用户 参考文档： https://cloud.tencent.com/document/product/1095/43676
     * @param $userid 用户的唯一ID
     * @param string $email 新的邮箱地址
     * @param string $username 新的用户昵称
     * @return mixed 成功返回空消息体，失败返回错误码和错误信息。
     * @throws WeMeetingException
     */
    public function updateUser($userid, $email='', $username='')
    {
        if (empty($userid)) {
            throw new WeMeetingException("缺少userid参数");
        }
        if (empty($email) && empty($username)) {
            throw new WeMeetingException("缺少更新数据");
        }
        if (!empty($email)) {
            $user_info['email'] = $email;
        }
        if (!empty($username)) {
            $user_info['username'] = $username;
        }
        $request_param = $this->user_api_config['UPDATE_USER'];
        $uri = str_replace('{userid}', $userid, $request_param['uri']);
        return $this->getMeetingApiResponse($uri, $request_param['method'], $user_info);
    }

    /**
     * 获取用户详情 参考文档： https://cloud.tencent.com/document/product/1095/43677
     * @param $userid 用户的唯一ID
     * @return mixed 返回用户相关的信息
     * @throws WeMeetingException
     */
    public function getUserInfo($userid)
    {
        if (empty($userid)) {
            throw new WeMeetingException("缺少userid参数");
        }
        $request_param = $this->user_api_config['GET_USER_INFO'];
        $uri = str_replace('{userid}', $userid, $request_param['uri']);
        return $this->getMeetingApiResponse($uri, $request_param['method']);
    }

    /**
     * 获取用户列表 参考文档： https://cloud.tencent.com/document/product/1095/43678
     * @param $page 当前页，默认为1
     * @param $page_size 分页大小，默认为10，最大为20
     * @return mixed 返回用户列表和分页信息
     * @throws WeMeetingException
     */
    public function getUserList($page=1, $page_size=10)
    {
        $page = (int)$page;
        $page_size = (int)$page_size;
        if ($page <= 0 || $page_size <= 0) {
            throw new WeMeetingException("参数错误");
        }

        $request_param = $this->user_api_config['GET_USERS_LIST'];
        $uri = str_replace('{page}', $page, $request_param['uri']);
        $uri = str_replace('{page_size}', $page_size, $uri);
        return $this->getMeetingApiResponse($uri, $request_param['method']);
    }

    /**
     * 删除用户 参考文档：https://cloud.tencent.com/document/product/1095/43679
     * @param $userid 用户的唯一ID
     * @return mixed 成功返回空消息体，失败返回错误码和错误信息
     * @throws WeMeetingException
     */
    public function delUser($userid)
    {
        if (empty($userid)) {
            throw new WeMeetingException("缺少userid参数");
        }
        $request_param = $this->user_api_config['DEL_USER'];
        $uri = str_replace('{userid}', $userid, $request_param['uri']);
        return $this->getMeetingApiResponse($uri, $request_param['method']);
    }
}