<?php
// 从控制器传递的 $data 数组中解压变量
extract($data); 
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <!-- 公告标题 -->
                    <h1 class="card-title text-center mb-4"><?php echo htmlspecialchars($announcement['title']); ?></h1>
                    
                    <!-- 公告元数据 -->
                    <div class="text-center text-muted border-bottom pb-3 mb-4">
                        <span>
                            <i class="fas fa-calendar-alt me-1"></i> 
                            发布于 <?php echo date('Y年m月d日', strtotime($announcement['created_at'])); ?>
                        </span>
                    </div>
                    
                    <!-- 公告内容 -->
                    <div class="announcement-content">
                        <?php 
                            // 直接输出HTML内容，因为后台编辑器保存的是HTML格式
                            // 注意：这里假设后台保存的内容是安全的。如果内容可以由非信任用户输入，需要进行过滤。
                            echo $announcement['content']; 
                        ?>
                    </div>
                    
                    <!-- 返回按钮 -->
                    <div class="text-center mt-5">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>返回首页
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 为公告内容增加一些基本样式 */
.announcement-content {
    line-height: 1.8;
    font-size: 1.1rem;
}
.announcement-content h1,
.announcement-content h2,
.announcement-content h3 {
    margin-top: 1.5em;
    margin-bottom: 0.8em;
    font-weight: bold;
}
.announcement-content p {
    margin-bottom: 1.2em;
}
.announcement-content ul,
.announcement-content ol {
    padding-left: 2em;
    margin-bottom: 1.2em;
}
.announcement-content blockquote {
    border-left: 4px solid #eee;
    padding-left: 1em;
    margin-left: 0;
    color: #666;
}
.announcement-content a {
    color: #0d6efd;
    text-decoration: none;
}
.announcement-content a:hover {
    text-decoration: underline;
}
</style>
