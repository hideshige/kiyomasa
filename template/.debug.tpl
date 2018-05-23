<!-- BEGIN DEBUG -->
<style>
#fw_debug *
{
    margin: 0;
    padding: 0;
    border: 0;
    font-family: arial, sans-serif;
    line-height: 20px;
}

#fw_debug p,
#fw_debug pre,
#fw_debug .fw_debug_trace
{
    font-size: 11px;
    line-height: 13px;
    padding: 5px;
    margin: 2px 1px 10px 5px;
    border: 1px solid #eee;
    border-radius: 5px;
    background: #eef;
    word-break: break-all;
}

#fw_debug h3
{
    font-size: 20px;
    color: red;
    font-weight: bold;
    margin-bottom: 9px;
    word-break: break-all;
}

#fw_debug h4
{
    font-size: 15px;
    color: blue;
}

#fw_debug input
{
    border: 1px solid #333;
    background: #999;
    margin: 2px;
    border-radius: 3px;
    cursor: pointer;
    padding: 0 2px;
    color: #fff;
}

#fw_debug input:hover
{
    background: #aaa;
}

#fw_debug input:active
{
    background: #ddd;
}

#fw_debug #fw_debug_guide
{
    position: fixed;
    top: 0;
    right: 0;
    z-index: 10003;
    opacity: 0.7;
}

#fw_debug .fw_debug_guide_html
{
    float: left;
    background: blue;
    color: white;
    width: 210px;
    text-align: right;
    
}

#fw_debug .fw_debug_guide_ajax
{
    float: left;
    background: brown;
    color: white;
    width: 130px;
    text-align: right;
    margin-left: 1px;
}

#fw_debug .fw_debug_area
{
    position: absolute;
    padding: 30px;
    font-size: 0.8em;
    top: 0;
    left: 0;
    z-index: 10004;
    background: rgba(50, 50, 50, 0.3);
}

#fw_debug .fw_debug_html
{
    border: 1px solid #999;
    box-shadow: 5px 5px 10px;
    background: white;
    color: #333;
    padding: 10px;
    position:relative;
}

#fw_debug .fw_debug_ajax
{
    border: 1px solid #999;
    box-shadow: 5px 5px 10px;
    background: #f7fee5;
    color: #333;
    padding: 10px;
    position: relative;
}

#fw_debug .fw_debug_header
{
    position: absolute;
    right: 10px;
    top: 10px;
    color: #999;
}

#fw_debug .fw_debug_logo
{
    position: absolute;
    right: 13px;
    bottom: 15px;
    opacity: 0.2;
    font-size: 20px;
    font-family: impact;
}

#fw_debug .fw_debug_exit_button
{
    cursor: pointer;
    color: #999;
    border: 1px solid #999;
    background: #eee;
    padding:3px 7px;
}

#fw_debug .fw_debug_dump
{
    padding: 7px;
    background: #ff9;
    color: #000;
}

#fw_debug .fw_debug_bold
{
    font-weight: bold;
}

#fw_debug .fw_debug_null
{
    color: orange;
}

#fw_debug .fw_debug_int
{
    color: green;
}

#fw_debug .fw_debug_str
{
    color: red;
}

#fw_debug .fw_debug_db_select
{
    color: #aaf;
}

#fw_debug .fw_debug_stmt
{
    color: brown;
}

#fw_debug .fw_debug_semicolon
{
    position: relative;
}

#fw_debug .fw_debug_counter
{
    color: #f90;
}

#fw_debug .fw_debug_time
{
    color: #09f;
}

#fw_debug .fw_debug_line
{
    color: #00c;
}

#fw_debug .fw_debug_mode
{
    color: #aaf;
    font-size:0.8em;
    margin-left:10px;
}

#fw_debug .fw_debug_trace table
{
    margin-bottom: 10px;
}

#fw_debug .fw_debug_trace table td,
#fw_debug .fw_debug_trace table th
{
    border: 1px solid #ccc;
    padding: 0 5px;
}

#fw_debug .fw_debug_trace_args
{
    width: 200px;
    height: 15px;
    font-size: 0.9em;
    background: #eee;
}
</style>

<div id="fw_debug">
    <div id="fw_debug_guide">
        <div class="fw_debug_guide_html">
            {env}　{process}秒
            <input type="button" onclick="FwDebugClass.fwDebug('html_{navi_id}', false);" value="HTML" />
        </div>
    </div>

    <div id="fw_debug_include_html">
    <!-- ELEMENT element/.debug_include.tpl -->
    </div>
    <div id="fw_debug_include_ajax">
    </div>
<div style="display: none;" id="fw_debug_counter_flag">1</div>
</div>
<script type="text/javascript">
var Js;
(function (Js) {
    "use strict";
    var FwDebugClass = (function () {
        function FwDebugClass() {
        }
        /**
         * デバッグの表示
         * @param tagId タグ識別ID
         * @param exitFlag 閉じる場合TRUE
         */
        FwDebugClass.prototype.fwDebug = function (tagId, exitFlag) {
            // いったんすべてのデバッグを非表示にする
            var elements = document.getElementsByClassName("fw_debug_area");
            for (var i = 0; elements.length > i; i++) {
                elements.item(i).style.display = "none";
            }
            var myArea = document.getElementById("fw_debug_area_" + tagId);
            if (myArea && exitFlag === false) {
                myArea.style.display = "block";
                window.scrollTo(0, 0);
            }
            else if (myArea && exitFlag === true) {
                myArea.style.display = "none";
            }
        };
        /**
         * カウンターの表示
         */
        FwDebugClass.prototype.fwDebugCounter = function () {
            var openFlag = document.getElementById("fw_debug_counter_flag");
            var hiddenTag = document.getElementsByName("fw_debug_process");
            var quTag = document.getElementsByName("fw_debug_process_qu");
            var dispMode = document.getElementsByName("fw_debug_mode");
            if (dispMode) {
                for (var di in dispMode) {
                    if (dispMode.item(di)) {
                        dispMode.item(di).textContent = openFlag.textContent
                            === "1" ? "True SQL Mode" : "Developper Mode";
                    }
                }
            }
            if (quTag) {
                for (var qi in quTag) {
                    if (quTag.item(qi)) {
                        quTag.item(qi).style.display = openFlag.textContent
                            === "1" ? "inline" : "none";
                    }
                }
            }
            if (hiddenTag) {
                for (var hi in hiddenTag) {
                    if (hiddenTag.item(hi)) {
                        hiddenTag.item(hi).style.display = openFlag.textContent
                            === "1" ? "none" : "inline";
                    }
                }
                openFlag.textContent = openFlag.textContent === "1" ? "0" : "1";
            }
        };
        return FwDebugClass;
    }());
    Js.FwDebugClass = FwDebugClass;
})(Js || (Js = {
}));
var FwDebugClass = new Js.FwDebugClass(); 
</script>
<!-- END DEBUG -->
