<?php

namespace App\Enums;

enum StageEvidenceType: string
{
    case Text = 'text';
    case Url = 'url';
    case File = 'file';
    case Image = 'image';
    case Video = 'video';
    case Document = 'document';
    case Screenshot = 'screenshot';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text update',
            self::Url => 'External URL',
            self::File => 'File',
            self::Image => 'Image',
            self::Video => 'Video',
            self::Document => 'Document',
            self::Screenshot => 'Screenshot',
        };
    }

    public function requiresFile(): bool
    {
        return in_array($this, [
            self::File,
            self::Image,
            self::Video,
            self::Document,
            self::Screenshot,
        ], true);
    }

    public function requiresUrl(): bool
    {
        return $this === self::Url;
    }

    /**
     * @return list<string>
     */
    public function acceptedMimes(): array
    {
        return match ($this) {
            self::Image, self::Screenshot => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::Video => ['mp4', 'webm', 'mov'],
            self::Document => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            self::File => ['pdf', 'zip', 'rar', 'fig', 'sketch', 'psd', 'ai', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            default => [],
        };
    }
}
