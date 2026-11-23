<?php
if (!hasPermission('tools')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}

// 分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 12;
$projects = getProjectsPaginated($page, $perPage);
$totalCount = getProjectsCount();
$totalPages = ceil($totalCount / $perPage);

$categories = getCategories();
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('project_list', 'admin') ?></h3>
        <button class="btn btn-primary" onclick="showProjectForm()"><?= __('project_add', 'admin') ?></button>
    </div>
    <?php if (empty($projects)): ?>
        <p><?= __('no_data', 'admin') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('project_image', 'admin') ?></th>
                    <th><?= __('project_title', 'admin') ?></th>
                    <th><?= __('project_category', 'admin') ?></th>
                    <th><?= __('project_sort', 'admin') ?></th>
                    <th><?= __('project_featured', 'admin') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $proj): ?>
                <?php
                $catName = '';
                foreach ($categories as $c) {
                    if ($c['slug'] === $proj['category_slug']) {
                        $catName = $c['name'];
                        break;
                    }
                }
                ?>
                <tr>
                    <td>
                        <?php if (!empty($proj['image'])): ?>
                            <img src="/<?= htmlspecialchars($proj['image']) ?>" style="max-width:60px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($proj['title']) ?></td>
                    <td><?= htmlspecialchars($catName) ?></td>
                    <td><?= $proj['sort_order'] ?? 0 ?></td>
                    <td><?= ($proj['featured'] ?? 0) ? __('project_yes', 'admin') : __('project_no', 'admin') ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editProject(<?= $proj['id'] ?>)"><?= __('edit', 'admin') ?></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProject(<?= $proj['id'] ?>)"><?= __('delete', 'admin') ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 分页导航 -->
        <?php if ($totalPages > 1): ?>
            <nav class="pagination-nav" style="margin-top:1.5rem;">
                <ul class="pagination">
                    <?php
                    $queryParams = $_GET;
                    ?>
                    <?php if ($page > 1): ?>
                        <?php $queryParams['page'] = $page - 1; ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?<?= http_build_query($queryParams) ?>"><i class="fas fa-chevron-left"></i> <?= __('page_prev', 'admin') ?></a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i> <?= __('page_prev', 'admin') ?></span></li>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php $queryParams['page'] = $p; ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?<?= http_build_query($queryParams) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <?php $queryParams['page'] = $page + 1; ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?<?= http_build_query($queryParams) ?>"><?= __('page_next', 'admin') ?> <i class="fas fa-chevron-right"></i></a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link"><?= __('page_next', 'admin') ?> <i class="fas fa-chevron-right"></i></span></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- 新增/编辑弹窗 -->
<div id="projectModal" class="modal">
    <div class="modal-content wide">
        <h3 id="projectModalTitle"><?= __('project_add', 'admin') ?></h3>
        <form id="projectForm">
            <input type="hidden" name="id" value="">
            <div class="form-group">
                <label><?= __('project_title', 'admin') ?></label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('project_summary', 'admin') ?></label>
                <textarea name="summary" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label><?= __('project_content', 'admin') ?></label>
                <textarea name="content" id="projectContent" class="form-control" rows="8"></textarea>
            </div>
            <div class="form-group">
                <label><?= __('project_image', 'admin') ?></label>
                <div class="input-group">
                    <input type="text" name="image" class="form-control" placeholder="<?= __('news_image_placeholder', 'admin') ?>" id="projectImage">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('projectImageUpload').click()">
                        <i class="fas fa-upload"></i> <?= __('upload', 'admin') ?>
                    </button>
                    <input type="file" id="projectImageUpload" accept="image/*" style="display:none" onchange="uploadProjectImage()">
                </div>
                <div id="projectImagePreview" style="margin-top:0.5rem;"></div>
            </div>
            <div class="form-group">
                <label><?= __('project_category', 'admin') ?></label>
                <select name="category_slug" class="form-control" required>
                    <option value=""><?= __('project_select_category', 'admin') ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('project_github', 'admin') ?></label>
                    <input type="url" name="github_url" class="form-control">
                </div>
                <div class="form-group">
                    <label><?= __('project_doc', 'admin') ?></label>
                    <input type="url" name="doc_url" class="form-control">
                </div>
                <div class="form-group">
                    <label><?= __('project_external', 'admin') ?></label>
                    <input type="url" name="external_url" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= __('project_sort', 'admin') ?></label>
                    <input type="number" name="sort_order" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label><?= __('project_featured', 'admin') ?></label>
                    <select name="featured" class="form-control">
                        <option value="1"><?= __('project_yes', 'admin') ?></option>
                        <option value="0"><?= __('project_no', 'admin') ?></option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="closeProjectModal()"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__PROJECT_LANG = {
    add_title: '<?= __('project_add', 'admin') ?>',
    edit_title: '<?= __('project_edit', 'admin') ?>',
    delete_confirm: '<?= __('project_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_success: '<?= __('success', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>',
    upload_fail: '<?= __('slide_image_upload_fail', 'admin') ?>',
    upload_btn: '<?= __('upload', 'admin') ?>',
    editor_placeholder: '<?= __('editor_placeholder', 'admin') ?>',
    network_error: '<?= __('network_error', 'admin') ?>'
};
</script>
<script src="assets/js/project-manage.js"></script>

