<?php
// Simple JSON-file based API for a social media style app
// NOTE: This is for demo purposes only. Use a real database and authentication for production.

header('Content-Type: application/json');

$DATA_DIR = __DIR__ . '/data';
if (!is_dir($DATA_DIR)) {
  mkdir($DATA_DIR, 0777, true);
}

function load_json($name, $default = []) {
  global $DATA_DIR;
  $path = $DATA_DIR . "/$name.json";
  if (!file_exists($path)) {
    file_put_contents($path, json_encode($default));
  }
  $raw = file_get_contents($path);
  $data = json_decode($raw, true);
  return is_array($data) ? $data : $default;
}

function save_json($name, $data) {
  global $DATA_DIR;
  $path = $DATA_DIR . "/$name.json";
  file_put_contents($path, json_encode($data));
}

$users = load_json('users', []);
$posts = load_json('posts', []);
$follows = load_json('follows', []); // key: follower -> array of followee usernames

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function json_ok($data) {
  echo json_encode(['ok' => true, 'data' => $data]);
  exit;
}
function json_err($msg) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $msg]);
  exit;
}

// Create or get user
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
  json_ok($users[$username]);
}

// Get user
if ($action === 'get_user') {
  $username = trim($_GET['username'] ?? '');
  if ($username === '' || !isset($users[$username])) json_err('user not found');
  json_ok($users[$username]);
}

// Follow user
if ($action === 'follow') {
  $follower = trim($_POST['follower'] ?? '');
  $followee = trim($_POST['followee'] ?? '');
  if ($follower === '' || $followee === '') json_err('follower and followee required');
  if (!isset($follows[$follower])) $follows[$follower] = [];
  if (!in_array($followee, $follows[$follower])) $follows[$follower][] = $followee;
  save_json('follows', $follows);
  json_ok(['follower' => $follower, 'followee' => $followee]);
}

// Unfollow user
if ($action === 'unfollow') {
  $follower = trim($_POST['follower'] ?? '');
  $followee = trim($_POST['followee'] ?? '');
  if ($follower === '' || $followee === '') json_err('follower and followee required');
  if (!isset($follows[$follower])) $follows[$follower] = [];
  $follows[$follower] = array_values(array_filter($follows[$follower], function ($u) use ($followee) { return $u !== $followee; }));
  save_json('follows', $follows);
  json_ok(['follower' => $follower, 'followee' => $followee]);
}

// Create post (text + optional imageURL)
if ($action === 'create_post') {
  $author = trim($_POST['author'] ?? '');
  $text = trim($_POST['text'] ?? '');
  $imageURL = trim($_POST['imageURL'] ?? '');
  if ($author === '' || $text === '') json_err('author and text required');
  $id = uniqid('p_', true);
  $post = [
    'id' => $id,
    'author' => $author,
    'text' => $text,
    'imageURL' => $imageURL,
    'createdAt' => time(),
    'likes' => [],
    'comments' => [],
  ];
  $posts[$id] = $post;
  save_json('posts', $posts);
  json_ok($post);
}

// Like post
if ($action === 'like_post') {
  $id = trim($_POST['id'] ?? '');
  $username = trim($_POST['username'] ?? '');
  if ($id === '' || $username === '' || !isset($posts[$id])) json_err('invalid');
  if (!in_array($username, $posts[$id]['likes'])) $posts[$id]['likes'][] = $username;
  save_json('posts', $posts);
  json_ok($posts[$id]);
}

// Comment post
if ($action === 'comment_post') {
  $id = trim($_POST['id'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $text = trim($_POST['text'] ?? '');
  if ($id === '' || $username === '' || $text === '' || !isset($posts[$id])) json_err('invalid');
  $posts[$id]['comments'][] = [
    'username' => $username,
    'text' => $text,
    'createdAt' => time(),
  ];
  save_json('posts', $posts);
  json_ok($posts[$id]);
}

// List feed for user (their posts + followees)
if ($action === 'feed') {
  $username = trim($_GET['username'] ?? '');
  $followees = $follows[$username] ?? [];
  $visibleAuthors = array_merge([$username], $followees);
  $list = array_values(array_filter($posts, function ($p) use ($visibleAuthors) {
    return in_array($p['author'], $visibleAuthors);
  }));
  // Sort by createdAt desc
  usort($list, function ($a, $b) { return $b['createdAt'] <=> $a['createdAt']; });
  json_ok($list);
}

// List users
if ($action === 'list_users') {
  $list = array_values(array_map(function ($u) { return $u; }, $users));
  usort($list, function ($a, $b) { return strcmp($a['username'], $b['username']); });
  json_ok($list);
}

json_err('unknown action');