<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Messages</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
  <script src="https://download.agora.io/sdk/release/AgoraRTM.min.js"></script>
</head>
<body>
  <header class="header">
    <div class="container" style="display:flex;align-items:center;gap:12px">
      <img src="<?php echo h($APP_LOGO_URL); ?>" alt="" width="48" height="48" style="border-radius:10px;border:1px solid #2a335a">
      <div>
        <h1 style="margin:0">Direct Messages</h1>
        <p style="margin:0" class="muted"><?php echo h($APP_TAGLINE); ?></p>
        <nav style="margin-top:8px">
          <a href="./feed.php">Feed</a> |
          <a href="./messages.php">Messages</a> |
          <a href="./live.php">Live Rooms</a> |
          <a href="./index.php">Calls</a>
        </nav>
      </div>
    </div>
  </header>

  <main class="container">
    <section class="card chat-shell">
      <div class="chat-sidebar">
        <div class="chat-profile">
          <img id="meAvatar" src="https://placehold.co/48x48" alt="" class="avatar">
          <div>
            <div class="title-sm">Your Username</div>
            <input id="me" placeholder="e.g., rahul">
          </div>
        </div>
        <div class="chat-target">
          <img id="toAvatar" src="https://placehold.co/40x40" alt="" class="avatar sm">
          <div style="flex:1">
            <div class="title-sm">Recipient</div>
            <input id="to" placeholder="e.g., sita">
          </div>
        </div>
        <div class="actions">
          <button id="startDM">Start Chat</button>
          <button id="leaveDM">Leave</button>
        </div>
        <p class="muted" style="margin-top:10px">Tip: Share the same usernames on another device to join the same DM room.</p>
      </div>

      <div class="chat-main">
        <div class="chat-header">
          <div class="chat-title">
            <img id="chatAvatar" src="https://placehold.co/44x44" class="avatar">
            <div>
              <div class="title">Chat</div>
              <div id="chatStatus" class="muted">Not connected</div>
            </div>
          </div>
        </div>

        <div id="log" class="chat-thread"></div>

        <div class="chat-input-bar">
          <input id="msg" placeholder="Type a message">
          <button id="send">Send</button>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container"><small>Powered by Agora RTM</small></div>
  </footer>

  <script>
    const TOKEN_SERVER_URL = "<?php echo htmlspecialchars($TOKEN_SERVER_URL, ENT_QUOTES, 'UTF-8'); ?>";

    let rtmClient = null;
    let rtmChannel = null;

    const els = {
      me: document.getElementById('me'),
      to: document.getElementById('to'),
      startDM: document.getElementById('startDM'),
      leaveDM: document.getElementById('leaveDM'),
      log: document.getElementById('log'),
      msg: document.getElementById('msg'),
      send: document.getElementById('send'),
      chatStatus: document.getElementById('chatStatus'),
      chatAvatar: document.getElementById('chatAvatar'),
      meAvatar: document.getElementById('meAvatar'),
      toAvatar: document.getElementById('toAvatar'),
    };

    function addBubble(sender, text, isMe=false) {
      const wrap = document.createElement('div');
      wrap.className = 'bubble-wrap ' + (isMe ? 'me' : 'them');

      const bubble = document.createElement('div');
      bubble.className = 'bubble';
      bubble.textContent = text;

      const meta = document.createElement('div');
      meta.className = 'meta';
      meta.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

      wrap.appendChild(bubble);
      wrap.appendChild(meta);
      els.log.appendChild(wrap);
      els.log.scrollTop = els.log.scrollHeight;
    }

    async function getRtmToken(account) {
      const res = await fetch(TOKEN_SERVER_URL + "/agora/rtm-token", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ account, expireSeconds: 3600 }),
      });
      return res.json();
    }

    els.startDM.onclick = async () => {
      const me = els.me.value.trim();
      const to = els.to.value.trim();
      if (!me || !to) return alert('Usernames required');

      const channelName = `dm_${[me, to].sort().join('_')}`;
      const { token, appId } = await getRtmToken(me);
      rtmClient = AgoraRTM.createInstance(appId);
      await rtmClient.login({ token, uid: me });
      rtmChannel = rtmClient.createChannel(channelName);
      await rtmChannel.join();
      rtmChannel.on("ChannelMessage", ({ text }, senderId) => addBubble(senderId, text, senderId === me ? true : false));

      els.chatStatus.textContent = `Connected to ${channelName}`;
      addBubble('System', `Joined DM channel ${channelName}`);

      // avatars preview (placeholder logic)
      els.chatAvatar.src = 'https://placehold.co/44x44';
      els.meAvatar.src = 'https://placehold.co/48x48';
      els.toAvatar.src = 'https://placehold.co/40x40';
    };

    els.leaveDM.onclick = async () => {
      if (rtmChannel) { await rtmChannel.leave(); rtmChannel = null; }
      if (rtmClient) { await rtmClient.logout(); rtmClient = null; }
      els.chatStatus.textContent = 'Not connected';
      addBubble('System', 'Left DM');
    };

    els.send.onclick = async () => {
      const text = els.msg.value.trim();
      const me = els.me.value.trim();
      if (!text || !rtmChannel) return;
      await rtmChannel.sendMessage({ text });
      addBubble(me, text, true);
      els.msg.value = '';
    };
  </script>
</body>
</html>