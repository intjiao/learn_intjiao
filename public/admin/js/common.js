/**
 * 管理后台公共函数库
 * 所有函数通过 parent 调用父级窗口的组件和方法
 */
(function () {
    var layer = parent._adminLayer;
    var table = parent._adminTable;
    var form = parent._adminForm;
    var upload = parent._adminUpload;
    var laydate = parent._adminLaydate;
    var $ = parent._admin$;

    // ==================== 消息提示 ====================

    window.showSuccess = function (msg, cb) {
        if (layer) layer.msg(msg, { icon: 1, shade: 0.3, time: 1500 }, cb);
    };

    window.showError = function (msg) {
        if (layer) layer.msg(msg, { icon: 2, shade: 0.3, time: 2000 });
    };

    window.showMsg = function (msg, icon, cb) {
        if (layer) layer.msg(msg, { icon: icon || 1, shade: 0.3, time: 1500 }, cb);
    };

    // ==================== 弹窗函数 ====================

    window.openForm = function (title, url, width, height) {
        width = width || 1040;
        height = height || 760;
        if (!layer) return;
        return layer.open({
            type: 2,
            title: title,
            area: [width + 'px', height + 'px'],
            fix: false,
            maxmin: true,
            resize: true,
            shadeClose: false,
            shade: 0.42,
            skin: 'admin-layer-shell',
            content: url,
            zIndex: layer.zIndex
        });
    };

    // ==================== AJAX 请求 ====================

    window.ajaxGet = function (url, data, callback) {
        if (parent.ajaxGet) {
            parent.ajaxGet(url, data, callback);
        } else {
            $.get(url, data, callback, 'json');
        }
    };

    window.ajaxPost = function (url, data, callback) {
        if (parent.ajaxPost) {
            parent.ajaxPost(url, data, callback);
        } else {
            $.post(url, data, callback, 'json');
        }
    };

    window.ajaxPut = function (url, data, callback) {
        if (parent.ajaxPut) {
            parent.ajaxPut(url, data, callback);
        } else {
            $.ajax({ url: url, type: 'PUT', data: data, dataType: 'json' });
        }
    };

    window.ajaxDelete = function (url, data, callback) {
        if (parent.ajaxDelete) {
            parent.ajaxDelete(url, data, callback);
        } else {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: data,
                dataType: 'json',
                beforeSend: function (xhr) {
                    var token = localStorage.getItem('admin_token');
                    if (token) xhr.setRequestHeader('Admin-Token', token);
                }
            });
        }
    };

    // ==================== 删除操作 ====================

    window.ajaxDeleteItem = function (url, id, tableId) {
        if (!url) return;
        var msg = id ? '确定删除ID为【' + id + '】的数据吗？' : '确定删除选中的数据吗？';
        layer.confirm(msg, function (idx) {
            $.ajax({
                url: url,
                type: 'DELETE',
                dataType: 'json',
                beforeSend: function (xhr) {
                    var token = localStorage.getItem('admin_token');
                    if (token) xhr.setRequestHeader('Admin-Token', token);
                },
                success: function (res) {
                    if (res.code === 200) {
                        showSuccess('删除成功');
                        if (tableId && table) {
                            table.reload(tableId);
                        }
                    } else {
                        showError(res.msg || '删除失败');
                    }
                },
                error: function () {
                    showError('请求失败，请重试');
                }
            });
            layer.close(idx);
        });
    };

    // ==================== 工具函数 ====================

    window.formatDate = function (date, fmt) {
        if (!date) return '';
        fmt = fmt || 'YYYY-MM-DD HH:mm:ss';
        var d = new Date(date);
        return fmt.replace('YYYY', d.getFullYear())
            .replace('MM', ('0' + (d.getMonth() + 1)).slice(-2))
            .replace('DD', ('0' + d.getDate()).slice(-2))
            .replace('HH', ('0' + d.getHours()).slice(-2))
            .replace('mm', ('0' + d.getMinutes()).slice(-2))
            .replace('ss', ('0' + d.getSeconds()).slice(-2));
    };

    window.getQueryParam = function (name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return decodeURIComponent(r[2]);
        return null;
    };

    // 将组件暴露到全局，方便子页面直接使用
    window._layer = layer;
    window._table = table;
    window._form = form;
    window._upload = upload;
    window._laydate = laydate;
    window._$ = $;

})();
