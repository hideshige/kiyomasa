<!-- BEGIN DEBUG -->
<style>
#fw_debug_guide
{
    width: 340px;
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
    width: 210px;
    text-align: right;
    
}

#fw_debug_guide_ajax
{
    float: left;
    background: brown;
    color: white;
    width: 130px;
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

.fw_debug .fw_debug_exit_button
{
    position: absolute;
    right: -1px;
    top: -1px;
    cursor: pointer;
    color: #999;
    border: 1px solid #999;
    background: #eee;
    padding:3px 7px;
}

.fw_debug h3
{
    color: red;
    font-size: 1.2em;
    font-weight: bold;
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
}

.fw_debug .fw_debug_bold
{
    font-weight: bold;
}

.fw_debug .fw_debug_u
{
    cursor:pointer;
    padding:0 3px;
    text-decoration: underline;
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
    display: none;
    position: absolute;
    border: 1px solid #999;
    background: white;
    white-space: nowrap;
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
    
    if (myArea.style['display'] === 'none') {
        myArea.style['display'] = 'block';
        otherArea.style['display'] = 'none';
    } else {
        myArea.style['display'] = 'none';
    }
}
        
/**
 * カウンターの表示
 * @param {integer} counterNum SQLの実行順番
 * @param {boolean} openFlag カウンターを表示する場合TRUE
 */
function fwDebugNo(counterNum, openFlag)
{
    var tagId = 'fw_debug_no' + counterNum;
    document.getElementById(tagId).style['display'] = 
        openFlag ? 'inline' : 'none';
}
</script>
<!-- END DEBUG -->
