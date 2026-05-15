<img width="1920" height="911" alt="image" src="https://github.com/user-attachments/assets/a8a19f32-1f5f-44b5-8a39-2b6375d91ac4" /># Peixun · 在线培训考试系统

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![ThinkPHP](https://img.shields.io/badge/ThinkPHP-6.x-1296DB.svg)](https://www.thinkphp.cn/)

一套面向企业内训、岗位认证与安全培训场景的在线培训考试系统。后端基于 ThinkPHP 6 + MySQL，前端提供三套入口：Layui 管理后台、H5 用户端、微信小程序。

##开发背景：
老婆在考试其他证书，所用的刷题软件用户体验很差

另外作为开发者的一员希望给大家做些贡献

开发之前搜了很多类似的项目，都是打着开源的口号实际上批量导入试题都需要付费买商业版。我直接开发了一套给各位同仁放心开发使用，不需要任何授权点个星即可

## 系统相关页面截图
<img width="1920" height="911" alt="image" src="https://github.com/user-attachments/assets/a8a19f32-1f5f-44b5-8a39-2b6375d91ac4" />

<img width="1920" height="911" alt="image" src="https://github.com/user-attachments/assets/c0e51297-4f77-4d1b-8b72-b202b72bcd31" />
<img width="1920" height="911" alt="image" src="https://github.com/user-attachments/assets/a1cfc63c-415a-441e-aece-be788f1f3e91" />
<img width="1920" height="911" alt="image" src="https://github.com/user-attachments/assets/1e02f25e-ed7c-4884-a4e9-c5b4654f7861" />
<img width="1920" height="911" alt="image" src="https://github.com/user-attachments/assets/8bbe3334-d5ad-4644-abe1-3327e460574c" />
<img width="378" height="820" alt="image" src="https://github.com/user-attachments/assets/f362bb83-5efe-4098-99ac-5ef29c62c8c4" />
<img width="378" height="820" alt="image" src="https://github.com/user-attachments/assets/ad004cd2-76d4-4031-a5da-ba6ac512cdb4" />
<img width="363" height="679" alt="image" src="https://github.com/user-attachments/assets/586cf2b4-1852-49de-a188-8554cda78afd" />
<img width="380" height="733" alt="image" src="https://github.com/user-attachments/assets/46ce6636-9b11-4c9f-8f07-ec87770b5f89" />
<img width="374" height="810" alt="image" src="https://github.com/user-attachments/assets/605f5f73-c5c7-40ae-92ba-8d4dcdf4f355" />
<img width="379" height="752" alt="image" src="https://github.com/user-attachments/assets/98dba3b6-6d1b-4a50-b9c5-3c66fc4a17bd" />
<img width="378" height="820" alt="image" src="https://github.com/user-attachments/assets/d70d7811-efbc-4cce-b111-4381b87b6103" />
<img width="1920" height="911" alt="image" src="https://github.com/user-attachments/assets/20bcdb04-ae6f-436a-8fcc-51980f4ad84c" />












## 功能特性

- 试题库管理：单选 / 多选 / 填空 / 判断 / 问答，支持 Excel 批量导入
- 试卷生成：固定试卷与随机抽题两种模式，可灵活配置抽题规则/刷题考试已在生产环境测试完成
  其他内容我并没有开发完，基本可以满足需求，小程序版本还在开发中

## 技术栈

| 层级 | 技术 |
|------|------|
| 后端框架 | ThinkPHP 6.0 |
| 数据库 | MySQL 5.7 / 8.0 |
| 缓存（可选） | Redis |
| 后台前端 | Layui 2.9 + Php|
| H5 用户端 | 原生 HTML / Layui |
| 移动端 | 微信小程序原生 |


## 目录结构

```
.
├── app/                     # 应用主目录
│   ├── controller/          # 控制器（admin / api）
│   ├── middleware/          # 中间件（鉴权 / CORS）
│   ├── model/               # 数据模型
│   └── service/             # 业务服务
├── config/                  # ThinkPHP 配置
├── database/migrations/     # 初始化 SQL
├── miniprogram/             # 微信小程序源码
├── public/                  # Web 入口
│   ├── admin/               # Layui 管理后台
│   ├── user/                # H5 用户端
│   ├── index.php            # 框架入口
│   └── nginx.conf           # Nginx 伪静态参考
├── route/                   # 路由
├── .env.example             # 环境变量模板
└── composer.json
```

## 快速开始

### 环境要求

- PHP ≥ 7.4（推荐 8.1）
- MySQL ≥ 5.7（推荐 8.0）
- Composer
- Nginx 或 Apache
- Redis（可选）

### 安装
 PHP项目比较简单，直接上传根目录即可
```bash
git clone https://github.com/<your-account>/peixun.git
cd peixun

composer install --no-dev

cp .env.example .env
# 编辑 .env 填入数据库账号、Redis、微信 AppID/AppSecret 等

mysql -u root -p -e "CREATE DATABASE peixun_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p peixun_db < database/migrations/2024_01_01_000001_init_database.sql
```

### Web 服务器配置

入口目录指向 `public/`，并启用伪静态。Nginx 关键片段：

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /www/wwwroot/peixun/public;
    index index.php;

    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=$1 last;
        }
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 目录权限

