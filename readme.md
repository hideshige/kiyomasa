# KIYOMASA V2.2
2020/07/03  
AUTHUR: SAWADA HIDESHIGE

--------------------------------------------------------------------------------
PHPの開発を円滑に進めるために設計された枠組み

PHP(バージョン7.4以上)がインストールされているLinux(CentOS,Ubuntuなど)、Windows、macOS上で動作可能。  
WebサーバはApache、nginxに対応。  
DBはMySQL(MariaDB)、PostgreSQLで動作確認済み。

コーディングについてはPHPフレームワーク展示会グループによる標準規約に準ずる  
[http://www.php-fig.org/](http://www.php-fig.org/ "PHP-FIG")

--------------------------------------------------------------------------------
## ディレクトリ構造

root/  
├ core/  
├ device/  
├ gate/  
├ log/  
├ public_html/  
├ shell/  
└ template/  

## 各ディレクトおよびファイルの説明

root/                  ルートディレクトリはプロジェクト名に変更する  
├ core/               枠組みの心臓部  
│ ├ *.camp.php       シェルプログラム・コントローラ  
│ ├ *.castle.php     WEBプログラム・コントローラ  
│ ├ *.debug.php      デバッグ用関数  
│ ├ *.define.php     定義ファイル  
│ ├ (*.router.php)   PHPビルトインサーバを利用する場合に必要な設定ファイル  
│ ├ *.tower.php      オートロード、エラーハンドラに関するファイル  
│ ├ *.turret.php     ビュー関連のコントローラ  
│ ├ *.wall.php       デバッグ関連のコントローラ  
│ ├ *mode.php        モード定数ファイル  
│ ├ config.php       プロジェクトの設定ファイル  
│ └ env.php          プロジェクトの環境設定ファイル（Gitでは更新されない）  
├ *device/            様々の便利機能を格納（変更不可）  
│ ├ (*equipment/)    枠組み固有の便利機能を格納  
│ ├ *db.php          データベースモジュール  
│ ├ *debug_db.php    デバッグ用データベースモジュール  
│ ├ *error_info.php  エラー情報モジュール  
│ ├ *log.php         ログ保存モジュール  
│ ├ *mainte.php      メンテナンスによる例外処理  
│ ├ *mem.php         memcachedモジュール  
│ ├ *s.php           静的ショートカット群  
│ ├ *session.php     セッションモジュール  
│ ├ *user_ex.php     ユーザ操作による例外処理  
│ └ *view.php        テンプレートに値を入れて表示するモジュール  
├ gate/               URLより実行されるプログラムファイルを格納  
├ (interfaces/)       インタフェースを格納  
├ log/                ログを格納（Gitでは更新されない）  
│ └ batch/           シェルで実行した結果ログを格納  
├ (module/)          プロジェクト共通プログラムを格納  
├ public_html/        WEBルートディレクトリ  
│ ├ css/             プロジェクトのスタイルシートを格納  
│ ├ js/              プロジェクトのJavaScriptを格納  
│ │ ├ (ajax/)       Ajax関連ファイル  
│ │ └ (base.js)     JavaScriptの基本ファイル  
│ ├ (.htaccess)      サーバ設定ファイル（WEBサーバがApacheの場合に限る）  
│ └ *index.php       最初にアクセスするファイル。変更不可  
├ shell/              プロジェクトのシェルプログラムを格納  
├ template/           プロジェクトのテンプレートを格納  
│ ├ include/         プロジェクトの共通部分テンプレートを格納  
│ └ *.debug.tpl      デバッグ表示用のテンプレート  
├ (template_mobile/)  プロジェクトの携帯用テンプレートを格納  
├ (.library/)         ライブラリ（ここで警告エラーが出ても例外を投げない）  
└ (.gitignore)        Gitを利用する場合に必要な設定ファイル  

補足  
()で囲っているディレクトリおよびファイルは任意のため必要に応じて利用する  
*のついているディレクトリおよびファイルは原則として変更しない  
クラスの機能と使い方については各ファイルにコメントで詳しく記述している  

--------------------------------------------------------------------------------
## インストール手順

ここでは一例としてプロジェクト名を「project_x」とし、PHP,MySQL(MariaDB),Memcached,WebサーバがLinuxにインストールされているものとし、/var/www/htmlの直下にKIYOMASAをインストールする手順を記載する。

1. /var/www/html直下で次のコマンドを実施  
    git clone https://github.com/hideshige/kiyomasa.git
 
2. kiyomasaディレクトリをproject_xにリネームする

3. /var/www/html/project_x/core/env.phpを以下の内容で作成する  
```php
<?php
const ENV_PRO = 3; // 本番環境
const ENV_STA = 2; // ステージング環境
const ENV_DEV = 1; // 開発環境
const ENV_PHP = 0; // ビルトインサーバ環境
const ENV = ENV_PHP;
```

4. /var/www/html/project_x/core/env.phpを以下の内容で作成する  
```php
<?php
const MODE_NORMAL = 0; // 通常モード
const MODE_DEBUG  = 1; // デバッグ環境
const MODE_MAINTE = 2; // メンテナンスモード
const MODE = MODE_NOMAL;
```

5. /var/www/html/project_x/core/config.phpをプロジェクト内容に合わせて変更する

6. /var/www/html/project_x/直下にlogとlog/batchのディレクトリを作成する

8. Webサーバのルートディレクトリを/var/www/html/project_x/public_html/にする

8. Webサーバのリダイレクト設定を行う  
```Apache
# Apache
# /var/www/html/project_x/public_html/.htaccess に記述するだけで良い  
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```
    
```nginx
# nginx
# 設定ファイルのlocation部に以下を記載してからnginxを再起動する
location / {
    try_files $uri /index.php?url=$uri&$args;
}
```

9. project_xディレクトリ配下のオーナーをWebサーバユーザに一括変更する

10. project_x/device/mem.phpのコメントの通りDBにmemcachedテーブルを作っておく

11. プロジェクトのURLにアクセスして画面に「YOURSITE OK」と表示されれば完了

--------------------------------------------------------------------------------
## KIYOMASAの大きな特長

開発環境では詳細なデバッグ情報（実行時間や実行されたSQLなど）がHTMLの末尾に付与されて返される。dump()コマンドを使うとデバッグ情報枠に成型したダンプデータを表示させることも可能。エラーや警告が出た場合も瞬時に教えてくれる。バグを見つけるためのヒントとして大いにKIYOMASAを活用して欲しい。

--------------------------------------------------------------------------------
## KIYOMASAのルール

名前空間はディレクトリに合わせる。プログラムファイルのファイル名はクラス名に合わせる。ただしファイル名はunder_bar形式、クラス名はStudlyCaps形式にする。ディレクトリ構造と、.（ドット）で始まるファイルは原則として変更しない。

セッションを利用する場合、 new Php\Framework\Device\Session を実施する。それ以降は $_SESSION が利用可能になる。

--------------------------------------------------------------------------------
## KIYOMASAの名前の由来
築城の名手・加藤清正の名にちなむ。プロジェクトのプログラムを作ることは、城を構築していくことに近いという思いが込められている。ファイル名やクラス名に城にちなんだ名前が付けられているのもユニークな特徴である。
