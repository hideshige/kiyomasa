<?php
/**
 * 入力フォーム検証モジュール
 *
 * @author   Hideshige Sawada
 * @version  1.3.4.0
 * @package  device
 *
 * 以下のような形でパラメーターを設定し検証ルールを適用させる。
 $params = array(
    'example1' => array('must' => 1, 'max' => 30, 'int' => 1, 'type' => 5, 'name' => '例1'),
    'example2' => array('select' => array('0' => 'OFF', '1' => 'ON'), 'type' => 3, 'name' => '例2'),
 );
 *
 * ルールの意味
 * type   : フォームの種類(1 textarea, 2 radio, 3 select, 4 check, 5 text, 6 password, 7 email)
 * must   : 入力必須
 * must_select: 選択必須
 * max    : 最大文字数
 * min    : 最小文字数
 * fill   : 指定文字数のみ有効
 * int    : 整数のみ有効
 * eng    : 英数字のみ有効
 * char   : 半角のみ有効
 * hiragana: ひらがなのみ有効
 * katakana: カタカナのみ有効
 * email  : メールアドレスのみ有効
 * count  : チェックボックスで選択可能な数
 * match_count: チェックボックスで選択する数
 * select : 選択項目を入れた配列
 * name   : パラメーターの表示用の名前
 * add    : 補足メッセージの表示
 * pre_add: 補足メッセージの表示(前に表示)
 * tag_add: タグ内に付加する文字
 * join   : ひとつ前の質問に回答欄を結合
 *
 *
 *
テンプレートは以下のようにする
<!-- BEGIN MESSAGE -->
<p style="color:red;">{message}</p>
<!-- END MESSAGE -->

<!-- BEGIN FORM_AREA -->
<form action="/example" method="post">
    <input type="hidden" name="post_flag" value="1" />
    <table style="width:100%;">
        <!-- BEGIN FORM -->
        <tr>
            <th style="width:100px">{question}<!-- BEGIN MUST_MARK -->
                <span style="color:#FF0000;">*</span>
            <!-- END MUST_MARK --></th>
            <td<!-- BEGIN COLOR --> style="background:#F8F8F8;"<!-- END COLOR -->><!-- BEGIN MESS -->
                <span style="color:red;">{mess}</span>
                <!-- END MESS -->{answer}
            </td>
        </tr>
        <!-- END FORM -->
    </table>
    <input type="submit" value="送信" />
</form>
<!-- END FORM_AREA -->
 *
 *
モデルは以下のようにする
$params = array(
    'example1' => array('must' => 1, 'max' => 30, 'int' => 1, 'type' => 5, 'name' => '例1'),
    'example2' => array('select' => array('0' => 'OFF', '1' => 'ON'), 'type' => 3, 'name' => '例2'),
);

$box = S::$post;

$form = [];
Form::formArr($params, $form, $box);

$form_list = &S::$disp[1]['FORM_AREA'][0]['FORM'];

$i = 0;
foreach ($form as $k => $v) {
    $form_list[$i]['id'] = $k;
    $form_list[$i]['question'] = $params[$k]['name'];
    if (isset($params[$k]['must']) or isset($params[$k]['must_select'])) {
        $form_list[$i]['MUST_MARK'][0] = '';
    }
    $form_list[$i]['answer'] = $v;
    $i ++;
}
if (isset(S::$post['post_flag'])) {
    $vali = Form::validation($params, $box);
    if ($vali) {
        S::$disp[1]['MESSAGE'][0]['message'] = '入力に不備があります。';
        Form::getMessage($vali, $form_list);
        return true;
    }
}
 *
 */

namespace Php\Framework\Device;

