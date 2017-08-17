/*
 * JSONの展開
 *
 * @author   Sawada Hideshige
 * @version  1.1.2.0
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
                                    case "eval":
                                        // evalは非推奨のため今後対策を考える
                                        eval(jsonData[i]);
                                        break;
                                    case "window_open":
                                        window.open(jsonData[i], "_blank");
                                        return false;
                                    case "alert":
                                        window.alert(jsonData[i]);
                                        break;
                                    case "clear":
                                        for (var ci in jsonData[i]) {
                                            if (document.getElementById(ci)) {
                                                document.getElementById(ci).innerHTML = "";
                                            }
                                        }
                                        break;
                                    case "style":
                                        for (var si in jsonData[i].key) {
                                            if (document.getElementById(si)) {
                                                document.getElementById(si).style[jsonData[i].key[si]] = jsonData[i].value[si];
                                            }
                                        }
                                        break;
                                    case "value":
                                        for (var vi in jsonData[i]) {
                                            if (document.getElementById(vi)) {
                                                var vie = document.getElementById(vi);
                                                vie.value = jsonData[i][vi];
                                            }
                                        }
                                        break;
                                    case "clear_value":
                                        for (var cvi in jsonData[i]) {
                                            if (document.getElementById(cvi)) {
                                                var cvie = document.getElementById(cvi);
                                                cvie.value = "";
                                            }
                                        }
                                        break;
                                    case "name":
                                        for (var cli in jsonData[i]) {
                                            if (document.getElementsByName(cli)[0]) {
                                                for (var fi = 0; fi < document.getElementsByName(cli).length; fi++) {
                                                    document.getElementsByName(cli)[fi].innerHTML
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
