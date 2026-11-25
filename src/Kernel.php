<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Event dates are entered and displayed in Tunisian time, whatever the
        // server's php.ini says (XAMPP defaults to Europe/Berlin, hosts often to UTC).
        date_default_timezone_set('Africa/Tunis');

        parent::boot();
    }
}