class Form
{
    private static $params;
    private static $e = [
        'must' => '入力してください。<br />',
        'must_select' => '選んでください。<br />',
        'max' => '文字数が%d文字を超えています。（現在%d文字）<br />',
        'min' => '文字数が%d文字に満たないです。（現在%d文字）<br />',
        'fill' => '文字数が%d文字ではありません。（現在%d文字）<br />',
        'int' => '数字以外の文字が含まれています。<br />',
        'eng' => '半角英数字以外の文字が含まれています。<br />',
        'char' => '半角以外の文字が含まれています。<br />',
        'hiragana' => 'ひらがな以外の文字が含まれています。<br />',
        'katakana' => 'カタカナ以外の文字が含まれています。<br />',
        'count' => '選択できるのは%sまでです。<br />',
        'match_count' => '%s選択してください。<br />',
        'select_error' => '一致する選択肢が存在しません。<br />',
        'not_match1' => '登録されていません。<br />',
        'not_match2' => '登録情報と一致しません。<br />',
        'email' => '正しいメールアドレスを入力してください。<br />',
        'email2' => 'メールアドレスは既に登録されています。<br />',
        'email3' => '確認用のメールアドレスと異なります。<br />',
        'password_error' => '確認用のパスワードと異なります。<br />', // model側から判定する
        'password_error2' => 'パスワードが正しくありません。<br />', // model側から判定する
        'checkdate' => '日付が正しくありません。<br />', // model側から判定する
        'ng_word' => '不適切な文字が含まれています。<br />', // model側から判定する
    ];

    /**
     * 入力フォームの内容を検証する
     * @param array $params 検証ルール
     * @param array $data 検証するデータ
     * return array (検証結果を格納した配列を返す)
     */
    public static function validation(
        $params,
        $data
    ) {
        self::$params = $params;
        $res = [];

        foreach ($params as $key => $val) {
            $str = @htmlspecialchars_decode($data[$key], ENT_QUOTES);
            foreach ($val as $k => $v) {
                if ($k === 'must' and preg_replace('/^[　 \r\n]+|[　 \r\n]+$/u', '', $str) == '') {
                    $res[$key][$k] = true;
                } else if ($k === 'must_select' and !$str) {
                    $res[$key][$k] = true;
                } else if ($k === 'max' and $str != '' and mb_strlen($str) > $v) {
                    $res[$key][$k] = mb_strlen($str);
                } else if ($k === 'min' and $str != '' and mb_strlen($str) < $v) {
                    $res[$key][$k] = mb_strlen($str);
                } else if ($k === 'fill' and $str != '' and mb_strlen($str) != $v) {
                    $res[$key][$k] = mb_strlen($str);
                } else if ($k === 'int' and $str != '' and !preg_match('/^[\d,]+$/', $str)) {
                    $res[$key][$k] = true;
                } else if ($k === 'eng' and $str != '' and !preg_match("/^[\w.',=-]+$/", $str)) {
                    $res[$key][$k] = true;
                } else if ($k === 'char' and $str != '' and strlen($str) != mb_strlen($str)) {
                    $res[$key][$k] = true;
                } else if ($k === 'hiragana' and $str != '' and !preg_match('/^[あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをんがぎぐげござじずぜぞだぢづでどばびぶべぼぱぴぷぺぽゃゅょっぁぃぅぇぉーゐゑゝゞ・　 ]+$/u', $str)) {
                    $res[$key][$k] = true;
                } else if ($k === 'katakana' and $str != '' and !preg_match('/^[アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンヴガギグゲゴザジズゼゾダヂヅデドバビブベボパピプペポャュョッァィゥェォヶーヰヱヽヾ・　 ]+$/u', $str)) {
                    $res[$key][$k] = true;
                } else if ($k === 'count' and $str != '' and count($str) > $v) {
                    $tani = $v < 10 ? 'つ' : '';
                    $res[$key][$k] = $v . $tani;
                } else if ($k === 'match_count' and count($str) != $v) {
                    $tani = $v < 10 ? 'つ' : '';
                    $res[$key][$k] = $v . $tani;
                } else if ($k === 'email' and $str != '' and (!preg_match('/^.+@.+\..+$/', $str) or !preg_match('/^[0-9a-zA-Z@\-_\.\+]+$/u', $str))) {
                    $res[$key][$k] = true;
                } else if ($k === 'select' and $str != '' and !is_array($str) and !isset($v[$str])) {//ラジオ、セレクト値送信の不正チェック
                    $res[$key]['select_error'] = true;
                } else if ($k === 'select' and $str != '' and is_array($str)) {//チェックボックス値送信の不正チェック
                    foreach ($str as $strv) {
                        if (!$v[$strv]) {
                            $res[$key]['select_error'] = true;
                        }
                    }
                }
            }
        }
        return $res;
    }

