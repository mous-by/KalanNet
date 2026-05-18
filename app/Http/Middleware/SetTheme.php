<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetTheme
{
    /**
     * Handle an incoming request.
     *
     * The middleware reads the authenticated user's theme_preference (if logged in)
     * or falls back to the value stored in localStorage (via a tiny inline script).
     * It injects a `data-theme` attribute on the <html> element before the response
     * is sent to the browser, preventing a flash of the default theme.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only modify HTML responses
        if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
            $content = $response->getContent();
            $theme = 'bleu-sombre'; // default

            if (auth()->check()) {
                $theme = auth()->user()->theme_preference ?? $theme;
            }

            // Inject data-theme attribute on <html> if not already present
            $pattern = '/<html(?![^>]*data-theme)/i';
            $replacement = "<html data-theme=\"{$theme}\"";
            $content = preg_replace($pattern, $replacement, $content, 1);
            $response->setContent($content);
        }

        return $response;
    }
}
?>
