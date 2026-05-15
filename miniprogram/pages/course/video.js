// miniprogram/pages/course/video.js
const { request } = require('../../utils/request.js');

Page({
  data: {
    resourceId: 0,
    courseId: 0,
    chapterId: 0,
    title: '',
    url: '',
    type: 'video',
    duration: 0,
    recorded: false,
    progress: 0
  },

  onLoad(e) {
    this.setData({
      resourceId: Number(e.id || 0),
      courseId: Number(e.courseId || 0),
      chapterId: Number(e.chapterId || 0),
      title: decodeURIComponent(e.title || ''),
      url: decodeURIComponent(e.url || ''),
      type: e.type || 'video',
      duration: Number(e.duration || 0)
    });
    this.recordStudy();
  },

  onUnload() {
    this.recordStudy();
  },

  recordStudy() {
    if (this.data.recorded || !this.data.resourceId) {
      return;
    }

    request({
      url: '/study/record',
      method: 'POST',
      data: {
        course_id: this.data.courseId,
        chapter_id: this.data.chapterId,
        resource_id: this.data.resourceId,
        watch_duration: this.data.duration
      }
    }).then(data => {
      this.setData({
        recorded: true,
        progress: data.progress || 0
      });
    }).catch(() => {});
  },

  previewImage() {
    if (!this.data.url) return;
    wx.previewImage({ urls: [this.data.url], current: this.data.url });
  },

  copyLink() {
    if (!this.data.url) {
      wx.showToast({ title: '暂无资源地址', icon: 'none' });
      return;
    }
    wx.setClipboardData({ data: this.data.url });
  }
});