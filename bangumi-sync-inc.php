<?php

//
// global initialize
//
$bgm_user = get_option('bgm_user');
if($bgm_user == false) { $bgm_user = ''; add_option('bgm_user',''); }

$bgm_psd = get_option('bgm_psd');
if($bgm_psd == false) { $bgm_psd = ''; add_option('bgm_psd','');  }

$bgm_cookie = NULL;
$bgm_formhash = '';

define('__DEBUG__',false);
define('__SHOW_HEADER__',true);
define('__ABOUT__','Bangumi Sync Plugin for WordPress');
define('__LOGIN_URL__','http://bgm.tv/FollowTheRabbit');
define('__POST_URL__','http://bgm.tv/blog/create');

//
// common http handler
//
function bgm_sync_http_handler( $url, $method, $postfields = NULL ) { 
	global $bgm_cookie;
	$ci = curl_init(); 
	$header = array(); 
	 
	curl_setopt($ci, CURLOPT_USERAGENT, __ABOUT__); 
	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 5); 
	curl_setopt($ci, CURLOPT_TIMEOUT, 5); 
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($ci, CURLOPT_HEADER, __SHOW_HEADER__ );
	curl_setopt($ci, CURLOPT_COOKIEFILE, $bgm_cookie);   // load cookie
	curl_setopt($ci, CURLOPT_HTTPHEADER, $header ); 
	curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE ); 
	curl_setopt($ci, CURLOPT_URL, $url);
	
	switch ($method) { 
	case 'POST': 
		curl_setopt($ci, CURLOPT_POST, TRUE); 
		if( !$bgm_cookie ) { 
			$bgm_cookie = tempnam('./tmp','cookie'); 
			curl_setopt($ci, CURLOPT_COOKIEJAR, $bgm_cookie);  // save cookie
		}
		if (!empty($postfields)) { 
			curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields); 
			if( __DEBUG__ ) echo '<textarea>'.$postfields.'</textarea>';
		} 
		break;  
	} 

	$response = curl_exec($ci);  
	
	curl_close ($ci);

	if( __DEBUG__ ) print '<textarea>'.$response.'</textarea>';
	return $response; 
}

//
// do login 
//
function bgm_sync_login( $tmp_user, $tmp_psd ){
	global $bgm_cookie;
	
	if( $bgm_cookie ) { unlink( $bgm_cookie ); $bgm_cookie = NULL; }
	
	$login_data = 'referer=http://bgm.tv/&email='.urlencode($tmp_user).'&password='.urlencode($tmp_psd).'&loginsubmit=%E7%99%BB%E5%BD%95&formhash=51d442b9';
	return bgm_sync_http_handler( __LOGIN_URL__ , 'POST', $login_data );
}

//
// check id 
//
function bgm_sync_check_id( $tmp_user, $tmp_psd ){
	global $bgm_user, $bgm_psd;
	
	$r = bgm_sync_login( $tmp_user, $tmp_psd );
	
	preg_match('/Set-Cookie:\schii_auth=/', $r, $m);
	if( $m ) {
		echo '<div id="message" class="updated"><p>与 Bangumi 娘通信成功，账号已保存~☆</p></div>';
		$bgm_user = $tmp_user;
		$bgm_psd = $tmp_psd;
		update_option( 'bgm_user', $bgm_user );
		update_option( 'bgm_psd', $bgm_psd );
	}
	else echo '<div id="message" class="updated"><p>Bangumi 娘回答账号或密码有误，有没有写错呢？</p></div>';
}

//
// get post formhash
//
function bgm_sync_get_formhash(){
	global $bgm_formhash;
	$r = bgm_sync_http_handler( __POST_URL__ , 'GET');
	
	preg_match('/\<input\stype=\"hidden\"\sname=\"formhash\"\svalue=\"([\w\d]+)\"\s\/\>/', $r, $m );
	if( $m ) { $bgm_formhash = $m[1]; }
	//else echo "Failed to get the formhash code QAQ";
}

