<?php
require __DIR__ . '/functions.php';
$order = my_order((int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$ROOT = '';
if (!$order) { http_response_code(404); $title = 'Not found'; include __DIR__ . '/partials/header.php'; echo '<h1>Order not found</h1><p><a class="btn" href="index.php">Back to shop</a></p>'; include __DIR__ . '/partials/footer.php'; exit; }

$demo = (string) cfg('bakong_token', '') === '';

// Demo mode only: simulate a successful payment when no Bakong token is configured.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $demo) {
    csrf_check();
    if ($order['status'] === 'approved') mark_paid((int) $order['id']);
    redirect('confirmation.php?id=' . (int) $order['id']);
}
if ($order['status'] === 'paid') redirect('confirmation.php?id=' . (int) $order['id']);

$waitingApproval = $order['status'] === 'pending';
$canPay = $order['status'] === 'approved';
$title = $waitingApproval ? 'Order awaiting approval' : ($canPay ? 'Pay with KHQR' : 'Order status');
include __DIR__ . '/partials/header.php';
?>
<?php if ($waitingApproval): ?>
<div class="center panel narrow">
  <h1>Order awaiting approval</h1>
  <p>Your order <strong>#<?= (int) $order['id'] ?></strong> has been sent to the shop for approval.</p>
  <p id="status" class="muted">We’ll show the payment QR here once your order is approved. This page checks for updates automatically.</p>
  <a class="btn secondary" href="index.php">Continue shopping</a>
</div>
<script>
(function () {
  var status = document.getElementById('status');
  var poll = setInterval(function () {
    fetch('pay_status.php?id=<?= (int) $order['id'] ?>', { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
      if (d.status === 'approved') { clearInterval(poll); location.reload(); }
      else if (d.status === 'rejected') { clearInterval(poll); status.textContent = 'This order was not approved. Please contact the shop if you have questions.'; }
    }).catch(function () {});
  }, 5000);
})();
</script>
<?php elseif (!$canPay): ?>
<div class="center panel narrow">
  <h1>Order <?= e($order['status']) ?></h1>
  <p>Order #<?= (int) $order['id'] ?> cannot be paid in its current status.</p>
  <a class="btn secondary" href="index.php">Continue shopping</a>
</div>
<?php else: ?>
<div class="center panel narrow">
  <h1>Pay with KHQR</h1>
  <p>Order #<?= (int) $order['id'] ?> &middot; Amount: <strong class="price"><?= money($order['total'], $order['currency']) ?></strong></p>
  <div id="qrbox" style="display:inline-block;padding:14px;background:#fff;border:2px solid var(--line);border-radius:20px;box-shadow:var(--shadow)"></div>
  <p>Scan with Bakong or any KHQR-supported banking app.</p>
  <p id="status" class="muted">Waiting for payment…</p>
  <p class="muted">Expires in <strong id="timer">--:--</strong></p>
  <?php if ($demo): ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
      <button class="btn secondary">Demo: simulate successful payment</button>
      <p class="muted"><small>Shown only because no Bakong token is set in config.php.</small></p>
    </form>
  <?php endif; ?>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
  var qrText = <?= json_encode($order['khqr_string']) ?>;
  var expires = <?= (int) $order['expires_at'] ?>;
  var status = document.getElementById('status'), timer = document.getElementById('timer');
  if (window.QRCode) {
    new QRCode(document.getElementById('qrbox'), { text: qrText, width: 300, height: 300, correctLevel: QRCode.CorrectLevel.M });
  } else {
    document.getElementById('qrbox').innerHTML = '<p class="err">Could not load the QR library (check internet).</p>';
  }
  function tick() {
    var s = Math.max(0, Math.floor((expires - Date.now()) / 1000));
    timer.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
  }
  var poll = setInterval(function () {
    fetch('pay_status.php?id=<?= (int) $order['id'] ?>', { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
      if (d.status === 'paid') { status.textContent = 'Payment received! Redirecting…'; location.href = 'confirmation.php?id=<?= (int) $order['id'] ?>'; }
      else if (d.status === 'expired') { clearInterval(poll); status.textContent = 'This QR code has expired. Please place the order again.'; status.className = 'err'; }
    }).catch(function () {});
  }, 3000);
  tick(); setInterval(tick, 1000);
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>
