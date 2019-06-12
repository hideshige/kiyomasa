/*
 * JavaScript 基本ファイル
 *
 * @author   Sawada Hideshige
 * @version  1.1.2.0
 * @package  js
 */
"use strict";
/**
 * 例外処理クラス
 */
var JsError = (function () {
    /**
     * コンストラクタ
     * @param {string} message
     */
    function JsError(message) {
        this.message = message;
        this.name = "JSエラー";
    }
    /**
     * 例外メッセージ
     * @returns {string}
     */
    JsError.prototype.toString = function () {
        var message = this.name + ": " + this.message;
        console.log(message);
        return message;
    };
    return JsError;
}());
/**
 * エイリアスクラス
 */
var $ = (function () {
    function $() {
    }
    /**
     * ドキュメントオブジェクトをIDより取得
     * @param tagId
     * @returns {boolean|HTMLElement}
     */
    $.id = function (tagId) {
        if (!document.getElementById(tagId)) {
            return false;
        }
        else {
            return document.getElementById(tagId);
        }
    };
    /**
     * ドキュメントオブジェクトを名前より取得
     * @param tagName
     * @returns {boolean|NodeListOf<Element>}
     */
    $.nm = function (tagName) {
        if (!document.getElementsByName(tagName)) {
            return false;
        }
        else {
            return document.getElementsByName(tagName);
        }
    };
    /**
     * ドキュメントオブジェクトをクラス名より取得
     * @param tagName
     * @returns {boolean|NodeListOf<Element>}
     */
    $.cls = function (tagName) {
        if (!document.getElementsByClassName(tagName)) {
            return false;
        }
        else {
            return document.getElementsByClassName(tagName);
        }
    };
    /**
     * ドキュメントオブジェクトの作成
     * @param {string} tagType
     * @returns {HTMLElement}
     */
    $.create = function (tagType) {
        return document.createElement(tagType);
    };
    /**
     * URLエンコード
     * @param {number|float|string} tag
     * @returns {string}
     */
    $.encode = function (tag) {
        return encodeURIComponent(String(tag));
    };
    return $;
}());