```bash
chmod -R 755 runtime public/uploads
chown -R www:www runtime public/uploads
```

## 访问入口

| 入口 | 地址 | 默认账号 |
|------|------|----------|
| 管理后台 | `https://your-domain.com/admin/login.html` | `admin` / `admin123` |
| H5 用户端 | `https://your-domain.com/user/login.html` | 后台创建后下发 |




## 客户端配置


### H5 用户端

`public/user/api.js` 默认走相对路径，部署到主站域名即可，无需改动。

## 常用 API

```
POST  /admin/login                # 后台登录
POST  /api/auth/login             # H5 / 小程序登录
POST  /api/auth/register          # 用户注册

GET   /api/course/list            # 课程列表
GET   /api/course/{id}            # 课程详情
POST  /api/exam/{id}/start        # 开始考试
POST  /api/exam/{id}/submit       # 提交答案
GET   /api/certificate/list       # 我的证书

GET   /admin/dashboard/stats      # 看板数据
GET   /admin/question/list        # 试题列表
POST  /admin/question/import      # 导入试题
GET   /admin/paper/list           # 试卷列表
POST  /admin/paper/{id}/publish   # 发布试卷
```

完整路由见 `route/route.php`。

## 二次开发提示

- 控制器分两类：`app/controller/admin/*` 走 `AdminAuth` 中间件，`app/controller/api/*` 走 `ApiAuth` 中间件，两者都默认开启 `Cors`。
- 业务逻辑集中在 `app/service/`：试卷生成、证书发放、题库导入等都在这一层，建议在此层扩展而不是直接改控制器。
- 数据模型放在 `app/model/`，统一继承 `app\BaseModel\BaseModel`，新增表先在 `database/migrations/` 增量加 SQL。
- 前端管理后台基于 Layui，没有打包流程，直接编辑 `public/admin/` 下的 HTML / JS 即可热更新。

## 部署常见问题

- 登录提示 "网络异常"：通常是伪静态没生效，确认网站根目录指向 `public`，并启用 ThinkPHP 伪静态。
- 接口 404：检查 `runtime/` 目录是否可写、`vendor/` 是否完整安装。
- 微信小程序请求失败：确认线上 API 走 HTTPS，并在小程序后台 `request 合法域名` 中添加。
- 上传图片 / 视频 403：检查 `public/uploads` 权限以及 Nginx `client_max_body_size`。

## 安全建议

- 上线前修改默认管理员密码、关闭 `APP_DEBUG`。
- 数据库账号使用最小权限的独立用户，避免 `root`。
- 建议在 Nginx 层关闭 `.env`、`.git`、`composer.*`、`/runtime/` 等路径的直接访问。
- 若要对外开放注册接口，务必配合验证码与频率限制（见 `app/controller/api/CaptchaController.php`）。

## 许可证

[MIT License](LICENSE)

## 提供有偿答疑部署或有二次开发需求，可同我联系
VX：int_float_v



##后期计划
这只是个初步版本，我会持续迭代更新。也会开发出其他纯开源项目供大家学习使用


## 贡献

欢迎以 Issue / Pull Request 的形式参与改进。提交前请确保：

- 代码风格与现有保持一致；
- 涉及数据库结构的改动放在 `database/migrations/` 增量 SQL；
- 不要把 `.env`、`vendor/`、`node_modules/`、`runtime/` 之类文件加入提交。
