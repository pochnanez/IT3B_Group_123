<?php $bookingReady=$bookingReady??false; $bookingLink=$bookingReady?site_url('bookings/new'):'#booking'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore solar products and services with Sun Son Solar, based in Pasig City. Learn about solar panels, energy storage, installation and maintenance.">
    <meta name="theme-color" content="#FFF9D0">
    <title>Sun Son Solar | A brighter everyday</title>
    <link rel="icon" href="<?= base_url('images/logo.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('css/home.css') ?>">
    <script src="<?= base_url('js/home.js') ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="nav-wrap container">
        <a class="brand" href="<?= site_url(session()->get('account') ? 'account' : '/') ?>" aria-label="Sun Son Solar home">
            <img src="<?= base_url('images/logo.png') ?>" width="58" height="58" alt="">
            <span>SUN SON <strong>SOLAR</strong><small>Clean energy. Brighter tomorrows.</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" hidden><span>Menu</span><span class="menu-bars" aria-hidden="true"></span></button>
        <nav class="site-navigation" id="site-navigation" aria-label="Main navigation">
            <div class="nav-links"><a href="#home">Home</a><a href="#about">About us</a><a href="#products">Products</a><a href="#services">Services</a><a href="#faqs">FAQs</a><a href="#contact">Contact us</a></div>
            <div class="nav-actions"><a href="<?= site_url('login') ?>">Login</a><a href="<?= site_url('register') ?>">Register</a><a class="button button-small" href="<?= esc($bookingLink,'attr') ?>" aria-describedby="booking-status">Book a service <span aria-hidden="true">↗</span></a></div>
        </nav>
    </div>
