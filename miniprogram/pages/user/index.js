// miniprogram/pages/user/index.js
const app = getApp();
const { request } = require('../../utils/request.js');

Page({
  data: {
    userInfo: null,
    menuItems: [
      [
        { id: 'info', icon: '&#xe60d;', title: '个人信息', arrow: true },
        { id: 'password', icon: '&#xe60e;', title: '修改密码', arrow: true }
      ],
      [
        { id: 'study', icon: '&#xe60f;', title: '学习记录', arrow: true },
        { id: 'exam', icon: '&#xe603;', title: '考试记录', arrow: true }
      ],
      [
        { id: 'about', icon: '&#xe610;', title: '关于我们', arrow: true },
        { id: 'help', icon: '&#xe611;', title: '帮助与反馈', arrow: true }
      ]
    ]
  },

  onShow() {
    this.loadUserInfo();
  },

  loadUserInfo() {
    request({ url: '/user/info' }).then(data => {
      this.setData({ userInfo: data });
      app.globalData.userInfo = data;
    }).catch(() => {});
  },

  onItemTap(e) {
    const id = e.currentTarget.dataset.id;
    if (id === 'info') {
      wx.navigateTo({ url: '/pages/user/info' });
    } else if (id === 'password') {
      wx.navigateTo({ url: '/pages/user/password' });
    } else if (id === 'study') {
      wx.navigateTo({ url: '/pages/user/study' });
    } else if (id === 'exam') {
      wx.switchTab({ url: '/pages/exam/index' });
    }
  },

  logout() {
    wx.showModal({
      title: '提示',
      content: '确定要退出登录吗？',
      success: (res) => {
        if (res.confirm) {
          wx.removeStorageSync('token');
          wx.reLaunch({ url: '/pages/login/index' });
        }
      }
    });
  }
});
