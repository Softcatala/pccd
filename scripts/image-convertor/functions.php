<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

const IMAGE_MIN_WIDTH = 350;
const SIZE_THRESHOLD = 5000;
const PNG_MIN_QUALITY = 70;
const PNG_MAX_QUALITY = 95;

/**
 * Returns a list of small images.
 */
function background_test_small_image(string $source_directory, int $minimum_width = IMAGE_MIN_WIDTH): string
{
    $output = '';
    $directory_iterator = new DirectoryIterator($source_directory);
    foreach ($directory_iterator as $file_info) {
        if ($file_info->isDot()) {
            continue;
        }

        $source_file = $file_info->getPathname();
        $filename = $file_info->getFilename();
        if ($filename === '.picasa.ini') {
            continue;
        }

        try {
            $imagick = new Imagick();
            $imagick->readImage($source_file);
            $width = $imagick->getImageWidth();
            if ($width < $minimum_width) {
                $output .= "{$filename} ({$width} px)\n";
            }
        } catch (Exception) {
            $output .= "Error while trying to open {$filename}\n";
        }
    }

    return $output;
}

/**
 * Returns a list of image files with unsupported extensions.
 */
function background_test_unsupported_extensions(string $source_directory): string
{
    $output = '';
    $directory_iterator = new DirectoryIterator($source_directory);
    foreach ($directory_iterator as $file_info) {
        if ($file_info->isDot()) {
            continue;
        }

        $filename = $file_info->getFilename();
        if ($filename === '.picasa.ini') {
            continue;
        }
        if (str_ends_with($filename, '.jpg') || str_ends_with($filename, '.png') || str_ends_with($filename, '.gif')) {
            continue;
        }

        $output .= $filename . "\n";
    }

    return $output;
}

/**
 * Resizes and optimizes images in bulk.
 *
 * TODO:
 *  Monitor jpegli development
 *  Consider https://github.com/lovell/sharp, especially to reduce the number system dev dependencies, and to be able to
 *  use MozJPEG, potentially with good defaults.
 */
function resize_and_optimize_images_bulk(string $source_directory, string $target_directory, int $width): void
{
    $directory_iterator = new DirectoryIterator($source_directory);
    foreach ($directory_iterator as $file_info) {
        if ($file_info->isDot()) {
            continue;
        }
        $source_file = $file_info->getPathname();
        $filename = $file_info->getFilename();
        if ($filename === '.picasa.ini') {
            continue;
        }
        $target_file = $target_directory . '/' . $filename;

        // Only process the file once.
        if (is_file($target_file)) {
            continue;
        }

        $extension = mb_strtolower($file_info->getExtension());

        switch ($extension) {
            case 'gif':
                process_gif($source_file, $target_file);

                break;

            case 'png':
                process_png($source_file, $target_file, $width);

                break;

            case 'jpg':
                process_jpg($source_file, $target_file, $width);

                break;
        }
    }
}

/**
 * Returns whether two files have similar sizes, where $bigger_file is expected to be bigger.
 *
 * This is used to discard lossy compression of images that do not save enough bytes.
 *
 * @phpstan-impure
 */
function files_have_similar_sizes(string $bigger_file, string $smaller_file): bool
{
    $bigger_file_size = filesize($bigger_file);
    $smaller_file_size = filesize($smaller_file);

    return $bigger_file_size > 0 && $smaller_file_size > 0 && ($bigger_file_size - SIZE_THRESHOLD) <= $smaller_file_size;
}

/**
 * Optimizes a GIF image and creates a WEBP version.
 *
 * TODO: consider converting GIF images to AVIF, although support for animated AVIF images is not great yet.
 */
