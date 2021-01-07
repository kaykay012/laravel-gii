@extends('admin.layouts.app')
@push('styles')
<style></style>
@endpush
@push('scripts')
<script></script>
@endpush
@section('content')
<div class="st-h15"></div>
<form class="layui-form st-form-search" lay-filter="ST-FORM-SEARCH" id="ST-FORM-SEARCH">
    <div class="layui-form-item">DummySearchInput
        <div class="layui-inline">
            <label class="layui-form-label">{{$model->getAttributeLabel('created_at')}}</label>
            <div class="layui-input-inline">
                <input type="text" name="created_at_begin" id="date" placeholder="年-月-日" autocomplete="off" class="layui-input">
            </div>
            <div class="layui-input-inline">
                <input type="text" name="created_at_end" id="date2" placeholder="年-月-日" autocomplete="off" class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-xs st-search-button">开始搜索</a>
        </div>
    </div>
</form>
<table class="layui-hide" id="ST-TABLE-LIST" lay-filter="ST-TABLE-LIST" lay-size="sm"></table>
<script type="text/html" id="ST-TOOL-BAR">
    <div class="layui-btn-container st-tool-bar">
        <a class="layui-btn layui-btn-xs" onclick="Util.createFormWindow('/DummyPathNameLcfirstTitleCase/create', this.innerText);">添加</a>
        <a class="layui-btn layui-btn-xs" lay-event="batchDelete" data-href="/DummyPathNameLcfirstTitleCase/destroy">删除选中</a>
    </div>
</script>
<script type="text/html" id="ST-OP-BUTTON">
    @verbatim
    <a class="layui-btn layui-btn-xs" onclick="Util.createFormWindow('/DummyPathNameLcfirstTitleCase/update?id={{d.id}}', this.innerText);">更新</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" onclick="Util.destroy('/DummyPathNameLcfirstTitleCase/destroy?id={{d.id}}');">删除</a>
    @endverbatim
</script>
@endsection
@push('scripts_bottom')        
<script>
!function () {
    var tableId = 'ST-TABLE-LIST';//容器唯一 id
    var searchFormId = 'ST-FORM-SEARCH';//查询搜索表单id
    //日期
    layui.laydate.render({
        elem: '#date'
    });
    layui.laydate.render({
        elem: '#date2'
    });
    //表格字段
    var cols = [
                {type: 'checkbox', fixed: 'left'}
                , {field: 'id', title: 'id', width: 80, fixed: 'left', unresize: true, totalRowText: '合计', sort: true}DummyList
                , {fixed: 'right', title: '操作', toolbar: '#ST-OP-BUTTON', width: 250}
            ];
    var tableConfig = {
        url: window.location.pathname
        ,cols: [cols]
    };
    var tableIns = layui.table.render(Object.assign(Util.tableConfig,tableConfig));
    //条件搜索
    layui.$('#' + searchFormId + ' .st-search-button').on('click', function () {
        var data = layui.form.val(searchFormId);
        tableIns.reload({
            where: data
        });
    });
    //监听排序事件 
    layui.table.on('sort(' + tableId + ')', function (obj) { //注：sort 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
        tableIns.reload({
            initSort: obj //记录初始排序，如果不设的话，将无法标记表头的排序状态。
            , where: {//请求参数（注意：这里面的参数可任意定义，并非下面固定的格式）
                field: obj.field //排序字段
                , order: obj.type //排序方式
            }
            , page: {
                curr: 1 //重新从第 1 页开始
            }
        });
        layer.msg('服务端排序。order by ' + obj.field + ' ' + obj.type);
    });
    //工具栏事件
    layui.table.on('toolbar(' + tableId + ')', function (obj) {
        var checkStatus = layui.table.checkStatus(obj.config.id);
        switch (obj.event) {
            case 'batchDelete':
                Util.batchDelete($(this).attr('data-href'),checkStatus.data);
                break;
            case 'ST-EXPORT-EXCEL':
                var data = layui.form.val(searchFormId);
                Util.exportFile(tableIns, data);
                break;
        }
        return;
    });
}();
</script>
@endpush