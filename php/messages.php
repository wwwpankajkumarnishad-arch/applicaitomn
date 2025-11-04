<?php
$TOKEN_SERVER_URL = getenv('TOKEN_SERVER_URL') ?: 'http://localhost:4000';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Astrology Social - Messages</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <script src="https://download.agora.io/sdk/release/AgoraRTM.min.js"></script>
</head>
<body>
  <header class="header">
    <div class="container">
      <h1>Direct Messages</h1>
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
      <h2>Start a DM</h2>
      <div class="grid">
        <div class="form">
          <label>Your Username
            <input id="me" placeholder="e.g., rahul">
          </label>
          <label>Recipient Username
            <input id="to" placeholder="e.g., sita">
          </label>
          <div class="actions">
            <button id="startDM">Start DM</button>
            <button id="leaveDM">Leave</button>
          </div>
        </div>
        <div>
          <h3>Messages</h3>
          <div id="log" class="chat-box"></div>
          <div class="chat-input">
            <input id="msg" placeholder="Type a message">
            <button id="send">Send</button>
          </div>
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
    };

    function addLog(sender, text) {
      const p = document.createElement('p');
      p.innerHTML = `<strong>${sender}:</strong> ${text}`;
      els.log.appendChild(p);
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
      rtmChannel.on("ChannelMessage", ({ text }, senderId) => addLog(senderId, text));
      addLog('System', `Joined DM channel ${channelName}`);
    };

    els.leaveDM.onclick = async () => {
      if (rtmChannel) { await rtmChannel.leave(); rtmChannel = null; }
      if (rtmClient) { await rtmClient.logout(); rtmClient = null; }
      addLog('System', 'Left DM');
    };

    els.send.onclick = async () => {
      const text = els.msg.value.trim();
      const me = els.me.value.trim();
      if (!text || !rtmChannel) return;
      await rtmChannel.sendMessage({ text });
      addLog(me, text);
      els.msg.value = '';
    };
  </script>
</body>
</html>