<?php
namespace AbnDevs\Installer\Http\Middleware;

use AbnDevs\Installer\Facades\License;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LicensedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Storage::disk('local')->exists('installed')){
            $verifyLicense = License::verify();

            if (! $verifyLicense['status']) {
                flash($verifyLicense['message'], 'error');

                return redirect()->route('installer.license.activation');
            }
        }

        return $next($request);
    }
}
