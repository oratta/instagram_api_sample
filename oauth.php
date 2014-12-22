<?php
//クライアントID・クライアントシークレット・リダイレクトURI・スコープ
$client_id = "4b2b081a23d34357a1e8521bef3d6f8f";
$client_secret = "43ee63c062084bfb9a1d7a159c986336";
$redirect_uri = "http://oratta.oratta/test";
$scope = "basic+comments+relationships+likes";

//セッションスタート
session_start();

//[ステップ1] アプリ認証画面でリクエストトークンを取得
if(!isset($_GET['code']) || !is_string($_GET['code']) || empty($_GET['code'])){
	//CSRF対策
	session_regenerate_id(true);
	$_SESSION['state'] = $state = sha1(uniqid(mt_rand(),true));

	//ユーザーをアプリ認証画面に飛ばす
	header("Location: https://api.instagram.com/oauth/authorize/?client_id={$client_id}&redirect_uri=".rawurlencode($redirect_uri)."&scope={$scope}&response_type=code&state={$state}");
	exit;
}

//[ステップ2] リクエストトークン($_GET["code"])とアクセストークンの交換
if(!isset($_SESSION['state']) || empty($_SESSION['state']) || !isset($_GET['state']) || empty($_GET['state']) || $_SESSION['state'] != $_GET['state']) exit;

//セッション終了
$_SESSION = array();
session_destroy();

//アクセストークンを取得し、JSONをオブジェクト形式に変換
$obj = json_decode(@file_get_contents(
	'https://api.instagram.com/oauth/access_token',
	false,
	stream_context_create(
		array('http' => array(
			'method' => 'POST',
			'content' => http_build_query(array(
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $redirect_uri,
			'code' => $_GET['code'],
		)),
    ))
  )
));

echo "<pre>";
print_r($obj);
print_r($_GET);
echo "</pre>";

//ユーザーID・ユーザーネーム・ユーザーアイコン・アクセストークン
$user_id = $obj->user->id;
$user_name = $obj->user->username;
$user_picture = $obj->user->profile_picture;
$access_token = $obj->access_token;

//出力
header("Content-Type: text/html; charset=UTF-8");
echo "<img src=\"{$user_picture}\" width=\"100\" height=\"100\"><br/>@{$user_name}(ID:{$user_id})さんのアクセストークンは<mark>{$access_token}</mark>です！";


/**
 * 画像取得
 */
//アクセストークンを取得し、JSONをオブジェクト形式に変換
//リクエストURL
$request_url = "https://api.instagram.com/v1/users/self/media/recent/";

//パラメータを配列形式で指定(その後、配列形式のパラメータを文字列に変換)
$params = array(
  'access_token' => $access_token,
  'count' => "24",
);
$query = http_build_query($params);

//JSONデータを取得し、オブジェクト形式に変換
$obj = json_decode(@file_get_contents("{$request_url}?{$query}"));
$dataArray = $obj->data;
$imageArray = array();
foreach ($dataArray as $data){
	$imageArray[] = $data->images->standard_resolution->url;
}
// echo "<pre>";
// print_r($obj);
// echo "</pre>";

foreach ($imageArray as $imageUrl){
	echo "<img src='{$imageUrl}'>";
}

