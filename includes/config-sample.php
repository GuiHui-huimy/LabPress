<?php
/**
 * LabPress v1.2.0 之后的推荐配置方式。
 *
 * 1. 复制 storage/config/site.example.php 为 storage/config/site.php；
 * 2. 填入数据库信息；
 * 3. includes/config.php 不再保存真实密码。
 *
 * 如果暂时不迁移，includes/config.php 里的 DB_* 常量仍可作为回退，
 * 但 v1.2.0 的 bootstrap 会优先读取 storage/config/site.php。
 */
