<?php
// api/view-ebook.php — View E-Book File
require_once __DIR__ . '/../config/db.php';

$bookId = (int)($_GET['id'] ?? 0);

if (!$bookId) {
    die('Invalid book ID');
}

$db = Database::getInstance();
$book = $db->fetchOne("SELECT * FROM libraries WHERE id = ? AND is_active = 1", [$bookId]);

if (!$book) {
    die('Book not found');
}

// Increment view count
$db->execute("UPDATE libraries SET views = views + 1 WHERE id = ?", [$bookId]);

// Get file path
$filePath = resolveUploadedFilePath($book['file_path']);

if (!file_exists($filePath)) {
    die('File not found on server');
}

$contentTypes = [
    'pdf' => 'application/pdf',
    'epub' => 'application/epub+zip',
    'mobi' => 'application/x-mobipocket-ebook',
];

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentType = $contentTypes[$ext] ?? 'application/octet-stream';

$base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $book['title'] ?: 'ebook');
$base = trim($base, '_');
$filename = $base ?: 'ebook';
if ($ext) {
    $filename .= '.' . $ext;
}

header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
