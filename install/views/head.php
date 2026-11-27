<?php
if (!defined('LABPRESS_INSTALLER')) {
    http_response_code(404);
    exit;
}
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? '安装 LabPress';
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle) ?></title>
<style>
:root{
  --ink:#333; --ink-2:#1f1f1f; --body:#666; --muted:#999;
  --wine:#851f1f; --wine-dark:#6d1818; --blue:#448aff;
  --line:#e4e4e4; --line-soft:#f0f0f0; --bg:#f2f3f5;
  --ok:#2e7d32; --amber:#b87333; --white:#fff;
  --mono:'Roboto Mono',ui-monospace,'SF Mono','Cascadia Mono',Consolas,'Liberation Mono',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{-webkit-text-size-adjust:100%}
body{background:var(--bg);color:var(--body);font-family:var(--mono);font-size:13px;line-height:1.65;-webkit-font-smoothing:antialiased}
a{color:var(--ink);text-decoration:none}
a:hover{color:var(--wine)}
h1,h2,h3{color:var(--ink);font-weight:700;line-height:1.3}
h1{font-size:30px;letter-spacing:-.4px;margin-bottom:10px}
h2{font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-bottom:16px}
h2 small{display:block;font-size:11px;letter-spacing:.3px;text-transform:none;color:var(--muted);font-weight:400;margin-top:5px}
.install-shell{display:grid;grid-template-columns:330px minmax(0,1fr);min-height:100vh}
.brand{position:relative;background:var(--ink-2);color:#c9c9c9;padding:46px 34px;display:flex;flex-direction:column;gap:16px;overflow:hidden}
.brand::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:26px 26px;pointer-events:none}
.brand::after{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--wine)}
.brand-mark{position:relative;color:#fff;font-size:25px;font-weight:700;letter-spacing:2px}
.brand-mark span{color:var(--wine);}
.brand-sub{position:relative;font-size:10px;letter-spacing:1.8px;text-transform:uppercase;color:#9a9a9a}
.brand-copy{position:relative;margin-top:18px;color:#b6b6b6;font-size:12px;line-height:1.9}
.brand-steps{position:relative;margin-top:auto;border-top:1px solid rgba(255,255,255,.09);padding-top:20px;display:grid;gap:12px}
.brand-step{display:flex;gap:12px;align-items:flex-start;font-size:11px;color:#909090}
.brand-step b{color:#ddd;font-weight:700;min-width:18px}
.brand-foot{position:relative;font-size:10px;letter-spacing:1.2px;color:#6f6f6f}
.panel{padding:46px 48px 60px;min-width:0;display:flex;justify-content:center}
.inner{width:100%;max-width:820px}
.eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:10px;font-weight:700;letter-spacing:2.6px;text-transform:uppercase;color:var(--wine);margin-bottom:12px}
.eyebrow::before{content:"";width:7px;height:7px;background:var(--wine)}
.lead{max-width:70ch;color:var(--body)}
.alerts{margin:22px 0 4px}
.alert{background:#fdf4f4;border-left:3px solid var(--wine);padding:12px 14px;margin-bottom:10px;color:#5d1a1a;font-size:12.5px}
.alert:last-child{margin-bottom:0}
.checks{border:1px solid var(--line);margin:26px 0 30px;background:var(--white)}
.check{display:flex;align-items:center;gap:12px;padding:10px 14px;border-bottom:1px solid var(--line-soft);font-size:12.5px}
.check:last-child{border-bottom:0}
.check .flag{width:18px;text-align:center;font-weight:700}
.check .name{color:var(--ink);min-width:230px}
.check .detail{color:var(--muted);font-size:11.5px;margin-left:auto;text-align:right}
.check.ok .flag{color:var(--ok)}
.check.bad .flag{color:var(--wine)}
.check.bad{background:#fdf7f7}
.check.optional{opacity:.85}
.group{margin-top:30px;padding-top:22px;border-top:1px solid var(--line)}
.form-row{display:flex;gap:14px;flex-wrap:wrap}
.form-row .field{flex:1 1 210px}
.form-row .field.narrow{flex:0 1 170px}
.field{margin-bottom:14px}
.field label{display:block;font-size:9.5px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#666;margin-bottom:7px}
.field input[type=text],.field input[type=password],.field input[type=number],.field input[type=url],.field select{
  width:100%;height:42px;padding:0 13px;background:#fff;border:1px solid var(--line);border-radius:0;color:var(--ink);font-family:var(--mono);font-size:12.5px;outline:none;transition:border-color .2s,box-shadow .2s
}
.field select{appearance:none;background-image:linear-gradient(45deg,transparent 50%,#999 50%),linear-gradient(135deg,#999 50%,transparent 50%);background-position:calc(100% - 17px) 18px,calc(100% - 12px) 18px;background-size:5px 5px,5px 5px;background-repeat:no-repeat}
.field input:focus,.field select:focus{border-color:var(--wine);box-shadow:inset 3px 0 0 var(--wine);background-color:#fffdfd}
.field input:disabled{background:#f7f7f7;color:#999}
.hint{font-size:11px;color:var(--muted);margin-top:6px;line-height:1.7}
.check-line{display:flex;align-items:flex-start;gap:10px;font-size:12.5px;color:var(--body);padding:6px 0}
.check-line input{margin-top:4px;accent-color:var(--wine)}
.actions{display:flex;align-items:center;gap:14px;margin-top:32px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;height:44px;padding:0 28px;background:var(--ink-2);border:1px solid var(--ink-2);border-radius:0;color:#fff;font-family:var(--mono);font-size:10.5px;font-weight:700;letter-spacing:1.8px;text-transform:uppercase;cursor:pointer;transition:background .2s,border-color .2s}
.btn:hover{background:var(--wine);border-color:var(--wine)}
.btn[disabled]{opacity:.45;cursor:not-allowed}
.btn-ghost{background:#fff;border-color:var(--line);color:var(--ink)}
.btn-ghost:hover{background:#fdf7f7;border-color:var(--wine);color:var(--wine)}
.mono-box{background:#1f1f1f;color:#d8d8d8;padding:14px 16px;font-size:11.5px;line-height:1.8;overflow-x:auto;margin-top:12px}
.summary{border:1px solid var(--line);margin-top:24px;background:#fff}
.summary .row{display:flex;justify-content:space-between;gap:16px;padding:11px 14px;border-bottom:1px solid var(--line-soft);font-size:12.5px}
.summary .row:last-child{border-bottom:0}
.summary .row span:first-child{color:var(--muted);text-transform:uppercase;font-size:10px;letter-spacing:1.2px}
.summary .row span:last-child{color:var(--ink);font-weight:700;word-break:break-all;text-align:right}
.tag{display:inline-block;padding:1px 8px;border:1px solid var(--amber);color:var(--amber);font-size:9.5px;letter-spacing:1px;text-transform:uppercase}
.tag.ok{border-color:var(--ok);color:var(--ok)}
.note{font-size:12px;color:var(--muted);line-height:1.8}
@media (max-width:900px){
  .install-shell{grid-template-columns:1fr}
  .brand{padding:28px 24px;flex-direction:row;align-items:center;flex-wrap:wrap;gap:10px}
  .brand-copy,.brand-steps,.brand-foot{display:none}
  .panel{padding:32px 24px 44px}
  .check .name{min-width:0}
  .check .detail{display:none}
}
</style>
</head>
<body>
<div class="install-shell">
<aside class="brand">
    <div class="brand-mark">LAB<span>PRESS</span></div>
    <div class="brand-sub">Research CMS · v1.2.0</div>
    <p class="brand-copy">轻量、可直接部署的科研实验室主页。<br>安装器会创建数据库表、管理员账号和可选演示内容。</p>
    <div class="brand-steps">
        <div class="brand-step"><b>01</b><span>检查运行环境</span></div>
        <div class="brand-step"><b>02</b><span>连接数据库并建表</span></div>
        <div class="brand-step"><b>03</b><span>创建管理员与站点</span></div>
        <div class="brand-step"><b>04</b><span>进入后台继续配置</span></div>
    </div>
    <div class="brand-foot">STORAGE / CONFIG / INSTALL.LOCK</div>
</aside>
<main class="panel">
<div class="inner">
