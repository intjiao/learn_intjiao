// miniprogram/pages/certificate/detail.js
const { request } = require('../../utils/request.js');

Page({
  data: {
    certificateId: 0,
    certificate: null,
    verifyResult: null
  },

  onLoad(e) {
    this.setData({ certificateId: Number(e.id || 0) });
    this.loadDetail();
  },

  loadDetail() {
    if (!this.data.certificateId) {
      wx.showToast({ title: '证书参数无效', icon: 'none' });
      return;
    }

    request({ url: `/certificate/${this.data.certificateId}` }).then(data => {
      this.setData({ certificate: this.normalizeCertificate(data) });
    }).catch(() => {
      wx.showToast({ title: '加载失败', icon: 'none' });
    });
  },

  normalizeCertificate(cert) {
    const result = { ...(cert || {}) };
    const today = new Date().toISOString().slice(0, 10);
    if (result.status !== 2 && result.expire_date && result.expire_date < today) {
      result.display_status = 3;
    } else {
      result.display_status = Number(result.status || 1);
    }
    return result;
  },

  verifyCertificate() {
    const cert = this.data.certificate || {};
    const certNo = cert.cert_no;
    if (!certNo) return;

    request({ url: `/certificate/verify/${certNo}` }).then(data => {
      const result = data || null;
      this.setData({ verifyResult: result });
      wx.showToast({ title: result && result.valid ? '验证通过' : '校验完成', icon: 'none' });
    }).catch(() => {
      wx.showToast({ title: '验证失败', icon: 'none' });
    });
  },

  copyFilePath() {
    const cert = this.data.certificate || {};
    const filePath = cert.file_path;
    if (!filePath) {
      wx.showToast({ title: '暂无证书文件', icon: 'none' });
      return;
    }
    wx.setClipboardData({ data: filePath });
  }
});