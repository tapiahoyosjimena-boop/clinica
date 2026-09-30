<?php

namespace App\Filament\Livewire;

use App\Domains\Notifications\Support\FilamentAdminListUrls;
use Filament\Livewire\DatabaseNotifications as FilamentDatabaseNotifications;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\On;

/**
 * Al cerrar el toast de notificación, Filament por defecto borra la fila; aquí se marca como leída para conservar el historial.
 *
 * {@see getNotification} añade `databaseIsRead` y `databaseTargetUrl` (desde `data.url`) para la vista publicada de notificaciones en línea.
 */
class DatabaseNotifications extends FilamentDatabaseNotifications
{
    public function getNotification(DatabaseNotification $notification): Notification
    {
        $data = $notification->data;
        $targetUrl = isset($data['url']) && is_string($data['url'])
            ? FilamentAdminListUrls::normalizeStoredUrl($data['url'])
            : null;

        return Notification::fromDatabase($notification)
            ->date($this->formatNotificationDate($notification->getAttributeValue('created_at')))
            ->viewData([
                'databaseIsRead' => $notification->read_at !== null,
                'databaseTargetUrl' => $targetUrl,
            ]);
    }

    /**
     * Marca la notificación como leída y redirige al listado (data.url).
     * Se usa desde la fila en línea del panel; evita depender de claves que Filament no mapea en {@see Notification::fromArray()}.
     */
    public function markNotificationAsReadAndRedirect(string $id, string $url): void
    {
        $url = trim($url);
        $query = $this->getNotificationsQuery()->where('id', $id);

        if (! $query->exists()) {
            return;
        }

        $query->update(['read_at' => now()]);

        $target = $this->resolveSafeInternalRedirectTarget($url);
        if ($target === null) {
            return;
        }

        $this->redirect($target);
    }

    /**
     * Devuelve una URL relativa (path + query) bajo el panel Filament actual, o null si no es segura.
     * Así evitamos que URLs absolutas guardadas con otro host (localhost vs 127.0.0.1, otro APP_URL) bloqueen el redirect.
     */
    private function resolveSafeInternalRedirectTarget(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $this->isPathAllowedForRedirect($url) ? $url : null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['path']) || ! is_string($parts['path'])) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || ! $this->isTrustedRedirectHost($host)) {
            return null;
        }

        $path = $parts['path'];
        if ($path === '' || $path[0] !== '/') {
            $path = '/'.$path;
        }

        $query = isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== ''
            ? '?'.$parts['query']
            : '';

        $relative = $path.$query;

        return $this->isPathAllowedForRedirect($relative) ? $relative : null;
    }

    private function isTrustedRedirectHost(string $host): bool
    {
        $candidates = array_unique(array_values(array_filter(array_map(
            static function (?string $h): ?string {
                if ($h === null || $h === '') {
                    return null;
                }

                return strtolower($h);
            },
            [
                request()->getHost(),
                parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
            ],
        ))));

        if (in_array($host, $candidates, true)) {
            return true;
        }

        $local = ['localhost', '127.0.0.1', '[::1]'];
        $hostIsLocal = in_array($host, $local, true);
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $local, true) && $hostIsLocal) {
                return true;
            }
        }

        return false;
    }

    private function isPathAllowedForRedirect(string $pathAndQuery): bool
    {
        if (! str_starts_with($pathAndQuery, '/') || str_starts_with($pathAndQuery, '//')) {
            return false;
        }

        $path = parse_url($pathAndQuery, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return false;
        }

        $panelPrefix = '/'.ltrim((string) filament()->getCurrentPanel()->getPath(), '/');

        return $path === $panelPrefix || str_starts_with($path, $panelPrefix.'/');
    }

    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        $query = $this->getNotificationsQuery()->where('id', $id);

        if (! $query->exists()) {
            return;
        }

        $query->update(['read_at' => now()]);
    }
}