//
// transfer html to ubb
//
function html2ubb( $c ){
	$c = preg_replace('/<strong>(.+?)<\/strong>/i','[b]${1}[/b]', $c);
	$c = preg_replace('/<em>(.+?)<\/em>/i','[i]${1}[/i]', $c);
	$c = preg_replace('/<span\s*style=\"text-decoration:\s*underline;\">(.+?)<\/span>/i','[u]${1}[/u]', $c);
	$c = preg_replace('/<del\s*datetime=\"[^"]+\">(.+?)<\/del>/i','[s]${1}[/s]', $c);
	$c = preg_replace('/<span\s*style=\"color:\s*(#[\w\d]+);\">(.+?)<\/span>/i','[color=${1}]${2}[/color]', $c);
	$c = preg_replace('/<span\s*style=\"background:\s*#[\w\d]+;\">(.+?)<\/span>/i','[mask]${1}[/mask]', $c);
	$c = preg_replace('/<span\s*style=\"font-size:\s*(\d+)px;\">(.+?)<\/span>/i','[size=${1}]${2}[/size]', $c);
	$c = preg_replace('/<a[^>]*href=\"(.+?)\"[^>]*>(.+?)<\/a>/i','[url=${1}]${2}[/url]', $c);
	$c = preg_replace('/<img[^>]*src="(.+?)"[^>]*>/i','[img]${1}[/img]', $c);
	$c = preg_replace('/&nbsp;/i',' ', $c);
	$c = preg_replace('/<br\s?\/?>/i','\n', $c);
	$c = preg_replace('/<[^>]+>/i' , '', $c);
	return $c;
}

//
// do post
//
function bgm_sync_do_post( $title, $content, $tags ){
	global $bgm_formhash, $bgm_user, $bgm_psd;

	bgm_sync_login( $bgm_user, $bgm_psd );
	bgm_sync_get_formhash();
	
	$transformedContent = html2ubb( $content );
	$transformedTags = str_replace( ',' , ' ' , $tags );
	$post_data = 'formhash='.$bgm_formhash.
				 '&title='.urlencode($title).
				 '&content='.urlencode($transformedContent).
				 '&tags='.urlencode($transformedTags).
				 '&public=1';
	bgm_sync_http_handler('http://bgm.tv/blog/create', 'POST', $post_data );
}

//
// get title/content and pass them to bgm_sync_post
//
function bgm_sync_post( $pid ){
	if( !wp_is_post_revision( $pid ) && $_POST['bgm_do_sync'] ){
		$p = get_post( $pid );
		bgm_sync_do_post( $p->post_title, $p->post_content, $_POST['newtag[post_tag]']);
	}
}


//
// show option page 
//
function bgm_sync_show_options(){
	global $bgm_user, $bgm_psd;
	if( $_POST['save_profile'] ) {
		if($bgm_user != $_POST['bgm_user'] || $bgm_psd != $_POST['bgm_psd']) {
			bgm_sync_check_id( $_POST['bgm_user'], $_POST['bgm_psd'] );
		}
		else
			echo '<div id="message" class="updated"><p>新输入的信息与已保存的信息相同喔~☆</p></div>';
	}
	if( $_POST['TEST'] ){
		bgm_sync_do_post('试试试试','能用否(bgm38)');
	}
	
?>
<div class="wrap">
	<h2>Bangumi 同步插件设置</h2>
    <form action="" method="post">
    	<table class="form-table">
        	<tr valign="top">
            	<td width="40" ><label for="bgm_user">id：</label></td>
                <td><input type="text" name="bgm_user" id="bgm_user" value="<?php echo $bgm_user; ?>" /></td>
            </tr><tr valign="top">
            	<td width="40"><label for="bgm_psd">密码：</label></td>
                <td><input type="password" name="bgm_psd" id="bgm_psd" value="<?php echo $bgm_psd; ?>" /><br />你输入的密码会被保存在你的wp所在的服务器上，仅用于与bangumi通信，不会被发送给其他任何人</td>
            </tr><tr>
            	<td colspan="2"><input type="submit" value="保存" name="save_profile" /></td>
            </tr>
        </table>
    </form>
</div>    
<?php
}

//
// add select to post.php
//
function bgm_sync_add_control(){
	add_meta_box('bgm_sync', '要合体吗', 'bgm_sync_control', 'post', 'side', 'high');
}
function bgm_sync_control(){
	if(get_option('bgm_user')) {
?>
	<input type="checkbox" name="bgm_do_sync" id="bgm_do_sync" value="true" />&nbsp;<label for="bgm_do_sync">把这篇文章同步到Bangumi</label>
<?php
	} else
		echo '请先到 设置->Bangumi同步插件 下完成账号验证';
}

//
// add option to admin bar
//
function bgm_sync_add_menu() {
	add_options_page('Bangumi同步插件', 'Bangumi同步插件', 'manage_options', 'bgm-sync-options', 'bgm_sync_options');
}

function bgm_sync_options() {
	if (!current_user_can('manage_options'))  {
		wp_die( '你没有设置这个插件的权限' );
	}
	bgm_sync_show_options();
}
?>