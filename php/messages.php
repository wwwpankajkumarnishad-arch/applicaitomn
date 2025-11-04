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
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

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

        <div class="card" id="paidInfo" style="margin-top:12px; display:none">
          <div class="title-sm">Paid Session</div>
          <div class="muted">Remaining: <span id="remainingTime">--:--</span></div>
          <div class="actions" style="margin-top:8px">
            <button id="endSession">End Session</button>
          </div>
        </div>
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

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const TOKEN_SERVER_URL_BASE = "./agora_proxy.php";

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
      paidInfo: document.getElementById('paidInfo'),
      remainingTime: document.getElementById('remainingTime'),
      endSession: document.getElementById('endSession'),
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
      const res = await fetch(TOKEN_SERVER_URL_BASE + "?type=rtm", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ account, expireSeconds: 3600 }),
      });
      return res.json();
    }

    // Paid session support via URL params
    const params = new URLSearchParams(window.location.search);
    const sessionId = params.get('sessionId') || '';
    const presetChannel = params.get('channel') || '';
    const presetMe = params.get('me') || '';
    const presetTo = params.get('to') || '';

    let statusTimer = null;

    async function joinChannel(channelName, me) {
      const { token, appId } = await getRtmToken(me);
      rtmClient = AgoraRTM.createInstance(appId);
      await rtmClient.login({ token, uid: me });
      rtmChannel = rtmClient.createChannel(channelName);
      await rtmChannel.join();
      rtmChannel.on("ChannelMessage", ({ text }, senderId) => addBubble(senderId, text, senderId === me));
      els.chatStatus.textContent = `Connected to ${channelName}`;
      addBubble('System', `Joined channel ${channelName}`);
      els.chatAvatar.src = 'https://placehold.co/44x44';
      els.meAvatar.src = 'https://placehold.co/48x48';
      els.toAvatar.src = 'https://placehold.co/40x40';
    }

    async function pollSession() {
      if (!sessionId) return;
      try {
        const res = await fetch('./api.php?action=session_status&id=' + encodeURIComponent(sessionId));
        const data = await res.json();
        if (!data.ok) return;
        const s = data.data;
        const rem = Math.max(0, s.remainingSec|0);
        const m = Math.floor(rem / 60);
        const sec = rem % 60;
        els.remainingTime.textContent = String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
        els.paidInfo.style.display = 'block';
        if (rem <= 0 || s.status !== 'active') {
          // auto end
          clearInterval(statusTimer);
          addBubble('System', 'Session time ended');
          if (rtmChannel) { await rtmChannel.leave(); rtmChannel = null; }
          if (rtmClient) { await rtmClient.logout(); rtmClient = null; }
          els.chatStatus.textContent = 'Ended';
        }
      } catch (e) { /* ignore */ }
    }

    if (sessionId && presetChannel && presetMe && presetTo) {
      // prefill and disable inputs
      els.me.value = presetMe;
      els.to.value = presetTo;
      els.me.disabled = true;
      els.to.disabled = true;
      els.startDM.disabled = true;
      // join paid channel
      joinChannel(presetChannel, presetMe).then(() => {
        statusTimer = setInterval(pollSession, 2000);
      });
      els.endSession.onclick = async () => {
        try {
          const body = new URLSearchParams({ action:'end_chat_session', id: sessionId, username: presetMe });
          const res = await fetch('./api.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
          const data = await res.json();
          if (data.ok) {
            addBubble('System', 'Session ended. Charged ₹' + data.data.charged);
          }
        } catch (e) {}
        pollSession();
      };
    }

    // Default DM flow
    els.startDM.onclick = async () => {
      const me = els.me.value.trim();
      const to = els.to.value.trim();
      if (!me || !to) return alert('Usernames required');

      const channelName = `dm_${[me, to].sort().join('_')}`;
      await joinChannel(channelName, me);
    };

    els.leaveDM.onclick = async () => {
      if (rtmChannel) { await rtmChannel.leave(); rtmChannel = null; }
      if (rtmClient) { await rtmClient.logout(); rtmClient = null; }
      els.chatStatus.textContent = 'Not connected';
      addBubble('System', 'Left DM');
      if (statusTimer) { clearInterval(statusTimer); statusTimer = null; }
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