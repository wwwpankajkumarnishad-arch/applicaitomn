<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo h($APP_NAME); ?> - Calls</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="./styles.css" rel="stylesheet">
  <link rel="icon" href="<?php echo h($APP_FAVICON_URL); ?>">
  <!-- Agora Web SDKs via CDN -->
  <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
  <script src="https://download.agora.io/sdk/release/AgoraRTM.min.js"></script>
</head>
<body class="theme-astro">
  <?php include __DIR__ . '/header.php'; ?>

  <main class="container">
    <section class="card">
      <h2>Join a Session</h2>
      <div class="grid">
        <div class="form">
          <label>
            Channel Name
            <input id="channel" placeholder="e.g., kundli123">
          </label>
          <label>
            Your Name
            <input id="username" placeholder="e.g., Rahul">
          </label>
          <div class="actions">
            <button id="joinVideo">Join Video</button>
            <button id="joinAudio">Join Audio</button>
            <button id="leave">Leave</button>
          </div>
          <h3>Chat</h3>
          <div id="chatBox" class="chat-thread"></div>
          <div class="chat-input-bar">
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

    <section class="card">
      <h2>Popular Services</h2>
      <ul class="services">
        <li>Live Kundli Consultation</li>
        <li>Match Making</li>
        <li>Career Guidance</li>
        <li>Vedic Remedies</li>
      </ul>
    </section>
  </main>

  <footer class="footer">
    <div class="container">
      <small>Powered by Agora</small>
    </div>
  </footer>

  <script>
    // Basic Agora RTC + RTM integration using CDN SDKs and backend token service
    const TOKEN_SERVER_URL_BASE = "./agora_proxy.php";

    const rtcClient = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
    let localAudioTrack = null;
    let localVideoTrack = null;
    let joined = false;
    let rtmClient = null;
    let rtmChannel = null;

    const els = {
      channel: document.getElementById("channel"),
      username: document.getElementById("username"),
      localVideo: document.getElementById("localVideo"),
      remoteContainer: document.getElementById("remoteContainer"),
      chatBox: document.getElementById("chatBox"),
      chatMessage: document.getElementById("chatMessage"),
      joinVideo: document.getElementById("joinVideo"),
      joinAudio: document.getElementById("joinAudio"),
      leave: document.getElementById("leave"),
      sendMessage: document.getElementById("sendMessage"),
      toggleMic: document.getElementById("toggleMic"),
      toggleCam: document.getElementById("toggleCam"),
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
      els.chatBox.appendChild(wrap);
      els.chatBox.scrollTop = els.chatBox.scrollHeight;
    }

    function validateInputs() {
      const channel = els.channel.value.trim();
      const username = els.username.value.trim();
      if (!channel || !username) {
        alert("Channel and Your Name are required.");
        return null;
      }
      return { channel, username };
    }

    // Fetch RTC token from backend
    async function getRtcToken(channel) {
      const res = await fetch(TOKEN_SERVER_URL_BASE + "?type=rtc", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ channelName: channel, uid: 0, role: "PUBLISHER", expireSeconds: 3600 }),
      });
      const data = await res.json();
      return data;
    }

    // Fetch RTM token from backend
    async function getRtmToken(account) {
      const res = await fetch(TOKEN_SERVER_URL + "/agora/rtm-token", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ account, expireSeconds: 3600 }),
      });
      const data = await res.json();
      return data;
    }

    // Join RTM channel for chat
    async function joinChat(username, channel) {
      if (!rtmClient) {
        const rtmInfo = await getRtmToken(username);
        rtmClient = AgoraRTM.createInstance(rtmInfo.appId);
        await rtmClient.login({ token: rtmInfo.token, uid: username });
      }
      rtmChannel = rtmClient.createChannel(channel);
      await rtmChannel.join();
      rtmChannel.on("ChannelMessage", ({ text }, senderId) => {
        addBubble(senderId, text, senderId === username);
      });
      addBubble("System", `Joined chat channel ${channel}`);
    }

    // Leave RTM
    async function leaveChat() {
      if (rtmChannel) {
        await rtmChannel.leave();
        rtmChannel = null;
      }
      if (rtmClient) {
        await rtmClient.logout();
        rtmClient = null;
      }
    }

    // Setup remote stream listeners
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

    // Join video call
    els.joinVideo.onclick = async () => {
      const inputs = validateInputs();
      if (!inputs) return;
      const { channel, username } = inputs;

      if (joined) return;

      setupRtcListeners();

      const { token, appId } = await getRtcToken(channel);
      await rtcClient.join(appId, channel, token, null);

      // Create local tracks
      localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
      localVideoTrack = await AgoraRTC.createCameraVideoTrack();
      localVideoTrack.play(els.localVideo);

      await rtcClient.publish([localAudioTrack, localVideoTrack]);
      await joinChat(username, channel);

      joined = true;
    };

    // Join audio-only call
    els.joinAudio.onclick = async () => {
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

    // Leave session
    els.leave.onclick = async () => {
      if (!joined) return;

      if (localAudioTrack) {
        localAudioTrack.stop();
        localAudioTrack.close();
        localAudioTrack = null;
      }
      if (localVideoTrack) {
        localVideoTrack.stop();
        localVideoTrack.close();
        localVideoTrack = null;
      }
      await rtcClient.leave();
      await leaveChat();
      els.remoteContainer.innerHTML = "";
      els.localVideo.srcObject = null;
      joined = false;
      addBubble("System", "Left session");
    };

    // Send chat message
    els.sendMessage.onclick = async () => {
      const text = els.chatMessage.value.trim();
      const username = els.username.value.trim();
      if (!text || !rtmChannel) return;
      await rtmChannel.sendMessage({ text });
      addBubble(username, text, true);
      els.chatMessage.value = "";
    };

    // Toggle mic
    let micEnabled = true;
    els.toggleMic.onclick = async () => {
      if (!localAudioTrack) return;
      micEnabled = !micEnabled;
      await localAudioTrack.setEnabled(micEnabled);
      els.toggleMic.textContent = micEnabled ? "Toggle Mic" : "Mic Off";
    };

    // Toggle camera
    let camEnabled = true;
    els.toggleCam.onclick = async () => {
      if (!localVideoTrack) return;
      camEnabled = !camEnabled;
      await localVideoTrack.setEnabled(camEnabled);
      els.toggleCam.textContent = camEnabled ? "Toggle Camera" : "Camera Off";
    };
  </script>
</body>
</html>