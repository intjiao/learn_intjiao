// miniprogram/pages/course/detail.js
const { request, formatDate } = require('../../utils/request.js');

Page({
  data: {
    course: null,
    chapters: [],
    progress: 0
  },

  onLoad(e) {
    this.setData({ courseId: e.id });
    this.loadDetail();
  },

  onShow() {
    if (this.data.courseId) {
      this.loadDetail();
    }
  },

  loadDetail() {
    request({ url: `/course/${this.data.courseId}` }).then(data => {
      this.setData({
        course: data,
        chapters: data.chapters || [],
        progress: data.progress || 0
      });
    });
  },

  playVideo(e) {
    const resourceId = e.currentTarget.dataset.resourceId;
    const chapterId = e.currentTarget.dataset.chapterId;
    const url = e.currentTarget.dataset.url;
    const title = e.currentTarget.dataset.title;
    const type = e.currentTarget.dataset.type || 'video';
    const duration = e.currentTarget.dataset.duration || 0;

    if (!url) {
      wx.showToast({ title: '暂无资源地址', icon: 'none' });
      return;
    }

    wx.navigateTo({
      url: `/pages/course/video?id=${resourceId}&courseId=${this.data.courseId}&chapterId=${chapterId}&type=${type}&duration=${duration}&url=${encodeURIComponent(url)}&title=${encodeURIComponent(title)}`
    });
  },

  enrollCourse() {
    request({ url: `/course/${this.data.courseId}/enroll`, method: 'POST' }).then(() => {
      wx.showToast({ title: '报名成功', icon: 'success' });
      this.loadDetail();
    });
  }
});
