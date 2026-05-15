// 部署时把 RELEASE_BASE_URL 改成实际后端地址；本地开发可走 DEV_BASE_URL
const DEV_BASE_URL = 'http://127.0.0.1:8080/api';
const RELEASE_BASE_URL = 'https://your-domain.example.com/api';

function isDevtools() {
  try {
    return wx.getSystemInfoSync().platform === 'devtools';
  } catch (error) {
    return false;
  }
}

function defaultBaseUrl() {
  return isDevtools() ? DEV_BASE_URL : RELEASE_BASE_URL;
}

App({
  globalData: {
    userInfo: null,
    token: null,
    baseUrl: wx.getStorageSync('baseUrl') || defaultBaseUrl(),
  },
  onLaunch() {
    const token = wx.getStorageSync('token');
    if (token) {
      this.globalData.token = token;
    }
    if (!this.globalData.baseUrl) {
      this.globalData.baseUrl = defaultBaseUrl();
    }
  },
  onShow() {},
  onHide() {},
});
