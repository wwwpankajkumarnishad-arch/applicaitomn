<?php
// Simple JSON-file based API for an AstroTalk-like social/consultation app (demo only)
// Uses flat JSON files for storage. Replace with a real database for production.

header('Content-Type: application/json');

$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) { mkdir($DATA_DIR, 0777, true); }

function load_json($name, $default = []) {
  global $DATA_DIR;
  $path = $DATA_DIR . "/$name.json";
  if (!file_exists($path)) { file_put_contents($path, json_encode($default)); }
  $raw = file_get_contents($path);
  $data = json_decode($raw, true);
  return is_array($data) ? $data : $default;
}
function save_json($name, $data) {
  global $DATA_DIR;
  $path = $DATA_DIR . "/$name.json";
  file_put_contents($path, json_encode($data));
}

// Core stores
$users = load_json('users', []);
$follows = load_json('follows', []); // follower -> [followee, ...]
$posts = load_json('posts', []);

// AstroTalk-like domain stores
$astrologers = load_json('astrologers', []); // id -> astrologer
$slots = load_json('slots', []);             // astrologerId -> [ {slotId, start, end, price, status}, ... ]
$bookings = load_json('bookings', []);       // id -> booking
$reviews = load_json('reviews', []);         // astrologerId -> [ {user, rating, text, createdAt}, ... ]
$wallets = load_json('wallets', []);         // username -> balance (int/float)

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function json_ok($data) { echo json_encode(['ok' => true, 'data' => $data]); exit; }
function json_err($msg) { http_response_code(400); echo json_encode(['ok' => false, 'error' => $msg]); exit; }

/* -------------------- Social features -------------------- */

// Create or update user
if ($action === 'upsert_user') {
  $username = trim($_POST['username'] ?? '');
  $display = trim($_POST['display'] ?? '');
  $bio = trim($_POST['bio'] ?? '');
  $avatar = trim($_POST['avatar'] ?? '');
  if ($username === '') json_err('username required');

  $users[$username] = [
    'username' => $username,
    'display' => $display !== '' ? $display : $username,
    'bio' => $bio,
    'avatar' => $avatar,
    'createdAt' => $users[$username]['createdAt'] ?? time(),
  ];
  save_json('users', $users);
  // initialize wallet if absent
  if (!isset($wallets[$username])) { $wallets[$username] = 0; save_json('wallets', $wallets); }
  json_ok($users[$username]);
}

// Get user
if ($action === 'get_user') {
  $username = trim($_GET['username'] ?? '');
  if ($username === '' || !isset($users[$username])) json_err('user not found');
  json_ok($users[$username]);
}

// List users
if ($action === 'list_users') {
  $list = array_values($users);
  usort($list, function ($a, $b) { return strcmp($a['username'], $b['username']); });
  json_ok($list);
}

// Follow / Unfollow
if ($action === 'follow') {
  $follower = trim($_POST['follower'] ?? '');
  $followee = trim($_POST['followee'] ?? '');
  if ($follower === '' || $followee === '') json_err('follower and followee required');
  if (!isset($follows[$follower])) $follows[$follower] = [];
  if (!in_array($followee, $follows[$follower])) $follows[$follower][] = $followee;
  save_json('follows', $follows);
  json_ok(['follower' => $follower, 'followee' => $followee]);
}
if ($action === 'unfollow') {
  $follower = trim($_POST['follower'] ?? '');
  $followee = trim($_POST['followee'] ?? '');
  if ($follower === '' || $followee === '') json_err('follower and followee required');
  if (!isset($follows[$follower])) $follows[$follower] = [];
  $follows[$follower] = array_values(array_filter($follows[$follower], fn($u) => $u !== $followee));
  save_json('follows', $follows);
  json_ok(['follower' => $follower, 'followee' => $followee]);
}

