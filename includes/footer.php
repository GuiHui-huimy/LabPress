<footer class="site-footer">
    <div class="container footer-content">
        <div class="footer-copyright">
            <p><?= str_replace('{year}', date('Y'), __('footer_copyright')) ?></p>
            <p><?= __('footer_open_source') ?></p>
        </div>
        <div class="footer-quote">
            <p><?= __('footer_quote') ?></p>
            <cite><?= __('footer_quote_cite') ?></cite>
        </div>
            <div class="footer-links">
                <?php
                $footerMenus = getNavMenus('footer');
                foreach ($footerMenus as $menu):
                    $target = ($menu['target'] === '_blank') ? ' target="_blank" rel="noopener"' : '';
                    $menuItemHtml = sprintf(
                        '<a href="%s"%s>%s</a>',
                        htmlspecialchars($menu['url']),
                        $target,
                        htmlspecialchars($menu['title'])
                    );
                    $menuItemHtml = LabPress_Hooks::applyFilters('nav_menu_item', $menuItemHtml, $menu);
                    echo $menuItemHtml;
                endforeach;
                ?>
            </div>
    </div>
    <!-- 调试用，随后可删除 -->
    <!-- <div style="position:fixed; bottom:0; right:0; background:red; color:white; padding:5px; z-index:9999;">
        Current Locale: <?= getLocale() ?>
    </div> -->
    <?php LabPress_Hooks::doAction('footer_scripts'); ?>
</footer>