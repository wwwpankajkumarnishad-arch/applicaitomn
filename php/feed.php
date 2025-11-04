<?php
require_once __DIR__ . '/config.php';
$API = './api.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Feed</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
</head>
<body>
  <header class="header">
    <div class="container">
      <h1>Astrology Social</h1>
      <p>Share insights, connect with astrologers, and go live.</p>
      <nav style="margin-top:8px">
        <a href="./feed.php">Feed</a> |
        <a href="./messages.php">Messages</a> |
        <a href="./live.php">Live Rooms</a> |
        <a href="./index.php">Calls</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <section class="card">
      <h2>Your Profile</h2>
      <div class="grid">
        <div class="form">
          <label>Username
            <input id="username" placeholder="e.g., rahul">
          </label>
          <label>Display Name
            <input id="display" placeholder="e.g., Rahul Kumar">
          </label>
          <label>Bio
            <input id="bio" placeholder="Astrology enthusiast">
          </label>
          <label>Avatar URL
            <input id="avatar" placeholder="https://...">
          </label>
          <div class="actions">
            <button id="saveProfile">Save Profile</button>
          </div>
        </div>
        <div>
          <h3>People</h3>
          <div id="usersList"></div>
        </div>
      </div>
    </section>

    <section class="card">
      <h2>Create a Post</h2>
      <div class="form">
        <label>Text
          <input id="postText" placeholder="Share your insight...">
        </label>
        <label>Image URL (optional)
          <input id="postImage" placeholder="https://...">
        </label>
        <div class="actions">
          <button id="createPost">Post</button>
        </div>
      </div>
    </section>

    <section class="card">
      <h2>Feed</h2>
      <div id="feed"></div>
    </section>
  </main>

  <footer class="footer">
    <div class="container">
      <small>Built with PHP + Agora</small>
    </div>
  </footer>

  <script>
    const API = "<?php echo htmlspecialchars($API, ENT_QUOTES, 'UTF-8'); ?>";

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
      display: document.getElementById('display'),
      bio: document.getElementById('bio'),
      avatar: document.getElementById('avatar'),
      saveProfile: document.getElementById('saveProfile'),
      usersList: document.getElementById('usersList'),
      postText: document.getElementById('postText'),
      postImage: document.getElementById('postImage'),
      createPost: document.getElementById('createPost'),
      feed: document.getElementById('feed'),
    };

    function getUser() {
      return els.username.value.trim();
    }

    function renderUsers(users) {
      els.usersList.innerHTML = '';
      users.forEach(u => {
        const div = document.createElement('div');
        div.style.marginBottom = '8px';
        div.innerHTML = `
          <div style="display:flex;align-items:center;gap:8px">
            <img src="${u.avatar || 'https://placehold.co/40x40'}" alt="" width="40" height="40" style="border-radius:50%">
            <div>
              <strong>${u.display || u.username}</strong>
              <div style="font-size:12px;color:#9fb0d0">@${u.username}</div>
            </div>
            <div style="margin-left:auto">
              <button data-username="${u.username}" class="followBtn">Follow</button>
              <button data-username="${u.username}" class="unfollowBtn">Unfollow</button>
            </div>
          </div>
        `;
        els.usersList.appendChild(div);
      });

      els.usersList.querySelectorAll('.followBtn').forEach(btn => {
        btn.onclick = async () => {
          const follower = getUser();
          const followee = btn.dataset.username;
          if (!follower) return alert('Set your username first');
          try {
            await api('follow', { follower, followee });
            alert('Followed ' + followee);
            loadFeed();
          } catch (e) { alert(e.message); }
        };
      });

      els.usersList.querySelectorAll('.unfollowBtn').forEach(btn => {
        btn.onclick = async () => {
          const follower = getUser();
          const followee = btn.dataset.username;
          if (!follower) return alert('Set your username first');
          try {
            await api('unfollow', { follower, followee });
            alert('Unfollowed ' + followee);
            loadFeed();
          } catch (e) { alert(e.message); }
        };
      });
    }

    function renderFeed(items) {
      els.feed.innerHTML = '';
      items.forEach(p => {
        const div = document.createElement('div');
        div.className = 'card';
        div.innerHTML = `
          <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
            <img src="${(p.author_avatar || 'https://placehold.co/40x40')}" width="40" height="40" style="border-radius:50%">
            <div><strong>${p.author}</strong><div style="font-size:12px;color:#9fb0d0">${new Date(p.createdAt*1000).toLocaleString()}</div></div>
          </div>
          <div style="margin-bottom:8px">${p.text}</div>
          ${p.imageURL ? `<img src="${p.imageURL}" style="max-width:100%;border-radius:10px;margin-bottom:8px">` : ''}
          <div class="actions">
            <button data-id="${p.id}" class="likeBtn">Like (${p.likes.length})</button>
          </div>
          <div>
            ${p.comments.map(c => `<p><strong>${c.username}:</strong> ${c.text}</p>`).join('')}
            <div class="chat-input">
              <input placeholder="Write a comment..." data-id="${p.id}" class="commentInput">
              <button data-id="${p.id}" class="commentBtn">Comment</button>
            </div>
          </div>
        `;
        els.feed.appendChild(div);
      });

      els.feed.querySelectorAll('.likeBtn').forEach(btn => {
        btn.onclick = async () => {
          const username = getUser();
          if (!username) return alert('Set your username first');
          const id = btn.dataset.id;
          try {
            await api('like_post', { id, username });
            loadFeed();
          } catch (e) { alert(e.message); }
        };
      });

      els.feed.querySelectorAll('.commentBtn').forEach(btn => {
        btn.onclick = async () => {
          const username = getUser();
          if (!username) return alert('Set your username first');
          const id = btn.dataset.id;
          const input = els.feed.querySelector(`.commentInput[data-id="${id}"]`);
          const text = (input.value || '').trim();
          if (!text) return;
          try {
            await api('comment_post', { id, username, text });
            loadFeed();
          } catch (e) { alert(e.message); }
        };
      });
    }

    async function loadUsers() {
      try {
        const users = await api('list_users', {}, 'GET');
        renderUsers(users);
      } catch (e) { console.log(e); }
    }

    async function loadFeed() {
      const username = getUser();
      if (!username) return;
      try {
        const items = await api('feed', { username }, 'GET');
        renderFeed(items);
      } catch (e) { console.log(e); }
    }

    els.saveProfile.onclick = async () => {
      const username = getUser();
      if (!username) return alert('Username required');
      try {
        await api('upsert_user', {
          username,
          display: els.display.value.trim(),
          bio: els.bio.value.trim(),
          avatar: els.avatar.value.trim(),
        });
        alert('Profile saved');
        loadUsers();
        loadFeed();
      } catch (e) { alert(e.message); }
    };

    els.createPost.onclick = async () => {
      const author = getUser();
      if (!author) return alert('Set your username first');
      const text = els.postText.value.trim();
      const imageURL = els.postImage.value.trim();
      if (!text) return alert('Post text required');
      try {
        await api('create_post', { author, text, imageURL });
        els.postText.value = '';
        els.postImage.value = '';
        loadFeed();
      } catch (e) { alert(e.message); }
    };

    // Initial load
    loadUsers();
  </script>
</body>
</html>