<?php

require_once __DIR__ . '/../includes/site.php';
$categories = fetch_categories($pdo);
$ready = (int)$pdo->query("SELECT COUNT(*) FROM equipment_items i WHERE " . AVAILABLE_SQL)->fetchColumn();
public_header('Borrow reusable mobility equipment', 'home', ['description' => 'CareShare lends reusable wheelchairs, walking frames, shower chairs and other mobility equipment to people in the community.']);
?>
<section class="hero">
  <div class="container hero-inner">
    <div>
      <p class="eyebrow">Community equipment lending</p>
      <h1>Mobility equipment, shared with the community.</h1>
      <p class="lead">CareShare lends wheelchairs, walking frames, shower chairs and other reusable equipment to people who need it, and takes it back so the next person can use it too.</p>
      <div class="btn-row">
        <a class="btn btn-primary btn-lg" href="<?= h(url('pages/equipment.php')) ?>">Browse equipment</a>
        <?php if (!borrower_logged_in()): ?>
          <a class="btn btn-outline btn-lg" href="<?= h(url('pages/register.php')) ?>">Register to request</a>
        <?php else: ?>
          <a class="btn btn-outline btn-lg" href="<?= h(url('user/dashboard.php')) ?>">My account</a>
        <?php endif; ?>
      </div>
      <p class="hero-note"><strong><?= $ready ?></strong> item<?= $ready === 1 ? '' : 's' ?> ready to borrow right now · <a href="<?= h(url('pages/how-it-works.php')) ?>">See how it works</a></p>
    </div>
    <div class="hero-art" aria-hidden="true"><img src="<?= h(asset('images/equipment/wheelchair.svg')) ?>" alt=""></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">What we lend</p>
      <h2>Equipment categories</h2>
      <p class="lead">Everything on this site comes from our live equipment records, so what you see is what we currently hold.</p>
    </div>
    <div class="grid grid-4">
      <?php foreach ($categories as $c): ?>
        <a class="card category-card" href="<?= h(url('pages/equipment.php')) ?>?category=<?= (int)$c['id'] ?>">
          <img src="<?= h(equipment_image($c['name'], $c['name'])) ?>" alt="" loading="lazy">
          <h3><?= h($c['name']) ?></h3>
          <p class="count"><?= (int)$c['models'] ?> type<?= (int)$c['models'] === 1 ? '' : 's' ?> listed</p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <p class="eyebrow">Simple and free to ask</p>
      <h2>How borrowing works</h2>
    </div>
    <ol class="steps steps-compact">
      <li><h3>Browse</h3><p>See what is available and read about each item.</p></li>
      <li><h3>Register</h3><p>Create a free account with your contact details.</p></li>
      <li><h3>Request</h3><p>Choose your dates. Our team reviews every request.</p></li>
      <li><h3>Collect and return</h3><p>Arrange collection once approved, and bring it back when you are done.</p></li>
    </ol>
    <p class="center" style="margin-top:28px"><a class="btn btn-outline" href="<?= h(url('pages/how-it-works.php')) ?>">Read the full process</a></p>
  </div>
</section>

<section class="section">
  <div class="container grid grid-2" style="align-items:center;gap:48px">
    <div>
      <p class="eyebrow">About CareShare</p>
      <h2>A small charity with a simple purpose</h2>
      <p>Mobility equipment is often needed for a short time: after an operation, an injury, or during a hard stretch. Buying it is expensive, and it often ends up unused in a cupboard.</p>
      <p>CareShare gathers reusable equipment, checks it between loans and lends it to neighbours who need it.</p>
      <a class="btn btn-outline" href="<?= h(url('pages/about.php')) ?>">More about us</a>
    </div>
    <div>
      <p class="eyebrow">Why reuse?</p>
      <h2>Benefits of sharing equipment</h2>
      <div class="grid" style="gap:18px">
        <div class="benefit"><div class="icon" aria-hidden="true">✔</div><div><h3>Saves money</h3><p>Borrow instead of buying equipment you only need for a while.</p></div></div>
        <div class="benefit"><div class="icon" aria-hidden="true">♻</div><div><h3>Reduces waste</h3><p>Good equipment stays in use instead of being thrown away.</p></div></div>
        <div class="benefit"><div class="icon" aria-hidden="true">♥</div><div><h3>Helps neighbours</h3><p>Equipment you return is ready for the next person.</p></div></div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="cta-band">
      <h2>Need equipment? Start with a free account.</h2>
      <p>Registering takes a minute and lets you request equipment and follow your request online.</p>
      <div class="btn-row">
        <a class="btn btn-light btn-lg" href="<?= h(url(borrower_logged_in() ? 'pages/equipment.php' : 'pages/register.php')) ?>"><?= borrower_logged_in() ? 'Browse equipment' : 'Create an account' ?></a>
        <a class="btn btn-lg" style="border-color:#fff;color:#fff" href="<?= h(url('pages/contact.php')) ?>">Ask us a question</a>
      </div>
    </div>
  </div>
</section>
<?php public_footer();
