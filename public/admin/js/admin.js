(function () {
    var $;
    layui.use(['element', 'layer', 'form'], function () {
        var element = layui.element;
        var layer = layui.layer;
        var form = layui.form;
        $ = layui.$;

        $.ajaxSetup({
            headers: {},
            beforeSend: function (xhr) {
            },
            error: function (xhr, status, error) {
                if (xhr.status === 401) {
                    layer.msg('请先登录', { icon: 2 }, function () {
                        location.href = 'login.html';
                    });
                } else {
                    layer.msg('请求失败: ' + error, { icon: 2 });
                }
            }
        });

        $('.nav-tab').on('click', function () {
            var url = $(this).data('url');
            var id = $(this).data('id');
            var title = $(this).find('span').text() || $(this).text();

            if (!url) return;

            var exists = false;
            $('.layui-tab-title li').each(function () {
                if ($(this).attr('lay-id') === id) {
                    exists = true;
                    return false;
                }
            });

            if (exists) {
                element.tabChange('tab', id);
            } else {
                element.tabAdd('tab', {
                    title: title,
                    content: '<div id="content-' + id + '" class="layui-tab-item"></div>',
                    id: id
                });
                element.tabChange('tab', id);
                loadTabContent(id, url);
            }
        });

        function loadTabContent(id, url) {
            $('#content-' + id).html('<div class="loading"><i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" style="font-size:30px;color:#1E9FFF;display:block;text-align:center;margin-top:50px;"></i></div>');
            $.get(url, function (res) {
                $('#content-' + id).html(res);
                if (typeof window['init_' + id.replace(/-/g, '_')] === 'function') {
                    window['init_' + id.replace(/-/g, '_')]();
                }
            }).fail(function () {
                $('#content-' + id).html('<div class="empty-state"><i class="layui-icon layui-icon-refresh"></i><p>加载失败，请重试</p></div>');
            });
        }

        if (location.pathname.indexOf('index.html') > -1) {
            loadTabContent('dashboard', 'pages/dashboard.html');
        }

        $('#logout').on('click', function () {
            layer.confirm('确定要退出登录吗？', function (idx) {
                $.post('logout', function (res) {
                    location.href = 'login.html';
                });
                layer.close(idx);
            });
        });

        window.showMsg = function (msg, icon, callback) {
            icon = icon || 1;
            layer.msg(msg, { icon: icon, shade: 0.3, time: 1500 }, callback);
        };

        window.showSuccess = function (msg, callback) {
            showMsg(msg, 1, callback);
        };

        window.showError = function (msg, callback) {
            showMsg(msg, 2, callback);
        };

        window.confirmAction = function (msg, okFn) {
            layer.confirm(msg, { icon: 3, title: '提示' }, function (idx) {
                if (typeof okFn === 'function') okFn();
                layer.close(idx);
            });
        };

        window.openForm = function (title, url, width, height) {
            width = width || 1040;
            height = height || 760;
            layer.open({
                type: 2,
                title: title,
                area: [width + 'px', height + 'px'],
                fixed: false,
                maxmin: true,
                resize: true,
                shadeClose: false,
                shade: 0.42,
                skin: 'admin-layer-shell',
                content: url,
                end: function () {
                    if (typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    }
                }
            });
        };

        window.ajaxDelete = function (url, id, tableId) {
            confirmAction('确定要删除吗？', function () {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    success: function (res) {
                        if (res.code === 200) {
                            showSuccess('删除成功', function () {
                                if (tableId && typeof table.render === 'function') {
                                    table.reload(tableId);
                                }
                            });
                        } else {
                            showError(res.msg || '删除失败');
                        }
                    }
                });
            });
        };

        window.formatDate = function (date, format) {
            if (!date) return '';
            format = format || 'YYYY-MM-DD HH:mm:ss';
            var d = new Date(date);
            var year = d.getFullYear();
            var month = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);
            var hour = ('0' + d.getHours()).slice(-2);
            var minute = ('0' + d.getMinutes()).slice(-2);
            var second = ('0' + d.getSeconds()).slice(-2);
            return format.replace('YYYY', year).replace('MM', month).replace('DD', day)
                .replace('HH', hour).replace('mm', minute).replace('ss', second);
        };

        window.formatStatus = function (status, map) {
            map = map || { 1: '正常', 0: '禁用' };
            return map[status] || '未知';
        };

        window.questionTypeMap = { 1: '单选题', 2: '多选题', 3: '填空题', 4: '判断题', 5: '问答题' };
        window.paperTypeMap = { 1: '固定试卷', 2: '随机试卷' };
        window.paperStatusMap = { 0: '禁用', 1: '草稿', 2: '已发布' };
    });
})();
