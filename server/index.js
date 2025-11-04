/**
 * Simple Express server to generate Agora RTC and RTM tokens
 * Also serves the static client for demo
 */
const express = require('express');
const cors = require('cors');
const path = require('path');
const dotenv = require('dotenv');
const { RtcTokenBuilder, RtcRole, RtmTokenBuilder, RtmRole } = require('agora-access-token');

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

// Load from env with fallbacks
const AGORA_APP_ID = process.env.AGORA_APP_ID || '7136c501bc034eb08d062a00d9a31719';
const AGORA_APP_CERTIFICATE = process.env.AGORA_APP_CERTIFICATE || 'e94d1ab535494daca1687e76f166c00b';

function buildRtcToken(channelName, uid, role = RtcRole.PUBLISHER, expireSeconds = 3600) {
  const currentTime = Math.floor(Date.now() / 1000);
  const privilegeExpireTime = currentTime + expireSeconds;

  return RtcTokenBuilder.buildTokenWithUid(
    AGORA_APP_ID,
    AGORA_APP_CERTIFICATE,
    channelName,
    uid,
    role,
    privilegeExpireTime
  );
}

function buildRtmToken(account, role = RtmRole.Rtm_User, expireSeconds = 3600) {
  const currentTime = Math.floor(Date.now() / 1000);
  const privilegeExpireTime = currentTime + expireSeconds;

  return RtmTokenBuilder.buildToken(
    AGORA_APP_ID,
    AGORA_APP_CERTIFICATE,
    account,
    role,
    privilegeExpireTime
  );
}

// Health
app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});

// RTC token for audio/video
app.post('/agora/rtc-token', (req, res) => {
  const { channelName, uid, role, expireSeconds } = req.body || {};
  if (!channelName) {
    return res.status(400).json({ error: 'channelName is required' });
  }
  const numericUid = Number.isInteger(uid) ? uid : 0; // 0 for dynamic uid
  const rtcRole = role === 'SUBSCRIBER' ? RtcRole.SUBSCRIBER : RtcRole.PUBLISHER;
  const token = buildRtcToken(channelName, numericUid, rtcRole, expireSeconds || 3600);
  res.json({ token, appId: AGORA_APP_ID });
});

// RTM token for chat
app.post('/agora/rtm-token', (req, res) => {
  const { account, expireSeconds } = req.body || {};
  if (!account) {
    return res.status(400).json({ error: 'account is required' });
  }
  const token = buildRtmToken(account, RtmRole.Rtm_User, expireSeconds || 3600);
  res.json({ token, appId: AGORA_APP_ID });
});

// Combined endpoint if needed
app.post('/agora/tokens', (req, res) => {
  const { channelName, uid, account, expireSeconds } = req.body || {};
  if (!channelName || !account) {
    return res.status(400).json({ error: 'channelName and account are required' });
  }
  const numericUid = Number.isInteger(uid) ? uid : 0;
  const rtc = buildRtcToken(channelName, numericUid, RtcRole.PUBLISHER, expireSeconds || 3600);
  const rtm = buildRtmToken(account, RtmRole.Rtm_User, expireSeconds || 3600);
  res.json({ rtcToken: rtc, rtmToken: rtm, appId: AGORA_APP_ID });
});

// Serve client
const clientPath = path.join(__dirname, '..', 'client');
app.use(express.static(clientPath));
app.get('*', (req, res) => {
  res.sendFile(path.join(clientPath, 'index.html'));
});

const PORT = process.env.PORT || 4000;
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});