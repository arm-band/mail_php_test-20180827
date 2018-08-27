<?php
require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/config.php";

//地域・タイムゾーン・言語・文字エンコーディングの環境設定
date_default_timezone_set("Asia/Tokyo");
mb_language("ja");
mb_internal_encoding("UTF-8");

//110番ポート(POP3)で接続する
$fp = fsockopen('tcp://' . $a['host'] . ':110', 110, $err, $errno, 10);

//認証
$r = fgets($fp, 1024);
fputs($fp, 'USER ' . $a['user'] . "\r\n");
$r = fgets($fp, 1024);
fputs($fp, 'PASS ' . $a['pass'] . "\r\n");
$r = fgets($fp, 1024);

//STAT 何件あるか？
fputs($fp, "STAT\r\n");
$r = fgets($fp, 1024);
sscanf($r, '+OK %d %d', $num, $size);

//メールデータ取得（件数分 RETR）
$data = array();
for ($i = 1; $i <= $num; ++$i) {
    //RETR n -n番目のメッセージ取得（ヘッダ含）
    fputs($fp, 'RETR ' . $i . "\r\n");
    //+OK
    $r = fgets($fp, 512);
    //EOFの.まで読む
    $d = null;
    do {
        $line = fgets($fp, 512);
        $d .= $line;
    } while (!preg_match('/^\.\r\n/', $line));
    $decoder = new Mail_mimeDecode($d);
    $params = array(
        "include_bodies" => true
    );
    $data[$i] = $decoder->decode($params);
    $charaset = "";
    $cte = "";
    if(empty($data[$i]->parts)) {
        $charaset = $data[$i]->ctype_parameters["charset"];
        $cte = $data[$i]->headers["content-transfer-encoding"];
        $data[$i]->body = mime_decode($data[$i]->body, $charaset, $cte);
    }
    else {
        $charaset = $data[$i]->parts[0]->ctype_parameters["charset"];
        $cte = $data[$i]->parts[0]->headers["content-transfer-encoding"];
        $data[$i]->parts[0]->body = mime_decode($data[$i]->parts[0]->body, $charaset, $cte);
    }

    $data[$i]->headers["from"] = mime_decode($data[$i]->headers["from"], $charaset, $cte);
    $data[$i]->headers["to"] = mime_decode($data[$i]->headers["to"], $charaset, $cte);
    $data[$i]->headers["subject"] = mime_decode($data[$i]->headers["subject"], $charaset, $cte);

    //結果表示
    if($i !== 1) echo "<br>\n<br>\n<br>\n";
    //件数
    echo "======================== " . $i . "件目 ===========================<br>\n";
    //From
    echo "<br>\n======================== from ===========================<br>\n";
    var_dump("from: " . $data[$i]->headers["from"]);
    //To
    echo "<br>\n<br>\n======================== to ===========================<br>\n";
    var_dump("to: " . $data[$i]->headers["to"]);
    //件名
    echo "<br>\n<br>\n======================== subject ===========================<br>\n";
    var_dump("subject: " . $data[$i]->headers["subject"]);
    //本文
    echo "<br>\n<br>\n======================== body ===========================<br>\n";
    //パーツありとなしで本文データがある場所が変わるので場合分けして表示
    if(empty($data[$i]->parts)) {
        var_dump($data[$i]->body);
    }
    else {
        var_dump($data[$i]->parts[0]->body);
    }

    //DELE n n番目のメッセージ削除(削除したい場合)
    //fputs($fp, 'DELE ' . $i . "\r\n");
    //fread($fp,1024);
}

//接続終了
fputs($fp, "QUIT\r\n");
fgets($fp, 1024);

//引数) $data: データ, $charset: 文字コード, $cte: content-transfer-encoding
function mime_decode($data, $charset, $cte) {
    $default_charset = "UTF-8";
    $resp = trim($data);
    if($cte === "quoted-printable") {
        $resp = quoted_printable_decode($resp);
    }
    if(preg_match("/=\?(ISO-2022-JP|UTF-8|EUC-JP)\?Q\?/", $resp)) {
        $resp = preg_replace("/=\?(ISO-2022-JP|UTF-8|EUC-JP)\?Q\?/mi", "", $resp);
        $resp = preg_replace("/\?=/mi", "", $resp);
    $resp = preg_replace("/[\s\t]/", "", $resp);
    $resp = preg_replace("/\?$/", "", $resp);
    }
    else if(preg_match("/=\?(ISO-2022-JP|UTF-8|EUC-JP)\?B\?/", $resp)) {
        $resp = preg_replace("/=\?(ISO-2022-JP|UTF-8|EUC-JP)\?B\?/mi", "", $resp);
        $resp = preg_replace("/\?=/mi", "", $resp);
        $str_array = explode(" ", $resp);
        foreach ($str_array as &$value) {
            $value = base64_decode($value);
        }
        $resp = "";
        foreach ($str_array as $value) {
            $resp .= $value;
        }
    $resp = preg_replace("/[\s\t]/", "", $resp);
    }
    if($charset != $default_charset) {
        $resp = mb_convert_encoding($resp, "UTF-8", $charset);
    }

    return $resp;
}