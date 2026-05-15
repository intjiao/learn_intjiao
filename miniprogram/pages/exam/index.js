// miniprogram/pages/exam/index.js
const { request } = require('../../utils/request.js');

Page({
  data: {
    papers: [],
    page: 1,
    limit: 10,
    hasMore: true,
    loading: false,
    activeTab: 0,
    tabs: ['全部考试', '可参加', '已完成']
  },

  onLoad() {
    this.loadExams();
  },

  onPullDownRefresh() {
    this.setData({ page: 1, papers: [], hasMore: true });
    this.loadExams();
    wx.stopPullDownRefresh();
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.setData({ page: this.data.page + 1 });
      this.loadExams();
    }
  },

  switchTab(e) {
    const index = Number(e.currentTarget.dataset.index);
    this.setData({ activeTab: index, page: 1, papers: [], hasMore: true });
    this.loadExams();
  },

  filterPapers(list) {
    if (this.data.activeTab === 1) {
      return list.filter(item => !item.is_finished);
    }
    if (this.data.activeTab === 2) {
      return list.filter(item => item.is_finished);
    }
    return list;
  },

  loadExams() {
    this.setData({ loading: true });
    request({
      url: '/exam/papers',
      data: { page: this.data.page, limit: this.data.limit }
    }).then(data => {
      const source = Array.isArray(data) ? data : [];
      const list = this.filterPapers(source);
      this.setData({
        papers: this.data.page === 1 ? list : this.data.papers.concat(list),
        hasMore: source.length >= this.data.limit,
        loading: false
      });
    }).catch(() => {
      this.setData({ loading: false });
    });
  },

  startExam(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/exam/answer?id=${id}` });
  },

  viewResult(e) {
    const recordId = e.currentTarget.dataset.recordId;
    if (!recordId) return;
    wx.navigateTo({ url: `/pages/exam/result?recordId=${recordId}` });
  }
});
