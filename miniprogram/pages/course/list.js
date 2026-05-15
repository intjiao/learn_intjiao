// miniprogram/pages/course/list.js
const { request, formatDate } = require('../../utils/request.js');

Page({
  data: {
    courses: [],
    page: 1,
    limit: 10,
    hasMore: true,
    loading: false
  },

  onLoad() {
    this.loadCourses();
  },

  onPullDownRefresh() {
    this.setData({ page: 1, courses: [], hasMore: true });
    this.loadCourses();
    wx.stopPullDownRefresh();
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.setData({ page: this.data.page + 1 });
      this.loadCourses();
    }
  },

  loadCourses() {
    this.setData({ loading: true });
    request({
      url: '/course/list',
      data: { page: this.data.page, limit: this.data.limit }
    }).then(data => {
      const list = Array.isArray(data) ? data : [];
      this.setData({
        courses: this.data.page === 1 ? list : this.data.courses.concat(list),
        hasMore: list.length >= this.data.limit,
        loading: false
      });
    }).catch(() => {
      this.setData({ loading: false });
    });
  },

  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/course/detail?id=${id}` });
  }
});
