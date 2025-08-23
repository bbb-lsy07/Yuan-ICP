<?php
/**
 * Yuan-ICP 后台侧边栏菜单
 */
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <h3>Yuan-ICP</h3>
    </div>
    <ul class="list-unstyled components">
        <li class="active">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> 仪表盘</a>
        </li>
        <li>
            <a href="#applications" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fas fa-file-alt"></i> 备案管理
            </a>
            <ul class="collapse list-unstyled" id="applications">
                <li><a href="applications.php">所有申请</a></li>
                <li><a href="applications.php?status=pending">待审核</a></li>
                <li><a href="applications.php?status=approved">已通过</a></li>
                <li><a href="applications.php?status=rejected">已驳回</a></li>
            </ul>
        </li>
        <li>
            <a href="announcements.php"><i class="fas fa-bullhorn"></i> 公告管理</a>
        </li>
        <li>
            <a href="#settings" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fas fa-cog"></i> 系统设置
            </a>
            <ul class="collapse list-unstyled" id="settings">
                <li><a href="settings.php">基本设置</a></li>
                <li><a href="settings.php?tab=seo">SEO设置</a></li>
                <li><a href="settings.php?tab=email">邮件设置</a></li>
                <li><a href="numbers.php">号码池列表</a></li>
                <li><a href="settings.php?tab=numbers">号码池设置</a></li>
            </ul>
        </li>
        <li>
            <a href="#extensions" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                <i class="fas fa-puzzle-piece"></i> 扩展管理
            </a>
            <ul class="collapse list-unstyled" id="extensions">
                <li><a href="plugins.php">插件管理</a></li>
                <li><a href="themes.php">主题管理</a></li>
            </ul>
        </li>
        <li>
            <a href="logs.php"><i class="fas fa-clipboard-list"></i> 系统日志</a>
        </li>
    </ul>
</nav>
