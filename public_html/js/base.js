/**
 * JavaScript ベース
 *
 * @author   Sawada Hideshige
 * @version  1.1.0.0
 * @package  js
 */

"use strict";

/**
 * 例外処理
 */
var JsError = (function () {
    function JsError(message) {
        this.message = message;
        this.name = "エラー";
    }
    /**
     * 例外メッセージ
     */
    JsError.prototype.toString = function () {
        return this.name + ": " + this.message;
    };
    return JsError;
}());
/**
 * エイリアス
 */
var $ = (function () {
    function $() {
    }
    /**
     * ドキュメントオブジェクトをIDより取得
     * @param tagId
     */
    $.id = function (tagId) {
        if (!document.getElementById(tagId)) {
            throw new JsError("タグが見つかりません");
        }
        else {
            return document.getElementById(tagId);
        }
    };
    /**
     * ドキュメントオブジェクトを名前より取得
     * @param tagName
     */
    $.nm = function (tagName) {
        if (!document.getElementsByName(tagName)[0]) {
            throw new JsError("タグが見つかりません");
        }
        else {
            return document.getElementsByName(tagName);
        }
    };
    /**
     * ドキュメントオブジェクトをクラス名より取得
     * @param tagName
     */
    $.cls = function (tagName) {
        if (!document.getElementsByClassName(tagName)[0]) {
            throw new JsError("タグが見つかりません");
        }
        else {
            return document.getElementsByClassName(tagName);
        }
    };
    /**
     * ドキュメントオブジェクトの作成
     * @param tagType
     */
    $.create = function (tagType) {
        return document.createElement(tagType);
    };
    /**
     * URLエンコード
     * @param tag
     */
    $.encode = function (tag) {
        return encodeURIComponent(String(tag));
    };
    return $;
}());

window.onload = function () {
    var ajax = new Js.Ajax.AjaxClass();
    setInterval(ajax.doubleClickCancel(), 500);
};
