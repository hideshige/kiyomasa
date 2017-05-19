<!-- BEGIN DEBUG -->
<style>
#fw_debug_guide
{
    position: fixed;
    top: 0;
    right: 0;
    z-index: 10001;
    background: blue;
    color: white;
    opacity: 0.7;
}

#fw_debug_area
{
    position: absolute;
    padding: 25px;
    font-size: 0.8em;
    top: 0;
    left: 0;
    z-index: 10000;
}

#fw_debug
{
    border: 1px solid #999;
    box-shadow: 5px 5px 10px;
    background: white;
    color: #333;
    padding: 10px;
    position:relative;
}

#fw_debug_exit_button
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

#fw_debug h3
{
    color: red;
    font-size: 1.2em;
    font-weight: bold;
}

#fw_debug h4
{
    color: blue;
}

#fw_debug p,
#fw_debug pre
{
    margin-bottom: 12px;
    margin-left: 20px;
}

#fw_debug_dump
{
    padding: 7px;
    background: #ff9;
}

#fw_debug .fw_debug_bold
{
    font-weight: bold;
}

#fw_debug .fw_debug_u
{
    cursor:pointer;
    padding:0 3px;
    text-decoration: underline;
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
    display: none;
    position: absolute;
    border: 1px solid #999;
    background: white;
}
</style>

<div id="fw_debug_include">
<!-- ELEMENT .debug_include.tpl -->
</div>
<script>
// デバッグの表示
function fwDebug()
{
    document.getElementById('fw_debug_area').style['display'] = 
        document.getElementById('fw_debug_area').style['display']
        === 'none' ? 'block' : 'none';
}
// カウンターの表示
function fwDebugNo(counterNum, openFlag)
{
    var tagId = 'fw_debug_no' + counterNum;
    document.getElementById(tagId).style['display'] = 
        openFlag ? 'inline' : 'none';
}
</script>
<!-- END DEBUG -->
