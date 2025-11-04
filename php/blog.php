<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Latest Articles</h2>
      <div id="list"></div>
    </section>

    <section class="card">
      <h2>Write an Article (Demo)</h2>
      <div class="form">
        <label>Author <input id="author" placeholder="your username"></label>
        <label>Title <input id="title" placeholder="e.g., Understanding Mercury Retrograde"></label>
        <label>Content <textarea id="content" rows="4" placeholder="Write your article..."></textarea></label>
        <label>Cover Image URL <input id="imageURL" placeholder="https://..."></label>
        <div class="actions"><button id="publish">Publish</button></div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const API = "<?php echo h($API); ?>";
    const list = document.getElementById('list');
    const els = {
      author: document.getElementById('author'),
      title: document.getElementById('title'),
      content: document.getElementById('content'),
      imageURL: document.getElementById('imageURL'),
      publish: document.getElementById('publish'),
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

    function render(items) {
      list.innerHTML = '';
      if (items.length === 0) {
        list.innerHTML = '<p class="muted">No articles yet.</p>';
        return;
      }
      items.forEach(p => {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
          <div style="display:flex;gap:12px;align-items:center">
            <img src="${p.imageURL || 'https://placehold.co/120x80'}" width="120" height="80" style="border-radius:12px;border:1px solid #e6e8ef;object-fit:cover">
            <div>
              <div class="title">${p.text || '(Untitled)'}</div>
              <div class="muted">By ${p.author} • ${new Date(p.createdAt*1000).toLocaleString()}</div>
            </div>
          </div>
          <div style="margin-top:8px">${p.text}</div>
        `;
        list.appendChild(card);
      });
    }

    async function load() {
      const items = await api('feed', { username: els.author.value.trim() || '' }, 'GET');
      render(items);
    }

    els.publish.onclick = async () => {
      const author = els.author.value.trim();
      const title = els.title.value.trim();
      const content = els.content.value.trim();
      const imageURL = els.imageURL.value.trim();
      if (!author || !content) return alert('Author and content required');
      try {
        await api('upsert_user', { username: author }); // ensure user exists
        await api('create_post', { author, text: title ? (title + ': ' + content) : content, imageURL });
        els.title.value = els.content.value = els.imageURL.value = '';
        load();
      } catch (e) { alert(e.message); }
    };

    load();
  </script>
</body>
</html>