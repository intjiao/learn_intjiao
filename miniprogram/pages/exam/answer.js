// miniprogram/pages/exam/answer.js
const { request } = require('../../utils/request.js');

Page({
  data: {
    paperId: null,
    recordId: null,
    questions: [],
    currentIndex: 0,
    answers: {},
    timeLeft: 0,
    timer: null,
    submitting: false
  },

  onLoad(e) {
    this.setData({ paperId: parseInt(e.id) });
    this.startExam();
  },

  onUnload() {
    if (this.data.timer) {
      clearInterval(this.data.timer);
    }
  },

  startExam() {
    wx.showLoading({ title: '加载中...' });
    request({ url: `/exam/${this.data.paperId}/start`, method: 'POST' }).then(data => {
      wx.hideLoading();
      this.setData({
        recordId: data.record_id,
        questions: data.questions,
        timeLeft: data.remaining_time
      });
      this.startTimer();
    }).catch(() => {
      wx.hideLoading();
      wx.navigateBack();
    });
  },

  startTimer() {
    this.data.timer = setInterval(() => {
      let time = this.data.timeLeft - 1;
      if (time <= 0) {
        clearInterval(this.data.timer);
        this.submitExam();
        return;
      }
      this.setData({ timeLeft: time });
    }, 1000);
  },

  formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  },

  prevQuestion() {
    if (this.data.currentIndex > 0) {
      this.setData({ currentIndex: this.data.currentIndex - 1 });
    }
  },

  nextQuestion() {
    if (this.data.currentIndex < this.data.questions.length - 1) {
      this.setData({ currentIndex: this.data.currentIndex + 1 });
    }
  },

  goToQuestion(index) {
    this.setData({ currentIndex: index });
  },

  selectOption(e) {
    const questionId = e.currentTarget.dataset.questionId;
    const optionLabel = e.currentTarget.dataset.label;
    const type = e.currentTarget.dataset.type;

    let answers = this.data.answers;
    if (type === 2) {
      if (!answers[questionId]) answers[questionId] = [];
      const idx = answers[questionId].indexOf(optionLabel);
      if (idx >= 0) {
        answers[questionId].splice(idx, 1);
      } else {
        answers[questionId].push(optionLabel);
      }
      answers[questionId] = [...answers[questionId]];
    } else {
      answers[questionId] = optionLabel;
    }
    this.setData({ answers });
  },

  submitExam() {
    if (this.data.submitting) return;
    this.setData({ submitting: true });

    wx.showModal({
      title: '确认提交',
      content: '确定要提交试卷吗？提交后将无法修改。',
      success: (res) => {
        if (res.confirm) {
          this.doSubmit();
        } else {
          this.setData({ submitting: false });
        }
      }
    });
  },

  doSubmit() {
    wx.showLoading({ title: '提交中...' });
    if (this.data.timer) clearInterval(this.data.timer);

    request({
      url: `/exam/${this.data.paperId}/submit`,
      method: 'POST',
      data: {
        record_id: this.data.recordId,
        answers: this.data.answers
      }
    }).then(data => {
      wx.hideLoading();
      wx.redirectTo({
        url: `/pages/exam/result?recordId=${this.data.recordId}`
      });
    }).catch(() => {
      wx.hideLoading();
      this.setData({ submitting: false });
    });
  }
});
