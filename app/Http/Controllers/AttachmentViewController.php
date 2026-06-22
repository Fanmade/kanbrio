<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesScopedAttachments;
use App\Models\Attachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentViewController extends Controller
{
    use ServesScopedAttachments;

    /**
     * Stream an attachment inline (e.g. to display an embedded image in the
     * browser) rather than forcing a download.
     */
    public function __invoke(string $short_name, Attachment $attachment): StreamedResponse
    {
        $this->authorizeScopedAttachment($short_name, $attachment);

        return $this->streamAttachment($attachment);
    }
}
