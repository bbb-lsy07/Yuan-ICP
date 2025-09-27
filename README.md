<div align="center">
  <h1>Yuan-ICP 虚拟备案管理系统</h1>
  <p>一个开源、高度可定制化的虚拟ICP备案系统，为爱好者和社区提供一个有趣的互动平台。</p>
  
  <p>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/blob/main/LICENSE"><img src="https://img.shields.io/github/license/bbb-lsy07/Yuan-ICP?style=for-the-badge&label=License&color=blue" alt="许可证"></a>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/stargazers"><img src="https://img.shields.io/github/stars/bbb-lsy07/Yuan-ICP?style=for-the-badge&logo=github" alt="GitHub Stars"></a>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/issues"><img src="https://img.shields.io/github/issues/bbb-lsy07/Yuan-ICP?style=for-the-badge&logo=github" alt="GitHub Issues"></a>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/forks"><img src="https://img.shields.io/github/forks/bbb-lsy07/Yuan-ICP?style=for-the-badge&logo=github" alt="GitHub Forks"></a>
  </p>

  <p>
    <a href="https://icp-doc.6uu.us/"><strong>在线文档</strong></a> ·
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/issues/new?template=bug_report.md">报告 Bug</a> ·
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/issues/new?template=feature_request.md">功能建议</a>
  </p>
</div>

> **重要声明**: 本项目提供的备案号为 **虚拟信息**，仅供娱乐和社区认证使用，与国家工信部的真实ICP备案 **没有任何关系**。请在法律允许的范围内使用本项目。

## ✨ 简介

Yuan-ICP 是一个功能强大、开源免费的虚拟ICP备案系统。它允许站长或社区成员为自己的网站申请一个虚拟的、富有个性的备案号。系统不仅提供了完整的前台申请、查询流程，还拥有一个现代化、功能全面的后台管理面板，并且通过强大的主题和插件系统，为您提供了极高的可定制性。

---

## 🚀 主要特性

Yuan-ICP 不仅仅是一个简单的申请页面，它是一个完整的生态系统，包含了丰富的功能，可以满足个人站长或小型社区的全部运营需求。

| 功能模块 | 特性描述 |
| :--- | :--- |
| 🧑‍💻 **前台体验** | • **多步申请流程**：清晰引导用户填写信息、选择心仪的备案号。<br>• **动态号码池**：支持按规则自动生成号码，或从管理员预设的号码池中选择。<br>• **靓号/赞助系统**：可将特定号码设为“靓号”，并引导用户通过赞助获取，增加趣味性。<br>• **自动邮件通知**：申请状态变更时，自动向用户发送邮件通知。<br>• **公开查询系统**：支持通过备案号或域名查询已通过的备案信息。<br>• **自助服务**：被驳回的用户可通过邮件验证码自助修改信息并重新提交审核。 |
| ⚙️ **后台管理** | • **现代化仪表盘**：直观展示各类备案申请统计数据与趋势图表。<br>• **强大的设置中心**：涵盖基础、SEO、邮件、号码池、赞助、页脚等全方位配置。<br>• **备案集中管理**：轻松进行筛选、搜索、批准、驳回及编辑操作。<br>• **公告管理**：内置简单的富文本编辑器，轻松发布和置顶公告。 |
| 🎨 **高度可扩展** | • **主题系统**：支持一键切换、上传和管理主题，主题可自定义配置选项并支持后台实时预览。<br>• **插件系统**：通过插件为系统添加新功能，支持插件的启用、禁用、上传和删除。<br>• **钩子机制 (Hooks)**：允许插件深度介入系统流程，实现高度定制化。 |
| 🛠️ **系统与安全** | • **一键备份与恢复**：(仅限SQLite) 轻松备份和恢复整个数据库。<br>• **数据库自动修复**：版本升级时，自动检查并修复数据库结构，升级无忧。<br>• **管理员操作日志**：记录所有关键操作，便于审计与追踪。<br>• **安全防护**：后台登录包含防暴力破解机制，所有表单均使用CSRF令牌保护。 |

## 🛠️ 技术栈

- **后端**: PHP (7.4+)
- **数据库**: SQLite (默认), PDO 支持 MySQL, PostgreSQL
- **前端**: HTML, CSS, JavaScript
- **主要库**:
    - **Bootstrap 5**: 构建现代化、响应式的后台管理界面。
    - **Chart.js**: 用于在仪表盘中渲染统计图表。
    - **PHPMailer**: 处理所有邮件发送任务。
    - **Swup.js**: (在 "Modern Light" 主题中使用) 实现平滑的页面过渡效果。

## 🔧 安装指南

1.  **上传文件**: 将项目所有文件上传到您的网站服务器。
2.  **设置权限**: 确保以下目录及其子目录具有写入权限 (权限 `755` 或 `777`):
    *   `/data` (用于存放SQLite数据库)
    *   `/uploads` (用于存放用户上传的文件，如收款码)
