<?php
/**
 * Plugin Name: FooterInfo
 * Description: 可编辑页脚信息，支持多行文字、占位符、自定义样式（对齐、大小、颜色、边距）。
 * Version: 2.1.0
 * Author: LabPress Community
 */

// 安装逻辑（使用闭包，不区分大小写 slug）
LabPress_Hooks::addAction('plugin_activation', function($slug) {
    if (strtolower($slug) === strtolower('FooterInfo')) {
        $db = LabPress::db();
        $db->exec("CREATE TABLE IF NOT EXISTS `footer_info` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `content` TEXT NOT NULL,
            `align` VARCHAR(20) DEFAULT 'center' COMMENT '对齐方式：left/center/right',
            `font_size` VARCHAR(10) DEFAULT '0.9rem' COMMENT '字体大小',
            `color` VARCHAR(20) DEFAULT '#666666' COMMENT '文字颜色',
            `padding` VARCHAR(20) DEFAULT '0 20px' COMMENT '左右边距',
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // 插入默认数据（如果不存在 id=1 的记录）
        $stmt = $db->prepare("SELECT COUNT(*) FROM footer_info WHERE id = 1");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $default = "© {year} {site_name}. All rights reserved.\nPowered by LabPress\nContact: admin@example.com";
            $stmt = $db->prepare("INSERT INTO footer_info (id, content, align, font_size, color, padding) VALUES (1, ?, 'center', '0.9rem', '#666666', '0 20px')");
            $stmt->execute([$default]);
        }
    }
});

// 注册后台管理页面
LabPress::registerAdminPage('footer-info-settings', '📝 页脚信息', function() {
    if (!hasPermission('all')) {
        echo '<div class="card"><p>无权限</p></div>';
        return;
    }

    $db = LabPress::db();
    // 确保表存在（防止激活时未创建成功）
    $db->exec("CREATE TABLE IF NOT EXISTS `footer_info` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `content` TEXT NOT NULL,
        `align` VARCHAR(20) DEFAULT 'center' COMMENT '对齐方式：left/center/right',
        `font_size` VARCHAR(10) DEFAULT '0.9rem' COMMENT '字体大小',
        `color` VARCHAR(20) DEFAULT '#666666' COMMENT '文字颜色',
        `padding` VARCHAR(20) DEFAULT '0 20px' COMMENT '左右边距',
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 获取 id=1 的记录
    $row = $db->query("SELECT * FROM footer_info WHERE id = 1")->fetch();
    if (!$row) {
        $db->exec("INSERT INTO footer_info (id, content) VALUES (1, '© {year} {site_name}. All rights reserved.')");
        $row = $db->query("SELECT * FROM footer_info WHERE id = 1")->fetch();
    }
    $content = $row['content'] ?? '';
    $align = $row['align'] ?? 'center';
    $fontSize = $row['font_size'] ?? '0.9rem';
    $color = $row['color'] ?? '#666666';
    $padding = $row['padding'] ?? '0 20px';

    // 保存逻辑
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newContent = $_POST['footer_content'] ?? '';
        $newAlign = $_POST['align'] ?? 'center';
        $newFontSize = $_POST['font_size'] ?? '0.9rem';
        $newColor = $_POST['color'] ?? '#666666';
        $newPadding = $_POST['padding'] ?? '0 20px';
        $stmt = $db->prepare("UPDATE footer_info SET content = ?, align = ?, font_size = ?, color = ?, padding = ? WHERE id = 1");
        $stmt->execute([$newContent, $newAlign, $newFontSize, $newColor, $newPadding]);
        echo '<div class="alert alert-success">保存成功</div>';
        $content = $newContent;
        $align = $newAlign;
        $fontSize = $newFontSize;
        $color = $newColor;
        $padding = $newPadding;
    }
    ?>
    <div class="card">
        <div class="card-header">
            <h3>页脚信息编辑</h3>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="form-group">
                    <label>页脚内容（每行一条，支持 HTML，占位符：{year}、{site_name}）</label>
                    <textarea name="footer_content" class="form-control" rows="6" required><?= htmlspecialchars($content) ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>对齐方式</label>
                        <select name="align" class="form-control">
                            <option value="left" <?= $align === 'left' ? 'selected' : '' ?>>居左</option>
                            <option value="center" <?= $align === 'center' ? 'selected' : '' ?>>居中</option>
                            <option value="right" <?= $align === 'right' ? 'selected' : '' ?>>居右</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>字体大小</label>
                        <select name="font_size" class="form-control">
                            <option value="0.75rem" <?= $fontSize === '0.75rem' ? 'selected' : '' ?>>小 (0.75rem)</option>
                            <option value="0.9rem" <?= $fontSize === '0.9rem' ? 'selected' : '' ?>>中 (0.9rem)</option>
                            <option value="1.1rem" <?= $fontSize === '1.1rem' ? 'selected' : '' ?>>大 (1.1rem)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>文字颜色</label>
                        <input type="color" name="color" class="form-control" value="<?= htmlspecialchars($color) ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>左右边距</label>
                        <select name="padding" class="form-control">
                            <option value="0 10px" <?= $padding === '0 10px' ? 'selected' : '' ?>>10px</option>
                            <option value="0 20px" <?= $padding === '0 20px' ? 'selected' : '' ?>>20px</option>
                            <option value="0 30px" <?= $padding === '0 30px' ? 'selected' : '' ?>>30px</option>
                            <option value="0 40px" <?= $padding === '0 40px' ? 'selected' : '' ?>>40px</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">保存</button>
            </form>
            <hr>
            <h5>占位符说明</h5>
            <ul>
                <li><code>{year}</code> - 当前年份</li>
                <li><code>{site_name}</code> - 站点名称（在基础设置中配置）</li>
            </ul>
        </div>
    </div>
    <?php
});

// 前端输出
LabPress_Hooks::addAction('footer_scripts', function() {
    $db = LabPress::db();
    $row = $db->query("SELECT content, align, font_size, color, padding FROM footer_info WHERE id = 1")->fetch();
    if (empty($row) || empty($row['content'])) return;

    $content = $row['content'];
    $align = $row['align'] ?? 'center';
    $fontSize = $row['font_size'] ?? '0.9rem';
    $color = $row['color'] ?? '#666666';
    $padding = $row['padding'] ?? '0 20px';

    $year = date('Y');
    $siteName = LabPress::config('site_name', 'LabPress');
    $content = str_replace(['{year}', '{site_name}'], [$year, $siteName], $content);

    $lines = explode("\n", $content);
    $output = '<div style="text-align:' . htmlspecialchars($align) . '; ' .
              'font-size:' . htmlspecialchars($fontSize) . '; ' .
              'color:' . htmlspecialchars($color) . '; ' .
              'padding:' . htmlspecialchars($padding) . '; margin-top:20px;">';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $output .= '<div>' . $line . '</div>';
        }
    }
    $output .= '</div>';
    echo $output;
});