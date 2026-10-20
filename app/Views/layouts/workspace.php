<?php helper('form'); $role=$account['role']; $customerHome=$role==='customer'&&$section==='dashboard'; ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title><?= esc($title) ?> | Sun Son Solar</title><link rel="stylesheet" href="<?= base_url('css/workspace.css') ?>"><?php if($role==='customer'): ?><link rel="stylesheet" href="<?= base_url('css/customer-home.css') ?>"><?php endif ?><script src="<?= base_url('js/workspace.js') ?>" defer></script></head><body class="<?= $role==='customer'?'customer-workspace'.($customerHome?' customer-home':''):'' ?>">
<a class="skip-link" href="#workspace-content">Skip to content</a>
<aside class="sidebar"><a class="brand" href="<?= site_url('account') ?>"><img src="<?= base_url('images/logo.png') ?>" width="48" height="48" alt="Sun Son Solar logo"><span>SUN SON SOLAR<small>Clean energy. Brighter tomorrows.</small></span></a><p class="role-label"><?= esc(ucfirst($role)) ?> workspace</p><nav aria-label="Account navigation">
<?php $links=['account'=>$role==='customer'?'Homepage':'Dashboard','profile'=>'Your profile','notifications'=>'Notifications'];
if(in_array($role,['customer','admin','dispatcher','technician'],true)) { $links['bookings']=$role==='technician'?'Assigned jobs':'Bookings & history'; }
if($role==='customer') { $links['bookings/new']='Request a service'; }
if(in_array($role,['customer','admin'],true)) { $links['payments']='Payments & receipts'; $links['support']='Support concerns'; }
if(in_array($role,['admin','hr'],true)) { $links['users']='Users & employees'; $links['employees/create']='Create employee'; }
if($role!=='customer') { $links['announcements']='Announcements & safety'; }
if($role==='admin') { $links+=['manage/products'=>'Products','manage/services'=>'Services & pricing','admin/register'=>'Create administrator','reports'=>'Operations reports','records'=>'Attendance & audit','settings'=>'System settings']; }
foreach($links as $url=>$label): $active=$role==='customer' && (($url==='account'&&$customerHome)||trim(uri_string(),'/')===$url); ?><a href="<?= site_url($url) ?>" <?= $active?'aria-current="page"':'' ?>><?php if($role==='customer'): ?><span class="nav-icon"><?= view('partials/customer_icon',['name'=>['account'=>'home','profile'=>'user','notifications'=>'bell','bookings'=>'calendar','bookings/new'=>'plus','payments'=>'receipt','support'=>'chat'][$url]??'home']) ?></span><?php endif ?><span><?= esc($label) ?></span><?php if($active): ?><span class="nav-current-dot" aria-hidden="true"></span><?php endif ?></a><?php endforeach ?>
</nav><form action="<?= site_url('logout') ?>" method="post"><?= csrf_field() ?><button class="secondary" type="submit">Sign out</button></form></aside>
<main id="workspace-content" class="workspace"><header class="page-header"><div><p class="eyebrow">SUN SON SOLAR</p><h1><?= esc($title) ?></h1></div><div class="identity"><?= esc($account['name']) ?><small>User #<?= (int)$account['user_id'] ?> · Philippine time (UTC+8)</small></div></header>
<?= view('partials/feedback') ?>
<?php if(ENVIRONMENT==='testing'||config('AccountAccess')->preview): ?><div class="feedback" role="status">TEST ENVIRONMENT · Synthetic records for verification only.</div><?php endif ?>
<?= view('workspace/'.$section, get_defined_vars()) ?>
</main></body></html>
