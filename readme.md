# メールの受信テストスクリプト

## 概要

メールサーバにPOP3でログインし、メールの内容を参照するテストプログラムです。

## 使用ライブラリ

- [pear/mail\_mime\-decode \- Packagist](https://packagist.org/packages/pear/mail_mime-decode)
    - メールデータを解析し、fromやtoで分解してオブジェクトに格納する

## 参考

### メールの扱い

- [受け取ったメールの内容をPHPで取得する方法！ \- プログラミングで飯を食え。腕をあげたきゃ備忘録！](http://senoway.hatenablog.com/entry/2014/03/12/194813)

### エンコード

- [MIMEエンコードされたメールのデコード方法](https://qiita.com/sheepland/items/2065ffcc7ec8c03145cc)
    - `Content-Transfer-Encoding`と`charset`の2つを見ないとデコードできない
- [phpメール送信例/メモ](https://qiita.com/KanaeYou/items/b096f8be1f5bbc5448fa)
- [特集　文字化けを出さないメール術](https://internet.watch.impress.co.jp/www/article/980525/mojibake.htm)
    - BASE64エンコードの場合
        - 文字コードを見つつ`=?UTF-8?B?`, `?=`を除去
        - サイズに限界があるため文字列が長いと複数パーツに分かれるので、半角スペースで分解してそのパーツごとにデコード、1つの文字列に結合
        - といったことをやる必要がある

### 関数

- [PHP: base64\_decode \- Manual](http://php.net/manual/ja/function.base64-decode.php)
- [PHP: quoted\_printable\_decode \- Manual](http://php.net/manual/ja/function.quoted-printable-decode.php)
- [PHP: mb\_convert\_encoding \- Manual](http://php.net/manual/ja/function.mb-convert-encoding.php)