    /**
     * フォームにエラーメッセージを生成
     * @param array $error_data 検証結果
     * @param array $form フォームオブジェクト
     * @param array $error_customize ユーザー定義エラー array ('must' => '入力されていません') の形でエラーメッセージを追加できる
     */
    public static function getMessage(
        $error_data,
        &$form_list,
        $error_customize = []
    ) {
        $k = 0;
        foreach (self::$params as $name => $v) {
            if (isset($v['join'])) {
                $k --;
            }
            if (isset($error_data[$name])) {
                $form_list[$k]['COLOR'] = '';
                $m = &$form_list[$k]['MESS'][0]['mess'];
                $m = '';
                if ($error_customize) {
                    foreach ($error_customize as $eck => $ecv) {
                        if (array_key_exists($eck, $error_data[$name])) {
                            $m .= $ecv;
                        }
                    }
                }
                if (array_key_exists('must', $error_data[$name])) $m .= self::$e['must'];
                if (array_key_exists('must_select', $error_data[$name])) $m .= self::$e['must_select'];
                if (array_key_exists('max', $error_data[$name])) $m .= sprintf(self::$e['max'], $v['max'], $error_data[$name]['max']);
                if (array_key_exists('min', $error_data[$name])) $m .= sprintf(self::$e['min'], $v['min'], $error_data[$name]['min']);
                if (array_key_exists('fill', $error_data[$name])) $m .= sprintf(self::$e['fill'], $v['fill'], $error_data[$name]['fill']);
                if (array_key_exists('int', $error_data[$name])) $m .= self::$e['int'];
                if (array_key_exists('eng', $error_data[$name])) $m .= self::$e['eng'];
                if (array_key_exists('char', $error_data[$name])) $m .= self::$e['char'];
                if (array_key_exists('hiragana', $error_data[$name])) $m .= self::$e['hiragana'];
                if (array_key_exists('katakana', $error_data[$name])) $m .= self::$e['katakana'];
                if (array_key_exists('email', $error_data[$name])) $m .= self::$e['email'];
                if (array_key_exists('email2', $error_data[$name])) $m .= self::$e['email2'];
                if (array_key_exists('email3', $error_data[$name])) $m .= self::$e['email3'];
                if (array_key_exists('count', $error_data[$name])) $m .= sprintf(self::$e['count'], $error_data[$name]['count']);
                if (array_key_exists('match_count', $error_data[$name])) $m .= sprintf(self::$e['match_count'], $error_data[$name]['match_count']);
                if (array_key_exists('checkdate', $error_data[$name])) $m .= self::$e['checkdate'];
                if (array_key_exists('select_error', $error_data[$name])) $m .= self::$e['select_error'];
                if (array_key_exists('not_match1', $error_data[$name])) $m .= self::$e['not_match1'];
                if (array_key_exists('not_match2', $error_data[$name])) $m .= self::$e['not_match2'];
                if (array_key_exists('password_error', $error_data[$name])) $m .= self::$e['password_error'];
                if (array_key_exists('password_error2', $error_data[$name])) $m .= self::$e['password_error2'];
                if (array_key_exists('ng_word', $error_data[$name])) $m .= self::$e['ng_word'];
            }
            $k ++;
        }
    }


