# Yuan-ICP - 一个开源、高度可定制化的虚拟ICP备案系统

<div align="center">

![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF?logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1-7952B3?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-blue.svg)
![GitHub stars](https://img.shields.io/github/stars/bbb-lsy07/Yuan-ICP)
![GitHub forks](https://img.shields.io/github/forks/bbb-lsy07/Yuan-ICP)

</div>

**Yuan-ICP** 是一个为社区、论坛或个人项目设计的虚拟网站备案系统。它旨在模仿真实的ICP备案流程，为用户的虚拟网站或项目提供一个趣味性的“官方认证”。

**请注意：** 本项目仅供娱乐和学习交流，生成的备案号并非由国家工信部签发，不具备任何法律效力。

---

## ✨ 主要功能

Yuan-ICP 提供了一套完整的前后台功能，用户可以轻松申请，管理员可以高效管理。

### 🚀 前台功能

*   **首页展示**: 动态展示最新公告、备案统计（总数、已通过、待审核）和备案查询入口。
*   **在线申请**: 用户可填写网站名称、域名、描述等信息，提交备案申请。
*   **智能号码选择**:
    *   **自动生成**: 根据管理员设定的规则（如 `Yuan{U}{U}{N}{N}{N}{N}{N}{N}`）自动生成备案号。
    *   **号码池选择**: 从管理员预设的号码池中随机抽取号码供用户选择。
    *   **靓号/自定义**: 支持靓号和自定义号码选择，增加趣味性。
*   **备案查询**: 用户可通过备案号或域名查询备案状态和详细信息。
*   **结果展示**: 申请提交后，展示申请状态（审核中、已通过、已驳回）和备案号代码。
*   **公告系统**: 查看由管理员发布的系统公告。
*   **网站迁跃**: 一个有趣的“随机跳转”功能，可以随机访问一个已通过备案的网站。

### 🛠️ 后台管理

*   **仪表盘**: 直观展示各项备案数据的统计信息，一览全局。
*   **备案管理**:
    *   按状态（待审核、已通过、已驳回）筛选和搜索申请。
    *   一键通过或驳回申请，并自动发送邮件通知用户。
*   **公告管理**:
    *   发布、编辑、置顶和删除公告。
    *   内置简单的富文本编辑器，方便内容排版。
*   **系统设置**:
    *   **基本设置**: 配置网站名称、URL、时区等。
    *   **SEO设置**: 设置首页的标题、关键词和描述。
    *   **邮件设置**: 配置SMTP服务器，用于发送备案状态通知邮件。
    *   **号码池设置**: 切换自动生成/手动号码池模式，自定义号码生成规则、保留号码等。
*   **号码池管理**: 批量添加、删除和管理号码池中的备案号，支持标记为“靓号”。
*   **扩展管理**:
    *   **主题管理**: 支持上传和一键切换网站前台主题。
    *   **插件管理**: 完善的插件机制，轻松扩展系统功能。
*   **系统日志**: 记录管理员的关键操作，便于审计和追踪。

## 📸 界面截图

<table>
  <tr>
    <td align="center"><strong>前台首页</strong></td>
    <td align="center"><strong>备案申请</strong></td>
  </tr>
  <tr>
    <td><img src="https://images.6uu.us/20250823105540169.png" alt="前台首页截图"></td>
    <td><img src="https://images.6uu.us/20250823104820996.png" alt="备案申请页面截图"></td>
  </tr>
  <tr>
    <td align="center"><strong>后台仪表盘</strong></td>
    <td align="center"><strong>备案管理</strong></td>
  </tr>
  <tr>
    <td><img src="https://images.6uu.us/20250823105316851.png" alt="后台仪表盘截图"></td>
    <td><img src="https://images.6uu.us/20250823105415869.png" alt="备案管理页面截图"></td>
  </tr>
</table>


## 📦 安装部署

部署 Yuan-ICP 非常简单，只需几步即可完成。

### 1. 环境要求

*   PHP >= 7.4
*   PHP 扩展: PDO (推荐 SQLite, MySQL 或 PostgreSQL), GD, cURL
*   Web 服务器 (Nginx, Apache, etc.)
*   Composer

### 2. 安装步骤

1.  **克隆或下载仓库**
    ```bash
    git clone https://github.com/bbb-lsy07/Yuan-ICP.git
    cd Yuan-ICP
    ```

2.  **安装依赖**
    使用 Composer 安装所需的依赖项（主要是 PHPMailer）。
    ```bash
    composer install
    ```

3.  **设置目录权限**
    确保以下目录对于 Web 服务器是可写的：
    ```    /config
    /uploads
    /data  (如果使用 SQLite)
    ```
    您可以使用 `chmod` 命令来设置权限，例如：
    ```bash
    chmod -R 755 config uploads data
    sudo chown -R www-data:www-data config uploads data # 根据你的服务器用户进行修改
    ```

4.  **运行Web安装向导**
    在浏览器中访问 `http://<你的域名>/install`，系统将引导您完成环境检查、数据库配置和管理员账户创建。

5.  **安全提示**
    **为了安全，安装完成后，请务必删除或重命名项目根目录下的 `install` 文件夹！**

## 💻 技术栈

*   **后端**: 原生 PHP
*   **前端**: Bootstrap 5, FontAwesome
*   **邮件服务**: PHPMailer
*   **数据库**: 支持 SQLite, MySQL, PostgreSQL

## 🤝 贡献指南

欢迎任何形式的贡献！无论是提交 Issue、发起 Pull Request，还是改进文档。

1.  Fork 本仓库
2.  创建您的新分支 (`git checkout -b feature/AmazingFeature`)
3.  提交您的更改 (`git commit -m 'Add some AmazingFeature'`)
4.  将您的分支推送到仓库 (`git push origin feature/AmazingFeature`)
5.  发起一个 Pull Request

## 📄 许可证

本项目采用 [MIT 许可证](LICENSE) 开源。
