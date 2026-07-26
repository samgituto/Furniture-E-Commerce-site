<?php
/**
 * admin/products/upload-helper.php
 * -----------------------------------------------------------------
 * Shared by add.php and edit.php. Validates and moves an uploaded
 * product image: checks the real MIME type (not just the file
 * extension), enforces a size limit, and generates a unique,
 * unguessable filename before saving.
 * -----------------------------------------------------------------
 */
if (!function_exists('handle_product_image_upload')) {
    function handle_product_image_upload(array $file, array &$errors): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
            return null;
        }
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Image must be smaller than 4MB.';
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
            $errors[] = 'Only JPEG, PNG, or WEBP images are allowed.';
            return null;
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $ext; // unique, unguessable filename
        $destination = UPLOAD_PATH . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors[] = 'Could not save the uploaded image.';
            return null;
        }

        return 'assets/images/products/' . $filename;
    }
}
