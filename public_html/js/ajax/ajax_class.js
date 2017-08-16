/**
 * Ajax
 *
 * @author   Sawada Hideshige
 * @version  1.1.0.0
 * @package  js
 */
var Js;
(function (Js) {
    var Ajax;
    (function (Ajax) {
        "use strict";
        var AjaxClass = (function () {
            function AjaxClass() {
                this.mainJson = new XMLHttpRequest();
                this.subJson = new XMLHttpRequest();
                this.url_root = "/";
                this.doubleClickCheck = false; // ダブルクリックによる二重投稿の防止
                this.commonParam = "";
            }
            /**
             * ダブルクリックのキャンセル
             * @return {void}
             */
            AjaxClass.prototype.doubleClickCancel = function () {
                this.doubleClickCheck = false;
            };
            /**
             * ノードの追加
             * @param {string} tagId HTMLタグID
             * @param {string} content 埋め込む内容
             * @param {string} nodeId HTMLタグノードID
             * @param {string} classId HTMLタグノードクラス
             * @param {number} addType 1:末尾に追加 2:先頭に追加 3:末尾に追加したあとスクロール
             * @param {string} tagType 追加するHTMLタグの種類
             */
            AjaxClass.prototype.moreContent = function (tagId, content, nodeId, classId, addType, tagType) {
                var tagTypes = tagType || "li";
                var parentBox = $.id(tagId);
                if (parentBox) {
                    var node_1 = $.create(tagTypes);
                    node_1.id = nodeId;
                    if (classId) {
                        node_1.className = classId;
                    }
                    node_1.innerHTML = content;
                    node_1.style.opacity = "0.1";
                    if (addType === 2) {
                        parentBox.insertBefore(node_1, parentBox.firstChild);
                    }
                    else {
                        parentBox.appendChild(node_1);
                    }
                    setTimeout(function () {
                        node_1.style.opacity = "1";
                        if (addType === 3) {
                            // コンテンツの最下部へスクロール
                            var contentsHeight = parentBox.offsetTop + parentBox.clientHeight;
                            window.scroll(0, contentsHeight);
                        }
                    }, 100);
                }
                return true;
            };
            /**
             * ノードの削除
             * @param {string} tagId HTMLタグID
             * @param {string} nodeId HTMLタグノードID
             * @return {boolean}
             */
            AjaxClass.prototype.deleteContent = function (tagId, nodeId) {
                var parentBox = $.id(tagId);
                var deleteBox = $.id(nodeId);
                if (deleteBox) {
                    deleteBox.style.opacity = "0";
                    setTimeout(function () {
                        parentBox.removeChild(deleteBox);
                    }, 300);
                }
                return true;
            };
            /**
             * 指定のJsonを反映
             * @param {XMLHttpRequest} objJson AJAXオブジェクト
             * @param {string} url_address 開くURL
             * @return {boolean}
             */
            AjaxClass.prototype.openJson = function (objJson, url_address) {
                if (this.doubleClickCheck) {
                    return false;
                }
                this.doubleClickCheck = true;
                if (document.getElementById("token")) {
                    this.commonParam += "&token=" + $.id("token").innerHTML;
                }
                objJson.open("GET", this.url_root + url_address, true);
                objJson.onreadystatechange = function () {
                    json.ajaxOpen(objJson);
                };
                objJson.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
                objJson.send(this.commonParam);
                this.commonParam = "";
                return true;
            };
            /**
             * 指定のフォームオブジェクトを送信
             * @param {XMLHttpRequest} objJson AJAXオブジェクト
             * @param {HTMLElement} formObject フォームオブジェクト
             * @param {string} url_address 開くURL
             * @return {boolean}
             */
            AjaxClass.prototype.postFormObject = function (objJson, formObject, url_address) {
                var date = new Date();
                // ブラウザによってはキャッシュを見ることがあるためURLにタイムスタンプを付けてキャッシュを無視させる
                objJson.open("POST", this.url_root + url_address + "?ver=" + $.encode(date), true);
                objJson.onreadystatechange = function () {
                    json.ajaxOpen(objJson);
                };
                objJson.setRequestHeader("enctype", "multipart/form-data");
                objJson.send(formObject);
                return true;
            };
            return AjaxClass;
        }());
        Ajax.AjaxClass = AjaxClass;
    })(Ajax = Js.Ajax || (Js.Ajax = {}));
})(Js || (Js = {}));
var ajax = new Js.Ajax.AjaxClass();