// Posts (simple feed)
if ($action === 'create_post') {
  $author = trim($_POST['author'] ?? '');
  $text = trim($_POST['text'] ?? '');
  $imageURL = trim($_POST['imageURL'] ?? '');
  if ($author === '' || $text === '') json_err('author and text required');
  $id = uniqid('p_', true);
  $posts[$id] = ['id'=>$id,'author'=>$author,'text'=>$text,'imageURL'=>$imageURL,'createdAt'=>time(),'likes'=>[],'comments'=>[]];
  save_json('posts', $posts);
  json_ok($posts[$id]);
}
if ($action === 'like_post') {
  $id = trim($_POST['id'] ?? '');
  $username = trim($_POST['username'] ?? '');
  if ($id === '' || $username === '' || !isset($posts[$id])) json_err('invalid');
  if (!in_array($username, $posts[$id]['likes'])) $posts[$id]['likes'][] = $username;
  save_json('posts', $posts);
  json_ok($posts[$id]);
}
if ($action === 'comment_post') {
  $id = trim($_POST['id'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $text = trim($_POST['text'] ?? '');
  if ($id === '' || $username === '' || $text === '' || !isset($posts[$id])) json_err('invalid');
  $posts[$id]['comments'][] = ['username'=>$username,'text'=>$text,'createdAt'=>time()];
  save_json('posts', $posts);
  json_ok($posts[$id]);
}
if ($action === 'feed') {
  $username = trim($_GET['username'] ?? '');
  $visibleAuthors = array_merge([$username], $follows[$username] ?? []);
  $list = array_values(array_filter($posts, fn($p) => in_array($p['author'], $visibleAuthors)));
  usort($list, fn($a,$b) => $b['createdAt'] <=> $a['createdAt']);
  json_ok($list);
}

/* -------------------- Wallet -------------------- */
if ($action === 'wallet_balance') {
  $username = trim($_GET['username'] ?? '');
  if ($username === '') json_err('username required');
  $balance = $wallets[$username] ?? 0;
  json_ok(['username'=>$username,'balance'=>$balance]);
}
if ($action === 'wallet_topup') {
  $username = trim($_POST['username'] ?? '');
  $amount = floatval($_POST['amount'] ?? 0);
  if ($username === '' || $amount <= 0) json_err('invalid');
  $wallets[$username] = ($wallets[$username] ?? 0) + $amount;
  save_json('wallets', $wallets);
  json_ok(['username'=>$username,'balance'=>$wallets[$username]]);
}

/* -------------------- Astrologers -------------------- */
if ($action === 'upsert_astrologer') {
  $id = trim($_POST['id'] ?? '') ?: uniqid('a_');
  $name = trim($_POST['name'] ?? '');
  $bio = trim($_POST['bio'] ?? '');
  $avatar = trim($_POST['avatar'] ?? '');
  $skills = trim($_POST['skills'] ?? ''); // comma separated
  $ratePerMin = floatval($_POST['ratePerMin'] ?? 0);
  if ($name === '') json_err('name required');

  $astrologers[$id] = [
    'id' => $id,
    'name' => $name,
    'bio' => $bio,
    'avatar' => $avatar,
    'skills' => array_values(array_filter(array_map('trim', explode(',', $skills)), fn($s)=>$s!=='')),
    'ratePerMin' => $ratePerMin,
    'rating' => $astrologers[$id]['rating'] ?? 4.8,
    'reviewsCount' => $astrologers[$id]['reviewsCount'] ?? 0,
  ];
  save_json('astrologers', $astrologers);
  if (!isset($slots[$id])) { $slots[$id] = []; save_json('slots', $slots); }
  json_ok($astrologers[$id]);
}
if ($action === 'list_astrologers') {
  $list = array_values($astrologers);
  // simple sort by name
  usort($list, fn($a,$b) => strcmp($a['name'],$b['name']));
  json_ok($list);
}
if ($action === 'get_astrologer') {
  $id = trim($_GET['id'] ?? '');
  if ($id === '' || !isset($astrologers[$id])) json_err('astrologer not found');
  json_ok($astrologers[$id]);
}

/* -------------------- Slots -------------------- */
if ($action === 'add_slot') {
  $astrologerId = trim($_POST['astrologerId'] ?? '');
  $start = trim($_POST['start'] ?? ''); // ISO date/time
  $end = trim($_POST['end'] ?? '');
  $price = floatval($_POST['price'] ?? 0);
  if ($astrologerId === '' || !isset($astrologers[$astrologerId])) json_err('invalid astrologer');
  if ($start === '' || $end === '' || $price <= 0) json_err('invalid slot data');

  $slotId = uniqid('s_');
  $slot = ['slotId'=>$slotId,'astrologerId'=>$astrologerId,'start'=>$start,'end'=>$end,'price'=>$price,'status'=>'available'];
  $slots[$astrologerId] = $slots[$astrologerId] ?? [];
  $slots[$astrologerId][] = $slot;
  save_json('slots', $slots);
  json_ok($slot);
}
if ($action === 'list_slots') {
  $astrologerId = trim($_GET['astrologerId'] ?? '');
  if ($astrologerId === '' || !isset($astrologers[$astrologerId])) json_err('invalid astrologer');
  $list = array_values($slots[$astrologerId] ?? []);
  // show available first
  usort($list, fn($a,$b) => strcmp($a['start'],$b['start']));
  json_ok($list);
}

/* -------------------- Booking -------------------- */
if ($action === 'create_booking') {
  $username = trim($_POST['username'] ?? '');
  $astrologerId = trim($_POST['astrologerId'] ?? '');
  $slotId = trim($_POST['slotId'] ?? '');
  if ($username === '' || $astrologerId === '' || $slotId === '') json_err('invalid booking data');
  if (!isset($users[$username])) json_err('user not found');
  if (!isset($astrologers[$astrologerId])) json_err('astrologer not found');

  // find slot
  $slotList = $slots[$astrologerId] ?? [];
  $slot = null;
  foreach ($slotList as $s) { if ($s['slotId'] === $slotId) { $slot = $s; break; } }
  if (!$slot) json_err('slot not found');
  if ($slot['status'] !== 'available') json_err('slot not available');

  // charge wallet
  $price = floatval($slot['price']);
  $balance = $wallets[$username] ?? 0;
  if ($balance < $price) json_err('insufficient wallet balance');
  $wallets[$username] = $balance - $price;
  save_json('wallets', $wallets);

  // mark slot taken
  foreach ($slots[$astrologerId] as &$s) {
    if ($s['slotId'] === $slotId) { $s['status'] = 'booked'; }
  }
  unset($s);
  save_json('slots', $slots);

  // create booking
  $id = uniqid('b_');
  $booking = [
    'id' => $id,
    'username' => $username,
    'astrologerId' => $astrologerId,
    'slotId' => $slotId,
    'start' => $slot['start'],
    'end' => $slot['end'],
    'price' => $price,
    'status' => 'confirmed',
    'createdAt' => time(),
  ];
  $bookings[$id] = $booking;
  save_json('bookings', $bookings);
  json_ok($booking);
}
if ($action === 'list_bookings') {
  $username = trim($_GET['username'] ?? '');
  if ($username === '') json_err('username required');
  $list = array_values(array_filter($bookings, fn($b) => $b['username'] === $username));
  usort($list, fn($a,$b) => $b['createdAt'] <=> $a['createdAt']);
  json_ok($list);
}
if ($action === 'cancel_booking') {
  $id = trim($_POST['id'] ?? '');
  $username = trim($_POST['username'] ?? '');
  if ($id === '' || $username === '') json_err('invalid');
  if (!isset($bookings[$id])) json_err('booking not found');
  $booking = $bookings[$id];
  if ($booking['username'] !== $username) json_err('not owner');

  // simple refund to wallet if status confirmed
  if ($booking['status'] === 'confirmed') {
    $wallets[$username] = ($wallets[$username] ?? 0) + floatval($booking['price']);
    save_json('wallets', $wallets);
    $booking['status'] = 'canceled';
    $bookings[$id] = $booking;
    save_json('bookings', $bookings);
    // set slot available again
    foreach ($slots[$booking['astrologerId']] as &$s) {
      if ($s['slotId'] === $booking['slotId']) { $s['status'] = 'available'; }
    }
    unset($s);
    save_json('slots', $slots);
  }
  json_ok($booking);
}

/* -------------------- Reviews -------------------- */
if ($action === 'add_review') {
  $astrologerId = trim($_POST['astrologerId'] ?? '');
  $user = trim($_POST['user'] ?? '');
  $rating = intval($_POST['rating'] ?? 0);
  $text = trim($_POST['text'] ?? '');
  if ($astrologerId === '' || !isset($astrologers[$astrologerId])) json_err('invalid astrologer');
  if ($user === '' || $rating < 1 || $rating > 5) json_err('invalid review');

  $reviews[$astrologerId] = $reviews[$astrologerId] ?? [];
  $reviews[$astrologerId][] = ['user'=>$user,'rating'=>$rating,'text'=>$text,'createdAt'=>time()];
  save_json('reviews', $reviews);

  // recompute rating
  $arr = $reviews[$astrologerId];
  $avg = array_sum(array_map(fn($r)=>$r['rating'], $arr)) / max(count($arr),1);
  $astrologers[$astrologerId]['rating'] = round($avg, 2);
  $astrologers[$astrologerId]['reviewsCount'] = count($arr);
  save_json('astrologers', $astrologers);

  json_ok(['rating'=>$astrologers[$astrologerId]['rating'], 'reviewsCount'=>$astrologers[$astrologerId]['reviewsCount']]);
}
if ($action === 'list_reviews') {
  $astrologerId = trim($_GET['astrologerId'] ?? '');
  if ($astrologerId === '' || !isset($astrologers[$astrologerId])) json_err('invalid astrologer');
  $list = array_values($reviews[$astrologerId] ?? []);
  usort($list, fn($a,$b) => $b['createdAt'] <=> $a['createdAt']);
  json_ok($list);
}

json_err('unknown action');