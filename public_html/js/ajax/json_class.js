/*
 * JSONの展開
 *
 * @author   Sawada Hideshige
 * @version  1.1.3.0
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
                        if (jsonData) {
                            for (var i in jsonData) {
                                switch (i) {
                                    case "debug":
                                    case "dump":
                                        console.log(jsonData[i]);
                                        break;
                                    case "jump":
                                        location.href = jsonData[i];
                                        return false;
                                    case "call":
                                        // サイト側のJSに記述
                                        js.callFunc(jsonData[i]);
                                        break;
                                    case "window_open":
                                        window.open(jsonData[i], "_blank");
                                        return false;
                                    case "alert":
                                        window.alert(jsonData[i]);
                                        break;
                                    case "clear":
                                        for (var ci in jsonData[i]) {
                                            if ($.id(ci)) {
                                                $.id(ci).innerHTML = "";
                                            }
                                        }
                                        break;
                                    case "style":
                                        for (var si in jsonData[i].key) {
                                            if ($.id(si)) {
                                                $.id(si).style[jsonData[i].key[si]] = jsonData[i].value[si];
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
                                                    $.nm(cli)[fi].innerHTML
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
                        }
                        else {
                            window.alert("JSエラー");
                        }
                    }
                    return true;
                }
                catch (e) {
                    console.log(e.name + ":" + e.message);
                }
            };
            /**
             * JSONをHTMLに展開
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
