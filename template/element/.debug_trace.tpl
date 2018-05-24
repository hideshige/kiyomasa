<div class="fw_debug_trace">
<!-- BEGIN TRACE -->
    追跡 {id}:
    <table>
        <tr>
            <th>No.</th>
            <th>ファイルと行番号</th>
            <th>名前空間とクラス</th>
            <th>関数</th>
            <th>引数</th>
        </tr>
        <!-- BEGIN TABLE_DATA -->
        <tr>
            <td>{trace_num}</td>
            <td>{file_name} <strong>{line}</strong></td>
            <td>{class_name}</td>
            <td>{function_name}()</td>
            <td><textarea class="fw_debug_trace_args">{args}</textarea></td>
        </tr>
        <!-- END TABLE_DATA -->
    </table>
<!-- END TRACE -->
</div>
