<?php
/**
 * BaseHelper.php
 *
 * 微信公众平台基础帮助操作类库文件（封装核心的公共方法，涉及参数，需要自行处理，本文件仅提供核心操作代码）
 *
 * @author $wuwh 2017-05-11 10:05 
 * @version 1.0
 */

class BaseHelper
{
    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin'; 
    const API_ACCESS_TOKEN = '/token?grant_type=client_credential&appid=%s&secret=%s';
    const API_JSAPI_TICKET = '/ticket/getticket?access_token=%s&type=jsapi';
    const API_MSG_TPL = '/message/template/send?access_token=%s';
    const API_MSG_CS = '/message/custom/send?access_token=%';
    const API_MENU_CREATE = '/menu/create?access_token=%s';
    
    private $options;
    private $appid;
    private $appsecret; 
    
    public function __construct($options)
    {
        $this->options = $options;
        $this->appid = 'appid';
        $this->appsecret = 'appsecret';
    }
    
    /**
     * 获取微信公众号的access_token
     * 
     * @return string
     */
    public function getAccessToken()
    {
        $api_url = sprintf(self::API_URL_PREFIX . self::API_ACCESS_TOKEN, $this->appid, $this->appsecret);
        
        $json_data = json_decode(file_get_contents('access_token.json'), true);
        $expires_time = $json_data["expires_time"];
        $access_token = $json_data["access_token"];
        
        if (!empty($access_token) && !empty($expires_time)) {
            $result = json_decode($this->curlGet($api_url), true);
            $access_token = $result["access_token"];
            file_put_contents('access_token.json', '{"access_token": "' . $access_token . '", "expires_time":' . time() . '}');
        } else {
            if (time() > intval($expires_time + 7000)) {
                $result = json_decode($this->curlGet($api_url), true);
                $access_token = $result["access_token"];
                file_put_contents('access_token.json', '{"access_token": "' . $access_token . '", "expires_time":' . time() . '}');
            }
        }
        return $access_token;
    }
    
    /**
     * 获取微信公众号的jsapi_ticket
     * 
     * @return string
     */
    public function getJsapiTicket()
    {
        $access_token = $this->getAccessToken();
        $api_url = sprintf(self::API_URL_PREFIX . self::API_JSAPI_TICKET, $access_token);
        $result = json_decode($this->curlGet($api_url), true);
        $jsapi_ticket = $result['ticket'];
        $_SESSION['ticket'] = $jsapi_ticket;
        return $jsapi_ticket;
    }
    
    /**
     * 获取微信公众号的signature
     * 
     * @return array
     */
    public function getSignature()
    {
        $noncestr = time(); // 随机字符串
        $jsapi_ticket = $_SESSION['ticket']; // 缓存的ticket值
        $timestamp = time(); // 时间戳
        $web_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; // 当前网页的URL
        
        // 拼接成字符串，并且进行sha1签名
        // 拼接格式：jsapi_ticket=&noncestr=&timestamp=&url
        $string1 = 'jsapi_ticket=' . $jsapi_ticket . '&noncestr=' . $noncestr . '&timestamp=' . $timestamp . '&url=' . $web_url;
        $signature = sha1($string1);
        $result = array('timestamp' => $timestamp, 'nonceStr' => $noncestr, 'signature' => $signature);
        return $result;
    }
    
    /**
     * 发送微信模板消息
     * 
     * @param mixed $tpl_data 模板数据包（具体内容，由不同的模板参数组成）
     * 
     * @return mixed
     */
    public function sendTplMsg($tpl_data)
    {
        $access_token = $this->getAccessToken();
        $api_url = sprintf(self::API_URL_PREFIX . self::API_MSG_TPL, $access_token);
        $result = json_decode($this->curlPost($url, $tpl_data), true);
        return $result;
    }
    
    /**
     * 发送微信客服消息
     * 
     * @param mixed $content 根据不同的发送消息类型，提交过来的消息数据包
     * 
     * @return mixed
     */
    public function sendCsMsg($content)
    {
        $access_token = $this->getAccessToken();
        $api_url = sprintf(self::API_URL_PREFIX . self::API_MSG_CS, $access_token);
        $result = json_decode($this->curlPost($api_url, $content), true);
        return $result; 
    }
    
    /**
     * 创建微信自定义菜单
     * 
     * @return mixed
     */
    public function createMenu()
    {
        $access_token = $this->getAccessToken();
        $menu_data = $this->getMenu();
        $api_url = sprintf(self::API_URL_PREFIX . self::API_MENU_CREATE, $access_token);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $menu_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Errno'.curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($tmpInfo, true);
        return $result;
    }
    
    /**
     * 获取自定义菜单的JSON数据包
     * 本方法模拟从数据库获取待发布的菜单数据，实际开发根据自身情况，进行整合下面的数据包即可
     * 
     * @return mixed
     */
    private function getMenu()
    {
        $main_menu = array(); // 模拟的主菜单数据

        foreach ($main_menu AS $k => $v) {
            $son_menu = array(); // 模拟的相应主菜单的子菜单数据
            if (count($son_menu) != 0) {
                foreach ($son_menu AS $k1 => $v1) {
                    if ($v1['menu_key']) { 
                        /**
                         * 此处的 menu_key 为数据库记录发送类型的标识。如果为空则是发送消息（click），否则跳转网页（view），至于后面的“小程序”等设置，请自行做好数据处理
                         * 此处的 name 为数据库记录菜单名称
                         * 此处的 keyword 为数据库记录的关键词设置，主要用于微信点击菜单触发事件处理
                         */
                        $kk[] = array('type' => 'view', 'name' => $v1['name'], 'url' = >$v1['menu_key']);
                    } else {
                        $kk[] = array('type' => 'click', 'name' => $v1['name'], 'key' => $v1['keyword']);
                    }
                }
            } else {
				if ($v['menu_key']) {
					$menu['button'][] = array('type' => 'view', 'name' => $v['name'], 'url' => $v['menu_key']);
				} else {
					$menu['button'][] = array('type' => 'click', 'name' => $v['name'], 'key' => $v['keyword']);
				}
			}
        }
        
        // 防止菜单中的中文会被unicode编码，造成生成失败处理
        // PHP5.4，Json新增了一个选项：JSON_UNESCAPED_UNICODE，故名思议，Json不要编码Unicode。如：json_encode("中文", JSON_UNESCAPED_UNICODE);
        // PHP5.3前用，一下方法处理
        return preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", json_encode($menu));
    }

    
    /**
     * CURL get方法
     * 
     * @param string $url
     * 
     * @return mixed
     */
    public function curlGet()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    /**
     * CURL post方法
     * 
     * @param string $url
     * @param mixed $json_data
     * 
     * @return mixed
     */
    public function curlPost($url, $json_data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
    
    
    
    
}
