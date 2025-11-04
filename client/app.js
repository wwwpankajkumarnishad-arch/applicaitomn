/* Basic Agora RTC + RTM integration using CDN SDKs and backend token service */

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

function addChat(sender, text) {
  const p = document.createElement("p");
  p.innerHTML = `<strong>${sender}:</strong> ${text}`;
  els.chatBox.appendChild(p);
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
  const res = await fetch("http://localhost:4000/agora/rtc-token", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ channelName: channel, uid: 0, role: "PUBLISHER", expireSeconds: 3600 }),
  });
  const data = await res.json();
  return data;
}

// Fetch RTM token from backend
async function getRtmToken(account) {
  const res = await fetch("http://localhost:4000/agora/rtm-token", {
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
    rtmClient = AgoraRTM.createInstance((await getRtmToken(username)).appId);
  }
  const { token } = await getRtmToken(username);
  await rtmClient.login({ token, uid: username });
  rtmChannel = rtmClient.createChannel(channel);
  await rtmChannel.join();
  rtmChannel.on("ChannelMessage", ({ text }, senderId) => {
    addChat(senderId, text);
  });
  addChat("System", `Joined chat channel ${channel}`);
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
  const uid = await rtcClient.join(appId, channel, token, null);

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
  const uid = await rtcClient.join(appId, channel, token, null);

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
  addChat("System", "Left session");
};

// Send chat message
els.sendMessage.onclick = async () => {
  const text = els.chatMessage.value.trim();
  const username = els.username.value.trim();
  if (!text || !rtmChannel) return;
  await rtmChannel.sendMessage({ text });
  addChat(username, text);
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