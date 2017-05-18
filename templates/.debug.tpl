<!-- BEGIN DEBUG -->
<style>
#fw_debug_button
{
    position: fixed;
    top: 0;
    left: 0;
    z-index: 10001;
    background: blue;
    color: white;
    cursor: pointer;
    opacity: 0.7;
}

#fw_debug_area
{
    position: absolute;
    padding: 20px;
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
</style>

<div id="fw_debug_button" onclick="fwDebug();">
    {env}　{process}秒
</div>

<div id="fw_debug_area" style="display:{debug_disp};">
    <div id="fw_debug">
        <h4>デバッグ</h4>
        <p>
            OS: {os} PHP ver: {php_ver}<br />
            メモリ使用量: {memory1} KB (固定分)
            + {memory2} KB (追加分) = {memory3} KB<br />
            IP: {ip}<br />
            実行時間: {process} 秒<br />
            タイムスタンプ: {timestamp} ({time})
        </p>
        
        <h4>【DB SLAVE】</h4>
        <p>{db_slave}</p>
        <h4>【DB MASTER】</h4>
        <p>{db_master}</p>
        <h4>【MEMCACHED】</h4>
        <p>{memcached}</p>
        <h4>【POST】</h4>
        <pre>{post}</pre>
        <h4>【GET】</h4>
        <pre>{get}</pre>
        <h4>【URL】</h4>
        <pre>{url}</pre>
        <h4>【FILES】</h4>
        <pre>{files}</pre>
        <h4>【DUMP】</h4>
        <pre>{dump}</pre>
    </div>
</div>
<script>
function fwDebug()
{
    document.getElementById('fw_debug_area').style['display'] = 
        document.getElementById('fw_debug_area').style['display']
        === 'none' ? 'block' : 'none';
}
</script>
<!-- END DEBUG -->