function process_gif(string $source_file, string $target_file): void
{
    // Avoid resizing of GIFs, as they may be animated and that could be problematic
    // Optimize GIF with lossless compression.
    exec("gifsicle --no-warnings -O3 \"{$source_file}\" -o \"{$target_file}\"");

    // Restore original file if its size is not bigger than generated file, or if file was not written.
    if (!is_file($target_file) || filesize($source_file) <= filesize($target_file)) {
        copy($source_file, $target_file);
    }

    // GIF -> WEBP conversion.
    $webp_file = str_ireplace('.gif', '.webp', $target_file);

    // Only process the file once.
    if (is_file($webp_file)) {
        return;
    }

    // Apply lossless compression.
    exec("gif2webp -q 100 -mt -m 6 -quiet \"{$target_file}\" -o \"{$webp_file}\"");

    // Delete WEBP file if it's not significantly smaller than the original file.
    if (files_have_similar_sizes($target_file, $webp_file)) {
        unlink($webp_file);

        // Try again with lossy compression.
        exec("gif2webp -mt -m 6 -quiet -lossy \"{$target_file}\" -o \"{$webp_file}\"");
        if (files_have_similar_sizes($target_file, $webp_file)) {
            unlink($webp_file);
        }
    }
}

/**
 * Resizes an image.
 */
function resize_image(string $source_file, string $target_file, int $width): void
{
    // Scale PNG and JPG images if bigger than provided $width.
    try {
        $imagick = new Imagick();
        $imagick->readImage($source_file);

        // Resize the image if it's wider than the specified width.
        if ($imagick->getImageWidth() > $width) {
            $imagick->resizeImage($width, 0, Imagick::FILTER_LANCZOS, 1);
            $imagick->writeImage($target_file);
        }
    } catch (Exception $exception) {
        echo "Error while trying to resize {$source_file}: {$exception->getMessage()}";
    }

    // Restore original file if it is not significantly bigger, or if the file was not written.
    if (!is_file($target_file) || files_have_similar_sizes($source_file, $target_file)) {
        copy($source_file, $target_file);
    }
}

/**
 * Optimizes a PNG image and creates an AVIF version.
 */
function process_png(string $source_file, string $target_file, int $width): void
{
    // Resize PNG image.
    resize_image($source_file, $target_file, $width);

    // Apply lossy compression to PNGs.
    exec('pngquant --skip-if-larger --quality=' . PNG_MIN_QUALITY . '-' . PNG_MAX_QUALITY . " --ext .png --force \"{$target_file}\"");

    // Optimize PNG with extreme lossless compression using Oxipng and Zopfli. This is currently very slow.
    exec("oxipng --quiet -o3 --strip safe --zopfli \"{$target_file}\"");

    // PNG -> AVIF conversion.
    create_avif_image($source_file, str_ireplace('.png', '.avif', $target_file), $width);
}

/**
 * Optimizes a JPEG image and creates an AVIF version.
 */
function process_jpg(string $source_file, string $target_file, int $width): void
{
    // Resize JPG image.
    resize_image($source_file, $target_file, $width);

    // Optimize JPG with lossless compression.
    exec("jpegoptim --strip-all --quiet \"{$target_file}\"");

    // JPEG -> AVIF conversion.
    create_avif_image($source_file, str_ireplace('.jpg', '.avif', $target_file), $width);
}

/**
 * Creates an AVIF image, resizing it down to a specific width.
 */
function create_avif_image(string $source_file, string $target_file, int $width): void
{
    // Only process the file once.
    if (is_file($target_file)) {
        return;
    }

    try {
        $imagick = new Imagick();
        $imagick->readImage($source_file);
        $imagick->setImageFormat('avif');

        // Resize the image if it's wider than the specified width.
        if ($imagick->getImageWidth() > $width) {
            $imagick->resizeImage($width, 0, Imagick::FILTER_LANCZOS, 1);
        }

        $imagick->writeImage($target_file);

        // Delete AVIF file if it's not significantly smaller than the original file.
        if (files_have_similar_sizes($source_file, $target_file)) {
            unlink($target_file);
        }
    } catch (Exception $exception) {
        echo "Error while trying to process {$source_file}: {$exception->getMessage()}";
    }
}
