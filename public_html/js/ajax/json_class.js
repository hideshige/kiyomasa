/*
 * JSONの展開
 *
 * @author   Sawada Hideshige
 * @version  1.1.4.0
 * @package  js
 */
var Js;
(function (Js) {
    var Ajax;
    (function (Ajax) {
        "use strict";
        var JsonClass = (function () {
            function JsonClass() {
            }
            /**
             * AJAXの処理
             * @param {XMLHttpRequest} objJson
             * @returns {boolean}
             */
            JsonClass.prototype.ajaxOpen = function (objJson) {
                try {
                    if (objJson.readyState === 4 && objJson.status === 200) {
                        var jsonData = JSON.parse(objJson.responseText);
                        this.jsonDeploy(jsonData);
                    }
                    else if (objJson.readyState === 4 && objJson.status === 504) {
                        window.alert("タイムアウトエラー");
                    }
                    return true;
                }
                catch (e) {
                    window.alert("エラーになりました");
                    console.log(e.name + ":" + e.message);
                }
            };
            /**
             * JSONの展開
             * @param {JSON} jsonData
             * @returns {boolean}
             */
            JsonClass.prototype.jsonDeploy = function (jsonData) {
                for (var i in jsonData) {
                    switch (i) {
                        case "debug":
                        case "dump":
                            console.log(jsonData[i]);
                            break;
                        case "call":
                            js.callFunc(jsonData[i]);
                            break;
                        case "jump":
                            location.href = jsonData[i];
                            return true;
                        case "window_open":
                            window.open(jsonData[i], "_blank");
                            return true;
                        case "alert":
                            window.alert(jsonData[i]);
                            break;
                        case "clear":
                            for (var ci in jsonData[i]) {
                                if ($.id(ci)) {
                                    $.id(ci).textContent = "";
                                }
                            }
                            break;
                        case "style":
                            for (var si in jsonData[i].key) {
                                if ($.id(si)) {
                                    $.id(si).style[jsonData[i].key[si]]
                                        = jsonData[i].value[si];
                                }
                            }
                            break;
                        case "value":
                            for (var vi in jsonData[i]) {
                                if ($.id(vi)) {
                                    var vie = $.id(vi);
                                    vie.value = jsonData[i][vi];
                                }
                            }
                            break;
                        case "clear_value":
                            for (var cvi in jsonData[i]) {
                                if ($.id(cvi)) {
                                    var cvie = $.id(cvi);
                                    cvie.value = "";
                                }
                            }
                            break;
                        case "name":
                            for (var cli in jsonData[i]) {
                                if ($.nm(cli)[0]) {
                                    for (var fi = 0; fi < $.nm(cli).length; fi++) {
                                        $.nm(cli).item(fi).innerHTML
                                            = jsonData[i][cli];
                                    }
                                }
                            }
                            break;
                        default:
                            this.jsonNode(jsonData, i);
                            break;
                    }
                }
                return true;
            };
            /**
             * HTMLに組み込む
             * @param {JSON} jsonData
             * @param {string} i
             * @returns {void}
             */
            JsonClass.prototype.jsonNode = function (jsonData, i) {
                if ($.id(i)) {
                    if (typeof (jsonData[i]) !== "object") {
                        if ($.id(i)) {
                            $.id(i).innerHTML = jsonData[i];
                        }
                    }
                    else {
                        for (var ni in jsonData[i]) {
                            if (jsonData[i][ni].node_add) {
                                ajax.moreContent(i, jsonData[i][ni].node, jsonData[i][ni].node_id, jsonData[i][ni].node_class, jsonData[i][ni].node_add, jsonData[i][ni].node_tag);
                            }
                            else {
                                ajax.deleteContent(i, jsonData[i][ni].node_id);
                            }
                        }
                    }
                }
            };
            return JsonClass;
        }());
        Ajax.JsonClass = JsonClass;
    })(Ajax = Js.Ajax || (Js.Ajax = {}));
})(Js || (Js = {}));
