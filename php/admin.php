<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Astrologer Requests</h2>
      <div id="requests"></div>
    </section>

    <section class="card">
      <h2>Astrologers</h2>
      <div id="astros"></div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const API = "<?php echo h($API); ?>";
    const els = {
      requests: document.getElementById('requests'),
      astros: document.getElementById('astros'),
    };

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

    function renderRequests(list) {
      els.requests.innerHTML = '';
      if (list.length === 0) {
        els.requests.innerHTML = '<p class="muted">No requests.</p>';
        return;
      }
      list.forEach(r => {
        const card = document.createElement('div');
        card.className = 'astro-card';
        card.innerHTML = `
          <div style="display:flex;gap:12px;align-items:center">
            <img src="${r.avatar || 'https://placehold.co/64x64'}" width="64" height="64" style="border-radius:12px;border:1px solid #e6e8ef">
            <div>
              <div class="title">${r.name} <span class="muted">(${r.contact})</span></div>
              <div class="muted">${r.skills.join(', ')}</div>
              <div class="muted">₹${r.ratePerMin}/min • ${r.status}</div>
            </div>
            <div style="margin-left:auto" class="astro-card-actions">
              ${r.status === 'pending' ? `
                <button class="astro-btn astro-btn-primary sm approveBtn" data-id="${r.id}">Approve</button>
                <button class="astro-btn sm rejectBtn" data-id="${r.id}">Reject</button>
              ` : `<button class="astro-btn sm" disabled>${r.status}</button>`}
            </div>
          </div>
        `;
        els.requests.appendChild(card);
      });

      els.requests.querySelectorAll('.approveBtn').forEach(btn => {
        btn.onclick = async () => {
          try {
            await api('approve_request', { id: btn.dataset.id });
            load();
          } catch (e) { alert(e.message); }
        };
      });
      els.requests.querySelectorAll('.rejectBtn').forEach(btn => {
        btn.onclick = async () => {
          const reason = prompt('Reason for rejection (optional):') || '';
          try {
            await api('reject_request', { id: btn.dataset.id, reason });
            load();
          } catch (e) { alert(e.message); }
        };
      });
    }

    function renderAstros(list) {
      els.astros.innerHTML = '';
      if (list.length === 0) {
        els.astros.innerHTML = '<p class="muted">No astrologers.</p>';
        return;
      }
      list.forEach(a => {
        const card = document.createElement('div');
        card.className = 'astro-card';
        card.innerHTML = `
          <div style="display:flex;gap:12px;align-items:center">
            <img src="${a.avatar || 'https://placehold.co/64x64'}" width="64" height="64" style="border-radius:12px;border:1px solid #e6e8ef">
            <div>
              <div class="title">${a.name}</div>
              <div class="muted">${a.skills.join(', ')}</div>
              <div class="muted">⭐ ${a.rating} • ${a.reviewsCount} reviews • ₹${a.ratePerMin}/min</div>
            </div>
            <div style="margin-left:auto" class="astro-card-actions">
              <button class="astro-btn sm deleteBtn" data-id="${a.id}">Delete</button>
            </div>
          </div>
        `;
        els.astros.appendChild(card);
      });

      els.astros.querySelectorAll('.deleteBtn').forEach(btn => {
        btn.onclick = async () => {
          if (!confirm('Delete astrologer?')) return;
          try {
            await api('delete_astrologer', { id: btn.dataset.id });
            load();
          } catch (e) { alert(e.message); }
        };
      });
    }

    async function load() {
      try {
        const reqs = await api('list_requests', {}, 'GET');
        renderRequests(reqs);
        const astros = await api('list_astrologers', {}, 'GET');
        renderAstros(astros);
      } catch (e) { alert(e.message); }
    }

    load();
  </script>
</body>
</html>