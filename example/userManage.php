<?php
require __DIR__ . "/../src/load_sdk.php";

use WeMeetingGateWay\WeMeetingApi\WeMeetingApi;
use WeMeetingGateWay\Credential;
use WeMeetingGateWay\Exception\WeMeetingException;
use WeMeetingGateWay\HttpProxy;

/**
 * 企业用户相关的操作示例
 */
try {
    // 测试时请将以下参数替换成有效参数，相关id获得参考：https://cloud.tencent.com/document/product/1095/42413
    $secret_id = 'secretId';
    $secret_key = 'secretKey';
    $AppId = "200xxxx001"; // appid
    $sdk_id = "200xxxx001"; // SdkId

    // 创建api对象
    $cred = new Credential($secret_id, $secret_key);
    $meeting_user_api = new WeMeetingApi($cred, $AppId);
    // 设置当前用户的终端设备类型
    $meeting_user_api->setInstanceId(1);
    // 设置http头中的 SdkId（未分配可不填）,此方法可以用来设置其他非必须公共参数
    $meeting_user_api->setCommonHeader(['SdkId' => $sdk_id]);

    // 设置请求参数(非必须)
    $http_proxy = new HttpProxy();
    $http_proxy->setTimeOut(5); //请求超时时间为5秒
    // 请求代理配置
    // $http_proxy->setCurlProxy('ip:port');
    $meeting_user_api->setHttpProxy($http_proxy);

    /*
     * 设置当前将要创建的用户为未注册用户
     * 默认创建的是注册用户，传false则为未注册用户，
     * 使用未注册的 userid 创建的会议，在会议客户端中无法看到会议列表，可以正常使用会议短链接或会议号加入会议。
     * 建议创建注册用户，此处仅做示例
     */
    $meeting_user_api->setUserRegistered(false);

    /**
     * 创建用户
     */
    $userid = 'wemeeting_user123'; // 用户id
    $email = 'xiaoming@qq.com'; // email
    $phone = '18302277427';  // 电话，不可与已有的用户重复
    $username = 'xiaoming'; // 用户昵称
    $result = $meeting_user_api->createUser($userid, $email, $phone, $username);
    print_r($result);

    /**
     * 创建另外一个用户
     */
    $userinfo = [
        'userid' => 'user_1234',
        'email' => 'xxxxx@163.com',
        'phone' => '13012275752',
        'username' => 'zhangjun'
    ];
    $result = $meeting_user_api->createUser($userinfo['userid'], $userinfo['email'], $userinfo['phone'], $userinfo['username']);
    print_r($result);

    //更新用户
    $new_email = 'zhangsan@qq.com';
    $new_username = 'zhangsan';
    $result = $meeting_user_api->updateUser($userid, $new_email, $new_username);
    print_r($result);

    // 获取用户详情
    $user_details = $meeting_user_api->getUserInfo($userid);
    print_r($user_details);

    // 分页获取用户列表,会获取所有非注册和注册的用户
    $user_list = $meeting_user_api->getUserList(1, 20);
    print_r($user_list);

    // 删除用户
    $result = $meeting_user_api->delUser($userinfo['userid']);
    print_r($result);

} catch (WeMeetingException $e) {
    echo $e;
}