    /**
     * 選択フォームの一括生成
     * @param array $params 検証ルール
     * @param array $form フォームオブジェクト
     * @param array $post ポストデータ
     * @param boolean $br_flag チェックボックスとラジオボタンに改行を入れるかどうか
     */
    public static function formArr(
        $params,
        &$form,
        $post,
        $br_flag = false
    ) {
        foreach ($params as $name => $val) {
            if (!isset($val['join'])) {
                $key = $name;
                $form[$key] = '';
            }
            if (isset($val['pre_add'])) {
                $form[$key] .= $val['pre_add'];
            }
            if ($val['type'] === 3) {
                self::selectForm(
                    $name,
                    $key,
                    $val['select'],
                    isset($post[$name]) ? $post[$name] : '',
                    $form,
                    isset($val['tag_add']) ? $val['tag_add'] : ''
                );
            } else if ($val['type'] === 2) {
                self::radioForm(
                    $name,
                    $key,
                    $val['select'],
                    isset($post[$name]) ? $post[$name] : '',
                    $form,
                    isset($val['tag_add']) ? $val['tag_add'] : '',
                    $br_flag
                );
            } else if ($val['type'] === 4) {
                self::checkForm(
                    $name,
                    $key,
                    $val['select'],
                    isset($post[$name]) ? $post[$name] : '',
                    $form,
                    isset($val['tag_add']) ? $val['tag_add'] : '',
                    $br_flag
                );
            } else if ($val['type'] === 5 or $val['type'] === 6 or $val['type'] === 7) {
                switch ($val['type']) {
                    case 6: $text_type = 'password'; break;
                    case 7: $text_type = 'email'; break;
                    default: $text_type = 'text';
                }
                $max = isset($val['max'])
                    ? sprintf(' maxlength="%d"', $val['max']) : '';
                $t = '<input type="%s" name="%s" value="%s" size="%d"%s%s%s />';
                $form[$key] .= sprintf(
                    $t,
                    $text_type,
                    $name,
                    isset($post[$name]) ? $post[$name] : '',
                    isset($val['size']) ? $val['size'] : 10,
                    $max,
                    isset($val['tag_add']) ? $val['tag_add'] : '',
                    isset($val['must']) ? ' required' : ''
                );
            } else {
                $t = '<textarea name="%s" cols="%d" rows="%d"%s%s>%s</textarea>';
                $form[$key] .= sprintf(
                    $t,
                    $name,
                    isset($val['cols']) ? $val['cols'] : 50,
                    isset($val['rows']) ? $val['rows'] : 4,
                    isset($val['tag_add']) ? $val['tag_add'] : '',
                    isset($val['must']) ? ' required' : '',
                    isset($post[$name]) ? $post[$name] : ''
                );
            }
            if (isset($val['add'])) {
                 $form[$key] .= $val['add'];
            }
        }
    }


    /**
     * セレクトボックスの生成
     * @param string $name 選択肢の名前
     * @param string $key 選択肢のキー
     * @param array $val 選択肢の内容
     * @param string $data 受け取ったデータ
     * @param array $form フォームオブジェクト
     * @param string $tag_add タグに付加する文字
     */
    private static function selectForm(
        $name,
        $key,
        $val,
        $data,
        &$form,
        $tag_add = ''
    ) {
        if (!isset($form[$key])) {
            $form[$key] = '';
        }
        $form[$key] .= sprintf('<select name="%s"%s>', $name, $tag_add);
        foreach ($val as $k => $v) {
            $select = (isset($data) and $data == $k) ? ' selected="selected"' : '';
            $t = '<option value="%s"%s>%s</option>';
            $form[$key] .= sprintf($t, $k, $select, $v);
        }
        $form[$key] .= '</select>';
    }


    /**
     * ラジオボタンの生成
     * @param string $name ラジオボタンの名前
     * @param string $key チェックボックスのキー
     * @param array $val ラジオボタンの内容
     * @param string $data 受け取ったデータの値
     * @param array $form フォームオブジェクト
     * @param string $tag_add タグに付加する文字
     * @param boolean $br_flag 改行を入れるか否か
     */
    private static function radioForm(
        $name,
        $key,
        $val,
        $data,
        &$form,
        $tag_add = '',
        $br_flag = false
    ) {
        if (!isset($form[$key])) {
            $form[$key] = '';
        }
        foreach ($val as $k => $v) {
            $check = (isset($data) and $data == $k) ? ' checked="checked"' : '';
            $br = $br_flag ? '<br />' : '';
            $t = '<label><input type="radio" name="%s" value="%s"%s%s /> %s </label>%s';
            $form[$key] .= sprintf($t, $name, $k, $check, $tag_add, $v, $br);
        }
    }


