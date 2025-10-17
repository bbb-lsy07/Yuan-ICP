<?php extract($data); ?>
<div class="header">
    <h1 class="holographic-text"><?php echo htmlspecialchars($announcement['title']); ?></h1>
    <p class="note">发布于：<?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?></p>
</div>
<div class="content card-effect" style="text-align:left; max-width: 900px; line-height: 1.8;">
    <?php echo $announcement['content']; // Backend editor outputs HTML, display directly ?>
</div>
<div style="text-align: center; margin-top: 2rem;">
    <a href="announcements.php" class="glow-button page-transition-link">返回公告列表</a>
</div>