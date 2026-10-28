<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReportAttachment extends Model
{
    protected $guarded = [];

    /** URL the browser can show inline, or null when only the Drive link exists. */
    public function previewUrl(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if (preg_match('#^https?://#i', $this->path)) {
            return $this->path;
        }

        return Storage::disk('public')->url($this->path);
    }

    /** Only https links are ever rendered as links. */
    public function safeDriveUrl(): ?string
    {
        return $this->drive_url && str_starts_with($this->drive_url, 'https://') ? $this->drive_url : null;
    }

    public function sizeLabel(): ?string
    {
        return $this->size_bytes ? number_format($this->size_bytes / 1048576, 1) . ' MB' : null;
    }

    public function displayName(): string
    {
        return $this->name ?: basename((string) $this->path) ?: 'Attachment';
    }
}