    /**
     * チェックボックスの生成
     * @param string $name チェックボックスの名前
     * @param string $key チェックボックスのキー
     * @param array $val チェックボックスの内容
     * @param array $data 受け取ったデータの値
     * @param array $form フォームオブジェクト
     * @param string $tag_add タグに付加する文字
     * @param boolean $br_flag 改行を入れるか否か
     */
    private static function checkForm(
        $name,
        $key,
        $val,
        $data,
        &$form,
        $tag_add = '',
        $br_flag = false
    ) {
        if (!isset($form[$key])) $form[$key] = '';
        foreach ($val as $k => $v) {
            if (!isset($data)) {
                $data = [];
            }
            if (!is_array ($data)) {
                // 区切り文字の場合は配列に変換
                $data = explode(',', $data);
            }
            $check = (array_search($k, $data, false) !== false)
                ? ' checked="checked"' : '';
            $br = $br_flag ? '<br />' : '';
            $t = '<label><input type="checkbox" name="%s[]" value="%s"%s%s /> %s </label>%s';
            $form[$key] .= sprintf($t, $name, $k, $check, $tag_add, $v, $br);
        }
    }

    /**
     * 確認画面を表示
     * @param object $form フォームオブジェクト
     * @param array $question 問題の入った配列
     * @param array $answer 回答の入った配列
     */
    public static function dispConfirm(
        &$form,
        $question,
        $answer
    ) {
        foreach ($question as $k => $v) {
            $form[$k]['question_num'] = $k + 1;
            $form[$k]['question'] = nl2br($v['question']);
            if ($v['answer_type'] === 2 or $v['answer_type'] === 3) {
                //セレクト、ラジオ
                $select = unserialize($v['answer_select']);
                if ($answer[$v['question_id']]) {
                    $form[$k]['answer'] = $select[$answer[$v['question_id']]];
                } else {
                    $form[$k]['answer'] = '回答なし';
                }
            } else if ($v['answer_type'] === 4) {
                //チェックボックス
                $box = [];
                if (count($answer[$v['question_id']])) {
                    foreach ($answer[$v['question_id']] as $ck => $cv) {
                        $select = unserialize($v['answer_select']);
                        $box[$ck] = $select[$cv];
                    }
                    $form[$k]['answer'] = implode('<br />', $box);
                } else {
                    $form[$k]['answer'] = '回答なし';
                }
            } else {
                //テキスト、フリーワード
                if ($answer[$v['question_id']]) {
                    $form[$k]['answer'] = nl2br($answer[$v['question_id']]);
                } else {
                    $form[$k]['answer'] = '回答なし';
                }
            }
        }
    }

    /**
     * データ作成
     * @param object $form フォームオブジェクト
     * @param array $question 問題の入った配列
     * @param array $answer 回答の入った配列
     */
    public static function makeData(
        &$form,
        $question,
        $answer
    ) {
        foreach ($question as $k => $v) {
            $form[$k]['question_id'] = $v['question_id'];
            $form[$k]['answer_type'] = $v['answer_type'];
            $form[$k]['question'] = $v['question'];
            if ($v['answer_type'] === 4) {
                //チェックボックス
                if (count($answer[$v['question_id']])) {
                    $word = implode('],[', $answer[$v['question_id']]);
                    $form[$k]['answer'] = sprintf('[%s]', $word);
                } else {
                    $form[$k]['answer'] = '';
                }
            } else {
                $form[$k]['answer'] = $answer[$v['question_id']];
            }
        }
    }
}