</header>
<main id="main">
    <section class="hero" id="home" aria-labelledby="hero-title">
        <div class="container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow"><span class="little-sun" aria-hidden="true">✳</span> A LITTLE SUN. A BRIGHTER FUTURE.</p>
                <h1 id="hero-title">Power your future<br>with <em>solar energy.</em></h1>
                <p class="hero-description">A brighter everyday starts with possibility. Explore solar products and services for your home or business, with Sun Son Solar.</p>
                <div class="button-row"><a class="button" href="#services">Explore our services <span aria-hidden="true">↗</span></a><a class="text-link" href="#products">View solar products <span aria-hidden="true">→</span></a></div>
                <div class="hero-footnote"><span class="location-dot" aria-hidden="true"></span> Based in Pasig City. Looking toward a brighter tomorrow.</div>
            </div>
            <div class="hero-visual">
                <div class="hero-art-frame"><img class="hero-art" src="<?= base_url('images/sssbg.png') ?>" alt="Illustration of rooftop solar panels, green surroundings and homes under a sunny sky" width="1672" height="941" fetchpriority="high"></div>
                <div class="sun-badge" aria-hidden="true"><?= view('partials/solar_icon', ['icon' => 'sun']) ?></div>
                <div class="image-caption"><span class="caption-icon" aria-hidden="true"><?= view('partials/solar_icon', ['icon' => 'panel']) ?></span><p>Good energy.<br><strong>Brighter possibilities.</strong></p><span aria-hidden="true">↗</span></div>
                <span class="art-label">A vision of a solar-powered everyday</span>
            </div>
        </div>
    </section>

    <div class="intro-strip"><div class="container"><span>Built around your energy journey</span><p>Explore the possibilities <b aria-hidden="true">✳</b> Understand your options <b aria-hidden="true">✳</b> Plan your next step</p></div></div>

    <section class="section container about-grid" id="about" aria-labelledby="about-title">
        <div><p class="eyebrow">HELLO, WE’RE SUN SON SOLAR</p><h2 id="about-title">A sunny outlook.<br>A practical <em>next step.</em></h2></div>
        <div class="about-copy"><p class="lead">Solar starts with understanding what’s possible for your space.</p><p>Sun Son Solar is a solar-energy company based in Pasig City, Philippines, founded by Katherine Sinagaraw (Kat) and Santino Sinagaraw (Sonny), with a focus on solar products and services for homes and businesses.</p><p>From exploring equipment to understanding design, installation and ongoing care, we bring the parts of the solar journey into one place—so you can begin with the right questions.</p><a class="text-link" href="#services">Get to know our services <span aria-hidden="true">→</span></a></div>
    </section>

    <section class="benefits-section" aria-labelledby="benefits-title">
        <div class="container"><div class="section-heading"><div><p class="eyebrow">WHY GO SOLAR?</p><h2 id="benefits-title">Small shift. <em>Lasting possibilities.</em></h2></div><p>A different way to think about the energy you use, every day.</p></div>
        <div class="benefits-grid">
            <?php foreach ([['sun', 'A renewable source', 'Make use of sunlight, a naturally replenished energy resource.'], ['monitor', 'Potential bill savings', 'Generate electricity that may reduce the amount you buy from the grid.'], ['battery', 'More energy options', 'Explore a system that can complement your existing electricity supply.'], ['leaf', 'A lighter footprint', 'Solar generation can reduce reliance on electricity from fossil fuels.']] as $benefit): ?>
            <article class="benefit"><div class="benefit-icon"><?= view('partials/solar_icon', ['icon' => $benefit[0]]) ?></div><h3><?= esc($benefit[1]) ?></h3><p><?= esc($benefit[2]) ?></p></article>
            <?php endforeach ?>
        </div><p class="section-note">Every site is different. Output and savings depend on your system, sunlight, usage and local conditions.</p></div>
    </section>

    <section class="section container" id="products" aria-labelledby="products-title">
        <div class="section-heading"><div><p class="eyebrow">THE BUILDING BLOCKS</p><h2 id="products-title">Meet your solar <em>essentials.</em></h2></div><p>Get familiar with the components that can make up a solar system.</p></div>
        <p class="catalog-note"><span aria-hidden="true">ⓘ</span> <?= !empty($catalogError)?'The live catalog is temporarily unavailable. General category information is shown below.':(!empty($catalogLive)?'Published product catalog. Prices and availability reflect recorded information; confirm details with the company.':'Product category guide. Specific models, prices and stock availability are not yet published.') ?></p>
        <div class="products-grid">
            <?php if ($products === []): ?><p class="empty-state">Product category information is being updated. Please check back for the guide.</p><?php endif ?>
            <?php foreach ($products as $index => $product): ?>
            <article class="product-card">
                <div class="product-illustration"><span class="product-index">0<?= $index + 1 ?></span><?= view('partials/solar_icon', ['icon' => $product['icon']]) ?><span class="illustration-label">CATEGORY GUIDE</span></div>
                <div class="product-copy"><p class="eyebrow"><?= esc($product['tag']) ?></p><h3><?= esc($product['name']) ?></h3><p><?= esc($product['description']) ?></p><details class="product-details"><summary>View details <span aria-hidden="true">+</span><span class="sr-only"> about <?= esc($product['name']) ?></span></summary><p><?= esc($product['details']) ?></p></details></div>
            </article>
            <?php endforeach ?>
        </div>
    </section>

    <section class="services-section" id="services" aria-labelledby="services-title">
        <div class="section container"><div class="section-heading"><div><p class="eyebrow">FROM FIRST QUESTION TO ONGOING CARE</p><h2 id="services-title">Support for your<br><em>solar journey.</em></h2></div><p>From your first questions to ongoing care. The right scope starts with understanding your site and needs.</p></div>
        <div class="services-grid">
            <?php if ($services === []): ?><p class="empty-state">Service information is being updated. Please check back for the guide.</p><?php endif ?>
            <?php foreach ($services as $index => $service): ?>
            <article class="service-card"><span class="service-number">0<?= $index + 1 ?></span><div class="service-icon"><?= view('partials/solar_icon', ['icon' => $service['icon']]) ?></div><div><h3><?= esc($service['name']) ?></h3><p><?= esc($service['description']) ?></p><details class="service-details"><summary>Learn more<span class="sr-only"> about <?= esc($service['name']) ?></span></summary><p><?= esc($service['details']) ?></p></details></div></article>
            <?php endforeach ?>
            <div class="service-card service-prompt"><span class="prompt-sun" aria-hidden="true">✳</span><div><h3>Start with a question.</h3><p>New to solar? Our FAQs are a good place to begin.</p><a class="text-link" href="#faqs">Find an answer <span aria-hidden="true">→</span></a></div></div>
        </div></div>
    </section>

    <section class="section container journey-section" aria-labelledby="journey-title">
        <div class="section-heading"><div><p class="eyebrow">GETTING STARTED</p><h2 id="journey-title">Your next chapter<br>starts <em>right here.</em></h2></div><p>Explore at your pace. Create an account when you’re ready.</p></div>
        <ol class="steps"><li><span class="step-number">01</span><h3>Explore your options</h3><p>Browse product categories, learn about services and get familiar with solar.</p><a href="#products">Explore the guide <span aria-hidden="true">→</span></a></li><li><span class="step-number">02</span><h3>Make yourself at home</h3><p>Create a customer account and sign in to your Sun Son Solar account page.</p><a href="<?= site_url('register') ?>">Create an account <span aria-hidden="true">→</span></a></li><li><span class="step-number">03</span><h3><?= $bookingReady?'Request your service':'Booking comes next' ?></h3><p><?= $bookingReady?'Submit a preferred time and address. The company reviews and schedules your request; track updates in your account.':'Online requests, scheduling and booking updates are planned. They are not available yet.' ?></p><a href="<?= esc($bookingLink,'attr') ?>"><?= $bookingReady?'Book a service':'Check booking availability' ?> <span aria-hidden="true">→</span></a></li></ol>
    </section>

    <section class="why-section"><div class="container why-grid"><div><p class="eyebrow">WHY SUN SON SOLAR?</p><h2>One solar journey.<br><em>A connected approach.</em></h2></div><div class="why-list"><p><span>01</span> Product options, explained simply</p><p><span>02</span> Consultation and system design</p><p><span>03</span> Installation and ongoing maintenance</p><p><span>04</span> Repair, monitoring and follow-up needs</p></div></div></section>

    <section class="section container faq-grid" id="faqs" aria-labelledby="faq-title"><div class="faq-intro"><p class="eyebrow">A LITTLE CLARITY</p><h2 id="faq-title">Good questions.<br><em>Clear answers.</em></h2><p>Start with the basics, then explore what might work for you.</p><a class="text-link" href="#contact">Company information <span aria-hidden="true">→</span></a></div><div class="faqs">
        <?php foreach ($faqs as $faq): ?><details class="faq"><summary><?= esc($faq['question']) ?><span aria-hidden="true">+</span></summary><p><?= esc($faq['answer']) ?></p></details><?php endforeach ?>
    </div></section>

    <section class="booking-section container" id="booking" aria-labelledby="booking-title"><div class="booking-art" aria-hidden="true"><?= view('partials/solar_icon', ['icon' => 'sun']) ?></div><div><p class="eyebrow">YOUR BRIGHTER BEGINNING</p><h2 id="booking-title">Let’s take your <em>next step.</em></h2><p id="booking-status"><?= $bookingReady?'Online service requests are available. Sign in or create an account, choose a service and tell us your preferred time. Track review, scheduling and service updates through your account.':'Online service booking is not available yet. Explore our services and create an account today; consultation requests, scheduling and tracking will be added next.' ?></p><p class="booking-note">A request is subject to review. Creating an account alone does not reserve a service.</p><div class="button-row"><a class="button" href="<?= $bookingReady?site_url('bookings/new'):site_url('register') ?>"><?= $bookingReady?'Book a service':'Create your account' ?> <span aria-hidden="true">↗</span></a><a class="text-link" href="<?= site_url('login') ?>">Already registered? Login <span aria-hidden="true">→</span></a></div></div></section>

    <section class="section container contact-grid" id="contact" aria-labelledby="contact-title"><div><p class="eyebrow">ROOTED IN PASIG</p><h2 id="contact-title">A brighter tomorrow<br><em>starts close to home.</em></h2></div><div class="contact-copy"><p class="contact-location"><span class="location-dot" aria-hidden="true"></span> Pasig City, Philippines</p><?php if(!empty($contacts['contact_email'])): ?><p><a href="mailto:<?= esc($contacts['contact_email'],'attr') ?>"><?= esc($contacts['contact_email']) ?></a></p><?php endif ?><?php if(!empty($contacts['contact_phone'])): ?><p>Phone: <?= esc($contacts['contact_phone']) ?></p><?php endif ?><?php if(!empty($contacts['office_address'])): ?><p><?= esc($contacts['office_address']) ?></p><?php endif ?><?php if(!array_filter($contacts??[])): ?><p>Our official phone number, email address and office details will be published here once confirmed.</p><?php endif ?><?php if($bookingReady): ?><p><a class="text-link" href="<?= site_url('support') ?>">Account support <span aria-hidden="true">→</span></a></p><?php endif ?></div></section>
</main>
<footer class="site-footer"><div class="container footer-grid"><div><a class="brand" href="<?= site_url(session()->get('account') ? 'account' : '/') ?>"><img src="<?= base_url('images/logo.png') ?>" alt="Sun Son Solar logo" width="58" height="58"><span>SUN SON <strong>SOLAR</strong><small>Clean energy. Brighter tomorrows.</small></span></a><p>Solar possibilities for a brighter everyday.<br>Pasig City, Philippines.</p></div><div><h2>Explore</h2><a href="#about">About us</a><a href="#products">Solar products</a><a href="#services">Our services</a></div><div><h2>Good to know</h2><a href="#faqs">FAQs</a><a href="#booking">Booking availability</a><a href="#contact">Company information</a></div><div><h2>Your account</h2><a href="<?= site_url('login') ?>">Login</a><a href="<?= site_url('register') ?>">Register</a><a href="#home">Back to top ↑</a></div></div><div class="container footer-bottom"><p>&copy; <?= date('Y') ?> Sun Son Solar. All rights reserved.</p><p>A little sun goes a long way. <span aria-hidden="true">✳</span></p></div></footer>
</body>
</html>
