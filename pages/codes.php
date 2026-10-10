<?php
function getFilesByFolder(string $root): array
{
    $result = [];
    $root = rtrim($root, DIRECTORY_SEPARATOR . '/');

    if (!is_dir($root)) {
        return $result;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'txt') {
            continue;
        }

        $relativePath = str_replace('\\', '/', $fileInfo->getPathname());
        $relativePath = str_replace($root . '/', '', $relativePath);
        $parts = array_values(array_filter(explode('/', $relativePath), fn($part) => $part !== ''));

        $category = $parts[0] ?? 'Loose files';

        if (count($parts) === 1) {
            $subcategory = 'General';
        } elseif (count($parts) === 2) {
            $subcategory = $parts[1];
        } else {
            // Ignore intermediate labels like "trite" and use the actual parent folder
            // as the subcategory. Example: KT/trite/pine/file.txt => category = KT, subcategory = pine.
            $subcategory = $parts[count($parts) - 2];
        }

        $timezone = new DateTimeZone('Europe/Amsterdam');
        $modifiedDate = new DateTime('@' . $fileInfo->getMTime(), $timezone);
        $modifiedDate->setTimezone($timezone);

        $result[$category][$subcategory][] = [
            'name' => pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME),
            'path' => $fileInfo->getPathname(),
            'modified' => $modifiedDate->format('j M Y, H:i'),
            'content' => file_get_contents($fileInfo->getPathname()),
        ];
    }

    ksort($result);

    foreach ($result as $category => $subcategories) {
        ksort($subcategories);

        foreach ($subcategories as $subcategory => $files) {
            usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));
            $result[$category][$subcategory] = $files;
        }
    }

    return $result;
}

// Point this to the folder you want to read from.
// Because this file lives in /pages, we need to go up one level to reach the project root.
$baseFolder = ' codes/';
$folderError = '';

if (!is_dir($baseFolder)) {
    $folderError = 'Folder not found: ' . $baseFolder;
    $baseFolder = __DIR__ . '/../data/codes/demo';
}

$filesByFolder = getFilesByFolder($baseFolder);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OCSheet | Codes</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
      .main-category {
        margin-bottom: 18px;
      }

      .main-category summary {
        background: #84A98C;
        color: #ffffff;
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 8px;
        font-weight: 700;
        list-style: none;
        cursor: pointer;
      }

      .main-category summary::-webkit-details-marker {
        display: none;
      }

      .folder-group summary,
      .folder-entry summary {
        background: #84A98C;
        color: #ffffff;
        border-radius: 8px;
        padding: 10px 14px;
        margin-bottom: 8px;
      }

      .file-row {
        margin: 6px 0;
        background: transparent;
        border-left: 2px solid #84A98C;
        padding-left: 10px;
      }

      .file-row summary {
        list-style: none;
        cursor: pointer;
        padding: 8px 10px;
        background: #f2f4f1;
        border-radius: 6px;
        color: #1f2d2a;
        font-weight: 600;
      }

      .file-row summary::-webkit-details-marker {
        display: none;
      }

      .file-row .file-content {
        padding: 10px 8px 0;
      }

      .collapse-all-btn {
        border: 1px solid #52796F;
        background: #52796F;
        color: white;
        border-radius: 8px;
        padding: 8px 14px;
        cursor: pointer;
        margin-bottom: 16px;
      }
    </style>
  </head>
  <body>
    <header class="navbar">
      <div class="container nav-inner">
        <nav class="nav-links" aria-label="Main navigation">
          <a href="world.html">World</a>
          <a href="sheets.html">Sheets</a>
          <a href="codes.php" class="active">Codes</a>
          <a href="gallery.html">Gallery</a>
        </nav>
      </div>
    </header>

    <main class="page-shell">
      <div class="codes-wrap">
        <button type="button" class="collapse-all-btn" onclick="collapseAllDetails()">Collapse all</button>
        <?php if (empty($filesByFolder)): ?>
          <p class="error-message">
            <?php echo htmlspecialchars($folderError ?: 'No data found. Check that the folder contains .txt files.'); ?>
          </p>
        <?php else: ?>
          <?php foreach ($filesByFolder as $category => $subcategories): ?>
            <details class="main-category">
              <summary><?php echo htmlspecialchars($category); ?></summary>
              <div class="file-list">
                <?php foreach ($subcategories as $subcategory => $files): ?>
                  <details class="file-item folder-entry">
                    <summary><?php echo htmlspecialchars($subcategory); ?></summary>
                    <div class="file-content">
                      <?php foreach ($files as $file): ?>
                        <details class="file-row">
                          <summary><?php echo htmlspecialchars($file['name']); ?></summary>
                          <div class="file-content">
                            <div class="file-meta">Last edited: <?php echo htmlspecialchars($file['modified']); ?></div>
                            <pre><?php echo htmlspecialchars($file['content'] ?? 'No content available.'); ?></pre>
                          </div>
                        </details>
                      <?php endforeach; ?>
                    </div>
                  </details>
                <?php endforeach; ?>
              </div>
            </details>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>

    <script>
      function collapseAllDetails() {
        document.querySelectorAll('details').forEach(function (detail) {
          detail.removeAttribute('open');
        });
      }
    </script>
  </body>
</html>
