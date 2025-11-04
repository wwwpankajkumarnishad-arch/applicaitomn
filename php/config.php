<?php
// Central config and asset references for the project

// Token server URL for Agora RTM/RTC (fallback to localhost for dev)
$TOKEN_SERVER_URL = getenv('TOKEN_SERVER_URL') ?: 'http://localhost:4000';

// Branding
$APP_NAME = 'Astrology Social';
$APP_TAGLINE = 'Consult, connect, and go live with astrologers.';

// External assets (use your own hosted URLs in production)
$APP_LOGO_URL = 'https://placehold.co/64x64?text=AS';
$APP_FAVICON_URL = 'https://placehold.co/32x32?text=AS';

// Social icons (placeholders)
$ICON_CALL = 'https://img.icons8.com/ios-filled/50/ffffff/phone.png';
$ICON_VIDEO = 'https://img.icons8.com/ios-filled/50/ffffff/video-call.png';
$ICON_CHAT = 'https://img.icons8.com/ios-filled/50/ffffff/chat.png';
$ICON_FEED = 'https://img.icons8.com/ios-filled/50/ffffff/activity-feed.png';

// Utility function to safely echo
function h($str) {
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}