// miniprogram/pages/index/index.js
const app = getApp();
const { request } = require('../../utils/request.js');

Page({
  data: {
    userInfo: null,
    recentExams: [],
    stats: {
      courseCount: 0,
      examCount: 0,
      certCount: 0,
      progress: 0
    }
  },

  onLoad() {
    if (!wx.getStorageSync('token')) {
      wx.reLaunch({ url: '/pages/login/index' });
    }
  },

  onShow() {
    this.loadUserInfo();
    this.loadStats();
  },

  onPullDownRefresh() {
    this.onShow();
    wx.stopPullDownRefresh();
  },

  loadUserInfo() {
    request({ url: '/user/info' }).then(data => {
      this.setData({ userInfo: data });
      app.globalData.userInfo = data;
    }).catch(() => {});
  },

  loadStats() {
    request({ url: '/user/info' }).then(data => {
      this.setData({
        stats: {
          courseCount: data.course_count || 0,
          examCount: data.exam_count || 0,
          certCount: data.cert_count || 0,
          progress: data.learning_progress || 0
        }
      });
    }).catch(() => {});
  },

  goToCourse() {
    wx.switchTab({ url: '/pages/course/list' });
  },

  goToExam() {
    wx.switchTab({ url: '/pages/exam/index' });
  },

  goToCertificate() {
    wx.navigateTo({ url: '/pages/certificate/index' });
  }
});
