<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Wallet</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <div class="grid">
        <div class="form">
          <label>Username <input id="username" placeholder="e.g., rahul"></label>
          <div class="actions">
            <button id="check">Check Balance</button>
          </div>
          <div id="balance" class="title-sm"></div>
          <h3 style="margin-top:16px">Top Up</h3>
          <label>Amount (₹) <input id="amount" placeholder="500"></label>
          <div class="actions">
            <button id="topup">Top Up</button>
          </div>
        </div>
        <div>
          <div class="card">
            <div class="title-sm">How it works</div>
            <p class="muted">This demo wallet is file-based. In production, integrate a payment gateway (Razorpay/Stripe) and securely record transactions.</p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const API = "<?php echo h($API); ?>";

    async function api(action, payload={}, method='POST') {
      const opts = { method, headers: { 'Content-Type': 'application/x-www-form-urlencoded' } };
      const body = new URLSearchParams({ action, ...payload }).toString();
      if (method === 'POST') opts.body = body;
      const url = method === 'GET' ? API + '?' + body : API;
      const res = await fetch(url, opts);
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'api error');
      return data.data;
    }

    const els = {
      username: document.getElementById('username'),
      balance: document.getElementById('balance'),
      amount: document.getElementById('amount'),
      check: document.getElementById('check'),
      topup: document.getElementById('topup'),
    };

    els.check.onclick = async () => {
      const username = els.username.value.trim();
      if (!username) return alert('Enter username');
      try {
        const info = await api('wallet_balance', { username }, 'GET');
        els.balance.textContent = `Balance: ₹${info.balance}`;
      } catch (e) { alert(e.message); }
    };

    els.topup.onclick = async () => {
      const username = els.username.value.trim();
      const amount = parseFloat(els.amount.value.trim());
      if (!username || !(amount > 0)) return alert('Enter username and valid amount');
      try {
        const info = await api('wallet_topup', { username, amount });
        els.balance.textContent = `Balance: ₹${info.balance}`;
        els.amount.value = '';
        alert('Top-up successful');
      } catch (e) { alert(e.message); }
    };
  </script>
</body>
</html>