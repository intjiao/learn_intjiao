-- ================================================================
-- 在线培训考试系统 - 数据库初始化脚本
-- 数据库名: peixun_db
-- 字符集: utf8mb4
-- 排序规则: utf8mb4_unicode_ci
-- ================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. 管理员表
-- ----------------------------
DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码（加密）',
  `realname` varchar(64) DEFAULT NULL COMMENT '真实姓名',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(128) DEFAULT NULL COMMENT '邮箱',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `role_id` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '角色ID：1超级管理员 2普通管理员',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
  `login_count` int unsigned NOT NULL DEFAULT 0 COMMENT '登录次数',
  `admin_token` varchar(64) DEFAULT NULL COMMENT '登录令牌',
  `token_expire` datetime DEFAULT NULL COMMENT '令牌过期时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- ----------------------------
-- 2. 学员用户表
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码（加密）',
  `realname` varchar(64) NOT NULL COMMENT '真实姓名',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `email` varchar(128) DEFAULT NULL COMMENT '邮箱',
  `idcard` varchar(18) DEFAULT NULL COMMENT '身份证号',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `gender` tinyint NOT NULL DEFAULT 0 COMMENT '性别：0未知 1男 2女',
  `birthday` date DEFAULT NULL COMMENT '出生日期',
  `company` varchar(255) DEFAULT NULL COMMENT '工作单位',
  `department` varchar(128) DEFAULT NULL COMMENT '部门',
  `job_title` varchar(128) DEFAULT NULL COMMENT '岗位',
  `user_group_id` bigint unsigned DEFAULT NULL COMMENT '用户组ID',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_user_group` (`user_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='学员用户表';

-- ----------------------------
-- 3. 用户组表
-- ----------------------------
DROP TABLE IF EXISTS `user_group`;
CREATE TABLE `user_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT '组名称',
  `parent_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '父级ID',
  `level` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '层级',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `description` varchar(500) DEFAULT NULL COMMENT '描述',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户组表';

