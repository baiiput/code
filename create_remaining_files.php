<?php
/**
 * Script untuk generate file-file yang tersisa
 * Jalankan sekali untuk membuat semua file
 */

$files = [
    'categories.php' => '<?php /* File untuk manajemen kategori - implement CRUD sederhana */',
    'suppliers.php' => '<?php /* File untuk manajemen supplier - implement CRUD sederhana */',
    'branches.php' => '<?php /* File untuk manajemen cabang - implement CRUD sederhana */',
];

foreach ($files as $filename => $content) {
    file_put_contents($filename, $content);
    echo "Created: $filename\n";
}

echo "\nAll files created successfully!\n";
