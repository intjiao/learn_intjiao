// miniprogram/pages/exam/result.js
const { request } = require('../../utils/request.js');

Page({
  data: {
    record: null
  },

  onLoad(e) {
    this.loadResult(e.recordId);
  },

  loadResult(recordId) {
    wx.showLoading({ title: '加载中...' });
    request({ url: `/exam/record/${recordId}` }).then(data => {
      wx.hideLoading();
      this.setData({ record: data });
    }).catch(() => {
      wx.hideLoading();
    });
  },

  viewAnswers() {
    wx.navigateTo({
      url: `/pages/exam/answers?recordId=${this.data.record.id}`
    });
  },

  goBack() {
    wx.navigateBack();
  }
});
