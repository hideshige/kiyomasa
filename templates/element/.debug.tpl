<div id="fw_debug_guide">
    {env}　{process}秒　<input type="button" onclick="fwDebug();" value="デバッグ" />
</div>

<div id="fw_debug_area" style="display:{debug_disp};">
    <div id="fw_debug">
        <div id="fw_debug_exit_button" onclick="fwDebug();">X</div>
        <h3>{request_url}</h3>
        <p>
            OS: {os} PHP ver: {php_ver}<br />
            メモリ使用量: {memory1} KB (固定分)
            + {memory2} KB (追加分) = {memory3} KB<br />
            IP: {ip}<br />
            タイムスタンプ: {timestamp} ({time})
        </p>

        <h4>【DUMP】</h4>
        <pre>{dump}</pre>
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
        <h4>【FILES】</h4>
        <pre>{files}</pre>
        <h4>【URL】</h4>
        <pre>{url}</pre>
    </div>
</div>