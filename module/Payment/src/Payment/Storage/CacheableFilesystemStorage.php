<?php
namespace Payment\Storage;

use Payum\Core\Storage\FilesystemStorage;

/**
 * Extends FilesystemStorage with __set_state() support,
 * which is required for Zend's config cache (var_export serialization).
 */
class CacheableFilesystemStorage extends FilesystemStorage
{
    public static function __set_state(array $array): self
    {
        return new self(
            $array['storageDir'],
            $array['modelClass'],
            $array['idProperty']
        );
    }
}
