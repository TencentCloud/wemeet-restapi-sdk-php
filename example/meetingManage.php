<?php

require __DIR__ . "/../src/load_sdk.php";

use WeMeetingGateWay\WeMeetingApi\WeMeetingApi;
use WeMeetingGateWay\Credential;
use WeMeetingGateWay\Exception\WeMeetingException;

/**
 * 会议相关的操作示例
 */
// example 1 :
try {
    // 测试时请将以下参数替换成有效参数，相关id获得参考：https://cloud.tencent.com/document/product/1095/42413
    $secret_id = 'secretId';
    $secret_key = 'secretKey';
    $AppId = "200xxxx001"; // appid
    $sdk_id = "200xxxx001"; // SdkId
    $userid = 'wemeeting_user123'; // 通过接口方法createUser创建

    $instanceid = 1; // 用户的终端设备类型
    // 创建api对象
    $cred = new Credential($secret_id, $secret_key);
    $meeting_api = new WeMeetingApi($cred, $AppId, $userid);
    $meeting_api->setInstanceId($instanceid);

    /**
     * 创建会议示例，会议参数的详细格式和含义参考文档：https://cloud.tencent.com/document/product/1095/42417
     */
    $meeting_config = [
        "subject" => "{$userid}'s meeting",
        "type" => 0,
        "hosts" => [$userid],  // 需要是注册用户
        "invitees" => ["wmeeting_u1", "wmeeting_u2", "wmeeting_u3"],
        "start_time" => (string)(time() + 10 * 60), // 字符串型
        "end_time" => (string)(time() + 40 * 60), // 字符串型
        "password" => "1111",
        "user_non_registered" => ["meeting00014"],
        "settings" => [
            "mute_enable_join" => true,
            "allow_unmute_self" => false,
            "mute_all" => false,
            "play_ivr_on_leave" => false,
            "play_ivr_on_join" => false,
            "allow_in_before_host" => true,
            "auto_in_waiting_room" => false,
            "allow_screen_shared_watermark" => false,
            "only_enterprise_user_allowed" => false
        ],
        "meeting_type" => 1,
        "recurring_rule" => [
            "recurring_type" => 0,
            "until_type" => 1,
            "until_count" => 7,
            "until_date" => time() + 86400 * 20, // 整型
        ],
        "enable_live" => true,
        "live_config" => [
            "live_subject" => "test_subject",
            "live_summary" => "test_summary",
            "enable_live_password" => true,
            "live_password" => "1234",
            "enable_live_im" => true,
            "enable_live_replay" => true,
        ],
    ];
    $meeting_info = $meeting_api->createMeeting($meeting_config);
    print_r($meeting_info);

    /**
     * 通过会议code查询会议示例
     */
    //得到上面创建的会议code
    $meeting_code = $meeting_info['meeting_info_list'][0]['meeting_code'];
    $meeting_info = $meeting_api->getMeeting('', $meeting_code);
    print_r($meeting_info);

    /*
     * 查询当前用户的会议列表示例
     * 当前用户($userid)必须是腾讯会议注册用户
     */
    $meeting_list = $meeting_api->getMeetings();
    print_r($meeting_list['meeting_info_list']);


} catch (WeMeetingException $e) {
    echo $e;
}

// example 2 :
try {
    /*
     * 获取会议开始后真实进入会议的参会成员列表示例
     */
    // 得到上面创建的会议id
    $meeting_id = $meeting_info['meeting_info_list'][0]['meeting_id'];
    $info = $meeting_api->getParticiPants($meeting_id);
    print_r($info['participants']);


    /**
     * 更新会议设置示例
     */
    $new_meeting_config = [
        "subject" => "update {$userid}'s meeting",
        "type" => 0,
        "invitees" => ["wemeeting_u2", "wemeeting_u3"],
        "start_time" => (string)(time() + 20 * 60),
        "end_time" => (string)(time() + 50 * 60),
        "password" => "1111",
        "user_non_registered" => [],
        "settings" => [
            "mute_enable_join" => true,
            "allow_unmute_self" => false,
            "mute_all" => false,
            "play_ivr_on_leave" => false,
            "play_ivr_on_join" => false,
            "allow_in_before_host" => true,
            "auto_in_waiting_room" => false,
            "allow_screen_shared_watermark" => false,
            "only_enterprise_user_allowed" => false
        ],
        "meeting_type" => 1,
        "recurring_rule" => [
            "recurring_type" => 0,
            "until_type" => 1,
            "until_count" => 7,
            "until_date" => time() + 86400 * 20
        ],
        "enable_live" => true,
        "live_config" => [
            "live_subject" => "test_subject 22",
            "live_summary" => "test_summary 22",
            "enable_live_password" => true,
            "live_password" => "1234",
            "enable_live_im" => true,
            "enable_live_replay" => true,
        ],
    ];
    $info = $meeting_api->updateMeeting($meeting_id, $new_meeting_config);
    print_r($info);


    /**
     * 修改直播配置示例
     */
    $live_config = [
        "live_subject" => "new test_subject",
        "live_summary" => "new test_subject",
        "enable_live_password" => true,
        "live_password" => "654322",
        "enable_live_im" => true,
        "enable_live_replay" => true
    ];
    $info = $meeting_api->updateLivePlay($meeting_id, '', $live_config);
    print_r($info);


    /**
     * 获取直播回看地址示例
     * 会议开始后必须开启直播录制，才有直播回看地址
     */
    $live_play_info = $meeting_api->getLiveReplays($meeting_id, '');
    print_r($live_play_info);


    // 删除直播回看文件示例,必须开启直播录制，才有回看文件
    $live_room_id = $live_play_info['meeting_info_list'][0]['live_replay_list'][0]['live_room_id']; //直播房间号,查询直播回看地址接口返回中获得
    $info = $meeting_api->delLiveReplays($meeting_id, $live_room_id);
    print_r($info);


    /**
     * 取消会议示例，如果为周期性会议 则取消整个周期会议中的最近一次会议
     */
    $info = $meeting_api->cancelMeeting($meeting_id, ['reason_code' => 2, 'reason_detail' => '参会人员时间冲突']);
    print_r($info);


    /**
     * 结束会议示例
     */
    $meeting_id = $meeting_info['meeting_info_list'][0]['meeting_id'];
    $info = $meeting_api->dismissMeeting($meeting_id, ['reason_code' => 1]);
    print_r($info);

} catch (WeMeetingException $e) {
    echo $e;
}