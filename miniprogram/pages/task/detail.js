// miniprogram/pages/task/detail.js
const { request } = require('../../utils/request.js');

Page({
  data: {
    taskId: 0,
    task: null,
    loading: false
  },

  onLoad(e) {
    this.setData({ taskId: Number(e.id || 0) });
    this.loadDetail();
  },

  onPullDownRefresh() {
    this.loadDetail();
    wx.stopPullDownRefresh();
  },

  normalizeTask(task) {
    const source = task || {};
    const stages = Array.isArray(source.stages) ? source.stages.map(item => {
      const typeTextMap = { study: '学习', exam: '考试', practice: '练习', sign: '签到' };
      return {
        ...item,
        type_text: typeTextMap[item.type] || item.type || '阶段'
      };
    }) : [];

    return {
      ...source,
      stages,
      stage_count: stages.length,
      status_text: source.is_complete ? '已完成' : source.is_enrolled ? '进行中' : '未报名'
    };
  },

  loadDetail() {
    if (!this.data.taskId) {
      wx.showToast({ title: '任务参数无效', icon: 'none' });
      return;
    }

    this.setData({ loading: true });
    request({ url: `/task/${this.data.taskId}` }).then(data => {
      this.setData({
        task: this.normalizeTask(data),
        loading: false
      });
    }).catch(() => {
      this.setData({ loading: false });
      wx.showToast({ title: '加载失败', icon: 'none' });
    });
  },

  enrollTask() {
    if (!this.data.taskId) return;

    request({ url: `/task/${this.data.taskId}/enroll`, method: 'POST' }).then(data => {
      const message = data && data.msg ? data.msg : '报名成功';
      wx.showToast({ title: message, icon: 'success' });
      this.loadDetail();
    }).catch(() => {
      wx.showToast({ title: '报名失败', icon: 'none' });
    });
  }
});