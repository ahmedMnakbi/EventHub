<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Stores uploaded event posters.
 *
 * The file must already have been validated by the form (Image constraint).
 * The stored name is built by the server: a slug of the original name, a random
 * part, and an extension guessed from the real content (MIME type), never the
 * extension sent by the client. So "shell.php.jpg" or "../../x.png" cannot do harm.
 */
class FileUploader
{
    public function __construct(
        #[Autowire('%app.event_upload_dir%')]
        private readonly string $targetDirectory,
        private readonly SluggerInterface $slugger,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /** Moves the file into the upload directory and returns its new name. */
    public function upload(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName)->lower()->truncate(50, '', false)->toString() ?: 'affiche';
        $extension = $file->guessExtension() ?? 'bin';
        $fileName = sprintf('%s-%s.%s', $safeName, bin2hex(random_bytes(8)), $extension);

        $file->move($this->targetDirectory, $fileName);

        return $fileName;
    }

    /** Deletes a previously uploaded file. Only bare file names are accepted. */
    public function remove(?string $fileName): void
    {
        if ($fileName === null || $fileName === '' || $fileName !== basename($fileName)) {
            return;
        }

        $this->filesystem->remove($this->targetDirectory.'/'.$fileName);
    }
}
