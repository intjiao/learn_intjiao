/**
 * API 工具类 - 在线培训考试系统
 * 统一处理所有后端API请求
 */
var API_BASE = '';  // 当前域名下，API请求到 public/index.php

var Api = {
    /**
     * 发送请求
     */
    request: function(url, method, data) {
        var token = localStorage.getItem('api_token') || '';
        var headers = {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/x-www-form-urlencoded'
        };

        var query = [];
        for (var key in data) {
            if (data[key] !== null && data[key] !== undefined) {
                if (typeof data[key] === 'object') {
                    query.push(key + '=' + encodeURIComponent(JSON.stringify(data[key])));
                } else {
                    query.push(key + '=' + encodeURIComponent(data[key]));
                }
            }
        }

        var fullUrl = '/' + url;
        var body = query.join('&');

        var options = {
            method: method,
            headers: headers
        };

        if (method !== 'GET' && method !== 'HEAD') {
            options.body = body;
        } else if (body) {
            fullUrl += '?' + body;
        }

        return fetch(fullUrl, options)
            .then(function(response) {
                if (response.status === 401) {
                    localStorage.removeItem('api_token');
                    localStorage.removeItem('user_info');
                    if (location.href.indexOf('login.html') === -1) {
                        location.href = 'login.html';
                    }
                    return Promise.reject({ code: 401, msg: '请先登录' });
                }
                return response.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        return { code: 500, msg: '服务器异常', data: text };
                    }
                });
            });
    },

    /**
     * GET 请求
     */
    get: function(url, data) {
        return this.request(url, 'GET', data);
    },

    /**
     * POST 请求
     */
    post: function(url, data) {
        return this.request(url, 'POST', data);
    },

    /**
     * PUT 请求
     */
    put: function(url, data) {
        return this.request(url, 'PUT', data);
    },

    // ==================== 认证相关 ====================

    /**
     * 用户登录
     */
    login: function(username, password) {
        return this.post('api/auth/login', { username: username, password: password });
    },

    /**
     * 用户注册
     */
    register: function(data) {
        return this.post('api/auth/register', data);
    },

    /**
     * 重置密码
     */
    resetPassword: function(phone, code, password) {
        return this.post('api/auth/resetPassword', { phone: phone, code: code, password: password });
    },

    /**
     * 退出登录
     */
    logout: function() {
        localStorage.removeItem('api_token');
        localStorage.removeItem('user_info');
    },

    /**
     * 检查登录状态
     */
    checkLogin: function() {
        var token = localStorage.getItem('api_token');
        var userInfo = localStorage.getItem('user_info');
        if (!token || !userInfo) {
            location.href = 'login.html';
            return false;
        }
        return true;
    },

    /**
     * 获取当前用户信息
     */
    getUserInfo: function() {
        return this.get('api/user/info');
    },

    /**
     * 更新用户信息
     */
    updateUserInfo: function(data) {
        return this.put('api/user/info', data);
    },

    /**
     * 修改密码
     */
    updatePassword: function(oldPassword, newPassword) {
        return this.put('api/user/password', {
            old_password: oldPassword,
            new_password: newPassword
        });
    },

    // ==================== 刷题练习 ====================

    getPracticeStats: function() {
        return this.get('api/practice/stats');
    },

    getPracticeCategories: function() {
        return this.get('api/practice/categories');
    },

    getPracticeQuestions: function(params) {
        return this.get('api/practice/questions', params || {});
    },

    submitPractice: function(answers) {
        return this.post('api/practice/submit', {
            answers: JSON.stringify(answers || {})
        });
    },

    getPracticeWrongBook: function(page, limit, keyword) {
        var data = { page: page || 1, limit: limit || 20 };
        if (keyword) data.keyword = keyword;
        return this.get('api/practice/wrong-book', data);
    },

    // ==================== 课程相关 ====================

    /**
     * 获取课程列表
     */
    getCourseList: function(page, limit, categoryId) {
        var data = { page: page || 1, limit: limit || 10 };
        if (categoryId) data.category_id = categoryId;
        return this.get('api/course/list', data);
    },

    /**
     * 获取课程详情
     */
    getCourseDetail: function(id) {
        return this.get('api/course/' + id);
    },

    /**
     * 获取课程章节
     */
    getCourseChapters: function(id) {
        return this.get('api/course/' + id + '/chapters');
    },

    /**
     * 课程报名
     */
    enrollCourse: function(id) {
        return this.post('api/course/' + id + '/enroll');
    },

    /**
     * 保存学习记录
     */
    saveStudyRecord: function(data) {
        return this.post('api/study/record', data || {});
    },

    // ==================== 考试相关 ====================

    /**
     * 获取可考试试卷列表
     */
    getExamPaperList: function(page, limit, keyword) {
        var data = { page: page || 1, limit: limit || 10 };
        if (keyword) data.keyword = keyword;
        return this.get('api/exam/papers', data);
    },

    getExamList: function(page, limit, type) {
        var data = { page: page || 1, limit: limit || 10 };
        if (type) data.type = type;
        return this.get('api/exam/list', data);
    },

    /**
     * 获取考试详情
     */
    getExamDetail: function(id) {
        return this.get('api/exam/' + id);
    },

    /**
     * 开始考试
     */
    startExam: function(id) {
        return this.post('api/exam/' + id + '/start');
    },

    /**
     * 提交考试
     */
    submitExam: function(id, recordId, answers) {
        return this.post('api/exam/' + id + '/submit', {
            record_id: recordId,
            answers: JSON.stringify(answers)
        });
    },

    /**
     * 获取考试记录详情
     */
    getExamRecord: function(id) {
        return this.get('api/exam/record/' + id);
    },

    // ==================== 培训任务 ====================

    /**
     * 获取培训任务列表
     */
    getTaskList: function(page, limit) {
        return this.get('api/task/list', { page: page || 1, limit: limit || 10 });
    },

    /**
     * 获取任务详情
     */
    getTaskDetail: function(id) {
        return this.get('api/task/' + id);
    },

    // ==================== 证书相关 ====================

    /**
     * 获取证书列表
     */
    getCertificateList: function(page, limit) {
        return this.get('api/certificate/list', { page: page || 1, limit: limit || 10 });
    },

    /**
     * 获取证书详情
     */
    getCertificateDetail: function(id) {
        return this.get('api/certificate/' + id);
    },

    /**
     * 验证证书
     */
    verifyCertificate: function(certNo) {
        return this.get('api/certificate/verify/' + certNo);
    },

    // ==================== 知识库 ====================

    /**
     * 获取知识库列表
     */
    getKnowledgeList: function(page, limit) {
        return this.get('api/knowledge/list', { page: page || 1, limit: limit || 10 });
    },

    /**
     * 获取知识详情
     */
    getKnowledgeDetail: function(id) {
        return this.get('api/knowledge/' + id);
    },

    // ==================== 辅助方法 ====================

    /**
     * 提示消息
     */
    msg: function(msg, icon) {
        if (typeof layer !== 'undefined') {
            var icons = { success: 1, error: 2, warning: 0, info: 0 };
            layer.msg(msg, { icon: icons[icon] || 1 });
        } else {
            alert(msg);
        }
    },

    /**
     * 加载层
     */
    loading: function() {
        if (typeof layer !== 'undefined') {
            return layer.load(2);
        }
        return 0;
    },

    /**
     * 关闭加载层
     */
    closeLoading: function(index) {
        if (typeof layer !== 'undefined' && index) {
            layer.close(index);
        }
    },

    /**
     * 确认框
     */
    confirm: function(msg, callback) {
        if (typeof layer !== 'undefined') {
            layer.confirm(msg, function(idx) {
                layer.close(idx);
                callback();
            });
        } else if (confirm(msg)) {
            callback();
        }
    }
};
