<?php
require_once __DIR__ . '/../includes/site.php';
$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 80);
$categoryId = (int)($_GET['category'] ?? 0);
$onlyAvailable = ($_GET['availability'] ?? '') === 'available';
$categories = fetch_categories($pdo);
$items = fetch_equipment($pdo, ['q' => $q, 'category' => $categoryId, 'only_available' => $onlyAvailable]);
public_header('Browse equipment', 'equipment');
?>
<section class="page-banner" style="padding-bottom:70px"><div class="container"><h1>Browse equipment</h1><p>Everything listed here comes from our live records. Availability shows what is ready to lend today.</p></div></section>
<section class="section" style="padding-top:0">
  <div class="container">
    <form class="filter-bar" method="get" action="<?= h(url('pages/equipment.php')) ?>" id="filter-form" role="search">
      <div class="field"><label for="q">Search</label><input type="search" id="q" name="q" value="<?= h($q) ?>" placeholder="e.g. wheelchair, shower" maxlength="80"></div>
      <div class="field small"><label for="category">Category</label>
        <select id="category" name="category"><option value="0">All categories</option>
          <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field small"><label for="availability">Availability</label>
        <select id="availability" name="availability"><option value="">All equipment</option><option value="available" <?= $onlyAvailable ? 'selected' : '' ?>>Available now only</option></select></div>
      <div class="field small" style="display:flex;gap:8px"><button class="btn btn-primary" type="submit">Search</button><a class="btn btn-ghost" href="<?= h(url('pages/equipment.php')) ?>">Reset</a></div>
    </form>

    <p class="result-info" id="result-info" aria-live="polite"><?= count($items) ?> item type<?= count($items) === 1 ? '' : 's' ?> found</p>

    <div class="equipment-grid" id="equipment-grid">
      <?php foreach ($items as $e): $avail = (int)$e['available_items'] > 0; ?>
        <article class="equipment-card <?= $avail ? '' : 'is-unavailable' ?>" data-name="<?= h(mb_strtolower($e['name'] . ' ' . $e['description'] . ' ' . $e['category'])) ?>" data-category="<?= (int)$e['category_id'] ?>" data-available="<?= $avail ? '1' : '0' ?>">
          <a class="equipment-img" href="<?= h(url('pages/equipment-details.php')) ?>?id=<?= (int)$e['id'] ?>" tabindex="-1" aria-hidden="true"><img src="<?= h(equipment_image($e['name'], $e['category'])) ?>" alt="" loading="lazy"></a>
          <div class="equipment-body">
            <span class="equipment-cat"><?= h($e['category']) ?></span>
            <h3><?= h($e['name']) ?></h3>
            <div><?= availability_badge($e) ?></div>
            <p class="equipment-desc"><?= h(mb_strimwidth((string)$e['description'], 0, 110, '…')) ?></p>
            <?php if (!$avail && $e['next_return']): ?><p class="equipment-note">Next expected back: <?= h(fmt_date($e['next_return'])) ?></p><?php endif; ?>
            <div class="equipment-actions">
              <a class="btn btn-outline btn-sm" href="<?= h(url('pages/equipment-details.php')) ?>?id=<?= (int)$e['id'] ?>">View details</a>
              <?php if ($avail): ?>
                <a class="btn btn-primary btn-sm" href="<?= h(url('user/request-new.php')) ?>?equipment=<?= (int)$e['id'] ?>">Request equipment</a>
              <?php else: ?>
                <span class="btn btn-sm is-disabled" style="background:#eef3f6;color:#5d6c78" aria-disabled="true">Unavailable</span>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="empty-state" id="empty-state" <?= $items ? 'hidden' : '' ?>>
      <h2>No equipment matches your search</h2>
      <p>Try a different word, choose another category, or include equipment that is not available right now.</p>
      <a class="btn btn-primary" href="<?= h(url('pages/equipment.php')) ?>">Show all equipment</a>
    </div>
  </div>
</section>
<?php public_footer(['js' => ['js/equipment.js']]);
