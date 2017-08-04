<?php
/**
 * ページングモジュール(Ajax用)
 *
 * @author   Sawada Hideshige
 * @version  1.0.5.0
 * @package  device/equipment
 * 
 */

namespace Php\Framework\Device\Equipment;

use Php\Framework\Device as D;

class PagingAjax
{
    /**
     * ページング処理
     * @param int $counts  全体件数
     * @param string $url そのURL
     * @param int $page  ページ番号
     * @param array $get GETで取得した配列
     * @param int $disp_num 1ページあたりの件数
     * @return array page:現在のページ num:ページ数 left:左矢印ボタン right:右矢印ボタン tag:ページングタグ
     * @throws D\UserEx
     */
    public static function set(
        int $counts,
        string $url,
        int $page,
        array $get,
        int $disp_num = 20
    ): array {
        // 38ページ目移行を読み込もうとした場合
        if ($page >= 38) {
            throw new D\UserEx('これ以降のページは除外されています。'
                . '検索結果をすべて表示するには再検索してください。 ');
        }
        
        if ($counts > 38 * $disp_num) {
            $counts = 38 * $disp_num;
        }
        
        $page_arr = [];
        $page_arr['num'] = ceil($counts / $disp_num);

        // 指定のページがない場合
        if ($page_arr['num'] < $page) {
            throw new D\UserEx('ページがありません');
        }

        $page_arr['page'] = $page_arr['num'] < $page 
            ? $page_arr['num'] : $page;

        $page_arr['left'] = ($page_arr['page'] === 1 or $counts === 0)
            ? null : $page_arr['page'] - 1;
        $page_arr['right'] =
            ($page_arr['num'] === $page_arr['page'] or $counts === 0)
            ? null : $page_arr['page'] + 1;

        $q = self::makeGet($get);
        $page_arr['tag'] = self::pagingTag($page_arr, sprintf('%s%s', $url, $q));

        return $page_arr;
    }


    /**
     * ページングのリンクにGET値を引き継ぐ
     * @param array $get GETで受け取った値
     * @return string 生成したクエリ
     */
    private static function makeGet(array $get): string
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
    private static function pagingTag(array $page_arr, string $url): string
    {
        $paging_tag = '';
        if ($page_arr['num'] > 1) {
            //左矢印
            if ($page_arr['left']) {
                $paging_tag .= sprintf('<li class="hover_on" onclick="'
                    . 'loadList(%d, false);">&lt;</li>',
                    $page_arr['left']);
            } else {
                $paging_tag .= '<li class="hover_off">&lt;</li>';
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
                $aclass = ($p === $page_arr['page']) ? ' page_on' : '';
                $paging_tag .= sprintf('<li class="hover_on%s" onclick="'
                    . 'loadList(%d, false);">%s</li>',
                    $aclass, $p, $p);
                $p ++;
            }

            //右矢印
            if ($page_arr['right']) {
                $paging_tag .= sprintf('<li class="hover_on" onclick="'
                    . 'loadList(%d, false);">&gt;</li>', $page_arr['right']);
            } else {
                $paging_tag .= '<li class="hover_off">&gt;</li>';
            }
        }

        if ($paging_tag) {
            $paging_tag =
                sprintf('<ul class="paging_list noprint">%s</ul>', $paging_tag);
        }

        return $paging_tag;
    }
}
