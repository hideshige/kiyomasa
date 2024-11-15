<!-- BEGIN DEBUG_INCLUDE -->
<div id="fw_debug_area_{disp_type}_{navi_id}" class="fw_debug_area" style="display: {debug_disp};">
    <div class="fw_debug_{disp_type}">
        <p class="fw_debug_logo">YOURSITE</p>
        <div class="fw_debug_header">
            <span class="fw_debug_exit_button" onclick="FwDebugClass.fwDebug('{disp_type}_{navi_id}', true);">X</span>
            {namespace}
        </div>
        <h3>{request_url}</h3>
        <h4><span title="dump()の結果がここに出ます">【DEBUG】</span></h4>
        <pre class="fw_debug_dump">{dump}</pre>
        <h4><span title="trace()の結果がここに出ます">【TRACE】</span></h4>
        {trace}
        <h4>
            【DB MASTER】
            <input type="button" onclick="FwDebugClass.fwDebugCounter();" value="CHANGE" />
            <span name="fw_debug_mode" class="fw_debug_mode">Developper Mode</span>
        </h4>
        <p>{db_master}</p>
        <h4>【DB SLAVE】</h4>
        <p>{db_slave}</p>
        <h4>【MEMCACHED】</h4>
        <p>{htbr:memcached}</p>
        <h4>【JSON】</h4>
        <pre>{json}</pre>
        <h4>【POST】</h4>
        <pre>{post}</pre>
        <h4>【GET】</h4>
        <pre>{get}</pre>
        <h4>【FILES】</h4>
        <pre>{files}</pre>
        <h4>【SESSION】</h4>
        <pre>{session}</pre>
        <h4>【COOKIE】</h4>
        <pre>{cookie}</pre>
        <h4>【URL】</h4>
        <pre>{url}</pre>
        <h4>【OS】</h4>
        <p>
            {os}　{web_server}<br />
            PHP　{php_ver}<br />
            {user_agent}<br />
            メモリ　{memory1} KB (固定分)
            + {memory2} KB (追加分) = {memory3} KB<br />
            IPアドレス　{ip}<br />
            タイムスタンプ　{timestamp} ({time})
        </p>
    </div>
</div>
<!-- END DEBUG_INCLUDE -->
