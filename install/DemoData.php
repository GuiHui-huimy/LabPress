<?php
declare(strict_types=1);

namespace LabPress\Install;

use PDO;

if (!defined('LABPRESS_INSTALLER')) {
    http_response_code(404);
    exit;
}

/**
 * LabPress 1.2.0 全新演示数据。
 *
 * 与旧版 labpressexample.sql 的演示内容完全分开；这里采用一套
 * “计算生物学实验室”主题的可直接替换内容，并在 projects 非空时跳过，
 * 保证安装失败重试不会重复插入。
 */
final class DemoData
{
    public static function seed(PDO $pdo, string $siteName, string $siteUrl, string $language = 'zh_CN'): void
    {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM `projects`')->fetchColumn();
        if ($count > 0) {
            return;
        }

        self::seedSiteConfig($pdo, $siteName);
        self::seedProjectCategories($pdo);
        self::seedProjects($pdo);
        self::seedPublications($pdo);
        self::seedNews($pdo);
        self::seedSlides($pdo);
        self::seedTools($pdo);
        self::seedNavigation($pdo);
        self::seedPlugins($pdo);
        self::seedFooter($pdo, $siteName);
    }

    private static function seedSiteConfig(PDO $pdo, string $siteName): void
    {
        $researchCards = [
            [
                'icon' => 'flask',
                'title' => '酶工程与蛋白设计',
                'desc' => '从序列分析、结构建模到突变效应评估，把分散的工具串成可复用的设计流程。',
            ],
            [
                'icon' => 'atom',
                'title' => '分子模拟与药物发现',
                'desc' => '围绕对接、动力学与相互作用分析，整理可追溯的批量计算与后处理管线。',
            ],
            [
                'icon' => 'microchip',
                'title' => '科研工具与基础设施',
                'desc' => '开发轻量的命令行工具、可视化组件与数据接口，让重复劳动交给程序。',
            ],
            [
                'icon' => 'book-open',
                'title' => '开放数据与协作',
                'desc' => '用清晰的项目文档、版本记录和开放数据，降低同行复现与协作的门槛。',
            ],
        ];
        $statItems = [
            ['icon' => 'fas fa-flask', 'label' => '在研项目', 'value' => '6'],
            ['icon' => 'fas fa-code', 'label' => '自研工具', 'value' => '3'],
            ['icon' => 'fas fa-book-open', 'label' => '开放笔记', 'value' => '20+'],
            ['icon' => 'fas fa-users', 'label' => '协作成员', 'value' => '5'],
        ];

        $configs = [
            'about_us' => "「{$siteName}」是一个以计算生物学方法与应用为线索的实验室主页。\n\n"
                . "这里记录正在推进的课题、可复用的分析流程、开源工具和阶段性的科研动态。"
                . "演示内容由 LabPress 安装器生成，可在后台逐项替换为团队真实介绍、研究方向与合作方式。",
            'research_title' => '研究方向',
            'research_subtitle' => '从分子机制到工具实现，构建可复现、可协作的科研工作流',
            'research_cards' => json_encode($researchCards, JSON_UNESCAPED_UNICODE),
            'stat_items' => json_encode($statItems, JSON_UNESCAPED_UNICODE),
            'stat_developers' => '5',
            'stat_layers' => '3',
            'stat_tools' => '3',
            'stat_workflows' => '20+',
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO `site_config` (`config_key`, `config_value`, `updated_at`)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`), `updated_at` = NOW()'
        );
        foreach ($configs as $key => $value) {
            $stmt->execute([$key, (string)$value]);
        }
    }

    private static function seedProjectCategories(PDO $pdo): void
    {
        $rows = [
            ['protein', '蛋白与酶工程', '序列分析、结构建模、突变设计与酶学性质预测。', 1],
            ['simulation', '分子模拟', '分子对接、动力学模拟、相互作用分析与批量后处理。', 2],
            ['software', '科研软件', '面向科研场景的轻量工具、可视化组件与自动化脚本。', 3],
            ['open-science', '开放科学', '数据整理、复现实验、文档建设与协作规范。', 4],
        ];
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO `project_categories` (`slug`, `name`, `description`, `sort_order`) VALUES (?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    private static function seedProjects(PDO $pdo): void
    {
        $rows = [
            [
                'title' => '酶热稳定性预测工作流',
                'summary' => '把结构预处理、能量计算与突变打分串成一条可复现的命令行流程。',
                'content' => "## 目标\n\n针对一批同源酶序列，快速评估单点突变对热稳定性的影响，并输出可排序的候选清单。\n\n"
                    . "## 流程\n\n1. 结构清洗与缺失残基补全\n2. 突变体批量建模\n3. 能量项与保守性特征汇总\n4. 生成实验优先级表\n\n"
                    . "## 当前状态\n\n流程已在示例数据上跑通，正在补充异常结构处理和结果可视化。",
                'image' => null,
                'category_slug' => 'protein',
                'featured' => 1,
                'github_url' => null,
                'doc_url' => null,
                'external_url' => null,
                'sort_order' => 1,
            ],
            [
                'title' => '配体筛选批处理管线',
                'summary' => '为大规模虚拟筛选准备的输入检查、并行对接与结果聚合工具。',
                'content' => "## 解决的问题\n\n当配体数量上千时，手工整理受体、配体和参数非常容易出错。\n\n"
                    . "## 主要能力\n\n- 自动检查配体格式与重复结构\n- 按批次并行执行对接\n- 汇总打分、相互作用与失败原因\n- 输出统一的 CSV 和图表\n\n"
                    . "## 后续计划\n\n加入多受体版本管理和可恢复的运行记录。",
                'image' => 'uploads/projects/projects-1786249605.png',
                'category_slug' => 'simulation',
                'featured' => 1,
                'github_url' => null,
                'doc_url' => null,
                'external_url' => null,
                'sort_order' => 2,
            ],
            [
                'title' => '结构查看器小组件',
                'summary' => '嵌入项目文档的轻量结构预览，支持残基高亮与突变位点标注。',
                'content' => "## 设计原则\n\n不依赖庞大的前端框架，用最小脚本完成结构预览和标注。\n\n"
                    . "## 功能\n\n- 读取常见结构格式\n- 按链、残基和突变位点着色\n- 导出静态截图用于组会与论文补充材料\n\n"
                    . "## 适用场景\n\n实验室内部文档、项目主页和教学示例。",
                'image' => null,
                'category_slug' => 'software',
                'featured' => 1,
                'github_url' => null,
                'doc_url' => null,
                'external_url' => null,
                'sort_order' => 3,
            ],
            [
                'title' => '实验室数据看板',
                'summary' => '汇总项目进度、计算任务和内容更新，给组会一个统一的观察入口。',
                'content' => "## 为什么需要它\n\n项目状态散落在聊天记录、表格和服务器日志里，组会前很难快速复盘。\n\n"
                    . "## 看板内容\n\n- 进行中项目与里程碑\n- 最近两周的内容更新\n- 计算任务完成情况\n- 待处理的数据与文档\n\n"
                    . "## 实现方式\n\n由 LabPress 后台统计接口提供数据，前端用图表呈现趋势。",
                'image' => null,
                'category_slug' => 'software',
                'featured' => 1,
                'github_url' => null,
                'doc_url' => null,
                'external_url' => null,
                'sort_order' => 4,
            ],
            [
                'title' => '酶学数据整理规范',
                'summary' => '为组内实验与计算结果建立统一的字段、命名和版本记录。',
                'content' => "## 背景\n\n同一个酶在不同成员手里常出现不同的命名和字段，导致后续分析反复清洗。\n\n"
                    . "## 规范内容\n\n- 酶编号、来源与突变命名\n- 实验条件与单位\n- 原始数据、处理脚本和结果文件的对应关系\n- 版本变更记录\n\n"
                    . "## 落地方式\n\n先从一个课题组的最小数据集开始，逐步补充模板和校验脚本。",
                'image' => null,
                'category_slug' => 'open-science',
                'featured' => 0,
                'github_url' => null,
                'doc_url' => null,
                'external_url' => null,
                'sort_order' => 5,
            ],
            [
                'title' => '组会文献共读计划',
                'summary' => '每周一篇方法学论文，记录问题、方法和可迁移到本组的实现点。',
                'content' => "## 形式\n\n每周选一篇与计算生物学方法相关的论文，由一位成员做 15 分钟导读。\n\n"
                    . "## 输出\n\n- 一页问题背景\n- 方法流程图\n- 可复现实验清单\n- 与本组课题的关联\n\n"
                    . "## 积累\n\n导读笔记统一归档到项目文档，形成可检索的方法库。",
                'image' => null,
                'category_slug' => 'open-science',
                'featured' => 0,
                'github_url' => null,
                'doc_url' => null,
                'external_url' => null,
                'sort_order' => 6,
            ],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO `projects`
             (`title`, `summary`, `content`, `image`, `category_slug`, `featured`, `github_url`, `doc_url`, `external_url`, `sort_order`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $base = strtotime('-45 days');
        foreach ($rows as $index => $row) {
            $stmt->execute([
                $row['title'],
                $row['summary'],
                $row['content'],
                $row['image'],
                $row['category_slug'],
                $row['featured'],
                $row['github_url'],
                $row['doc_url'],
                $row['external_url'],
                $row['sort_order'],
                date('Y-m-d H:i:s', $base + $index * 86400 * 4),
            ]);
        }
    }

    private static function seedPublications(PDO $pdo): void
    {
        $rows = [
            ['酶结构稳定性预测的流程化实现（示例）', 'Demo Lab; Zhang, L.; Li, M.', 'Journal of Computational Biology — Demo Issue', null, 1],
            ['面向高通量对接的结果聚合与质量控制（示例）', 'Demo Lab; Wang, Y.; Chen, R.', 'Methods in Silico — Demo Issue', null, 2],
            ['科研项目文档的可复现组织方式（示例）', 'Demo Lab', 'Open Research Notes — Demo Issue', null, 3],
            ['计算生物学工具链的版本管理实践（示例）', 'Demo Lab; Zhao, Q.', 'Lab Operations — Demo Issue', null, 4],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO `publications` (`title`, `authors`, `journal`, `link`, `sort_order`) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    private static function seedNews(PDO $pdo): void
    {
        $rows = [
            [
                'title' => '项目主页完成第一轮结构梳理',
                'summary' => '把在研项目、工具和开放数据拆成清晰的栏目，方便组内外快速了解实验室在做什么。',
                'content' => "这一轮主要调整了项目分类和信息层级：先说明问题，再给出方法、当前进展和可参与的方式。\n\n"
                    . "下一步会补充每个项目的复现实验说明和负责人。",
                'image' => 'uploads/news/news-1786248122.png',
                'author' => 'LabPress Demo',
                'date' => date('Y-m-d', strtotime('-6 days')),
                'link' => null,
                'sort_order' => 1,
            ],
            [
                'title' => '批处理管线加入失败任务重跑',
                'summary' => '对接任务中断后不再需要整批重跑，失败项可以单独恢复。',
                'content' => "新的运行记录会保存每一批的输入、参数和状态。重跑时只处理失败或缺失的条目，并保留原始日志。\n\n"
                    . "同时增加了结果文件的校验步骤，避免把空结果误当成成功。",
                'image' => 'uploads/news/news-1786249762.png',
                'author' => 'LabPress Demo',
                'date' => date('Y-m-d', strtotime('-3 days')),
                'link' => null,
                'sort_order' => 2,
            ],
            [
                'title' => '本周组会：如何整理可复现的方法笔记',
                'summary' => '从问题、输入、步骤、输出了四个层面，建立一份别人能照着运行的方法记录。',
                'content' => "组会记录了方法笔记的最小结构，并约定在项目文档中统一存放。\n\n"
                    . "示例模板会随后放到开放科学分类下，欢迎补充。",
                'image' => null,
                'author' => 'LabPress Demo',
                'date' => date('Y-m-d', strtotime('-1 days')),
                'link' => null,
                'sort_order' => 3,
            ],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO `news` (`title`, `summary`, `content`, `image`, `author`, `date`, `link`, `sort_order`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $stmt->execute(array_values($row));
        }
    }

    private static function seedSlides(PDO $pdo): void
    {
        $rows = [
            ['uploads/general/general-1786248935.png', '把科研流程做成可复用的基础设施', '项目、工具、数据与笔记，在同一个站点里持续积累。', 1],
            ['uploads/news/news-1786248122.png', '从问题到可复现的流程', '记录方法、版本和结果，让每一次计算都能被追溯。', 2],
            ['uploads/projects/projects-1786249605.png', '开放协作，持续迭代', '用清晰的项目文档降低协作门槛，让好方法被更多人使用。', 3],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO `slides` (`image`, `title`, `description`, `sort_order`) VALUES (?, ?, ?, ?)'
        );
        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    private static function seedTools(PDO $pdo): void
    {
        $tools = [
            [
                'name' => 'SeqPrep',
                'type' => '命令行工具',
                'icon' => 'fas fa-dna',
                'description' => '序列清洗、去重与格式转换的轻量工具，适合作为分析流程的第一步。',
                'features' => 'FASTA/CSV 互转；重复序列检测；批次命名规范化',
                'github' => null,
                'website' => null,
                'detail_link' => 'tool-detail.php?name=SeqPrep',
                'sort_order' => 1,
            ],
            [
                'name' => 'DockFlow',
                'type' => '分析管线',
                'icon' => 'fas fa-cubes',
                'description' => '把受体准备、配体筛选、批量对接和结果汇总组织成一条可恢复的流程。',
                'features' => '输入检查；并行执行；失败重跑；统一结果表',
                'github' => null,
                'website' => null,
                'detail_link' => 'tool-detail.php?name=DockFlow',
                'sort_order' => 2,
            ],
            [
                'name' => 'StructNote',
                'type' => '可视化组件',
                'icon' => 'fas fa-cube',
                'description' => '在项目文档中嵌入结构预览和残基标注，减少截图与手工说明。',
                'features' => '结构预览；链与残基着色；突变位点标注；静态图导出',
                'github' => null,
                'website' => null,
                'detail_link' => 'tool-detail.php?name=StructNote',
                'sort_order' => 3,
            ],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO `tools` (`name`, `type`, `icon`, `description`, `features`, `github`, `website`, `detail_link`, `sort_order`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($tools as $tool) {
            $stmt->execute([
                $tool['name'],
                $tool['type'],
                $tool['icon'],
                $tool['description'],
                $tool['features'],
                $tool['github'],
                $tool['website'],
                $tool['detail_link'],
                $tool['sort_order'],
            ]);
        }

        $details = [
            [
                'tool_name' => 'SeqPrep',
                'short_description' => '面向批量序列数据的清洗与格式转换工具。',
                'detailed_description' => "## 使用场景\n\n当上游导出的 FASTA 混有重复记录、异常字符或命名不一致时，先运行 SeqPrep 统一格式。\n\n"
                    . "## 设计取舍\n\n只处理确定性的清洗规则，不做比对和注释；复杂分析交给后续步骤。",
                'version' => '0.3.1',
                'language' => 'Python',
                'license' => 'MIT',
                'update_date' => date('Y-m-d', strtotime('-20 days')),
                'github' => null,
                'document' => null,
                'homepage' => null,
                'image' => null,
                'screenshot' => null,
                'citation_title' => null,
                'citation_url' => null,
            ],
            [
                'tool_name' => 'DockFlow',
                'short_description' => '可恢复的批量分子对接流程。',
                'detailed_description' => "## 工作方式\n\n将任务拆成准备、对接、汇总三个可独立检查的阶段。每个阶段都写入运行记录，失败后按记录恢复。\n\n"
                    . "## 输出\n\n统一的结果表、失败清单和一份可直接放进组会材料的摘要。",
                'version' => '0.8.0',
                'language' => 'Python / Bash',
                'license' => 'MIT',
                'update_date' => date('Y-m-d', strtotime('-9 days')),
                'github' => null,
                'document' => null,
                'homepage' => null,
                'image' => null,
                'screenshot' => null,
                'citation_title' => null,
                'citation_url' => null,
            ],
            [
                'tool_name' => 'StructNote',
                'short_description' => '嵌入文档的轻量结构标注组件。',
                'detailed_description' => "## 目标\n\n让结构图不再只是一张截图。读者可以在项目文档里直接看到链、残基和突变位点。\n\n"
                    . "## 输出\n\n交互式预览、静态图和可复用的标注配置。",
                'version' => '0.2.0',
                'language' => 'JavaScript',
                'license' => 'MIT',
                'update_date' => date('Y-m-d', strtotime('-4 days')),
                'github' => null,
                'document' => null,
                'homepage' => null,
                'image' => null,
                'screenshot' => null,
                'citation_title' => null,
                'citation_url' => null,
            ],
        ];
        $detailStmt = $pdo->prepare(
            'INSERT INTO `tool_details`
             (`tool_name`, `short_description`, `detailed_description`, `version`, `language`, `license`, `update_date`, `github`, `document`, `homepage`, `image`, `screenshot`, `citation_title`, `citation_url`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($details as $row) {
            $detailStmt->execute([
                $row['tool_name'],
                $row['short_description'],
                $row['detailed_description'],
                $row['version'],
                $row['language'],
                $row['license'],
                $row['update_date'],
                $row['github'],
                $row['document'],
                $row['homepage'],
                $row['image'],
                $row['screenshot'],
                $row['citation_title'],
                $row['citation_url'],
            ]);
        }

        $versions = [
            ['SeqPrep', '0.3.1', date('Y-m-d', strtotime('-20 days')), ['新增重复序列检测', '修复空文件处理']],
            ['DockFlow', '0.8.0', date('Y-m-d', strtotime('-9 days')), ['新增失败任务重跑', '结果表增加校验状态']],
            ['StructNote', '0.2.0', date('Y-m-d', strtotime('-4 days')), ['支持突变位点标注', '增加静态图导出']],
        ];
        $versionStmt = $pdo->prepare(
            'INSERT INTO `tool_versions` (`tool_name`, `version`, `date`, `changes`) VALUES (?, ?, ?, ?)'
        );
        foreach ($versions as $row) {
            $versionStmt->execute([$row[0], $row[1], $row[2], json_encode($row[3], JSON_UNESCAPED_UNICODE)]);
        }
    }

    private static function seedNavigation(PDO $pdo): void
    {
        $rows = [
            ['首页', 'index.php', '_self', 1, 1, 'header'],
            ['项目', 'projects.php', '_self', 2, 1, 'header'],
            ['成果', 'publications.php', '_self', 3, 1, 'header'],
            ['工具', 'tools.php', '_self', 4, 1, 'header'],
            ['动态', 'news.php', '_self', 5, 1, 'header'],
            ['关于', 'index.php#about', '_self', 6, 1, 'header'],
            ['LabPress', 'https://github.com/GuiHui-huimy/LabPress', '_blank', 1, 1, 'footer'],
            ['文档', 'docs/', '_blank', 2, 1, 'footer'],
        ];
        $stmt = $pdo->prepare(
            'INSERT INTO `nav_menu` (`title`, `url`, `target`, `sort_order`, `is_active`, `location`, `parent_id`) VALUES (?, ?, ?, ?, ?, ?, NULL)'
        );
        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    private static function seedPlugins(PDO $pdo): void
    {
        $rows = [
            ['footer-info', 'Footer Info', '可编辑页脚信息，支持多行文字、占位符与自定义样式。', '3.0.0', 'LabPress Community', 1, '>=1.3.0'],
            ['dynamic-multilang', 'Dynamic Multi-Language', '为导航、页脚、轮播图和站点介绍提供动态多语言翻译。', '3.0.0', 'LabPress Community', 0, '>=1.3.0'],
        ];
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO `plugins`
             (`slug`, `name`, `description`, `version`, `author`, `status`, `priority`, `requires_core`, `last_synced_at`)
             VALUES (?, ?, ?, ?, ?, ?, 10, ?, NOW())'
        );
        foreach ($rows as $row) {
            $stmt->execute($row);
        }
    }

    private static function seedFooter(PDO $pdo, string $siteName): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM `footer_info` WHERE `id` = 1');
        $stmt->execute();
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }
        $content = "© {year} {$siteName}. All rights reserved.\nPowered by LabPress";
        $stmt = $pdo->prepare(
            'INSERT INTO `footer_info` (`id`, `content`, `align`, `font_size`, `color`, `padding`)
             VALUES (1, ?, \'center\', \'0.9rem\', \'#666666\', \'0 20px\')'
        );
        $stmt->execute([$content]);
    }
}
