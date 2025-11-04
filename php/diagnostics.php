<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Diagnostics</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Environment</h2>
      <div class="astro-grid">
        <div class="card">
          <div class="title-sm">BASE_URL</div>
          <div class="muted"><?php echo h($BASE_URL ?? ''); ?></div>
        </div>
        <div class="card">
          <div class="title-sm">TOKEN_SERVER_URL</div>
          <div class="muted" id="tokenServer"><?php echo h($TOKEN_SERVER_URL ?? ''); ?></div>
        </div>
      </div>
    </section>

    <section class="card">
      <h2>Token Server Checks</h2>
      <div id="results"></div>
      <div class="actions" style="margin-top:10px">
        <button id="run">Run Diagnostics</button>
      </div>
      <p class="muted" style="margin-top:8px">This tests Agora token endpoints at TOKEN_SERVER_URL.</p>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const TOKEN_SERVER_URL_BASE = "./agora_proxy.php";
    const results = document.getElementById('results');
    const runBtn = document.getElementById('run');

    function addResult(title, obj, ok=true) {
      const card = document.createElement('div');
      card.className = 'card';
      card.innerHTML = `
        <div class="${ok ? 'title-sm' : 'title-sm'}">${title}</div>
        <pre style="white-space:pre-wrap">${typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2)}</pre>
      `;
      results.appendChild(card);
    }

    async function checkHealth() {
      try {
        const res = await fetch(`${TOKEN_SERVER_URL_BASE}?type=health`);
        const data = await res.json();
        addResult('Health', data, true);
      } catch (e) {
        addResult('Health (error)', String(e), false);
      }
    }

    async function checkRTM() {
      try {
        const res = await fetch(`${TOKEN_SERVER_URL_BASE}?type=rtm`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ account: 'diagnostic_user', expireSeconds: 60 })
        });
        const data = await res.json();
        addResult('RTM Token', data, !!data.token);
      } catch (e) {
        addResult('RTM Token (error)', String(e), false);
      }
    }

    async function checkRTC() {
      try {
        const res = await fetch(`${TOKEN_SERVER_URL_BASE}?type=rtc`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ channelName: 'diagnostic_channel', uid: 0, role: 'PUBLISHER', expireSeconds: 60 })
        });
        const data = await res.json();
        addResult('RTC Token', data, !!data.token);
      } catch (e) {
        addResult('RTC Token (error)', String(e), false);
      }
    }

    runBtn.onclick = async () => {
      results.innerHTML = '';
      await checkHealth();
      await checkRTM();
      await checkRTC();
    };
  </script>
</body>
</html>