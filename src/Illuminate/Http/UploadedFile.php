<?php

namespace Illuminate\Http;

use Illuminate\Contracts\Http\UploadedFile as UploadedFileContract;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadedFile extends SymfonyUploadedFile implements UploadedFileContract
{
    use Macroable;

    /**
     * Get the fully qualified path to the file.
     *
     * @return string
     */
    public function path()
    {
        return $this->getRealPath();
    }

    /**
     * Get the file's extension.
     *
     * @return string
     */
    public function extension()
    {
        return $this->guessClientExtension();
    }

    /**
     * Get the file size in bytes.
     *
     * @return int
     */
    public function size()
    {
        return $this->getClientSize();
    }

    /**
     * Get the file mime type.
     *
     * @return string
     */
    public function mimeType()
    {
        return $this->getMimeType();
    }

    /**
     * Get a filename for the file that is the MD5 hash of the contents.
     *
     * @return string
     */
    public function hashName()
    {
        return md5_file($this->path()).'.'.$this->extension();
    }

    /**
     * Create a new file instance from a base instance.
     *
     * @param  \Symfony\Component\HttpFoundation\File\UploadedFile  $file
     * @return static
     */
    public static function createFromBase(SymfonyUploadedFile $file)
    {
        return $file instanceof static ? $file : new static(
            $file->getPathname(), $file->getClientOriginalName(), $file->getClientMimeType(),
            $file->getClientSize(), $file->getError()
        );
    }
}
