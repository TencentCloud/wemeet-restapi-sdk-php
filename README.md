# WeMeetingSdk
# 简介
欢迎使用腾讯会议开发者工具套件（SDK）1.0，
为方便 PHP 开发者调试和接入腾讯会议 API，这里向您介绍适用于 PHP 的腾讯会议开发工具包，并提供首次使用开发工具包的简单示例。
让您快速获取腾讯会议 PHP SDK 并开始调用。

由于demo 代码不会跟随官网接口更新，需要用户使用源码下载到本地改造适配，以使用新接口功能。
                                                                                                     
## 通过 下载源码安装
1. 下载源码，将src目录拷贝到根目录下，如果项目根目录下已经存在src目录，可以将下载的源码合并入src目录。
2. 将下载的源码根目录下的文件composer.json中的autoload添加到本地的项目的composer安装文件(composer.json)中，执行composer update命令引入sdk；
或者直接在代码中添加以下代码引入sdk `require 'src/load_sdk.php'`; 

# 使用sdk示例
可以参考 SDK 仓库中 [examples] 目录中的示例，展示了更多的用法。

下面以查询会议详情接口为例:
```php
<?php
require_once '/path/to/vendor/autoload.php';

// 导入需要的类
use WeMeetingGateWay\WeMeetingApi\WeMeetingApi;
use WeMeetingGateWay\Credential;
use WeMeetingGateWay\Exception\WeMeetingException;

try {
    // 测试时请将以下参数替换成有效参数
    $secret_id = 'AKI****PLE'; // SecretID
    $secret_key = 'Gu5****PLE'; // SecretKey
    $AppId = '14*******0'; // appid
    $sdk_id = "14*******0";
    $userid = 'tester';

    $cred = new Credential($secret_id, $secret_key);
    $meeting_api = new WeMeetingApi($cred, $AppId, $userid);
    $instanceid = 1; // 用户的终端设备类型
    $meeting_api->setInstanceId($instanceid)
    ->setCommonHeader(['SdkId' => $sdk_id]);  // 设置公共参数，如果没有SdkId 可不设置

    $meeting_id = '13727277909477321615';  //会议id
    // 查询会议，需要会议id或者会议Code参数
    $meeting_info = $api->getMeeting($meeting_id, null);
    print_r($meeting_info);
} catch (WeMeetingException $e) {
    echo $e;
}
```
# 常见问题
## 设置代理和超时
```php
<?php
$cred = new Credential('secret_id', 'secret_key');
$meeting_api = (new WeMeetingApi($cred, 'appid','userid'))->setInstanceId(1);

// 设置请求参数
$http_proxy = new HttpProxy();
$http_proxy->setTimeOut(5); //请求超时时间为5秒
$http_proxy->setCurlProxy('ip:port'); //代理配置
$api->setHttpProxy($http_proxy);

```

## 设置公共参数
```php
<?php
$cred = new Credential('secret_id', 'secret_key');
$api = new WeMeetingApi($cred, 'appid');
$common_header = ['X-TC-Action' => '操作的接口名称' , 'X-TC-Region' => '地域'];
$api->setCommonHeader($common_header);

```

## php版本问题
低于php5.6.0 版本的可能会遇到报错`PHP Fatal error:  Arrays are not allowed in class constants in  xxxxxxx/xxxx.php`,请升级服务器上PHP版本到5.6.0+

## 证书问题

如果你的 PHP 环境证书有问题，可能会遇到报错，类似于 `cURL error 60: See http://curl.haxx.se/libcurl/c/libcurl-errors.html`，请尝试按如下步骤解决：

1. 到 [https://curl.haxx.se/ca/cacert.pem](https://curl.haxx.se/ca/cacert.pem) 下载证书文件 `cacert.pem`，将其保存到 PHP 安装路径下。
2. 编辑 `php.ini` 文件，删除 `curl.cainfo` 配置项前的分号注释符（;），值设置为保存的证书文件 `cacert.pem` 的绝对路径。
3. 重启依赖 PHP 的服务。

## php_curl 扩展

此 SDK需要开启 php_curl 扩展，查看环境上的 php.ini 环境确认是否已启用，例如在 Linux 环境下，PHP 7.1 版本，托管在 apache 下的服务，可以打开 /etc/php/7.1/apache2/php.ini 中查看 extension=php_curl.dll 配置项是否已被注释，请删除此项配置前的注释符并重启 apache。
