-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-08-11 17:30:20
-- 服务器版本： 8.0.36
-- PHP 版本： 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `labpressexample`
--

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `slug` varchar(50) NOT NULL COMMENT '英文标识，如 enzyme, md',
  `name` varchar(100) NOT NULL COMMENT '显示名称，如 酶工程 - Enzyme Engineering',
  `description` text COMMENT '分类详情描述，将显示在分类详情区',
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `categories`
--

INSERT INTO `categories` (`slug`, `name`, `description`, `sort_order`) VALUES
('chem_ai', '计算化学与ML方法 - Computational Chemistry & ML', 'ORCA + MultiWFN 批量 RESP 电荷计算，FlexTrimmer 机器学习截短预测，探索蛋白质语言模型在功能预测中的应用。', 4),
('dev', '计算生物学 - Computational Bio Dev', '自研 C++ 蛋白质解析框架、特征计算引擎、BioCUDA GPU 加速库，以及 Rosetta Python 自动化接口。', 3),
('enzyme', '酶工程 - Enzyme Engineering', '【应用】集成序列分析、突变效应预测与催化活性评估的计算工作流，为湿实验酶活改造提供理性设计指导。', 1),
('md', '动力学模拟与对接 - MD Simulation & Molecular Docking', '覆盖 Vina 大规模虚拟筛选、PLIP 相互作用分析、GROMACS 四种体系全自动模拟，以及 RMSD/RMSF/MM‑PBSA 全套后处理。', 2);

-- --------------------------------------------------------

--
-- 表的结构 `footer_info`
--

