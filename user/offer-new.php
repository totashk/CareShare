<?php
require_once __DIR__ . '/../includes/site.php';
$b = require_borrower($pdo);
$categories = fetch_categories($pdo);
$error = '';
verify_csrf();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $category = (int)($_POST['category_id'] ?? 0);
    $description = trim((string)($_POST['description'] ?? ''));
    $photo = $_FILES['photo'] ?? null;
    $categoryIds = array_map(fn($c) => (int)$c['id'], $categories);
    if ($name === '' || mb_strlen($name) > 120 || !in_array($category, $categoryIds, true)) {
        $error = 'Enter an equipment name and choose a valid category.';
    } elseif (mb_strlen($description) > 4000) {
        $error = 'The description must be 4,000 characters or fewer.';
    } elseif (!$photo || ($photo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int)$photo['size'] > 5 * 1024 * 1024) {
        $error = 'Choose a photo no larger than 5 MB.';
    } elseif (!class_exists('finfo') || !function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        $error = 'Image verification is unavailable on this PHP setup. Enable Fileinfo and GD in MAMP, then try again.';
    } else {
        $tmp = (string)$photo['tmp_name'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $dimensions = @getimagesize($tmp);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowed, true) || !$dimensions || $dimensions[0] < 100 || $dimensions[1] < 100 || $dimensions[0] > 4000 || $dimensions[1] > 4000 || $dimensions[0] * $dimensions[1] > 12000000) {
            $error = 'Use a genuine JPEG, PNG or WebP photo between 100 × 100 and 4,000 × 4,000 pixels (12 megapixels maximum).';
        } else {
            $image = @imagecreatefromstring((string)file_get_contents($tmp));
            if (!$image) {
                $error = 'The uploaded photo could not be decoded.';
            } else {
                $dir = __DIR__ . '/../public/assets/images/offers';
                if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) $error = 'The photo could not be saved. Please contact staff.';
                if ($error === '') {
                    $file = bin2hex(random_bytes(20)) . '.jpg';
                    $path = $dir . '/' . $file;
                    if (!imagejpeg($image, $path, 88)) $error = 'The photo could not be saved. Please try again.';
                    else {
                        chmod($path, 0640);
                        try {
                            $s = $pdo->prepare("INSERT INTO equipment_offers(borrower_id,category_id,name,description,photo_path) VALUES(?,?,?,?,?)");
                            $s->execute([(int)$b['id'], $category, $name, $description, 'images/offers/' . $file]);
                            flash('Your equipment offer was sent to the CareShare team for review.');
                            redirect(url('user/offers.php'));
                        } catch (PDOException $e) {
                            @unlink($path);
                            $error = 'The offer could not be saved. Please try again later.';
                        }
                    }
                }
                imagedestroy($image);
            }
        }
    }
}
account_header('Offer equipment', 'offers', $b);
?>
<section class="panel form-panel">
 <h2>Offer equipment to CareShare</h2>
 <p>Staff will review your details and photo. If accepted, the item will be added as awaiting inspection and will not be available to borrow until checked.</p>
 <?php if ($error): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>
 <form method="post" enctype="multipart/form-data" class="form-stack">
  <?= csrf_field() ?>
  <label for="offer-name">Equipment name</label><input id="offer-name" name="name" maxlength="120" required value="<?= h($_POST['name'] ?? '') ?>">
  <label for="offer-category">Category</label><select id="offer-category" name="category_id" required><option value="">Choose a category</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($_POST['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select>
  <label for="offer-description">Description</label><textarea id="offer-description" name="description" maxlength="4000" rows="4"><?= h($_POST['description'] ?? '') ?></textarea>
  <label for="offer-photo">Photo (JPEG, PNG or WebP; maximum 5 MB)</label><input id="offer-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
  <button class="btn btn-primary">Submit for review</button>
 </form>
</section>
<?php account_footer();
