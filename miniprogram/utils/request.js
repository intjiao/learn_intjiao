const app = getApp();

const request = (options) => {
  return new Promise((resolve, reject) => {
    const token = wx.getStorageSync('token');
    wx.request({
      url: app.globalData.baseUrl + options.url,
      method: options.method || 'GET',
      data: options.data || {},
      header: {
        'Content-Type': 'application/json',
        'Authorization': token ? `Bearer ${token}` : '',
        ...options.header
      },
      success: (res) => {
        if (res.statusCode === 200) {
          if (res.data.code === 200) {
            resolve(res.data.data);
          } else if (res.data.code === 401) {
            wx.removeStorageSync('token');
            wx.reLaunch({ url: '/pages/login/index' });
            reject(res.data);
          } else {
            wx.showToast({ title: res.data.msg || '请求失败', icon: 'none' });
            reject(res.data);
          }
        } else {
          wx.showToast({ title: '网络错误', icon: 'none' });
          reject(res);
        }
      },
      fail: (err) => {
        wx.showToast({ title: '网络错误', icon: 'none' });
        reject(err);
      }
    });
  });
};

const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const formatDuration = (seconds) => {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${m}:${String(s).padStart(2, '0')}`;
};

module.exports = { request, formatDate, formatDuration };
