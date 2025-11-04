<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Your Bookings</title>
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
          <label>Your Username <input id="username" placeholder="e.g., rahul"></label>
          <div class="actions"><button id="load">Load Bookings</button></div>
        </div>
        <div>
          <div id="list"></div>
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
      list: document.getElementById('list'),
      load: document.getElementById('load'),
    };

    function render(list) {
      els.list.innerHTML = '';
      if (list.length === 0) {
        els.list.innerHTML = '<p class="muted">No bookings yet.</p>';
        return;
      }
      list.forEach(b => {
        const div = document.createElement('div');
        div.className = 'astro-card';
        div.innerHTML = `
          <div style="display:flex;align-items:center;gap:12px">
            <div>
              <div class="title-sm">${new Date(b.start).toLocaleString()} - ${new Date(b.end).toLocaleString()}</div>
              <div class="muted">₹${b.price} • ${b.status}</div>
              <div class="muted">Astrologer: ${b.astrologerId}</div>
            </div>
            <div style="margin-left:auto" class="astro-card-actions">
              ${b.status === 'confirmed' ? `<button class="astro-btn astro-btn-primary sm cancelBtn" data-id="${b.id}">Cancel</button>` : `<button class="astro-btn sm" disabled>${b.status}</button>`}
            </div>
          </div>
        `;
        els.list.appendChild(div);
      });

      els.list.querySelectorAll('.cancelBtn').forEach(btn => {
        btn.onclick = async () => {
          const id = btn.dataset.id;
          const username = els.username.value.trim();
          if (!username) return alert('Set username');
          try {
            await api('cancel_booking', { id, username });
            alert('Canceled: ' + id);
            load();
          } catch (e) { alert(e.message); }
        };
      });
    }

    async function load() {
      const username = els.username.value.trim();
      if (!username) return alert('Enter username');
      try {
        const items = await api('list_bookings', { username }, 'GET');
        render(items);
      } catch (e) { alert(e.message); }
    }

    els.load.onclick = load;
  </script>
</body>
</html>