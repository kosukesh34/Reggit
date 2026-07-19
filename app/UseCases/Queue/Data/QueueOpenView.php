<?php

namespace App\UseCases\Queue\Data;

/** 順番待ち QR 起動ページの表示用データ。不変。 */
class QueueOpenView
{
    /**
     * @param  'line'|'native'|'chooser'|'error'  $mode
     */
    public function __construct(
        public string $mode,
        public string $projectKey,
        public string $token,
        public ?string $lineUrl,
        public ?string $nativeSchemeUrl,
        public ?string $errorMessage,
    ) {}
}
