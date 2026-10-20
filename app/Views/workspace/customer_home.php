<?php
$firstName=explode(' ',trim($account['name']))[0];
$visits=array_values(array_filter($bookings,static fn($b)=>in_array($b['status'],['scheduled','in_progress'],true)));
usort($visits,static fn($a,$b)=>strcmp($a['scheduled_at']??'',$b['scheduled_at']??''));
$next=$visits[0]??null;
$dateLabel=static function(?string $value,string $format='M j, Y'): string { return $value?(new DateTimeImmutable($value,new DateTimeZone('Asia/Manila')))->format($format):'To be confirmed'; };
$statusLabel=static fn(string $status): string=>ucwords(str_replace('_',' ',$status));
?>
<section class="customer-welcome" aria-labelledby="welcome-title">
    <div class="welcome-copy">
        <p class="home-eyebrow"><span class="tiny-sun" aria-hidden="true"><?= view('partials/customer_icon',['name'=>'sun']) ?></span> GOOD TO SEE YOU, <?= esc(mb_strtoupper($firstName)) ?></p>
        <h2 id="welcome-title">A brighter day.<br><em>A little more possibility.</em></h2>
        <p>Your solar journey, thoughtfully brought together.<br class="desktop-break"> Make your next move. We’ll keep the details in view.</p>
        <div class="welcome-actions"><a class="button home-primary" href="<?= site_url('bookings/new') ?>">Book a service <span aria-hidden="true">&nearr;</span></a><a class="home-text-link" href="<?= site_url('bookings') ?>">My bookings <span aria-hidden="true">&rarr;</span></a></div>
    </div>
    <div class="welcome-art" aria-hidden="true">
        <svg viewBox="0 0 400 310" fill="none" focusable="false">
            <circle class="orbit" cx="220" cy="150" r="127"/><circle class="orbit orbit-inner" cx="220" cy="150" r="98"/>
            <circle class="art-sun" cx="289" cy="77" r="39"/>
            <g class="sun-rays"><path d="M289 22V12m0 131v-10m55-56h10m-130 0h10m94-39 8-8m-93 93 8-8m77 0 8 8m-93-93 8 8"/></g>
            <path class="art-ground" d="M57 253c38-29 89-34 139-14 52 22 108 5 155-13v48H57z"/>
            <path class="art-house" d="m173 163 66-48 68 48v87H173z"/>
            <path class="art-roof" d="m155 165 84-61 85 61"/>
            <path class="art-window" d="M245 176h33v32h-33zM261.5 176v32m-16.5-16h33"/>
            <path class="art-door" d="M201 250v-50h25v50"/>
            <path class="art-panel" d="M78 156h96l29 65H57z"/>
            <path class="art-panel-lines" d="m102 156-6 65m31-65v65m24-65 12 65M71 178h113m-122 21h132M96 221v30m66-30v30M80 253h99"/>
            <circle class="orbit-dot" cx="95" cy="123" r="7"/><circle class="orbit-dot small-dot" cx="329" cy="213" r="4"/>
        </svg>
        <div class="art-caption"><span class="caption-dot"></span> A LITTLE SUN GOES A LONG WAY</div>
    </div>
</section>

<section class="service-overview" aria-labelledby="overview-title">
    <div class="home-section-heading"><div><p class="home-eyebrow">THE BIG PICTURE</p><h2 id="overview-title">Your service overview</h2></div><a class="home-text-link" href="<?= site_url('bookings') ?>">View history <span aria-hidden="true">&nearr;</span></a></div>
    <div class="customer-stats">
    <?php foreach([['requested','clock','Requested','Awaiting review'],['scheduled','calendar','Scheduled','Confirmed visits'],['in_progress','sun','In progress','Service underway'],['completed','check','Completed','Finished services']] as [$key,$icon,$label,$hint]): ?>
        <article class="customer-stat stat-<?= esc($key,'attr') ?>"><div class="stat-top"><span><?= esc($label) ?></span><span class="stat-icon"><?= view('partials/customer_icon',['name'=>$icon]) ?></span></div><strong><?= (int)($counts[$key]??0) ?></strong><span class="stat-hint"><?= esc($hint) ?></span></article>
    <?php endforeach ?>
    </div>
</section>