-- ----------------------------
-- 4. 分类字典表
-- ----------------------------
DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL COMMENT '分类名称',
  `type` varchar(32) NOT NULL DEFAULT 'question' COMMENT '类型：question/course/knowledge',
  `parent_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '父级ID',
  `level` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '层级',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_parent` (`type`,`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类字典表';

-- ----------------------------
-- 5. 试题表
-- ----------------------------
DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '题型：1单选 2多选 3填空 4判断 5问答',
  `difficulty` tinyint NOT NULL DEFAULT 1 COMMENT '难度：1-5',
  `stem` text NOT NULL COMMENT '题干',
  `answer` text DEFAULT NULL COMMENT '标准答案',
  `analysis` text DEFAULT NULL COMMENT '解析',
  `score` decimal(5,1) NOT NULL DEFAULT 1.0 COMMENT '分值',
  `tag_ids` varchar(255) DEFAULT NULL COMMENT '标签ID集合，逗号分隔',
  `source` varchar(255) DEFAULT NULL COMMENT '来源',
  `is_public` tinyint NOT NULL DEFAULT 1 COMMENT '是否公开：1是 0否',
  `use_count` int unsigned NOT NULL DEFAULT 0 COMMENT '使用次数',
  `correct_count` int unsigned NOT NULL DEFAULT 0 COMMENT '正确次数',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_type` (`type`),
  KEY `idx_difficulty` (`difficulty`),
  KEY `idx_category_type` (`category_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='试题表';

-- ----------------------------
-- 6. 试题选项表（单选/多选）
-- ----------------------------
DROP TABLE IF EXISTS `question_option`;
CREATE TABLE `question_option` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint unsigned NOT NULL COMMENT '试题ID',
  `label` varchar(4) NOT NULL COMMENT '选项标签：A/B/C/D/E/F',
  `content` text NOT NULL COMMENT '选项内容',
  `is_correct` tinyint NOT NULL DEFAULT 0 COMMENT '是否正确答案：1是 0否',
  `sort` tinyint NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='试题选项表';

-- ----------------------------
-- 7. 试卷表
-- ----------------------------
DROP TABLE IF EXISTS `paper`;
CREATE TABLE `paper` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '试卷标题',
  `category_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '试卷类型：1固定试卷 2随机试卷',
  `total_score` decimal(5,1) NOT NULL DEFAULT 100.0 COMMENT '总分',
  `pass_score` decimal(5,1) NOT NULL DEFAULT 60.0 COMMENT '及格分',
  `duration` int NOT NULL DEFAULT 60 COMMENT '考试时长（分钟）',
  `total_count` int unsigned NOT NULL DEFAULT 0 COMMENT '题目数量',
  `question_rules` json DEFAULT NULL COMMENT '随机抽题规则',
  `valid_start` datetime DEFAULT NULL COMMENT '有效开始时间',
  `valid_end` datetime DEFAULT NULL COMMENT '有效结束时间',
  `allow_view_answer` tinyint NOT NULL DEFAULT 1 COMMENT '考后查看答案：1允许 0禁止',
  `allow_retry` tinyint NOT NULL DEFAULT 0 COMMENT '允许重考：1允许 0禁止',
  `max_retry` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '最大重考次数',
  `show_score` tinyint NOT NULL DEFAULT 1 COMMENT '显示成绩：1显示 0隐藏',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1草稿 2已发布 0禁用',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='试卷表';

-- ----------------------------
-- 8. 试卷试题关联表
-- ----------------------------
DROP TABLE IF EXISTS `paper_question`;
CREATE TABLE `paper_question` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `paper_id` bigint unsigned NOT NULL COMMENT '试卷ID',
  `question_id` bigint unsigned NOT NULL COMMENT '试题ID',
  `sort` int unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  `score` decimal(5,1) NOT NULL DEFAULT 1.0 COMMENT '本题分值',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_paper_question` (`paper_id`,`question_id`),
  KEY `idx_paper` (`paper_id`),
  KEY `idx_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='试卷试题关联表';

-- ----------------------------
-- 9. 课程表
-- ----------------------------
DROP TABLE IF EXISTS `course`;
CREATE TABLE `course` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `title` varchar(255) NOT NULL COMMENT '课程标题',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面图',
  `teacher` varchar(128) DEFAULT NULL COMMENT '授课教师',
  `description` text DEFAULT NULL COMMENT '课程描述',
  `content` longtext DEFAULT NULL COMMENT '富文本内容',
  `total_duration` int unsigned NOT NULL DEFAULT 0 COMMENT '总时长（秒）',
  `chapter_count` int unsigned NOT NULL DEFAULT 0 COMMENT '章节数',
  `student_count` int unsigned NOT NULL DEFAULT 0 COMMENT '学习人数',
  `is_free` tinyint NOT NULL DEFAULT 0 COMMENT '是否免费：1免费 0付费',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `is_public` tinyint NOT NULL DEFAULT 1 COMMENT '是否公开：1公开 0不公开',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1上架 0下架',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程表';

-- ----------------------------
-- 10. 课程章节表
-- ----------------------------
DROP TABLE IF EXISTS `course_chapter`;
CREATE TABLE `course_chapter` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL COMMENT '课程ID',
  `title` varchar(255) NOT NULL COMMENT '章节标题',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程章节表';

-- ----------------------------
-- 11. 课程资源表（视频/文档）
-- ----------------------------
DROP TABLE IF EXISTS `course_resource`;
CREATE TABLE `course_resource` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chapter_id` bigint unsigned NOT NULL COMMENT '章节ID',
  `course_id` bigint unsigned NOT NULL COMMENT '课程ID',
  `title` varchar(255) NOT NULL COMMENT '资源标题',
  `type` varchar(32) NOT NULL DEFAULT 'video' COMMENT '类型：video/audio/document/image/file',
  `file_url` varchar(500) NOT NULL COMMENT '文件URL',
  `file_size` bigint unsigned NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
  `duration` int unsigned NOT NULL DEFAULT 0 COMMENT '时长（秒，视频/音频）',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chapter` (`chapter_id`),
  KEY `idx_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程资源表';

-- ----------------------------
-- 12. 培训任务表
-- ----------------------------
DROP TABLE IF EXISTS `training_task`;
CREATE TABLE `training_task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '任务名称',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面图',
  `description` text DEFAULT NULL COMMENT '任务描述',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '类型：1在线学习 2混合培训',
  `stages` json DEFAULT NULL COMMENT '阶段配置',
  `target_type` tinyint NOT NULL DEFAULT 1 COMMENT '指派方式：1指定用户组 2指定用户 3公开',
  `target_value` json DEFAULT NULL COMMENT '指派值（用户组ID或用户ID列表）',
  `valid_start` datetime DEFAULT NULL COMMENT '开始时间',
  `valid_end` datetime DEFAULT NULL COMMENT '截止时间',
  `certificate_template` varchar(255) DEFAULT NULL COMMENT '证书模板',
  `certificate_expire_years` tinyint unsigned DEFAULT NULL COMMENT '证书有效期（年）',
  `total_progress` int unsigned NOT NULL DEFAULT 0 COMMENT '总进度百分比',
  `enroll_count` int unsigned NOT NULL DEFAULT 0 COMMENT '报名人数',
  `complete_count` int unsigned NOT NULL DEFAULT 0 COMMENT '完成人数',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1进行中 2已结束 0草稿',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_valid_period` (`valid_start`,`valid_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训任务表';

-- ----------------------------
-- 13. 培训阶段表
-- ----------------------------
DROP TABLE IF EXISTS `training_stage`;
CREATE TABLE `training_stage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL COMMENT '任务ID',
  `name` varchar(255) NOT NULL COMMENT '阶段名称',
  `type` tinyint NOT NULL DEFAULT 1 COMMENT '类型：1学习阶段 2考试阶段',
  `course_ids` json DEFAULT NULL COMMENT '关联课程ID列表',
  `exam_paper_id` bigint unsigned DEFAULT NULL COMMENT '关联试卷ID',
  `required_score` decimal(5,1) NOT NULL DEFAULT 60.0 COMMENT '合格分数',
  `required_progress` int unsigned NOT NULL DEFAULT 100 COMMENT '完成进度要求（%）',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训阶段表';

-- ----------------------------
-- 14. 知识库表
-- ----------------------------
DROP TABLE IF EXISTS `knowledge`;
CREATE TABLE `knowledge` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '分类ID',
  `title` varchar(255) NOT NULL COMMENT '标题',
  `cover` varchar(255) DEFAULT NULL COMMENT '封面图',
  `type` varchar(32) NOT NULL DEFAULT 'document' COMMENT '类型：video/audio/document/image/file',
  `content` text DEFAULT NULL COMMENT '富文本内容（文档类）',
  `file_url` varchar(500) DEFAULT NULL COMMENT '文件URL（视频/音频/附件）',
  `file_size` bigint unsigned NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
  `duration` int unsigned NOT NULL DEFAULT 0 COMMENT '时长（秒）',
  `view_count` int unsigned NOT NULL DEFAULT 0 COMMENT '浏览次数',
  `download_count` int unsigned NOT NULL DEFAULT 0 COMMENT '下载次数',
  `is_public` tinyint NOT NULL DEFAULT 1 COMMENT '是否公开',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库表';

-- ----------------------------
-- 15. 考试记录表
-- ----------------------------
DROP TABLE IF EXISTS `exam_record`;
CREATE TABLE `exam_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `paper_id` bigint unsigned NOT NULL COMMENT '试卷ID',
  `paper_title` varchar(255) DEFAULT NULL COMMENT '试卷标题快照',
  `task_id` bigint unsigned DEFAULT NULL COMMENT '培训任务ID',
  `stage_id` bigint unsigned DEFAULT NULL COMMENT '培训阶段ID',
  `answer_ids` json DEFAULT NULL COMMENT '答案记录ID列表',
  `score` decimal(5,1) DEFAULT NULL COMMENT '得分',
  `total_score` decimal(5,1) DEFAULT NULL COMMENT '总分',
  `correct_count` int unsigned NOT NULL DEFAULT 0 COMMENT '正确题数',
  `wrong_count` int unsigned NOT NULL DEFAULT 0 COMMENT '错误题数',
  `pass_status` tinyint DEFAULT NULL COMMENT '及格状态：1及格 0不及格',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1进行中 2已完成 0已取消',
  `start_time` datetime NOT NULL COMMENT '开始时间',
  `submit_time` datetime DEFAULT NULL COMMENT '提交时间',
  `duration` int unsigned NOT NULL DEFAULT 0 COMMENT '用时（秒）',
  `source` varchar(32) DEFAULT NULL COMMENT '来源：miniprogram/admin',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_paper` (`paper_id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_status` (`status`),
  KEY `idx_user_paper` (`user_id`,`paper_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试记录表';

-- ----------------------------
-- 16. 考试答案明细表
-- ----------------------------
DROP TABLE IF EXISTS `exam_answer`;
CREATE TABLE `exam_answer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint unsigned NOT NULL COMMENT '考试记录ID',
  `question_id` bigint unsigned NOT NULL COMMENT '试题ID',
  `question_type` tinyint NOT NULL DEFAULT 1 COMMENT '题型',
  `user_answer` text DEFAULT NULL COMMENT '用户答案',
  `correct_answer` text DEFAULT NULL COMMENT '正确答案',
  `is_correct` tinyint DEFAULT NULL COMMENT '是否正确：1正确 0错误 2待批阅',
  `score` decimal(5,1) DEFAULT NULL COMMENT '本题得分',
  `sort` int unsigned NOT NULL DEFAULT 0 COMMENT '题号',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`),
  KEY `idx_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试答案明细表';

-- ----------------------------
-- 17. 证书表
-- ----------------------------
DROP TABLE IF EXISTS `certificate`;
CREATE TABLE `certificate` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT '学员ID',
  `task_id` bigint unsigned NOT NULL COMMENT '培训任务ID',
  `task_title` varchar(255) DEFAULT NULL COMMENT '任务名称快照',
  `user_name` varchar(64) DEFAULT NULL COMMENT '学员姓名快照',
  `cert_no` varchar(64) NOT NULL COMMENT '证书编号',
  `issue_date` date NOT NULL COMMENT '颁发日期',
  `expire_date` date DEFAULT NULL COMMENT '有效期截止',
  `file_path` varchar(500) DEFAULT NULL COMMENT '证书文件路径',
  `qr_code` varchar(500) DEFAULT NULL COMMENT '二维码路径',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1有效 2撤销 3过期',
  `revoke_reason` varchar(500) DEFAULT NULL COMMENT '撤销原因',
  `revoke_time` datetime DEFAULT NULL COMMENT '撤销时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cert_no` (`cert_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_user_task` (`user_id`,`task_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='证书表';

-- ----------------------------
-- 18. 学习记录表
-- ----------------------------
DROP TABLE IF EXISTS `study_record`;
CREATE TABLE `study_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `course_id` bigint unsigned NOT NULL COMMENT '课程ID',
  `chapter_id` bigint unsigned DEFAULT NULL COMMENT '章节ID',
  `resource_id` bigint unsigned DEFAULT NULL COMMENT '资源ID',
  `progress` int unsigned NOT NULL DEFAULT 0 COMMENT '进度百分比',
  `watch_duration` int unsigned NOT NULL DEFAULT 0 COMMENT '已观看时长（秒）',
  `total_duration` int unsigned NOT NULL DEFAULT 0 COMMENT '总时长（秒）',
  `is_complete` tinyint NOT NULL DEFAULT 0 COMMENT '是否完成：1已完成 0未完成',
  `last_study_time` datetime DEFAULT NULL COMMENT '最后学习时间',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_resource` (`user_id`,`resource_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_course` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='学习记录表';

-- ----------------------------
-- 19. 培训报名表
-- ----------------------------
DROP TABLE IF EXISTS `training_enroll`;
CREATE TABLE `training_enroll` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL COMMENT '培训任务ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `progress` int unsigned NOT NULL DEFAULT 0 COMMENT '总进度（%）',
  `stage_progress` json DEFAULT NULL COMMENT '各阶段进度',
  `is_complete` tinyint NOT NULL DEFAULT 0 COMMENT '是否完成：1已完成 0未完成',
  `complete_time` datetime DEFAULT NULL COMMENT '完成时间',
  `certificate_id` bigint unsigned DEFAULT NULL COMMENT '证书ID',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1进行中 2已完成 0已取消',
  `enroll_time` datetime DEFAULT NULL COMMENT '报名时间',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_user` (`task_id`,`user_id`),
  KEY `idx_task` (`task_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='培训报名表';

-- ----------------------------
-- 20. 系统配置表
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(64) NOT NULL DEFAULT 'basic' COMMENT '配置分组',
  `name` varchar(128) NOT NULL COMMENT '配置名称',
  `value` text DEFAULT NULL COMMENT '配置值',
  `type` varchar(32) NOT NULL DEFAULT 'text' COMMENT '类型：text/textarea/number/select/switch/file',
  `options` json DEFAULT NULL COMMENT '可选值（select类型）',
  `description` varchar(500) DEFAULT NULL COMMENT '说明',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_name` (`group`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ----------------------------
-- 21. 操作日志表
-- ----------------------------
DROP TABLE IF EXISTS `admin_log`;
CREATE TABLE `admin_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned NOT NULL COMMENT '管理员ID',
  `admin_name` varchar(64) DEFAULT NULL COMMENT '管理员名称',
  `action` varchar(64) NOT NULL COMMENT '操作类型',
  `module` varchar(64) DEFAULT NULL COMMENT '模块',
  `param` text DEFAULT NULL COMMENT '请求参数',
  `ip` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '浏览器信息',
  `result` text DEFAULT NULL COMMENT '操作结果',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- ================================================================
-- 初始化数据
-- ================================================================

-- 插入超级管理员 (密码: admin123)
INSERT INTO `admin_user` (`username`, `password`, `realname`, `role_id`, `status`, `created_at`) VALUES
('admin', '$2y$10$bVEF5/1Bsfr/DSDvRbvbWOiu98m3xvSaD.G9lIqTrPYW3ppFc3TNK', '系统管理员', 1, 1, NOW());

-- 插入试题分类
INSERT INTO `category` (`name`, `type`, `parent_id`, `level`, `sort`, `status`, `created_at`) VALUES
('特种作业', 'question', 0, 1, 1, 1, NOW()),
('电工', 'question', 1, 2, 1, 1, NOW()),
('焊工', 'question', 1, 2, 2, 1, NOW()),
('高处作业', 'question', 1, 2, 3, 1, NOW()),
('职业技能', 'question', 0, 1, 2, 1, NOW()),
('车工', 'question', 5, 2, 1, 1, NOW()),
('钳工', 'question', 5, 2, 2, 1, NOW());

-- 插入课程分类
INSERT INTO `category` (`name`, `type`, `parent_id`, `level`, `sort`, `status`, `created_at`) VALUES
('安全培训', 'course', 0, 1, 1, 1, NOW()),
('技能提升', 'course', 0, 1, 2, 1, NOW()),
('岗位认证', 'course', 0, 1, 3, 1, NOW());

-- 插入知识库分类
INSERT INTO `category` (`name`, `type`, `parent_id`, `level`, `sort`, `status`, `created_at`) VALUES
('政策法规', 'knowledge', 0, 1, 1, 1, NOW()),
('技术标准', 'knowledge', 0, 1, 2, 1, NOW()),
('操作规程', 'knowledge', 0, 1, 3, 1, NOW()),
('安全案例', 'knowledge', 0, 1, 4, 1, NOW());

-- 插入系统配置
INSERT INTO `system_config` (`group`, `name`, `value`, `type`, `description`, `sort`, `created_at`) VALUES
('basic', 'site_name', '在线培训考试系统', 'text', '网站名称', 1, NOW()),
('basic', 'site_logo', '/static/admin/images/logo.png', 'file', '网站Logo', 2, NOW()),
('exam', 'default_pass_score', '60', 'number', '默认及格分数', 1, NOW()),
('exam', 'default_duration', '60', 'number', '默认考试时长（分钟）', 2, NOW()),
('exam', 'allow_retry', '1', 'switch', '是否允许重考', 3, NOW()),
('certificate', 'cert_prefix', 'CERT', 'text', '证书编号前缀', 1, NOW()),
('certificate', 'org_name', '某某职业技能培训中心', 'text', '发证机构名称', 2, NOW());

-- ----------------------------
-- 创建视图：考试统计视图
-- ----------------------------
CREATE OR REPLACE VIEW `v_exam_statistics` AS
SELECT
    `er`.`paper_id` AS `paper_id`,
    `p`.`title` AS `paper_title`,
    COUNT(*) AS `total_count`,
    SUM(CASE WHEN `er`.`pass_status` = 1 THEN 1 ELSE 0 END) AS `pass_count`,
    ROUND(AVG(`er`.`score`), 1) AS `avg_score`,
    ROUND(SUM(`er`.`pass_status` = 1) / COUNT(*) * 100, 1) AS `pass_rate`
FROM `exam_record` `er`
LEFT JOIN `paper` `p` ON `er`.`paper_id` = `p`.`id`
WHERE `er`.`status` = 2
GROUP BY `er`.`paper_id`;

SET FOREIGN_KEY_CHECKS = 1;
