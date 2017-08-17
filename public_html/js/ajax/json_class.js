/*
 * JSONの展開
 *
 * @author   Sawada Hideshige
 * @version  1.1.1.1
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
                                if (i === "debug" || i === "dump") {
                                    console.log(jsonData[i]);
                                }
                                else if (i === "jump") {
                                    location.href = jsonData[i];
                                    return false;
                                }
                                else if (i === "eval") {
                                    // evalは非推奨のため今後対策を考える
                                    eval(jsonData[i]);
                                }
                                else if (i === "window_open") {
                                    window.open(jsonData[i], "_blank");
                                    return false;
                                }
                                else if (i === "alert") {
                                    window.alert(jsonData[i]);
                                    $.id("content").innerHTML = "aaabcd";
                                }
                                else if (i === "clear") {
                                    for (var ci in jsonData[i]) {
                                        if (document.getElementById(ci)) {
                                            document.getElementById(ci).innerHTML = "";
                                        }
                                    }
                                }
                                else if (i === "style") {
                                    var key = "key";
                                    var value = "value";
                                    for (var si in jsonData[i][key]) {
                                        if (document.getElementById(si)) {
                                            document.getElementById(si).style[jsonData[i][key][si]] = jsonData[i][value][si];
                                        }
                                    }
                                }
                                else if (i === "value") {
                                    for (var vi in jsonData[i]) {
                                        if (document.getElementById(vi)) {
                                            var vie = document.getElementById(vi);
                                            vie.value = jsonData[i][vi];
                                        }
                                    }
                                }
                                else if (i === "clear_value") {
                                    for (var cvi in jsonData[i]) {
                                        if (document.getElementById(cvi)) {
                                            var cvie = document.getElementById(cvi);
                                            cvie.value = "";
                                        }
                                    }
                                }
                                else if (i === "name") {
                                    for (var cli in jsonData[i]) {
                                        if (document.getElementsByName(cli)[0]) {
                                            for (var fi = 0; fi < document.getElementsByName(cli).length; fi++) {
                                                document.getElementsByName(cli)[fi].innerHTML
                                                    = jsonData[i][cli];
                                            }
                                        }
                                    }
                                }
                                else if (typeof (document.getElementById(i)) !== "undefined") {
                                    if (typeof (jsonData[i]) !== "object") {
                                        if (document.getElementById(i)) {
                                            document.getElementById(i).innerHTML = jsonData[i];
                                        }
                                    }
                                    else {
                                        var ajax = new Js.Ajax.AjaxClass();
                                        var tagName = ["node", "node_id", "node_class", "node_add", "node_tag"];
                                        for (var ni in jsonData[i]) {
                                            if (jsonData[i][ni][tagName[3]]) {
                                                ajax.moreContent(i, jsonData[i][ni][tagName[0]], jsonData[i][ni][tagName[1]], jsonData[i][ni][tagName[2]], jsonData[i][ni][tagName[3]], jsonData[i][ni][tagName[4]]);
                                            }
                                            else {
                                                ajax.deleteContent(i, jsonData[i][ni][tagName[1]]);
                                            }
                                        }
                                    }
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
            return JsonClass;
        }());
        Ajax.JsonClass = JsonClass;
    })(Ajax = Js.Ajax || (Js.Ajax = {}));
})(Js || (Js = {}));
