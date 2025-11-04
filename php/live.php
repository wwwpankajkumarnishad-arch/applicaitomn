<?php
$TOKEN_SERVER_URL = getenv('TOKEN_SERVER_URL') ?: 'http://localhost:4000';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Astrology Social - Live Rooms</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
  <script src="https://download.agora.io/sdk/release/AgoraRTM.min.js"></script>
</head>
<body>
  <header class="header">
    <div class="container">
      <h1>Live Rooms</h1>
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
      <h2>Create or Join</h2>
      <div class="grid">
        <div class="form">
          <label>Room Name
            <input id="room" placeholder="e.g., daily_rashifal">
          </label>
          <label>Your Name
            <input id="username" placeholder="e.g., guruji">
          </label>
          <div class="actions">
            <button id="startVideo">Start Video Room</button>
            <button id="startAudio">Start Audio Room</button>
            <button id="leave">Leave</button>
          </div>
          <h3>Room Chat</h3>
          <div id="chatBox" class="chat-box"></div>
          <div class="chat-input">
            <input id="chatMessage" placeholder="Type a message...">
            <button id="sendMessage">Send</button>
          </div>
        </div>

        <div class="media">
          <div class="video-area">
            <div class="video-wrapper">
              <video id="localVideo" autoplay playsinline muted></video>
              <span class="label">You</span>
            </div>
            <div id="remoteContainer" class="remote-container"></div>
          </div>
          <div class="media-actions">
            <button id="toggleMic">Toggle Mic</button>
            <button id="toggleCam">Toggle Camera</button>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container"><small>Powered by Agora RTC + RTM</small></div>
  </footer>

  <script>
    const TOKEN_SERVER_URL = "<?php echo htmlspecialchars($TOKEN_SERVER_URL, ENT_QUOTES, 'UTF-8'); ?>";

    const rtcClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
    let localAudioTrack = null;
    let localVideoTrack = null;
    let joined = false;
    let rtmClient = null;
    let rtmChannel = null;

    const els = {
      room: document.getElementById("room"),
      username: document.getElementById("username"),
      localVideo: document.getElementById("localVideo"),
      remoteContainer: document.getElementById("remoteContainer"),
      chatBox: document.getElementById("chatBox"),
      chatMessage: document.getElementById("chatMessage"),
      startVideo: document.getElementById("startVideo"),
      startAudio: document.getElementById("startAudio"),
      leave: document.getElementById("leave"),
      sendMessage: document.getElementById("sendMessage"),
      toggleMic: document.getElementById("toggleMic"),
      toggleCam: document.getElementById("toggleCam"),
    };

    function addChat(sender, text) {
      const p = document.createElement("p");
      p.innerHTML = `<strong>${sender}:</strong> ${text}`;
      els.chatBox.appendChild(p);
      els.chatBox.scrollTop = els.chatBox.scrollHeight;
    }

    function validateInputs() {
      const channel = els.room.value.trim();
      const username = els.username.value.trim();
      if (!channel || !username) {
        alert("Room and Your Name are required.");
        return null;
      }
      return { channel, username };
    }

    async function getRtcToken(channel) {
      const res = await fetch(TOKEN_SERVER_URL + "/agora/rtc-token", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ channelName: channel, uid: 0, role: "PUBLISHER", expireSeconds: 3600 }),
      });
      return res.json();
    }
    async function getRtmToken(account) {
      const res = await fetch(TOKEN_SERVER_URL + "/agora/rtm-token", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ account, expireSeconds: 3600 }),
      });
      return res.json();
    }

    async function joinChat(username, channel) {
      if (!rtmClient) {
        const rtmInfo = await getRtmToken(username);
        rtmClient = AgoraRTM.createInstance(rtmInfo.appId);
        await rtmClient.login({ token: rtmInfo.token, uid: username });
      }
      rtmChannel = rtmClient.createChannel(channel);
      await rtmChannel.join();
      rtmChannel.on("ChannelMessage", ({ text }, senderId) => addChat(senderId, text));
      addChat("System", `Joined chat ${channel}`);
    }
    async function leaveChat() {
      if (rtmChannel) { await rtmChannel.leave(); rtmChannel = null; }
      if (rtmClient) { await rtmClient.logout(); rtmClient = null; }
    }

    function setupRtcListeners() {
      rtcClient.on("user-published", async (user, mediaType) => {
        await rtcClient.subscribe(user, mediaType);
        if (mediaType === "video") {
          const player = document.createElement("div");
          player.id = `player-${user.uid}`;
          player.className = "video-wrapper";
          const label = document.createElement("span");
          label.className = "label";
          label.textContent = `Remote ${user.uid}`;
          const video = document.createElement("div");
          video.style.width = "100%";
          video.style.height = "220px";
          player.appendChild(video);
          player.appendChild(label);
          els.remoteContainer.appendChild(player);
          user.videoTrack.play(video);
        }
        if (mediaType === "audio") {
          user.audioTrack.play();
        }
      });
      rtcClient.on("user-unpublished", (user) => {
        const el = document.getElementById(`player-${user.uid}`);
        if (el) el.remove();
      });
    }

    els.startVideo.onclick = async () => {
      const inputs = validateInputs();
      if (!inputs) return;
      const { channel, username } = inputs;
      if (joined) return;

      setupRtcListeners();
      const { token, appId } = await getRtcToken(channel);
      await rtcClient.join(appId, channel, token, null);

      localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
      localVideoTrack = await AgoraRTC.createCameraVideoTrack();
      localVideoTrack.play(els.localVideo);

      await rtcClient.publish([localAudioTrack, localVideoTrack]);
      await joinChat(username, channel);
      joined = true;
    };

    els.startAudio.onclick = async () => {
      const inputs = validateInputs();
      if (!inputs) return;
      const { channel, username } = inputs;
      if (joined) return;

      setupRtcListeners();
      const { token, appId } = await getRtcToken(channel);
      await rtcClient.join(appId, channel, token, null);

      localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
      await rtcClient.publish([localAudioTrack]);
      await joinChat(username, channel);
      joined = true;
    };

    els.leave.onclick = async () => {
      if (!joined) return;
      if (localAudioTrack) { localAudioTrack.stop(); localAudioTrack.close(); localAudioTrack = null; }
      if (localVideoTrack) { localVideoTrack.stop(); localVideoTrack.close(); localVideoTrack = null; }
      await rtcClient.leave();
      await leaveChat();
      els.remoteContainer.innerHTML = "";
      els.localVideo.srcObject = null;
      joined = false;
      addChat("System", "Left room");
    };

    let micEnabled = true;
    els.toggleMic.onclick = async () => {
      if (!localAudioTrack) return;
      micEnabled = !micEnabled;
      await localAudioTrack.setEnabled(micEnabled);
      els.toggleMic.textContent = micEnabled ? "Toggle Mic" : "Mic Off";
    };

    let camEnabled = true;
    els.toggleCam.onclick = async () => {
      if (!localVideoTrack) return;
      camEnabled = !camEnabled;
      await localVideoTrack.setEnabled(camEnabled);
      els.toggleCam.textContent = camEnabled ? "Toggle Camera" : "Camera Off";
    };

    els.sendMessage.onclick = async () => {
      const text = els.chatMessage.value.trim();
      const username = els.username.value.trim();
      if (!text || !rtmChannel) return;
      await rtmChannel.sendMessage({ text });
      addChat(username, text);
      els.chatMessage.value = "";
    };
  </script>
</body>
</html>