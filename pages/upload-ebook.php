<?php
// pages/upload-ebook.php — Upload E-Book
$title = 'Upload E-Book';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/upload_media_helpers.php';
require_once __DIR__ . '/../includes/media_blob_helpers.php';

$db = Database::getInstance();
$user = currentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
  if (empty($_POST) && empty($_FILES) && $contentLength > 0) {
    $error = 'Upload failed. The request is too large for the server. Increase post_max_size and upload_max_filesize in php.ini.';
  } else {
    $bookTitle = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($bookTitle)) {
      $error = 'Book title is required';
    } elseif (!isset($_FILES['ebook'])) {
      $error = 'Please upload an e-book file';
    } else {
      $file = $_FILES['ebook'];
      $allowedExts = ['pdf', 'epub', 'mobi'];

      if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
          case UPLOAD_ERR_INI_SIZE:
          case UPLOAD_ERR_FORM_SIZE:
            $error = 'File too large. Maximum size is 50MB.';
            break;
          case UPLOAD_ERR_NO_FILE:
            $error = 'Please upload an e-book file';
            break;
          default:
            $error = 'Failed to upload file. Please try again.';
            break;
        }
      } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
          $error = 'Invalid file type. Only PDF, EPUB, and MOBI are allowed.';
        } elseif ($file['size'] > 50 * 1024 * 1024) { // 50MB limit
          $error = 'File too large. Maximum size is 50MB.';
        } else {
          $uploadRoot = __DIR__ . '/../uploads';
          $ebooksDir = $uploadRoot . '/ebooks';
          $thumbDir = $uploadRoot . '/thumbnails';

          if (!brEnsureDirectory($ebooksDir) || !brEnsureDirectory($thumbDir)) {
            $error = 'Upload directory is not writable. Please contact admin.';
          } else {
            $filename = uniqid() . '_' . time() . '.' . $ext;
            $uploadPath = $ebooksDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
              $coverImage = brAutoCaptureThumbnail($uploadPath, $ext, $thumbDir);

              if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $coverFile = $_FILES['cover'];
                $coverExt = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
                if (in_array($coverExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                  $coverFilename = uniqid() . '_cover.' . $coverExt;
                  $coverPath = $thumbDir . '/' . $coverFilename;
                  if (move_uploaded_file($coverFile['tmp_name'], $coverPath)) {
                    $coverImage = APP_URL . '/uploads/thumbnails/' . $coverFilename;
                  }
                }
              }

              $filePath = APP_URL . '/uploads/ebooks/' . $filename;
              $fileSize = (int) $file['size'];

              $conn = $db->getConnection();

              try {
                $conn->beginTransaction();

                $stmt = $conn->prepare(
                  "INSERT INTO libraries (title, author, category, description, file_path, file_size, file_type, cover_image, uploaded_by, is_active)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
                );
                $stmt->execute([$bookTitle, $author, $category, $description, $filePath, $fileSize, $ext, $coverImage, $user['id']]);

                $bookId = (int) $conn->lastInsertId();
                if ($bookId <= 0) {
                  throw new RuntimeException('Could not create library record.');
                }

                $blobSaved = brSaveEntityFileBlob(
                  $db,
                  'libraries',
                  $bookId,
                  $uploadPath,
                  (string) ($file['name'] ?? $filename),
                  $filename,
                  $ext,
                  null,
                  $fileSize,
                  'hybrid',
                  $filePath
                );

                if (!$blobSaved) {
                  throw new RuntimeException('Could not save file in uploaded_files.');
                }

                $conn->commit();

                $success = 'E-book uploaded successfully!';
                header('Location: ' . APP_URL . '/pages/libraries.php');
                exit;
              } catch (Throwable $e) {
                if ($conn->inTransaction()) {
                  $conn->rollBack();
                }
                error_log('E-book upload failed: ' . $e->getMessage());

                if (is_file($uploadPath)) {
                  @unlink($uploadPath);
                }
                if (!empty($coverImage)) {
                  $coverLocal = resolveUploadedFilePath($coverImage);
                  if (is_file($coverLocal)) {
                    @unlink($coverLocal);
                  }
                }

                $error = 'Could not save the e-book. Please run database/setup_database.php and try again.';
              }
            }

            $error = 'Failed to upload file. Please check folder permissions and try again.';
          }
        }
      }
    }
  }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-5">
  <div class="container" style="max-width: 800px;">

    <div class="mb-4">
      <a href="<?= APP_URL ?>/pages/libraries.php" class="text-decoration-none text-muted">
        <i class="bi bi-arrow-left me-2"></i>Back to Library
      </a>
    </div>

    <h1 class="display-6 fw-bold mb-4">📚 Upload E-Book</h1>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="br-card p-4">

      <div class="mb-4">
        <label class="br-form-label">Book Title *</label>
        <input type="text" name="title" class="br-form-control" required
          value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
      </div>

      <div class="row mb-4">
        <div class="col-md-6">
          <label class="br-form-label">Author</label>
          <input type="text" name="author" class="br-form-control"
            value="<?= htmlspecialchars($_POST['author'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="br-form-label">Category</label>
          <select name="category" class="br-form-control">
            <option value="">Select Category</option>
            <option value="Technology">Technology</option>
            <option value="Business">Business</option>
            <option value="Science">Science</option>
            <option value="Fiction">Fiction</option>
            <option value="Non-Fiction">Non-Fiction</option>
            <option value="Education">Education</option>
            <option value="Programming">Programming</option>
            <option value="Self-Help">Self-Help</option>
            <option value="Mathematics">Mathematics</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      <div class="mb-4">
        <label class="br-form-label">Description</label>
        <textarea name="description" class="br-form-control" rows="4"
          placeholder="Brief description of the book..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="mb-4">
        <label class="br-form-label">E-Book File * (PDF, EPUB, MOBI - Max 50MB)</label>
        <input type="file" name="ebook" class="br-form-control" accept=".pdf,.epub,.mobi" required>
      </div>

      <div class="mb-4">
        <label class="br-form-label">Cover Image (Optional)</label>
        <input type="file" name="cover" class="br-form-control" accept="image/*">
        <small class="text-muted">Auto-generated from the file. Upload a custom image only if you want to override
          it.</small>
      </div>

      <button type="submit" class="btn br-btn-gold btn-lg w-100">
        <i class="bi bi-upload me-2"></i>Upload E-Book
      </button>

    </form>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>