<?php if($role==='customer'): ?>
<?= view('workspace/customer_home',compact('account','bookings','counts')) ?>
<?php else: ?>
<section class="panel"><h2>Welcome, <?= esc($account['name']) ?></h2><p>Your <?= esc($account['role']) ?> workspace brings your authorized tasks together.</p>
<?php if($role==='customer'): ?><a class="button" href="<?= site_url('bookings/new') ?>">Request a service →</a><?php endif ?>
<?php if(in_array($role,['hr','admin'],true)): ?><a class="button" href="<?= site_url('users') ?>">Manage accounts →</a><?php endif ?>
<?php if(in_array($role,['technician','dispatcher'],true)): ?><a class="button" href="<?= site_url('bookings') ?>"><?= $role==='technician'?'View assigned jobs':'Review service requests' ?> →</a><?php endif ?></section>
<?php if(in_array($role,['customer','admin','dispatcher','technician'],true)): ?><div class="metrics"><?php foreach(['requested','scheduled','in_progress','completed'] as $state): ?><article><strong><?= (int)($counts[$state]??0) ?></strong><span><?= esc(ucwords(str_replace('_',' ',$state))) ?></span></article><?php endforeach ?></div>
<section class="panel"><h2>Upcoming visits & reminders</h2><?php $upcoming=array_filter($bookings,fn($b)=>in_array($b['status'],['scheduled','in_progress'],true)); if(!$upcoming): ?><p>No scheduled visits yet.</p><?php endif ?><?php foreach($upcoming as $b): ?><p><a href="<?= site_url('bookings/'.$b['id']) ?>">Booking #<?= (int)$b['id'] ?> · <?= esc($b['service_name']) ?></a><br><?= esc($b['scheduled_at']) ?> · <?= esc($b['status']) ?></p><?php endforeach ?></section><?php endif ?>
<?php if($role!=='customer'): ?><section class="panel"><h2>Company announcements & safety</h2><?php if(!$announcements): ?><p>No announcements have been published.</p><?php endif ?><?php foreach($announcements as $notice): ?><article class="notice"><span class="badge"><?= esc($notice['category']) ?></span><h3><?= esc($notice['title']) ?></h3><p class="pre-line"><?= esc($notice['body']) ?></p><small><?= esc($notice['created_at']) ?></small></article><?php endforeach ?></section><?php endif ?>
<?php endif ?>
