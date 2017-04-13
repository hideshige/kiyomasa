/**
 * Ajax モデル
 *
 * @author   Hideshige Sawada
 * @version  1.0.0.0
 * @package  js
 */

var mainJson = newXMLHttp();//メイン
var subJson = newXMLHttp();//サブ

var commonObj = new Object();
commonObj.url = '';

//ダブルクリックによる二重投稿の防止
var doubleClickCheck = false;
setInterval("doubleClickCancel()", 500);

/*
 * ---------------------------------------------------------------------------
 * Ajaxの起動
 * ---------------------------------------------------------------------------
 */
function newXMLHttp() {
  if (window.XMLHttpRequest){
    return new XMLHttpRequest();
  } else if(window.ActiveXObject) {
    try {
      return new ActiveXObject("MXSML2.XMLHTTP");
    } catch(e) {
      try {
        return new ActiveXObject("Microsoft.XMLHTTP");
      } catch(e) {
        return null;
      }
    }
  }
  return null;
}

/*
 * ---------------------------------------------------------------------------
 * ノードの追加
 * ---------------------------------------------------------------------------
 * string tagId HTMLタグID
 * string content 埋め込む内容
 * integer nodeId HTMLタグノードID
 * string classId HTMLタグノードクラス
 * integer addType 1:末尾に追加 2:先頭に追加 3:末尾に追加したあとスクロール
 * string tagType 追加するHTMLタグの種類
 */
function moreContent(tagId, content, nodeId, classId, addType, tagType) {
  var tagTypes = tagType || 'li';
  var parentBox = document.getElementById(tagId);
  if (parentBox) {
    var node = document.createElement(tagTypes);
    node.id = nodeIdNames + nodeId;
    node.className = classId;
    node.innerHTML = content;
    node.style['opacity'] = '0.1';
    if (addType == 2) {
      parentBox.insertBefore(node, parentBox.firstChild);
    } else {
      parentBox.appendChild(node);
    }
    setTimeout(function(){
      node.style['opacity'] = '1';
      if (addType == 3) {
        //コンテンツの最下部へスクロール
        var contentsHeight = parentBox.offsetTop + parentBox.clientHeight;
        window.scroll(0,contentsHeight);
      }
    },50);
  }
  return true;
}

/*
 * ---------------------------------------------------------------------------
 * ノードの削除
 * ---------------------------------------------------------------------------
 * string tagId HTMLタグID
 * string nodeId HTMLタグノードID
 */
function deleteContent(tagId, nodeId) {
  var parentBox = document.getElementById(tagId);
  var deleteBox = document.getElementById(nodeId);
  if (deleteBox) {
    deleteBox.style['opacity'] = '0';
    setTimeout(function(){parentBox.removeChild(deleteBox)},300);
  }
  return true;
}

/*
 * ---------------------------------------------------------------------------
 * 指定のJsonを反映
 * ---------------------------------------------------------------------------
 * object objJson AJAXオブジェクト
 * string url_address 開くURL
 */
function openJson(objJson, url_address) {
  if (doubleClickCheck) return false;
  doubleClickCheck = true;
  if (document.getElementById('token')) {
    commonObj.url += '&token=' + document.getElementById('token').innerHTML;
  }
  objJson.open('POST', domain_name + url_address, true);
  objJson.onreadystatechange = function(){ajaxOpen(objJson)};
  objJson.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
  objJson.send(commonObj.url);
  commonObj.url = '';
  return true;
}

/*
 * ---------------------------------------------------------------------------
 * 指定のフォームオブジェクトを送信
 * ---------------------------------------------------------------------------
 * object objJson AJAXオブジェクト
 * object formJson フォームオブジェクト
 * string url_address 開くURL
 */
function postFormObject(objJson, formObject, url_address) {
  //ブラウザによってはキャッシュを見ることがあるためURLにタイムスタンプを付けてキャッシュを無視させる
  objJson.open('POST', domain_name + url_address + '?ver=' + new Date(), true);
  objJson.onreadystatechange = function(){ajaxOpen(objJson)};
  objJson.setRequestHeader('enctype', 'multipart/form-data');
  objJson.send(formObject);
  return true;
}

/*
 * ---------------------------------------------------------------------------
 * AJAXの処理
 * ---------------------------------------------------------------------------
 * object objJson AJAXオブジェクト
 */
function ajaxOpen(objJson) {
  if (objJson.readyState == 4 && objJson.status == 200) {
    try {
      var jsonData = JSON.parse(objJson.responseText);
    } catch (e) {
      try {
        var jsonData = eval('('+objJson.responseText+')');
      } catch (e) {
        //エラー
      }
    }
    if (jsonData) {
      for (var i in jsonData) {
        if (i == 'debug' || i == 'dump') {
          console.log(jsonData[i]);
        } else if (i == 'jump') {
          location.href = jsonData['jump'];
          return false;
        } else if (i == 'eval') {
          eval(jsonData[i]);
        } else if (i == 'window_open') {
          window.open(jsonData[i], '_blank');
          return false;
        } else if (i == 'alert') {
          window.alert(jsonData[i]);
        } else if (i == 'clear') {
          for (var ci in jsonData[i]) {
            document.getElementById(ci).innerHTML = '';
          }
        } else if (i == 'style') {
          for (var si in jsonData[i]['key']) {
            if (document.getElementById(si)) {
              document.getElementById(si).style[jsonData[i]['key'][si]] = jsonData[i]['value'][si];
            }
          }          
        } else if (i == 'value') {
          for (var vi in jsonData[i]) {
            if (document.getElementById(vi)) {
              document.getElementById(vi).value = jsonData[i][vi];
            }
          }
        } else if (i == 'clear_value') {
          for (var cvi in jsonData[i]) {
            document.getElementById(cvi).value = '';
          }
        } else if (i == 'name') {
          for (var cli in jsonData[i]) {
            if (document.getElementsByName(cli)[0]) {
              for (var fi=0; fi<document.getElementsByName(cli).length; fi++){
                document.getElementsByName(cli)[fi].innerHTML = jsonData[i][cli];
              }
            }
          }
        } else if (typeof(document.getElementById(i)) != 'undefined') {
          if (typeof(jsonData[i]) != 'object') {
            if (document.getElementById(i)) {
              document.getElementById(i).innerHTML = jsonData[i];
            }
          } else {
            for (var ni in jsonData[i]) {
              if (jsonData[i][ni]['node_add']) {
                moreContent(i, jsonData[i][ni]['node'], jsonData[i][ni]['node_id'], jsonData[i][ni]['node_class'], jsonData[i][ni]['node_add'], jsonData[i][ni]['node_tag']);
              } else {
                deleteContent(i, jsonData[i][ni]['node_id']);
              }
            }
          }
        }
      }
    }
  }
  return true;
}

/*
 * ---------------------------------------------------------------------------
 * ダブルクリック制御の解除
 * ---------------------------------------------------------------------------
 */
function doubleClickCancel() {
  doubleClickCheck = false;
}