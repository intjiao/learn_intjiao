// miniprogram/pages/certificate/index.js
const { request, formatDate } = require('../../utils/request.js');

Page({
  data: {
    certificates: [],
    page: 1,
    limit: 10,
    hasMore: true,
    loading: false
  },

  onLoad() {
    this.loadCertificates();
  },

  onPullDownRefresh() {
    this.setData({ page: 1, certificates: [], hasMore: true });
    this.loadCertificates();
    wx.stopPullDownRefresh();
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.setData({ page: this.data.page + 1 });
      this.loadCertificates();
    }
  },

  loadCertificates() {
    this.setData({ loading: true });
    request({
      url: '/certificate/list',
      data: { page: this.data.page, limit: this.data.limit }
    }).then(data => {
      const list = Array.isArray(data) ? data : [];
      this.setData({
        certificates: this.data.page === 1 ? list : this.data.certificates.concat(list),
        hasMore: list.length >= this.data.limit,
        loading: false
      });
    }).catch(() => {
      this.setData({ loading: false });
    });
  },

  viewDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/certificate/detail?id=${id}` });
  }
});