3.  **访问安装程序**: 在浏览器中访问 `http://你的域名/install/step1.php`。
4.  **环境检查**: 安装向导将自动检查您的服务器环境。如果所有项目都通过，点击“下一步”。
5.  **信息配置**:
    *   选择数据库类型。**强烈推荐使用默认的 SQLite**，它无需额外配置，开箱即用。
    *   填写您的站点名称和URL。
    *   创建您的管理员账户（用户名和密码）。
6.  **完成安装**: 点击“安装”，系统将自动完成数据库的初始化和配置文件的生成。
7.  **⚠️ 安全提示**: **安装完成后，请立即删除或重命名服务器上的 `install` 目录！**
8.  **开始使用**: 访问 `http://你的域名/admin` 登录后台，开始您的虚拟备案之旅！

## 🎨 主题与插件开发

Yuan-ICP 的核心魅力在于其强大的可扩展性。

### 主题开发

主题是定义系统前台外观的方式，所有主题文件位于 `/themes` 目录下。

-   **`theme.json`**: 这是主题的灵魂。它定义了主题的元信息（名称、版本等），最重要的是，您可以在此定义**自定义配置选项**。这些选项会自动出现在后台的“主题选项”页面中，允许用户通过图形界面轻松修改主题（例如更换颜色、开关模块等），而无需修改代码。
-   **`/templates` 目录**: 存放主题的所有视图文件（`.php` 文件），例如 `header.php`, `footer.php`, `home.php` 等。

### 插件开发

插件是为系统添加新功能的核心方式，所有插件文件位于 `/plugins` 目录下。

-   **`plugin.php`**: 每个插件的入口和核心文件。它定义了插件的元信息，并包含插件的生命周期函数：
    -   `{identifier}_activate()`: 插件**激活**时执行，通常用于创建数据表。
    -   `{identifier}_deactivate()`: 插件**禁用**时执行。
    -   `{identifier}_uninstall()`: 插件**被删除**时执行，用于清理数据和数据表。
-   **钩子 (Hooks)**: 插件通过“钩子”与核心系统交互。您可以使用 `PluginHooks::add('hook_name', ...)` 来挂载您的函数到系统的特定执行点，从而在不修改核心代码的情况下改变或增强系统功能。
-   **后台页面**: 插件可以使用 `EnhancedPluginHooks::registerAdminMenu(...)` 函数，轻松地向后台侧边栏添加自己的管理页面。

## 🤝 贡献

我们热烈欢迎各种形式的贡献！无论是提交 Bug、建议新功能还是贡献代码。

1.  **Fork** 本仓库
2.  创建您的功能分支 (`git checkout -b feature/AmazingFeature`)
3.  提交您的更改 (`git commit -m 'Add some AmazingFeature'`)
4.  将您的分支推送到远程仓库 (`git push origin feature/AmazingFeature`)
5.  **发起一个 Pull Request**

## 📄 许可证