<div class="customer-content-grid">
    <div class="customer-main-column">
        <section class="home-card visit-card" aria-labelledby="visit-title">
            <div class="home-card-heading"><h2 id="visit-title">Next on your calendar</h2><span class="card-symbol" aria-hidden="true"><?= view('partials/customer_icon',['name'=>'calendar']) ?></span></div>
            <?php if($next): ?>
            <div class="next-visit"><div class="visit-date"><span><?= esc($dateLabel($next['scheduled_at'],'M')) ?></span><strong><?= esc($dateLabel($next['scheduled_at'],'d')) ?></strong><small><?= esc($dateLabel($next['scheduled_at'],'D')) ?></small></div><div class="visit-copy"><span class="visit-status"><?= esc($statusLabel($next['status'])) ?></span><h3><?= esc($next['service_name']) ?></h3><p><?= esc($dateLabel($next['scheduled_at'],'g:i A')) ?> <span aria-hidden="true">&middot;</span> Philippine time</p><p class="visit-address"><?= esc($next['address']) ?></p></div></div>
            <div class="visit-footer"><span>Booking #<?= (int)$next['id'] ?><?= count($visits)>1?' · '.(count($visits)-1).' more scheduled':'' ?></span><a class="home-text-link" href="<?= site_url('bookings/'.$next['id']) ?>">View visit details <span aria-hidden="true">&rarr;</span></a></div>
            <?php else: ?>
            <div class="calendar-empty"><span class="empty-sun" aria-hidden="true"><?= view('partials/customer_icon',['name'=>'sun']) ?></span><div><h3>A little room for what’s next.</h3><p>Your calendar is clear. Once a visit is confirmed, you’ll find the time and details right here.</p><a class="home-text-link" href="<?= site_url('bookings/new') ?>">Plan your first service <span aria-hidden="true">&rarr;</span></a></div></div>
            <?php endif ?>
        </section>
        <section class="home-card recent-card" aria-labelledby="recent-title"><div class="home-card-heading"><h2 id="recent-title">Your latest requests</h2><a class="home-text-link" href="<?= site_url('bookings') ?>">View all <span aria-hidden="true">&nearr;</span></a></div>
            <?php if(!$bookings): ?><div class="requests-empty"><p>No requests yet. Your service history will grow here, one step at a time.</p><a class="home-text-link" href="<?= site_url('bookings/new') ?>">Start a request <span aria-hidden="true">&rarr;</span></a></div><?php endif ?>
            <?php foreach(array_slice($bookings,0,3) as $b): ?><a class="recent-request" href="<?= site_url('bookings/'.$b['id']) ?>"><span class="request-icon"><?= view('partials/customer_icon',['name'=>'sun']) ?></span><span class="request-name"><strong><?= esc($b['service_name']) ?></strong><small>#<?= (int)$b['id'] ?> &middot; <?= esc($dateLabel($b['created_at'])) ?></small></span><span class="request-status"><?= esc($statusLabel($b['status'])) ?></span><span class="request-arrow" aria-hidden="true">&nearr;</span></a><?php endforeach ?>
        </section>
    </div>
    <aside class="customer-side-column" aria-label="Helpful account links">
        <section class="home-card shortcuts-card"><p class="home-eyebrow">A LITTLE LESS SEARCHING</p><h2>Right where you need it.</h2>
        <?php foreach([['notifications','bell','Your updates','Stay in the loop'],['payments','receipt','Payments & receipts','Keep the details together'],['support','chat','A helping hand','Send us a concern']] as [$url,$icon,$label,$hint]): ?><a class="customer-shortcut" href="<?= site_url($url) ?>"><span class="shortcut-icon"><?= view('partials/customer_icon',['name'=>$icon]) ?></span><span><strong><?= esc($label) ?></strong><small><?= esc($hint) ?></small></span><span class="shortcut-arrow" aria-hidden="true">&nearr;</span></a><?php endforeach ?>
        </section>
        <a class="solar-discover" href="<?= site_url('/') ?>#products"><span class="discover-icon"><?= view('partials/customer_icon',['name'=>'sun']) ?></span><p class="home-eyebrow">LET CURIOSITY LEAD</p><h2>Good energy.<br><em>New possibilities.</em></h2><p>Get to know the essentials<br>of a solar-powered everyday.</p><span class="home-text-link">Explore solar products <span aria-hidden="true">&nearr;</span></span></a>
    </aside>
</div>
<footer class="customer-home-footer"><p>Sun Son Solar <span aria-hidden="true">&middot;</span> A brighter everyday, together.</p><span class="footer-sun" aria-hidden="true"><?= view('partials/customer_icon',['name'=>'sun']) ?></span></footer>
