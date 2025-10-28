<?php
/**
 * Setup world files - Copy game files to world directory
 */

echo "=== WORLD FILES SETUP ===\n\n";

$sourceDir = '/var/www/html/main_script/copyable';
$targetDir = '/var/www/html/sections/servers/testworld';

echo "Source: $sourceDir\n";
echo "Target: $targetDir\n\n";

// Check if source exists
if (!is_dir($sourceDir)) {
    echo "❌ Source directory not found!\n";
    exit(1);
}

// Copy public files
echo "Step 1: Copying public files...\n";
$sourcePublic = $sourceDir . '/public';
$targetPublic = $targetDir . '/public';

if (is_dir($sourcePublic)) {
    // For testing, let's create symlink instead of copy (faster)
    if (is_link($targetPublic)) {
        echo "  ℹ️  Symlink already exists\n";
        unlink($targetPublic);
    }
    
    // On Windows in Docker, symlink might not work, so let's just check what we need
    echo "  Files to copy: \n";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourcePublic, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $count = 0;
    foreach ($iterator as $item) {
        if ($count < 10) { // Show first 10
            echo "    - " . $item->getFilename() . "\n";
            $count++;
        }
    }
    
    echo "  Total items: " . iterator_count($iterator) . "\n";
    
    // For now, let's just copy index.php which is the entry point
    if (file_exists($sourcePublic . '/index.php')) {
        if (!is_dir($targetPublic)) {
            mkdir($targetPublic, 0755, true);
        }
        copy($sourcePublic . '/index.php', $targetPublic . '/index.php');
        echo "  ✓ Copied index.php\n";
    }
} else {
    echo "  ❌ Source public directory not found\n";
}

// Copy include files
echo "\nStep 2: Copying include files...\n";
$sourceInclude = $sourceDir . '/include';
$targetInclude = $targetDir . '/include';

if (is_dir($sourceInclude)) {
    $files = scandir($sourceInclude);
    echo "  Files in source include: " . count($files) . "\n";
    
    // For a quick setup, copy critical files
    $criticalFiles = ['env.php', 'config.php'];
    foreach ($criticalFiles as $file) {
        if (file_exists($sourceInclude . '/' . $file)) {
            copy($sourceInclude . '/' . $file, $targetInclude . '/' . $file);
            echo "  ✓ Copied $file\n";
        } else {
            echo "  ℹ️  $file not found in source\n";
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "World directory structure created.\n";
echo "For full setup, consider:\n";
echo "  1. Symlinking entire public directory\n";
echo "  2. Or copying all files with proper permissions\n";
echo "  3. Configuring world-specific settings\n";

// Check if we can access activate.php through routing
echo "\n=== TESTING ===\n";
if (file_exists($targetPublic . '/index.php')) {
    echo "✓ World index.php exists\n";
    echo "  Access via: http://testworld.travian.local/activate.php?token=<token>\n";
} else {
    echo "⚠️  World index.php missing\n";
}
