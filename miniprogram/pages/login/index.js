// miniprogram/pages/login/index.js
const app = getApp();
const { request } = require('../../utils/request.js');

Page({
  data: {
    username: '',
    password: '',
    loading: false
  },

  onUsernameInput(e) {
    this.setData({ username: e.detail.value });
  },

  onPasswordInput(e) {
    this.setData({ password: e.detail.value });
  },

  login() {
    const { username, password } = this.data;
    if (!username) {
      wx.showToast({ title: '请输入用户名', icon: 'none' });
      return;
    }
    if (!password) {
      wx.showToast({ title: '请输入密码', icon: 'none' });
      return;
    }

    this.setData({ loading: true });
    request({
      url: '/auth/login',
      method: 'POST',
      data: { username, password }
    }).then(data => {
      this.setData({ loading: false });
      wx.setStorageSync('token', data.token);
      app.globalData.token = data.token;
      wx.showToast({ title: '登录成功', icon: 'success' });
      setTimeout(() => {
        wx.switchTab({ url: '/pages/index/index' });
      }, 1500);
    }).catch(() => {
      this.setData({ loading: false });
    });
  },

  goRegister() {
    wx.navigateTo({ url: '/pages/login/register' });
  }
});