本项目采用 **GPL-3.0** 许可证。详情请参阅 [LICENSE](https://github.com/bbb-lsy07/Yuan-ICP/blob/main/LICENSE) 文件。

---

<div align="center">
  <h1>Yuan-ICP Virtual Filing System</h1>
  <p>An open-source, highly customizable virtual ICP filing system designed for enthusiasts and communities.</p>
  
  <p>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/blob/main/LICENSE"><img src="https://img.shields.io/github/license/bbb-lsy07/Yuan-ICP?style=for-the-badge&label=License&color=blue" alt="License"></a>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/stargazers"><img src="https://img.shields.io/github/stars/bbb-lsy07/Yuan-ICP?style=for-the-badge&logo=github" alt="GitHub Stars"></a>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/issues"><img src="https://img.shields.io/github/issues/bbb-lsy07/Yuan-ICP?style=for-the-badge&logo=github" alt="GitHub Issues"></a>
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/forks"><img src="https://img.shields.io/github/forks/bbb-lsy07/Yuan-ICP?style=for-the-badge&logo=github" alt="GitHub Forks"></a>
  </p>

  <p>
    <a href="https://icp-doc.6uu.us/"><strong>Documentation</strong></a> ·
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/issues/new?template=bug_report.md">Report a Bug</a> ·
    <a href="https://github.com/bbb-lsy07/Yuan-ICP/issues/new?template=feature_request.md">Request a Feature</a>
  </p>
</div>

> **Important Disclaimer**: The filing numbers provided by this project are **virtual**, intended for entertainment and community authentication purposes only. They have **no connection** whatsoever with the official ICP filings from government authorities. Please use this project in compliance with your local laws and regulations.

## ✨ About The Project

Yuan-ICP is a powerful, free, and open-source virtual ICP filing system. It enables website owners and community members to apply for a unique, personalized virtual filing number for their sites. The system not only offers a complete frontend application and query process but also features a modern, comprehensive admin panel. With its robust theme and plugin systems, Yuan-ICP provides exceptional customizability.

---

## 🚀 Key Features

Yuan-ICP is more than just a simple application form; it's a complete ecosystem with a rich feature set designed to meet the operational needs of individual bloggers or small communities.

| Module | Feature Description |
| :--- | :--- |
| 🧑‍💻 **Frontend Experience** | • **Multi-step Application**: A clear, guided process for users to submit site information and select a filing number.<br>• **Dynamic Number Pool**: Supports auto-generation of numbers based on custom formats or selection from a manually curated pool.<br>• **Premium Numbers & Sponsorship**: Mark specific numbers as "premium" and guide users to obtain them via sponsorship, adding a fun and monetizable element.<br>• **Automated Email Notifications**: Automatically sends email updates to users when their application status changes (approved/rejected).<br>• **Public Query System**: Allows anyone to verify approved filings by number or domain name.<br>• **Self-Service Portal**: Users with rejected applications can self-verify via email to edit and resubmit their information. |
| ⚙️ **Admin Panel** | • **Modern Dashboard**: An intuitive dashboard with statistics and charts for a quick overview of all applications.<br>• **Powerful Settings Center**: Comprehensive configuration for site basics, SEO, email (SMTP), number pool, sponsorship, and footer details.<br>• **Centralized Application Management**: Easily filter, search, approve, reject, and edit all submissions.<br>• **Announcement Management**: A built-in simple rich-text editor to publish, edit, and pin system announcements. |
| 🎨 **High Extensibility** | • **Theme System**: One-click theme switching, uploading, and management. Themes can have their own custom options with a live preview in the admin panel.<br>• **Plugin System**: Extend system functionality with plugins. Supports enabling, disabling, uploading, and deleting plugins.<br>• **Hooking Mechanism**: Allows plugins to deeply integrate with the core system for advanced customizations. |
| 🛠️ **System & Security** | • **One-Click Backup & Restore**: (SQLite only) Easily back up and restore the entire database.<br>• **Automatic Database Migration**: Automatically checks and updates the database schema on version upgrades, making updates seamless.<br>• **Admin Action Logs**: Records key administrator actions for auditing and tracking purposes.<br>• **Security Hardened**: The admin login includes brute-force protection, and all forms are protected with CSRF tokens. |

## 🛠️ Technology Stack

- **Backend**: PHP (7.4+)
- **Database**: SQLite (default), PDO support for MySQL, PostgreSQL
- **Frontend**: HTML, CSS, JavaScript
- **Key Libraries**:
    - **Bootstrap 5**: For building a modern, responsive admin interface.
    - **Chart.js**: For rendering statistical charts on the dashboard.
    - **PHPMailer**: For handling all email-sending tasks.
    - **Swup.js**: Used in the "Modern Light" theme for smooth page transitions.

## 🔧 Installation Guide

1.  **Upload Files**: Upload all project files to your web server.
2.  **Set Permissions**: Ensure the following directories are writable (permissions `755` or `777`):
    *   `/data` (for the SQLite database)
    *   `/uploads` (for user-uploaded files like QR codes)
3.  **Run Installer**: Visit `http://your-domain.com/install/step1.php` in your browser.
4.  **Environment Check**: The wizard will automatically check if your server meets the requirements. Click "Next" if all checks pass.
5.  **Configuration**:
    *   Choose your database type. **SQLite is highly recommended** for its simplicity and zero-configuration setup.
    *   Enter your site's name and URL.
    *   Create your administrator account.
6.  **Complete Installation**: Click "Install". The system will initialize the database and create the configuration file.
7.  **⚠️ Security Warning**: After installation, you **must** delete or rename the `install` directory on your server!
8.  **Get Started**: Log in to the admin panel at `http://your-domain.com/admin` and start your virtual filing journey!

## 🎨 Theme & Plugin Development

The core appeal of Yuan-ICP lies in its powerful extensibility.

### Theme Development

Themes define the frontend appearance of your system and are located in the `/themes` directory.

-   **`theme.json`**: This is the heart of your theme. It defines meta-information and, most importantly, **custom configuration options**. You can define text fields, color pickers, checkboxes, etc., which will automatically appear on the "Theme Options" page in the admin panel.
-   **`/templates` directory**: Contains all the PHP template files for your theme, such as `header.php`, `footer.php`, and `home.php`.

### Plugin Development

Plugins are the primary way to add new functionality and are located in the `/plugins` directory.

-   **`plugin.php`**: The entry point for every plugin. It defines the plugin's meta-information and contains its lifecycle functions:
    -   `{identifier}_activate()`: Runs when the plugin is **activated**. Typically used to create database tables.
    -   `{identifier}_deactivate()`: Runs when the plugin is **deactivated**.
    -   `{identifier}_uninstall()`: Runs when the plugin is **deleted**. Used to clean up data and tables.
-   **Hooks**: Plugins interact with the core system via hooks. Use `PluginHooks::add('hook_name', ...)` to attach your functions to specific execution points in the system, allowing you to modify behavior without altering core code.
-   **Admin Pages**: Plugins can easily add their own management pages to the admin sidebar using the `EnhancedPluginHooks::registerAdminMenu(...)` function.

## 🤝 Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1.  **Fork** the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a **Pull Request**

## 📄 License

This project is licensed under the **GPL-3.0 License**. See the [LICENSE](https://github.com/bbb-lsy07/Yuan-ICP/blob/main/LICENSE) file for more details.
