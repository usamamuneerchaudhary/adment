<?php

declare(strict_types=1);

namespace Usamamuneerchaudhary\Adment\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdMediaType: string implements HasLabel
{
    case Image = 'image';
    case Gif = 'gif';
    case Video = 'video';

    /** Return the human-readable media type label. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Image => __('Image'),
            self::Gif => __('GIF'),
            self::Video => __('Video'),
        };
    }

    /**
     * Return accepted MIME types for Filament uploads.
     *
     * @return list<string>
     */
    public function acceptedFileTypes(): array
    {
        return match ($this) {
            self::Image => ['image/jpeg', 'image/png', 'image/webp', 'image/avif'],
            self::Gif => ['image/gif'],
            self::Video => ['video/mp4', 'video/webm'],
        };
    }
}
