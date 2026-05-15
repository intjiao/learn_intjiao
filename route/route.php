<?php
use think\facade\Route;

// ============================================================
// 公开接口 - 无需认证
// ============================================================
Route::post('admin/login', 'admin.LoginController/login');
Route::post('admin/logout', 'admin.LoginController/logout');
Route::post('upload/image', 'admin.Upload/image');
Route::post('upload/video', 'admin.Upload/video');
Route::post('upload/file', 'admin.Upload/file');

// ============================================================
// 小程序API - 公开接口
// ============================================================
Route::post('api/auth/login', 'api.AuthController/login');
Route::post('api/auth/register', 'api.AuthController/register');
Route::post('api/auth/resetPassword', 'api.AuthController/resetPassword');
Route::get('api/captcha', 'api.CaptchaController/index');

// ============================================================
// 小程序API - 需要认证
// ============================================================
Route::group('api', function () {
    // 用户
    Route::get('user/info', 'api.UserController/info');
    Route::put('user/info', 'api.UserController/updateInfo');
    Route::put('user/password', 'api.UserController/updatePassword');
    Route::post('user/avatar', 'api.UserController/updateAvatar');

    // 刷题练习
    Route::get('practice/stats', 'api.PracticeController/stats');
    Route::get('practice/categories', 'api.PracticeController/categories');
    Route::get('practice/questions', 'api.PracticeController/questions');
    Route::get('practice/wrong-book', 'api.PracticeController/wrongBook');
    Route::post('practice/submit', 'api.PracticeController/submit');

    // 课程
    Route::get('course/list', 'api.CourseController/list');
    Route::get('course/:id', 'api.CourseController/detail');
    Route::get('course/:id/chapters', 'api.CourseController/chapters');
    Route::post('course/:id/enroll', 'api.CourseController/enroll');
    Route::get('course/:id/progress', 'api.CourseController/progress');

    // 学习记录
    Route::post('study/record', 'api.StudyController/record');

    // 培训任务
    Route::get('task/list', 'api.TaskController/list');
    Route::get('task/:id', 'api.TaskController/detail');
    Route::post('task/:id/enroll', 'api.TaskController/enroll');
    Route::get('task/:id/progress', 'api.TaskController/progress');

    // 考试
    Route::get('exam/papers', 'api.ExamController/papers');
    Route::get('exam/list', 'api.ExamController/list');
    Route::get('exam/:id', 'api.ExamController/detail');
    Route::post('exam/:id/start', 'api.ExamController/start');
    Route::post('exam/:id/submit', 'api.ExamController/submit');
    Route::get('exam/record/:id', 'api.ExamController/record');

    // 证书
    Route::get('certificate/list', 'api.CertificateController/list');
    Route::get('certificate/:id', 'api.CertificateController/detail');
    Route::get('certificate/verify/:certNo', 'api.CertificateController/verify');

    // 知识库
    Route::get('knowledge/list', 'api.KnowledgeController/list');
    Route::get('knowledge/:id', 'api.KnowledgeController/detail');
})->middleware('apiAuth')->middleware('cors');