CREATE TABLE `footer_info` (
  `id` int NOT NULL,
  `content` text NOT NULL,
  `align` varchar(20) DEFAULT 'center' COMMENT '对齐方式：left/center/right',
  `font_size` varchar(10) DEFAULT '0.9rem' COMMENT '字体大小',
  `color` varchar(20) DEFAULT '#666666' COMMENT '文字颜色',
  `padding` varchar(20) DEFAULT '0 20px' COMMENT '左右边距',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `footer_info`
--

INSERT INTO `footer_info` (`id`, `content`, `align`, `font_size`, `color`, `padding`, `updated_at`) VALUES
(1, '© {year} {site_name}. All rights reserved.\nPowered by LabPress\nContact: admin@example.com', 'center', '0.9rem', '#666666', '0 20px', '2026-08-09 14:45:00');

-- --------------------------------------------------------

--
-- 表的结构 `ml_translations`
--

CREATE TABLE `ml_translations` (
  `id` int NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` varchar(100) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `translated_value` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `ml_translations`
--

INSERT INTO `ml_translations` (`id`, `table_name`, `record_id`, `field_name`, `locale`, `translated_value`) VALUES
(1, 'nav_menu', '1', 'title', 'en_US', 'HUI-MY Site'),
(2, 'nav_menu', '2', 'title', 'en_US', 'HomePage'),
(3, 'nav_menu', '3', 'title', 'en_US', 'Tools'),
(4, 'nav_menu', '4', 'title', 'en_US', 'Achievement'),
(5, 'nav_menu', '5', 'title', 'en_US', 'Project'),
(6, 'nav_menu', '8', 'title', 'en_US', 'News'),
(7, 'nav_menu', '9', 'title', 'en_US', 'Contacts'),
(8, 'nav_menu', '10', 'title', 'en_US', 'HUI-MY Site'),
(9, 'nav_menu', '11', 'title', 'en_US', 'HUI-MY Blog'),
(10, 'nav_menu', '12', 'title', 'en_US', 'HUI-MY Community'),
(11, 'nav_menu', '13', 'title', 'en_US', 'Cite US'),
(12, 'nav_menu', '14', 'title', 'en_US', 'Cooperative Agency'),
(13, 'slides', '1', 'title', 'en_US', 'Structural Biology and Computational Biology'),
(14, 'slides', '1', 'description', 'en_US', 'Deciphering structural information through data, exploring the structure of substances, and revolutionizing new drug discoveries'),
(15, 'slides', '2', 'title', 'en_US', 'ProteinTrim trimming tool'),
(16, 'slides', '2', 'description', 'en_US', 'A protein trimming assistance tool based on deep learning error correction, with a one-stop workflow'),
(17, 'slides', '3', 'title', 'en_US', 'The latest achievements of the research team'),
(18, 'slides', '3', 'description', 'en_US', 'Several papers have been published in internationally renowned journals.'),
(19, 'site_config', 'about_us', 'config_value', 'en_US', '<p>This is a personal-driven computational biology laboratory that focuses on full-stack technology development ranging from <strong>enzyme engineering applications, molecular simulation pipelines</strong> to <strong>C++/CUDA high-performance tools at the core level</strong>. We not only utilize existing software, but also strive to create reusable underlying infrastructure for computational biology.</p><p>Currently, we have independently developed the protein analysis framework <strong>Protein Phaser</strong>, the complete set of automated GROMACS simulation and data analysis tools, the Vina virtual screening pipeline, the FlexTrimmer protein truncation prediction tool, and the <strong>BioCUDA</strong> general GPU acceleration library that is under construction. At the same time, we maintain a <a href=\"https://docs.huimy.top\">molecular dynamics theory tutorial</a> and an open-source toolset, aiming to lower the threshold of computational biology by making knowledge publicly available.</p>'),
(20, 'site_config', 'research_title', 'config_value', 'en_US', 'Research Direction'),
(21, 'site_config', 'research_subtitle', 'config_value', 'en_US', 'From the rational design of enzyme engineering at the application layer to the high-performance computing engine at the infrastructure layer, a complete computational biology technology stack has been constructed.'),
(22, '__custom', 'learn_more', 'text', 'en_US', 'Learn More'),
(23, '__custom', 'view_details', 'text', 'en_US', 'View Details'),
(24, '__custom', 'loading_publications', 'text', 'en_US', 'Loading latest publications...'),
(25, '__custom', 'footer_copyright', 'text', 'en_US', '© 2017-{year} HUI-MY.LAB. All rights reserved.'),
(26, '__custom', 'footer_open_source', 'text', 'en_US', 'Based on open source spirit, dedicated to the development and sharing of bioinformatics tools.'),
(27, '__custom', 'footer_quote', 'text', 'en_US', '\"Biological data itself does not speak; computational biology gives it the language of life.\"'),
(28, '__custom', 'footer_quote_cite', 'text', 'en_US', '—— \"Cell\"'),
(29, '__custom', 'about_us_title', 'text', 'en_US', 'About Us'),
(30, '__custom', 'projects_title', 'text', 'en_US', 'Projects and Achievements'),
(31, '__custom', 'projects_subtitle', 'text', 'en_US', 'Self-developed foundational software, databases, and acceleration libraries, as well as applied research in enzyme engineering and molecular simulation.'),
(32, '__custom', 'publications_title', 'text', 'en_US', 'Latest Publications'),
(33, '__custom', 'hero_slide1_title', 'text', 'en_US', 'Automated Design of Enzyme Engineering Workflow'),
(34, '__custom', 'hero_slide1_desc', 'text', 'en_US', 'From sequence conservation analysis to mutation effect prediction, building a computation-driven rational enzyme engineering pipeline.'),
(35, '__custom', 'hero_slide2_title', 'text', 'en_US', 'Molecular Simulation and Virtual Screening Platform'),
(36, '__custom', 'hero_slide2_desc', 'text', 'en_US', 'Supports Vina docking + PLIP analysis + GROMACS multi-system automated simulation for libraries of tens of thousands of molecules.'),
(37, '__custom', 'hero_slide3_title', 'text', 'en_US', 'Self-developed C++/CUDA Computational Biology Toolchain'),
(38, '__custom', 'hero_slide3_desc', 'text', 'en_US', 'Protein Phaser, BioCUDA, Vina-CUDA, high-performance underlying frameworks.'),
(39, '__custom', 'hero_slide4_title', 'text', 'en_US', 'Integration of Computational Chemistry and Machine Learning'),
(40, '__custom', 'hero_slide4_desc', 'text', 'en_US', 'RESP charge calculation, FlexTrimmer protein truncation prediction, protein language model exploration.'),
(62, '__custom', '自研工具/框架', 'text', 'en_US', 'self-innovate tools/frameworks'),
(63, '__custom', '核心技术层', 'text', 'en_US', 'Core Technology Layer'),
(64, '__custom', '分析工作流', 'text', 'en_US', 'Analyze workflow'),
(65, '__custom', '独立开发者', 'text', 'en_US', 'Independent developer'),
(66, '__custom', '酶工程与蛋白设计', 'text', 'en_US', 'Enzyme Engineering and Protein Design'),
(67, '__custom', '集成 FoldX、DynaMut2、CataPro、DLKcat 等工具，搭建从序列保守性分析到突变效应预测的自动化工作流，为湿实验提供理性设计建议。', 'text', 'en_US', 'Integrating tools such as FoldX, DynaMut2, CataPro, and DLKcat, we have established an automated workflow from sequence conservation analysis to mutation effect prediction, providing rational design suggestions for wet experiments.'),
(68, '__custom', '分子模拟与药物发现', 'text', 'en_US', 'Molecular Simulation and Drug Discovery'),
(69, '__custom', '覆盖 Vina 大规模虚拟筛选、PLIP 相互作用分析、GROMACS 四种体系全自动模拟，以及 RMSD/RMSF/MM‑PBSA 全套后处理。', 'text', 'en_US', 'It covers Vina\'s large-scale virtual screening, PLIP interaction analysis, GROMACS\' automatic simulation of four systems, as well as the complete set of post-processing including RMSD/RMSF/MM-PBSA.'),
(70, '__custom', '计算生物学软件开发', 'text', 'en_US', 'Development of computational biology software'),
(71, '__custom', '自研 C++ 蛋白质解析框架 (Protein Phaser)、特征计算引擎、BioCUDA GPU 加速库，以及 Rosetta Python 自动化接口。', 'text', 'en_US', 'Self-developed C++ protein parsing framework (Protein Phaser), feature calculation engine, BioCUDA GPU acceleration library, and Rosetta Python automation interface.'),
(72, '__custom', '计算化学与AI方法', 'text', 'en_US', 'Computational Chemistry and AI Methods'),
(73, '__custom', 'ORCA + MultiWFN 批量 RESP 电荷计算，FlexTrimmer 机器学习截短预测，探索蛋白质语言模型在功能预测中的应用。', 'text', 'en_US', 'ORCA + MultiWFN 进行批量 RESP 电荷计算，使用 FlexTrimmer 进行机器学习截短预测，探索蛋白质语言模型在功能预测中的应用。');

-- --------------------------------------------------------

--
-- 表的结构 `nav_menu`
--

CREATE TABLE `nav_menu` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL COMMENT '显示文字',
  `url` varchar(500) NOT NULL COMMENT '链接',
  `target` varchar(20) DEFAULT '_self' COMMENT '打开方式',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `location` varchar(20) NOT NULL DEFAULT 'header' COMMENT '菜单位置：header/footer',
  `parent_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='导航菜单';

--
-- 转存表中的数据 `nav_menu`
--

INSERT INTO `nav_menu` (`id`, `title`, `url`, `target`, `sort_order`, `is_active`, `location`, `parent_id`) VALUES
(1, '主站', 'https://www.huimy.top', '_blank', 1, 1, 'header', NULL),
(2, '首页', '/index.php', '_self', 2, 1, 'header', NULL),
(3, '工具', '/tools.php', '_self', 3, 1, 'header', NULL),
(4, '成果', '/publications.php', '_self', 4, 1, 'header', NULL),
(5, '项目', '/projects.php', '_self', 5, 1, 'header', NULL),
(8, '科研动态', '/news.php', '_self', 8, 1, 'header', NULL),
(9, '联系', 'https://www.huimy.top/cv.html', '_blank', 9, 1, 'header', NULL),
(10, 'HUI-MY 主站', 'https://www.huimy.top', '_blank', 1, 1, 'footer', NULL),
(11, 'HUI-MY 博客', 'https://blog.huimy.top', '_blank', 2, 1, 'footer', NULL),
(12, 'HUI-MY 论坛', 'https://comm.huimy.top', '_blank', 3, 1, 'footer', NULL),
(13, '引用我们', 'https://comm.huimy.top', '_blank', 4, 1, 'footer', NULL),
(14, '合作机构', 'https://comm.huimy.top', '_blank', 5, 1, 'footer', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `news`
--

CREATE TABLE `news` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(300) NOT NULL,
  `summary` text NOT NULL,
  `content` text COMMENT '详细内容（可含HTML）',
  `image` varchar(255) DEFAULT NULL COMMENT '配图路径',
  `author` varchar(100) DEFAULT 'HUI-MY.LAB',
  `date` date NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='科研动态';

--
-- 转存表中的数据 `news`
--

INSERT INTO `news` (`id`, `title`, `summary`, `content`, `image`, `author`, `date`, `link`, `sort_order`) VALUES
(1, '蛋白质结构预测新方法取得突破', '我们提出了一种基于深度学习的蛋白质结构预测框架，在多个基准数据集上显著提升了精度。', '', 'uploads/news/news-1786249762.png', '张教授', '2024-03-15', 'https://blog.huimy.top/article/123', 0),
(2, '团队新工具 ProteinTrim 发布 v2.5', 'ProteinTrim v2.5 正式发布，新增二级结构自动识别和批量处理功能，大幅提升工作效率。', '<p>ProteinTrim v2.5 详细更新日志：<br>1. 新增二级结构自动识别<br>2. 支持批量处理多个PDB文件<br>3. 优化内存占用<br>4. 修复已知BUG</p>', 'images/news/proteintrim.png', '李博士', '2024-01-20', 'https://blog.huimy.top/article/124', 0),
(3, '参与国际基因组学会议 ICG-16', '团队成员受邀在第16届国际基因组学会议上作报告，分享最新研究进展。', '<p>团队在ICG-16上作了题为\"Integrative Genomics of Aging\"的报告，分享了最新的多组学数据整合方法，并宣布了新的数据库项目。</p>', 'images/news/icg16.jpg', 'HUI-MY.LAB', '2023-12-10', 'https://blog.huimy.top/article/125', 0);

-- --------------------------------------------------------

--
-- 表的结构 `plugins`
--

CREATE TABLE `plugins` (
  `id` int UNSIGNED NOT NULL,
  `slug` varchar(100) NOT NULL COMMENT '插件目录名',
  `name` varchar(255) NOT NULL COMMENT '插件显示名称',
  `description` text,
  `version` varchar(50) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=停用 1=启用',
  `installed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `plugins`
--

INSERT INTO `plugins` (`id`, `slug`, `name`, `description`, `version`, `author`, `status`, `installed_at`, `updated_at`) VALUES
(1, 'hello-word', 'Hello World', '在页面底部添加问候语，并修改新闻标题。', '1.0.0', 'LabPress', 0, '2026-08-09 01:34:15', '2026-08-09 03:32:07'),
(10, 'FooterInfo', 'FooterInfo', '可编辑页脚信息，支持多行文字、占位符、自定义样式（对齐、大小、颜色、边距）。', '2.1.0', 'LabPress Community', 1, '2026-08-09 14:44:56', '2026-08-09 14:44:59'),
(15, 'mltest', 'ML Test', '', '1.0', '', 0, '2026-08-11 02:25:10', '2026-08-11 02:25:50'),
(17, 'dynamic-multilang', 'Dynamic Multi-Language', '为导航菜单、页脚链接、轮播图、关于我们、研究方向提供动态多语言翻译管理。', '2.0.0', 'LabPress Community', 1, '2026-08-11 04:18:54', '2026-08-11 07:07:00');

-- --------------------------------------------------------

--
-- 表的结构 `projects`
--

CREATE TABLE `projects` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text NOT NULL COMMENT '卡片简短描述',
  `content` text COMMENT 'Markdown 正文详情',
  `image` varchar(255) DEFAULT NULL COMMENT '展示图片路径',
  `category_slug` varchar(50) NOT NULL COMMENT '分类标识',
  `featured` tinyint(1) DEFAULT '1' COMMENT '是否在首页展示（1=是，0=否）',
  `github_url` varchar(255) DEFAULT NULL,
  `doc_url` varchar(255) DEFAULT NULL,
  `external_url` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `projects`
--

INSERT INTO `projects` (`id`, `title`, `summary`, `content`, `image`, `category_slug`, `featured`, `github_url`, `doc_url`, `external_url`, `sort_order`, `created_at`) VALUES
(1, '酶工程全流程设计工作流', '集成序列检索、比对、保守性分析与突变筛选的自动化管线', '', 'uploads/general/general-1786248935.png', 'enzyme', 1, 'https://github.com/yourrepo', 'https://docs.huimy.top', '', 1, '2026-08-07 07:46:03'),
(2, 'FoldX 批量突变扫描与稳定性预测', '自动化FoldX突变扫描，高通量预测ΔΔG', '', 'uploads/projects/projects-1786249605.png', 'enzyme', 1, 'https://github.com/yourrepo', '', '', 2, '2026-08-07 07:46:03'),
(3, 'DynaMut2 蛋白动力学突变效应分析', '预测突变对蛋白柔性与稳定性的影响', '', 'uploads/projects/projects-1786249630.png', 'enzyme', 1, 'https://github.com/yourrepo', '', '', 3, '2026-08-07 07:46:03'),
(4, '基于CataPro / DLKcat 催化活性的批量分析', '大规模预测Km/Kcat，开发优化分析脚本', '', 'uploads/projects/projects-1786249720.png', 'enzyme', 1, 'https://github.com/yourrepo', '', '', 4, '2026-08-07 07:46:03'),
(5, '关键氨基酸识别与保守性分析工具包', '多序列比对+保守性评分，辅助突变设计', '', 'uploads/projects/projects-1786249736.png', 'enzyme', 1, 'https://github.com/yourrepo', '', '', 5, '2026-08-07 07:46:03'),
(6, '蛋白-配体虚拟筛选 (Vina) 自动化工作流', '从靶点口袋到大规模对接的高通量流程', NULL, 'uploads/gallery/Gromacs/Docking.jpg', 'md', 1, 'https://github.com/yourrepo', NULL, NULL, 1, '2026-08-07 07:46:03'),
(7, 'PLIP 批量对接作用力分析', '自动解析对接结果，批量分析非共价作用', NULL, 'uploads/gallery/Gromacs/PLIP.png', 'md', 1, 'https://github.com/yourrepo', NULL, NULL, 2, '2026-08-07 07:46:03'),
(8, 'GROMACS 多体系全自动模拟框架', '支持4种体系，全程自动化MD模拟', NULL, 'uploads/gallery/Gromacs/MD_run.png', 'md', 1, 'https://github.com/yourrepo', NULL, NULL, 3, '2026-08-07 07:46:03'),
(9, '分子动力学数据分析全家桶', 'RMSD/RMSF/Rg/MMPBSA等全套一键生成', NULL, 'uploads/gallery/Gromacs/MD_Ana.jpg', 'md', 1, 'https://github.com/yourrepo', NULL, NULL, 4, '2026-08-07 07:46:03'),
(10, '分子动力学底层理论文档', '从统计物理推导MD算法的深入学习教程', NULL, 'uploads/gallery/Gromacs/MDdoc.png', 'md', 1, 'https://github.com/yourrepo', 'https://docs.huimy.top', NULL, 5, '2026-08-07 07:46:03'),
(11, 'Protein Phaser - 蛋白质文件解析C++框架', '基于ciflib的高性能PDB/mmCIF解析开发框架', NULL, 'uploads/gallery/ComputBio/proteinPhare.png', 'dev', 1, 'https://github.com/yourrepo', NULL, NULL, 1, '2026-08-07 07:46:03'),
(12, '蛋白质特征计算 C++ 引擎', '口袋中心、SASA等几何特征计算', NULL, 'uploads/gallery/ComputBio/FeatureEng.png', 'dev', 1, 'https://github.com/yourrepo', NULL, NULL, 2, '2026-08-07 07:46:03'),
(13, 'BioCUDA 计算生物学加速引擎', '基于C++/CUDA的GPU加速库', NULL, 'uploads/gallery/ComputBio/BioCuda.png', 'dev', 1, 'https://github.com/yourrepo', NULL, NULL, 3, '2026-08-07 07:46:03'),
(14, 'Vina-CUDA 分子对接GPU加速', '将Vina核心移植到CUDA，提升吞吐量', NULL, 'uploads/gallery_dev-04.jpg', 'dev', 1, 'https://github.com/yourrepo', NULL, NULL, 4, '2026-08-07 07:46:03'),
(15, 'Rosetta Python 流程化对接与分析包', '自动化构象采样、聚类与分析', NULL, 'uploads/gallery/ComputBio/Rosetta.png', 'dev', 1, 'https://github.com/yourrepo', NULL, NULL, 5, '2026-08-07 07:46:03'),
(16, 'RESP 电荷计算工作流 (ORCA + MultiWFN)', '批量计算RESP电荷，用于力场参数化', NULL, 'uploads/gallery/ComputChem/Com_Resp.png', 'chem_ai', 1, 'https://github.com/yourrepo', NULL, NULL, 1, '2026-08-07 07:46:03'),
(17, 'FlexTrimmer - 蛋白质序列修剪预测框架', '基于ML预测蛋白质结构区域柔性', NULL, 'uploads/gallery/ComputChem/Com_Flex.png', 'chem_ai', 1, 'https://github.com/yourrepo', NULL, NULL, 2, '2026-08-07 07:46:03'),
(18, 'ResNet - 残差神经网络 (规划中)', '利用残差神经网络预测大分子小分子相关特征', NULL, 'uploads/gallery/ComputChem/ResNet.png', 'chem_ai', 1, 'https://github.com/yourrepo', NULL, NULL, 3, '2026-08-07 07:46:03');

-- --------------------------------------------------------

--
-- 表的结构 `project_categories`
--

CREATE TABLE `project_categories` (
  `slug` varchar(50) NOT NULL COMMENT '英文标识，如 enzyme, md, dev, chem_ai',
  `name` varchar(100) NOT NULL COMMENT '显示名称，如 酶工程 - Enzyme Engineering',
  `description` text COMMENT '分类详情描述，将显示在分类详情区',
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 转存表中的数据 `project_categories`
--

INSERT INTO `project_categories` (`slug`, `name`, `description`, `sort_order`) VALUES
('chem_ai', '计算化学与ML方法 - Computational Chemistry & ML', 'ORCA + MultiWFN 批量 RESP 电荷计算，FlexTrimmer 机器学习截短预测，探索蛋白质语言模型在功能预测中的应用。', 4),
('dev', '计算生物学 - Computational Bio Dev', '自研 C++ 蛋白质解析框架、特征计算引擎、BioCUDA GPU 加速库，以及 Rosetta Python 自动化接口。', 3),
('enzyme', '酶工程 - Enzyme Engineering', '【应用】集成序列分析、突变效应预测与催化活性评估的计算工作流，为湿实验酶活改造提供理性设计指导。', 1),
('md', '动力学模拟与对接 - MD Simulation & Molecular Docking', '覆盖 Vina 大规模虚拟筛选、PLIP 相互作用分析、GROMACS 四种体系全自动模拟，以及 RMSD/RMSF/MM‑PBSA 全套后处理。', 2);

-- --------------------------------------------------------

--
-- 表的结构 `publications`
--

CREATE TABLE `publications` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(500) NOT NULL,
  `authors` text NOT NULL,
  `journal` varchar(300) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='最新成果/论文';

--
-- 转存表中的数据 `publications`
--

INSERT INTO `publications` (`id`, `title`, `authors`, `journal`, `link`, `sort_order`) VALUES
(1, '深度学习在蛋白质结构预测中的应用进展', '张三, 李四, 王五', '生物信息学杂志', 'https://doi.org/10.12345/example1', 0),
(2, '基于GPU加速的分子对接方法研究', '赵六, 孙七', '计算生物学学报', 'https://doi.org/10.12345/example2', 0);

-- --------------------------------------------------------

--
-- 表的结构 `site_config`
--

CREATE TABLE `site_config` (
  `config_key` varchar(100) NOT NULL,
  `config_value` text COMMENT '配置值（支持HTML）',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='站点基础配置';

--
-- 转存表中的数据 `site_config`
--

INSERT INTO `site_config` (`config_key`, `config_value`, `updated_at`) VALUES
('about_us', '这是一个由个人驱动的计算生物学实验室，专注于从酶工程应用、分子模拟管线到底层C++/CUDA高性能工具的全栈技术研发。我们不只使用现有软件，更致力于为计算生物学打造可复用的底层基础设施。\n目前已经独立开发了蛋白质解析框架 Protein Phaser、自动化 GROMACS 模拟与数据分析全家桶、Vina 虚拟筛选 pipeline、FlexTrimmer 蛋白截短预测工具，以及正在构建的BioCUDA 通用 GPU 加速库。同时维护着一个和开源工具集，希望通过公开知识降低计算生物学的门槛。', '2026-08-11 04:04:45'),
('language', 'en_US', '2026-08-11 07:30:28'),
('ml_source_locale', 'zh_CN', '2026-08-09 14:45:09'),
('research_cards', '[{\"icon\":\"flask\",\"title\":\"酶工程与蛋白设计\",\"desc\":\"集成 FoldX、DynaMut2、CataPro、DLKcat 等工具，搭建从序列保守性分析到突变效应预测的自动化工作流，为湿实验提供理性设计建议。\"},{\"icon\":\"atom\",\"title\":\"分子模拟与药物发现\",\"desc\":\"覆盖 Vina 大规模虚拟筛选、PLIP 相互作用分析、GROMACS 四种体系全自动模拟，以及 RMSD/RMSF/MM‑PBSA 全套后处理。\"},{\"icon\":\"microchip\",\"title\":\"计算生物学软件开发\",\"desc\":\"自研 C++ 蛋白质解析框架 (Protein Phaser)、特征计算引擎、BioCUDA GPU 加速库，以及 Rosetta Python 自动化接口。\"},{\"icon\":\"brain\",\"title\":\"计算化学与AI方法\",\"desc\":\"ORCA + MultiWFN 批量 RESP 电荷计算，FlexTrimmer 机器学习截短预测，探索蛋白质语言模型在功能预测中的应用。\"},{\"icon\":\"asdWD\",\"title\":\"wadwad\",\"desc\":\"asdwdawef\"}]', '2026-08-11 07:34:48'),
('research_subtitle', '从应用层的酶工程理性设计，到基础设施层的高性能计算引擎，构建完整的计算生物学技术栈', '2026-08-08 15:35:24'),
('research_title', '研究方向', '2026-08-08 15:35:24'),
('site_name', 'HUI-MY.LAB', '2026-08-08 15:35:24'),
('site_title', 'HUI MY-计算生物学科研页', '2026-08-09 03:57:32'),
('stat_developers', '1', '2026-08-08 15:35:24'),
('stat_items', '[{\"icon\":\"fas fa-tools\",\"label\":\"自研工具/框架\",\"value\":\"4+\"},{\"icon\":\"fas fa-layer-group\",\"label\":\"核心技术层\",\"value\":\"3\"},{\"icon\":\"fas fa-project-diagram\",\"label\":\"分析工作流\",\"value\":\"20+\"},{\"icon\":\"fas fa-user\",\"label\":\"独立开发者\",\"value\":\"1\"}]', '2026-08-08 15:45:52'),
('stat_layers', '3', '2026-08-08 15:35:24'),
('stat_tools', '4+', '2026-08-08 15:35:24'),
('stat_workflows', '20+', '2026-08-08 15:35:24'),
('title_format', '{page} - {site}', '2026-08-09 03:52:06'),
('title_separator', ' - ', '2026-08-09 03:48:43');

-- --------------------------------------------------------

--
-- 表的结构 `slides`
--

CREATE TABLE `slides` (
  `id` int UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `title` varchar(300) NOT NULL,
  `description` text NOT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='首页轮播图';

--
-- 转存表中的数据 `slides`
--

INSERT INTO `slides` (`id`, `image`, `title`, `description`, `sort_order`) VALUES
(1, 'images/slide-1771610110.png', '结构生物学与计算生物学', '用数据解密结构信息，探索物质结构，革新药物新发现', 0),
(2, 'images/slide-1771663308.png', 'ProteinTrim修剪工具', '基于深度学习纠错的蛋白质修剪辅助工具，一站式工作流', 0),
(3, 'images/slide3.jpg', '课题组最新成果', '多篇论文发表于国际权威期刊', 0);

-- --------------------------------------------------------

--
-- 表的结构 `tools`
--

CREATE TABLE `tools` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '工具名称（唯一）',
  `type` varchar(20) NOT NULL DEFAULT '软件',
  `icon` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `features` text,
  `github` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `detail_link` varchar(255) NOT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='工具列表';

--
-- 转存表中的数据 `tools`
--

INSERT INTO `tools` (`id`, `name`, `type`, `icon`, `description`, `features`, `github`, `website`, `detail_link`, `sort_order`) VALUES
(1, 'ProteinTrim', '软件', 'fas fa-cut', '蛋白质结构智能修剪工具，基于二级结构和能量最小化原则，智能识别并保留功能核心区域，去除柔性区域，提高后续分析的准确性。支持PDB、MMCIF格式，提供3D可视化界面，可导出修剪后的结构。', '二级结构分析、能量最小化、批量处理、GPU加速。', 'https://github.com/your/proteintrim', 'https://dock.huimy.top/proteintrim', 'ProteinTrim.html', 0),
(2, 'huisu', '软件', NULL, '问问', 'wddf', '', '', '#', 0);

-- --------------------------------------------------------

--
-- 表的结构 `tool_details`
--

CREATE TABLE `tool_details` (
  `id` int UNSIGNED NOT NULL,
  `tool_name` varchar(100) NOT NULL,
  `short_description` text,
  `detailed_description` text,
  `version` varchar(30) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `license` varchar(50) DEFAULT NULL,
  `update_date` date DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `homepage` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `citation_title` varchar(500) DEFAULT NULL,
  `citation_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='工具详细信息';

--
-- 转存表中的数据 `tool_details`
--

INSERT INTO `tool_details` (`id`, `tool_name`, `short_description`, `detailed_description`, `version`, `language`, `license`, `update_date`, `github`, `document`, `homepage`, `image`, `screenshot`, `citation_title`, `citation_url`) VALUES
(1, 'ProteinTrim', '蛋白质结构智能修剪工具，基于二级结构和能量最小化原则，智能保留功能核心区域。', '<p>ProteinTrim 是一款专门用于蛋白质结构优化的软件。它通过分析蛋白质的二级结构、溶剂可及性和能量分布，自动识别并去除不稳定的柔性区域，同时保留对功能至关重要的核心结构。软件内置了多种能量最小化算法，并支持 GPU 加速，可高效处理大规模蛋白质结构数据。</p><p>输出文件可直接用于分子对接、动力学模拟等下游分析。支持 PDB、mmCIF 格式导入导出，并提供 3D 可视化界面，方便用户检查修剪结果。</p>', '2.5.1', 'Python / C++', 'GPL-3.0', '2023-10-05', 'https://github.com/your/proteintrim', 'https://proteintrim.readthedocs.io', 'https://dock.huimy.top/proteintrim', '/images/proteintrim-logo.png', '/images/proteintrim-screenshot.jpg', 'ProteinTrim: A tool for protein structure refinement', 'https://doi.org/10.xxx/xxxx'),
(2, 'huisu', '示例工具详情，待补充', '<p>这是一个占位详情，待实际内容替换。</p>', '0.1.0', 'Python', 'MIT', '2024-01-01', '', '', '', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `tool_versions`
--

CREATE TABLE `tool_versions` (
  `id` int UNSIGNED NOT NULL,
  `tool_name` varchar(100) NOT NULL,
  `version` varchar(30) NOT NULL,
  `date` date NOT NULL,
  `changes` json DEFAULT NULL,
  `downloads` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='工具版本历史';

--
-- 转存表中的数据 `tool_versions`
--

INSERT INTO `tool_versions` (`id`, `tool_name`, `version`, `date`, `changes`, `downloads`) VALUES
(1, 'ProteinTrim', '2.5.1', '2023-10-05', '[\"修复了特定PDB文件格式下的解析错误\", \"优化了内存使用效率\"]', '[{\"url\": \"/downloads/proteintrim-2.5.1-win64.exe\", \"icon\": \"fab fa-windows\", \"name\": \"Windows 64-bit\"}, {\"url\": \"/downloads/proteintrim-2.5.1-linux.tar.gz\", \"icon\": \"fab fa-linux\", \"name\": \"Linux 64-bit\"}]'),
(2, 'ProteinTrim', '2.5.0', '2023-09-20', '[\"新增二级结构自动识别功能\", \"改进了蛋白质可视化渲染效果\"]', '[{\"url\": \"/downloads/proteintrim-2.5.0-win64.exe\", \"icon\": \"fab fa-windows\", \"name\": \"Windows 64-bit\"}, {\"url\": \"\", \"icon\": \"fab fa-linux\", \"name\": \"Linux 64-bit\"}]'),
(3, 'huisu', '0.1.0', '2024-01-01', '[\"首个测试版本\"]', '[{\"url\": \"#\", \"icon\": \"fas fa-code\", \"name\": \"Source code\"}]');

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `permissions` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员用户';

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `permissions`, `created_at`) VALUES
(1, 'root', '$2y$10$j3DjQ842oLGeGK6OZhQvTeqvuUzA7QVpPYhl4AkofL63O4cb1QK/W', '[\"all\"]', '2026-08-11 09:06:48');

--
-- 转储表的索引
--

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`slug`);

--
-- 表的索引 `footer_info`
--
ALTER TABLE `footer_info`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ml_translations`
--
ALTER TABLE `ml_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_translation` (`table_name`,`record_id`,`field_name`,`locale`);

--
-- 表的索引 `nav_menu`
--
ALTER TABLE `nav_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- 表的索引 `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `plugins`
--
ALTER TABLE `plugins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- 表的索引 `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_slug` (`category_slug`);

--
-- 表的索引 `project_categories`
--
ALTER TABLE `project_categories`
  ADD PRIMARY KEY (`slug`);

--
-- 表的索引 `publications`
--
ALTER TABLE `publications`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `site_config`
--
ALTER TABLE `site_config`
  ADD PRIMARY KEY (`config_key`);

--
-- 表的索引 `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- 表的索引 `tool_details`
--
ALTER TABLE `tool_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tool_name` (`tool_name`);

--
-- 表的索引 `tool_versions`
--
ALTER TABLE `tool_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tool_name` (`tool_name`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `footer_info`
--
ALTER TABLE `footer_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `ml_translations`
--
ALTER TABLE `ml_translations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- 使用表AUTO_INCREMENT `nav_menu`
--
ALTER TABLE `nav_menu`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `news`
--
ALTER TABLE `news`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `plugins`
--
ALTER TABLE `plugins`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用表AUTO_INCREMENT `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `slides`
--
ALTER TABLE `slides`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `tools`
--
ALTER TABLE `tools`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `tool_details`
--
ALTER TABLE `tool_details`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `tool_versions`
--
ALTER TABLE `tool_versions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 限制导出的表
--

--
-- 限制表 `nav_menu`
--
ALTER TABLE `nav_menu`
  ADD CONSTRAINT `nav_menu_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `nav_menu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 限制表 `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`category_slug`) REFERENCES `project_categories` (`slug`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- 限制表 `tool_details`
--
ALTER TABLE `tool_details`
  ADD CONSTRAINT `tool_details_ibfk_1` FOREIGN KEY (`tool_name`) REFERENCES `tools` (`name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `tool_versions`
--
ALTER TABLE `tool_versions`
  ADD CONSTRAINT `tool_versions_ibfk_1` FOREIGN KEY (`tool_name`) REFERENCES `tools` (`name`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
