<?php
/*
Plugin Name: Siiwoo
Plugin URI: http://siiwoo.com/
Description: Siiwoo是一款弹幕视频插件，只需把视频源地址复制到文章中，即可生成支持弹幕视频，就是这么简单赶紧试试吧！目前支持淘宝视频源！
Version: 1.0.1
Author: 凌枫<i@surcar.cn>
Author URI: http://surcar.cn
*/

define('SIIWOO_VERSION', '1.0.1');
define('SIIWOO_URL', plugins_url('', __FILE__));
define('SIIWOO_PATH', dirname( __FILE__ ));

$siiwoo = new siiwoo();

class siiwoo{
    private $edit = false;
    private $width = '500';
    private $height = '380';

    public function __construct(){
        if(is_admin()){
            add_action('admin_menu', array($this, 'admin_menu'));
            $this->edit = true;
        }
        $option = get_option('siiwoo_option');
        if(!empty($option)){
            $option = json_decode($option, true);
        }else{
            $option = array();
        }
        extract($option);
        if(!empty($width)){
            $this->width = $width;
        }
        if(!empty($height)){
            $this->height = $height;
        }
        if(!empty($strategy)){
            $this->strategy = $strategy;
        }
        
        //加载资源
        add_action('wp_enqueue_scripts', array($this, 'siiwoo_scripts'));
        
        //注册
        wp_embed_unregister_handler('taobao');

        //正则匹配URL
        wp_embed_register_handler(
            'siiwoo_taobao',
            '#http?://cloud\.video\.taobao\.com/play/u/608857445/p/1/e/6/t/1/(?<video_id>\d+).mp4#i',
            array($this, 'siiwoo_embed_handler_taobao')
            );
    }

    public function siiwoo_embed_handler_taobao($matches, $attr, $url, $rawattr){
        $embed = $this->get_embed("http://cloud.video.taobao.com/play/u/608857445/p/1/e/6/t/1/{$matches['video_id']}.mp4");
        return apply_filters('embed_taobao', $embed, $matches, $attr, $url, $rawattr);
    }
    
    //URL转化视频视图
    private function get_embed($url){
        if (is_admin()) {
            return $ur;
        }
        global $post;
        $id = $post->ID;
        $html = '';
        $html .= sprintf('<link rel="stylesheet" id="siiwoo-cssdd" href="%1$s" type="text/css" media="screen">', SIIWOO_URL . '/static/siiwoo.css?ver=' . SIIWOO_VERSION);
        $html .= sprintf('<script type="text/javascript" src="%1$s"></script>', SIIWOO_URL . '/static/send.js?ver=' . SIIWOO_VERSION);
        
        //video
        $html .= '<div id="siiwoo_video">';
        $html .= sprintf('<video width="%1$s" height="%2$s" controls="controls">', $this->width, $this->height);
        $html .= sprintf('<source src="%1$s" type="video/mp4">', $url);
        $html .= 'Your browser does not support HTML5 video';
        $html .= '</video>';
        $html .= '</div>';

        //show message
        $html .= '<div id="siiwoo_barrage">
                        <!--div class="siiwoo_animation one">One~</div-->
                    </div>';
        //post message
        $html .= '<textarea id="siiwoo_content"></textarea>';
        $html .= '<input type="button" class="btn" value="POST" onclick="siiwoo_message();"/>';
        $html .= '<input type="hidden" id="siiwoo_single" value="single_'.$id.'">';
        $html .= '<script type="text/javascript">
                function siiwoo_message() {
                  var singleId = eval(document.getElementById("siiwoo_single")).value;
                  var content = eval(document.getElementById("siiwoo_content")).value;
                  if (content.length > 50) {
                      alert("弹幕字数不能超过50字!");
                      return false;
                  }
                  ws.send(JSON.stringify({"type":"send", "single":singleId,"to_client_id":"all", "content":content}));
                };
                </script>';
        return $html;
    }
    
    //插件资源
    public function siiwoo_scripts(){
        wp_enqueue_style('siiwoo', SIIWOO_URL . '/static/siiwoo.css', array(), SIIWOO_VERSION, 'screen');
        wp_enqueue_script('siiwoo', SIIWOO_URL . '/static/send.js', array(), SIIWOO_VERSION, 'screen');
    }
    
    //后台菜单
    public function admin_menu(){
        add_plugins_page('siiwoo 设置', 'siiwoo 设置', 'manage_options', 'siiwoo_settings', array($this, 'admin_settings'));
    }
    
    //后台设置
    public function admin_settings(){
        if($_POST['siiwoo_submit'] == '保存'){
            $param = array('width', 'height');
            $json = array();
            foreach($_POST as $key => $val){
                if(in_array($key, $param)){
                    $json[$key] = $val;
                }
            }
            $json = json_encode($json); 
            update_option('siiwoo_option', $json);
        }
        $option = get_option('siiwoo_option');
        if(!empty($option)){
            $option = json_decode($option, true);
        }
        if(empty($option['width'])){
            $option['width'] = '640';
        }
        if(empty($option['height'])){
            $option['height'] = '440';
        }
        
        echo '<h2>siiwoo 设置</h2>';
        echo '<form action="" method="post">	
            <table class="form-table">
		<tr valign="top">
                    <th scope="row">播放器宽度</th>
                    <td>
                        <label><input type="text" class="regular-text code" name="width" value="'.$option['width'].'"></label>
                        <br />
                        <p class="description">默认宽度为500px</p>
                    </td>
		</tr>
		<tr valign="top">
                    <th scope="row">播放器高度</th>
                    <td>
                        <label><input type="text" class="regular-text code" name="height" value="'.$option['height'].'"></label>
                        <br />
                        <p class="description">默认高度为380px</p>
                    </td>
		</tr>
            </table>
            <p class="submit"><input type="submit" name="siiwoo_submit" id="submit" class="button-primary" value="保存"></p>
        </form>';
    }
}
