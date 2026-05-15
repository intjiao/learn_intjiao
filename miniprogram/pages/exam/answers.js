// miniprogram/pages/exam/answers.js
const { request } = require('../../utils/request.js');

Page({
  data: {
    recordId: 0,
    record: null
  },

  onLoad(e) {
    this.setData({ recordId: Number(e.recordId || 0) });
    this.loadDetail();
  },

  onPullDownRefresh() {
    this.loadDetail();
    wx.stopPullDownRefresh();
  },

  loadDetail() {
    if (!this.data.recordId) {
      wx.showToast({ title: '记录参数无效', icon: 'none' });
      return;
    }

    request({ url: `/exam/record/${this.data.recordId}` }).then(data => {
      this.setData({ record: data });
    }).catch(() => {
      wx.showToast({ title: '加载失败', icon: 'none' });
    });
  },

  goBack() {
    wx.navigateBack();
  }
});