// ============================================================
// 后台管理 - 需要认证
// ============================================================
Route::group('admin', function () {
    // 首页看板
    Route::get('dashboard/stats', 'admin.Dashboard/stats');
    Route::get('dashboard/quickActions', 'admin.Dashboard/quickActions');

    // 用户管理
    Route::get('user/groups', 'admin.UserController/groups');
    Route::get('user/list', 'admin.UserController/list');
    Route::get('user/:id/study-records', 'admin.UserController/studyRecords');
    Route::get('user/:id/exam-records', 'admin.UserController/examRecords');
    Route::get('user/:id', 'admin.UserController/read');
    Route::post('user', 'admin.UserController/save');
    Route::put('user/:id', 'admin.UserController/update');
    Route::delete('user/:id', 'admin.UserController/delete');
    Route::post('user/import', 'admin.UserController/import');
    Route::post('user/export', 'admin.UserController/export');
    Route::post('user/batch-delete', 'admin.UserController/batchDelete');

    // 试题管理
    Route::get('question/list', 'admin.QuestionController/list');
    Route::post('question/batch-delete$', 'admin.QuestionController/batchDelete');
    Route::post('question/batch-status$', 'admin.QuestionController/batchStatus');
    Route::post('question$', 'admin.QuestionController/save');
    Route::post('question/import', 'admin.QuestionController/import');
    Route::get('question/import-template', 'admin.QuestionController/importTemplate');
    Route::get('question/export', 'admin.QuestionController/export');
    Route::get('question/:id', 'admin.QuestionController/read');
    Route::put('question/:id', 'admin.QuestionController/update');
    Route::delete('question/:id', 'admin.QuestionController/delete');

    // 刷题管理
    Route::get('practice/stats', 'admin.PracticeController/stats');
    Route::get('practice/wrong-book', 'admin.PracticeController/wrongBook');

    // 试卷管理
    Route::get('paper/list', 'admin.PaperController/list');
    Route::get('paper/:id', 'admin.PaperController/read');
    Route::post('paper', 'admin.PaperController/save');
    Route::put('paper/:id', 'admin.PaperController/update');
    Route::delete('paper/:id', 'admin.PaperController/delete');
    Route::post('paper/:id/publish', 'admin.PaperController/publish');
    Route::post('paper/:id/generate', 'admin.PaperController/generate');

    // 课程管理
    Route::get('course/list', 'admin.CourseController/list');
    Route::get('course/:id', 'admin.CourseController/read');
    Route::post('course', 'admin.CourseController/save');
    Route::put('course/:id', 'admin.CourseController/update');
    Route::delete('course/:id', 'admin.CourseController/delete');
    Route::get('course/:id/chapters', 'admin.CourseController/chapters');
    Route::post('course/:id/chapter', 'admin.CourseController/saveChapter');
    Route::delete('chapter/:id', 'admin.CourseController/deleteChapter');

    // 培训任务
    Route::get('task/list', 'admin.TaskController/list');
    Route::get('task/:id', 'admin.TaskController/read');
    Route::post('task', 'admin.TaskController/save');
    Route::put('task/:id', 'admin.TaskController/update');
    Route::delete('task/:id', 'admin.TaskController/delete');
    Route::get('task/:id/stages', 'admin.TaskController/stages');
    Route::post('task/:id/stage', 'admin.TaskController/saveStage');
    Route::delete('stage/:id', 'admin.TaskController/deleteStage');

    // 知识库
    Route::get('knowledge/list', 'admin.KnowledgeController/list');
    Route::get('knowledge/:id', 'admin.KnowledgeController/read');
    Route::post('knowledge', 'admin.KnowledgeController/save');
    Route::put('knowledge/:id', 'admin.KnowledgeController/update');
    Route::delete('knowledge/:id', 'admin.KnowledgeController/delete');

    // 分类管理
    Route::get('category/tree', 'admin.CategoryController/tree');
    Route::get('category/:id', 'admin.CategoryController/read');
    Route::post('category', 'admin.CategoryController/save');
    Route::put('category/:id', 'admin.CategoryController/update');
    Route::delete('category/:id', 'admin.CategoryController/delete');

    // 考试记录
    Route::get('exam/list', 'admin.ExamController/list');
    Route::get('exam/:id', 'admin.ExamController/read');
    Route::get('exam/:id/answers', 'admin.ExamController/answers');
    Route::get('exam/export', 'admin.ExamController/export');

    // 证书管理
    Route::get('certificate/list', 'admin.CertificateController/list');
    Route::get('certificate/:id', 'admin.CertificateController/read');
    Route::post('certificate/:id/revoke', 'admin.CertificateController/revoke');
    Route::get('certificate/verify/:certNo', 'admin.CertificateController/verify');

    // 系统设置
    Route::get('config/group/:group', 'admin.ConfigController/group');
    Route::put('config/:name', 'admin.ConfigController/update');
})->middleware('adminAuth');
