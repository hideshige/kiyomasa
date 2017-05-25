<?php
/**
 * ページングモジュール
 *
 * @author   Sawada Hideshige
 * @version  1.0.4.1
 * @package  device
 *
 */

namespace Php\Framework\Device;

class Paging
{
    /**
     * ページング処理
     * @param int $counts  全体件数
     * @param string $url そのURL
     * @param int $page  ページ番号
     * @param array $get GETで取得した配列
     * @param int $disp_num 1ページあたりの件数
     * @return array page:現在のページ num:ページ数 left:左矢印ボタン right:右矢印ボタン tag:ページングタグ
     */
    public static function set($counts, $url, $page, $get, $disp_num = 20)
    {
        if (!$disp_num) {
            $disp_num = 20;
        }

        $page_arr = [];
        $page_arr['num'] = ceil($counts / $disp_num);

        //指定のページがない場合
        if ($page_arr['num'] < $page) {
            throw new UserException('ページがありません');
        }

        $page_arr['page'] = $page_arr['num'] < $page
            ? $page_arr['num'] : $page;

        $page_arr['left'] = ($page_arr['page'] == 1 or !$counts)
            ? null : $page_arr['page'] - 1;
        $page_arr['right'] = ($page_arr['num'] == $page_arr['page'] or !$counts)
            ? null : $page_arr['page'] + 1;

        $page_arr['maxleft_flag'] = $page_arr['page'] != 1
            ? true : false;
        $page_arr['maxright_flag'] = $page_arr['num'] != $page_arr['page']
            ? true : false;

        $q = self::makeGet($get);
        $page_arr['tag'] = self::pagingTag($page_arr, sprintf('%s%s', $url, $q));

        return $page_arr;
    }


    /**
     * ページングのリンクにGET値を引き継ぐ
     * @param array $get GETで受け取った値
     * @return string 生成したクエリ
     */
    private static function makeGet($get)
    {
        if (isset($get['page'])) {
            unset($get['page']);
        }
        $q = '?';
        foreach ($get as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $vv) {
                    $q .= sprintf('%s[]=%s&', $k, $vv);
                }
            } else {
                $q .= sprintf('%s=%s&', $k, $v);
            }
        }
        return $q;
    }


    /**
     * ページングタグの生成
     * @param array $page_arr
     * @param string $url そのURL
     * @return string
     */
    private static function pagingTag($page_arr, $url)
    {
        $paging_tag = '';

        if ($page_arr['num'] > 1) {
            //左矢印
            if ($page_arr['maxleft_flag']) {
                $paging_tag .= sprintf(
                    '<li class="arrowList off"><a href="%spage=1">&lt;&lt;</a></li>',
                    $url
                );
            }
            if ($page_arr['left']) {
                $paging_tag .= sprintf(
                    '<li class="arrowList"><a href="%spage=%d">&lt;</a></li>',
                    $url,
                    $page_arr['left']
                );
            }

            //ページリングを10個に絞り込み、現在のページを中央に置く
            if ($page_arr['num'] > 11) {
                $link_count = 11;
                if ($page_arr['page'] < 6) {
                    $start_page = 1;
                } else {
                    if ($page_arr['page'] > $page_arr['num'] -6) {
                        $start_page = $page_arr['num'] - 10;
                    } else {
                        $start_page = $page_arr['page'] - 5;
                    }
                }
            } else {
                $start_page = 1;
                $link_count = $page_arr['num'];
            }

            //ページリンク
            $p = $start_page;
            for ($i = 1; $i <= $link_count; $i ++) {
                $aclass = ($p == $page_arr['page']) ? ' class="on"' : '';
                $paging_tag .= sprintf(
                    '<li%s><a href="%spage=%d">%s</a></li>',
                    $aclass,
                    $url,
                    $p,
                    $p
                );
                $p ++;
            }

            //右矢印
            if ($page_arr['right']) {
                $paging_tag .= sprintf(
                    '<li class="arrowList"><a href="%spage=%d">&gt;</a></li>',
                    $url,
                    $page_arr['right']
                );
            }
            if ($page_arr['maxright_flag']) {
                $paging_tag .= sprintf(
                    '<li class="arrowList"><a href="%spage=%d">&gt;&gt;</a></li>',
                    $url,
                    $page_arr['num']
                );
            }
        }

        if ($paging_tag) {
            $paging_tag = sprintf(
                '<ul class="pagingList">%s</ul>',
                $paging_tag
            );
        }

        return $paging_tag;
    }
}

