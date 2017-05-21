<!-- BEGIN DEBUG -->
<style>
#fw_debug_guide
{
    width: 400px;
    position: fixed;
    top: 0;
    right: 0;
    z-index: 10003;
    opacity: 0.7;
}

#fw_debug_guide_html
{
    float: left;
    background: blue;
    color: white;
    width: 230px;
    text-align: right;
    
}

#fw_debug_guide_ajax
{
    float: left;
    background: brown;
    color: white;
    width: 170px;
    text-align: right;
}

#fw_debug_area_html
{
    position: absolute;
    padding: 25px;
    font-size: 0.8em;
    top: 0;
    left: 0;
    
    z-index: 10001;
}

#fw_debug_area_ajax
{
    position: absolute;
    padding: 25px;
    font-size: 0.8em;
    top: 0;
    left: 0;
    z-index: 10002;
}

#fw_debug_html
{
    border: 1px solid #999;
    box-shadow: 5px 5px 10px;
    background: white;
    color: #333;
    padding: 10px;
    position:relative;
}

#fw_debug_ajax
{
    border: 1px solid #999;
    box-shadow: 5px 5px 10px;
    background: #eefefe;
    color: #333;
    padding: 10px;
    position:relative;
}

.fw_debug .fw_debug_header
{
    position: absolute;
    right: 10px;
    top: 10px;
    color: #999;
}

.fw_debug .fw_debug_logo
{
    position: absolute;
    right: 13px;
    bottom: 15px;
    margin: 2px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    opacity: 0.2;
    border: 27px solid #ffee00;
}

.fw_debug .fw_debug_exit_button
{
    cursor: pointer;
    color: #999;
    border: 1px solid #999;
    background: #eee;
    padding:3px 7px;
}

.fw_debug h3
{
    color: red;
    font-size: 1.4em;
    font-weight: bold;
    margin-bottom: 9px;
    font-family: 'arial';
}

.fw_debug h4
{
    color: blue;
}

.fw_debug p,
.fw_debug pre
{
    margin-bottom: 12px;
    margin-left: 20px;
}

.fw_debug .fw_debug_dump
{
    padding: 7px;
    background: #ff9;
    color: #000;
}

.fw_debug .fw_debug_bold
{
    font-weight: bold;
}

.fw_debug .fw_debug_null
{
    color: orange;
}

.fw_debug .fw_debug_int
{
    color: green;
}

.fw_debug .fw_debug_str
{
    color: red;
}

.fw_debug .fw_debug_stmt
{
    color: brown;
}

.fw_debug .fw_debug_semicolon
{
    position: relative;
}

.fw_debug .fw_debug_counter
{
    color: #f90;
}

.fw_debug .fw_debug_time
{
    color: #09f;
}

.fw_debug .fw_debug_line
{
    color: #00c;
}
</style>

<div id="fw_debug_guide">
    <div id="fw_debug_guide_html">
        {env}　{process}秒
        <input type="button" onclick="fwDebug('html');" value="HTML" />
    </div>
    <div id="fw_debug_guide_ajax">
        <span id="fw_debug_guide_ajax_time">---</span>
        <input type="button" onclick="fwDebug('ajax');" value="Ajax" />
    </div>
</div>

<div id="fw_debug_include_html">    
<!-- ELEMENT .debug_include.tpl -->
</div>
<div id="fw_debug_include_ajax">
</div>
<div style="display:none;" id="fw_debug_counter_flag">1</div>
<script>
/**
 * デバッグの表示
 * @param {string} tagId タグ識別ID
 */
function fwDebug(tagId)
{
    var otherTag = tagId === 'html' ? 'ajax' : 'html';
    var myArea = document.getElementById('fw_debug_area_' + tagId);
    var otherArea = document.getElementById('fw_debug_area_' + otherTag);
    
    if (myArea && myArea.style['display'] === 'none') {
        myArea.style['display'] = 'block';
        window.scrollTo(0, 0);
        if (otherArea) {
            otherArea.style['display'] = 'none';
        }
    } else if (myArea) {
        myArea.style['display'] = 'none';
    }
}

/**
 * カウンターの表示
 */
function fwDebugCounter()
{
    var hiddenTag = document.getElementsByName('fw_debug_process');
    var quTag = document.getElementsByName('fw_debug_process_qu');
    var openFlag = document.getElementById('fw_debug_counter_flag').innerHTML;
    
    if (quTag[0]) {
        for (var qi in quTag) {
            if (quTag[qi].style) {
                quTag[qi].style['display'] = openFlag === '1' ? 'inline' : 'none';
            }
        }
    }
    if (hiddenTag[0]) {
        for (var hi in hiddenTag) {
            if (hiddenTag[hi].style) {
                hiddenTag[hi].style['display'] = openFlag === '1' ? 'none' : 'inline';
            }
        }
        document.getElementById('fw_debug_counter_flag').innerHTML
            = openFlag === '1' ? '0' : '1';
    }
}
</script>
<!-- END DEBUG -->
