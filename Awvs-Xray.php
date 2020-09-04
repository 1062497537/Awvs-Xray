<?php
Error_reporting(0);
$Type = 'flase'; // flase = Awvs自主扫描 ; true = Awvs爬虫模式 Ps:需打开Xray 
$GLOBALS['AwvsUser']  = '';//Awvs User
$GLOBALS['AwvsPassWord']  = '';//Awvs PassWord
$GLOBALS['AwvsUrl'] = "https://localhost:3443";//Awvs Url
if ($Type == 'true') 
{
$GLOBALS['XrayIp']  = '127.0.0.1';//Xray IP
$GLOBALS['XrayPort'] = '8111';// Xray Port
$GLOBALS['ScanType'] = '7';
}
else if($Type == 'flase')
{
$GLOBALS['XrayIp']  = $GLOBALS['XrayPort'] = '';
$GLOBALS['ScanType'] = '1';
}
setcookie("ui_session",'');

function parseHeaders($headers)
 {
  $head = array();
  foreach( $headers as $k=>$v )
  {
   $t = explode( ':', $v, 2 );
   if( isset( $t[1] ) )
    $head[ trim($t[0]) ] = trim( $t[1] );
    else
    {
     $head[] = $v;
     if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
      $head['reponse_code'] = intval($out[1]);
    }
  }
  return $head;
}
function StartAwvs($linkurl) {
$login_awvs_data = '{"email":"'.$GLOBALS['AwvsUser'].'","password":"'.hash('sha256',$GLOBALS['AwvsPassWord']).'","remember_me":false,"logout_previous":true}';
$loginpostdata = trim(stripslashes(json_encode($login_awvs_data,true)),'"');
$options = array(
'http' => array(
'method' => 'POST',
'header' => 'Content-Type: application/json;charset=utf-8',
'content' => $loginpostdata,
)
);
$context = stream_context_create($options);
$result = file_get_contents($GLOBALS['AwvsUrl'].'/api/v1/me/login', false, $context);
$Array = parseHeaders($http_response_header);
$GLOBALS['Cookie'] = $GLOBALS['Auth'] = $Array['X-Auth'];
$add_targets_data = '{"address":"'.$linkurl.'","description":"","criticality":"10"}';
return Add_Targets($GLOBALS['AwvsUrl'],$add_targets_data);
}
function Add_Targets($url, $post_data) {
$addpostdata = trim(stripslashes(json_encode($post_data,true)),'"');
$options = array(
'http' => array(
'method' => 'POST',
'header' => 'Content-Type: application/json;charset=utf-8'."\r\n"."X-Auth:".$GLOBALS['Auth']."\r\n"."Cookie:ui_session=".$GLOBALS['Cookie']."\r\n",
'content' => $addpostdata
)
);
$context = stream_context_create($options);
$result = file_get_contents($url.'/api/v1/targets', false, $context);
$Array = json_decode($result,true);
file_put_contents('target_id.txt',$Array['target_id']);
$add_targets_proxy_data = '{"proxy":{"enabled":true,"address":"'.$GLOBALS['XrayIp'].'","protocol":"http","port":'.$GLOBALS['XrayPort'].'}}';
return Add_Targets_Proxy($GLOBALS['AwvsUrl'],$add_targets_proxy_data);
}
function Add_Targets_Proxy($url, $post_data) {
$addpostdata = trim(stripslashes(json_encode($post_data,true)),'"');
$options = array(
'http' => array(
'method' => 'PATCH',
'header' => 'Content-Type: application/json;charset=utf-8'."\r\n"."X-Auth:".$GLOBALS['Auth']."\r\n"."Cookie: ui_session=".$GLOBALS['Cookie']."\r\n",
'content' => $addpostdata
)
);
$context = stream_context_create($options);
$result = file_get_contents($url.'/api/v1/targets/'.file_get_contents('target_id.txt').'/configuration', false, $context);
$add_targets_scans_data = '{"target_id":"'.file_get_contents('target_id.txt').'","profile_id":"11111111-1111-1111-1111-11111111111'.$GLOBALS['ScanType'].'","schedule":{"disable":false,"start_date":null,"time_sensitive":false},"ui_session_id":"2dd9f618c0ca36fb1fe8a3aa8a2a173a"}';
return Add_Targets_Scans($GLOBALS['AwvsUrl'],$add_targets_scans_data);
}
function Add_Targets_Scans($url, $post_data) {
$addpostdata = trim(stripslashes(json_encode($post_data,true)),'"');
$options = array(
'http' => array(
'method' => 'POST',
'header' => 'Content-Type: application/json;charset=utf-8'."\r\n"."X-Auth:".$GLOBALS['Auth']."\r\n"."Cookie:ui_session=".$GLOBALS['Cookie']."\r\n",
'content' => $addpostdata
)
);
$context = stream_context_create($options);
$result = file_get_contents($url.'/api/v1/scans', false, $context);
return unlink('target_id.txt');
}
$link=fopen('url.txt','rb+');
for ($i = 0 ; $i = !feof($link); $i++){
$linkurl =  rtrim(fgets($link));
StartAwvs($linkurl);
}
echo "<script language='javascript'>alert('Success!');window.location.href='".$GLOBALS['AwvsUrl']."';</script>";
?>
