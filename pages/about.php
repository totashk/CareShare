<?php
require_once __DIR__ . '/../includes/site.php';
public_header('About us', 'about');
?>
<section class="page-banner"><div class="container"><h1>About CareShare</h1><p>A community project that keeps useful equipment in use and in reach.</p></div></section>
<section class="section">
  <div class="container narrow prose">
    <h2>What CareShare is</h2>
    <p>CareShare is a community charity service that lends reusable mobility and daily-living equipment, such as wheelchairs, walking frames, shower chairs and walking sticks, to people who need it.</p>
    <h2>Why the service exists</h2>
    <p>Many people need equipment only for a limited time, for example while recovering from surgery or an injury. New equipment can be costly, and equipment that is no longer needed is often stored away or thrown out. CareShare brings those two problems together: equipment is gathered, checked and lent again and again.</p>
    <h2>Our mission</h2>
    <p>To make safe, reusable mobility equipment easy to borrow, so that cost and waste are not barriers to people staying independent and comfortable.</p>
    <h2>How the lending service works</h2>
    <ul>
      <li>Each physical item is recorded individually, with its condition and whether it is available.</li>
      <li>People register, request an item and choose the dates they need it.</li>
      <li>Our team reviews each request and checks real availability before approving it.</li>
      <li>After a loan, every item is checked and only returned to circulation when it is fit to lend.</li>
    </ul>
    <h2>Benefits for the community</h2>
    <ul>
      <li><strong>Less cost</strong> for individuals and families.</li>
      <li><strong>Less waste</strong>, because equipment is reused rather than discarded.</li>
      <li><strong>More access</strong> to equipment at the moment it is needed.</li>
    </ul>
    <div class="notice">
      <strong>Please note:</strong> CareShare lends equipment but does not give medical advice, and we do not ask for or store medical information. If you are unsure which equipment suits you, please speak to a healthcare professional.
    </div>
    <p class="btn-row"><a class="btn btn-primary" href="<?= h(url('pages/equipment.php')) ?>">Browse equipment</a><a class="btn btn-outline" href="<?= h(url('pages/contact.php')) ?>">Contact us</a></p>
  </div>
</section>
<?php public_footer();
