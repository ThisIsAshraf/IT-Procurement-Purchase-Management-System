<?php

declare(strict_types=1);

/**
 * Project Directory Tree Generator
 *
 * Generates a clean ASCII folder structure in `folder_tree.txt`,
 * excluding VCS metadata, Composer dependencies, IDE configs,
 * secrets, and runtime upload/log artifacts.
 *
 * Usage:
 *   php generate_tree.php
 *   php generate_tree.php [target_dir] [max_depth]
 */

const EXCLUDED_DIRS = [
    '.git',
    '.github',
    '.vscode',
    '.idea',
    'vendor',
    'node_modules',
];

const EXCLUDED_FILES = [
    '.DS_Store',
    'Thumbs.db',
    '.env',
    'folder_tree.txt',
];

// Directories shown in the tree whose internal runtime files are collapsed
const COLLAPSED_RUNTIME_DIRS = [
    'storage/logs',
    'storage/uploads',
    'storage/backups',
    'public/uploads',
];

/**
 * Recursively build a clean ASCII directory tree.
 *
 * @param array{dirs: int, files: int} $stats
 */
function buildDirectoryTree(
    string $dir,
    string $rootDir,
    string $prefix = '',
    int $depth = 1,
    ?int $maxDepth = null,
    array &$stats = ['dirs' => 0, 'files' => 0]
): string {
    if (!is_dir($dir)) {
        return "Directory does not exist: {$dir}\n";
    }

    if ($maxDepth !== null && $depth > $maxDepth) {
        return '';
    }

    $rawItems = scandir($dir);
    if ($rawItems === false) {
        return '';
    }

    $items = array_values(array_filter(
        array_diff($rawItems, ['.', '..']),
        static function (string $item) use ($dir): bool {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                return !in_array($item, EXCLUDED_DIRS, true);
            }
            return !in_array($item, EXCLUDED_FILES, true);
        }
    ));

    usort($items, static function (string $a, string $b) use ($dir): int {
        $pathA = $dir . DIRECTORY_SEPARATOR . $a;
        $pathB = $dir . DIRECTORY_SEPARATOR . $b;
        $dirA  = is_dir($pathA);
        $dirB  = is_dir($pathB);

        if ($dirA === $dirB) {
            return strnatcasecmp($a, $b);
        }
        return $dirA ? -1 : 1;
    });

    $output       = '';
    $totalItems   = count($items);
    $currentIndex = 0;

    foreach ($items as $item) {
        $currentIndex++;
        $isLast   = ($currentIndex === $totalItems);
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        $isDir    = is_dir($fullPath);

        $branch     = $isLast ? '└── ' : '├── ';
        $nextPrefix = $prefix . ($isLast ? '    ' : '│   ');

        if ($isDir) {
            $stats['dirs']++;
            $relPath = str_replace('\\', '/', ltrim(substr($fullPath, strlen($rootDir)), '/\\'));

            if (in_array($relPath, COLLAPSED_RUNTIME_DIRS, true)) {
                $output .= $prefix . $branch . $item . "/  [runtime files omitted]\n";
                continue;
            }

            $output .= $prefix . $branch . $item . "/\n";
            $output .= buildDirectoryTree($fullPath, $rootDir, $nextPrefix, $depth + 1, $maxDepth, $stats);
        } else {
            $stats['files']++;
            $output .= $prefix . $branch . $item . "\n";
        }
    }

    return $output;
}

$targetDirectory = isset($argv[1]) && is_dir($argv[1])
    ? (string)realpath($argv[1])
    : (string)realpath(__DIR__);

$maxDepth       = isset($argv[2]) && ctype_digit((string)$argv[2]) ? (int)$argv[2] : null;
$outputFilePath = $targetDirectory . DIRECTORY_SEPARATOR . 'folder_tree.txt';

$stats      = ['dirs' => 0, 'files' => 0];
$rootName   = basename($targetDirectory);
$treeBody   = buildDirectoryTree($targetDirectory, $targetDirectory, '', 1, $maxDepth, $stats);

$header  = "# Project Structure: {$rootName}\n";
$header .= '# Generated: ' . date('Y-m-d H:i:s T') . "\n";
$header .= '# Excluded: ' . implode(', ', array_merge(EXCLUDED_DIRS, EXCLUDED_FILES)) . "\n";
$header .= "# Summary: {$stats['dirs']} directories, {$stats['files']} files\n\n";

$treeResult = $header . $rootName . "/\n│\n" . $treeBody;

file_put_contents($outputFilePath, $treeResult);

echo "Folder tree generated successfully at: {$outputFilePath}\n";
echo "Summary: {$stats['dirs']} directories, {$stats['files']} files indexed.\n";
