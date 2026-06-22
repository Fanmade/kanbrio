<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesScopedAttachments;
use App\Models\Attachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController extends Controller
{
    use ServesScopedAttachments;

    /**
     * Stream an attachment back to an authorized user.
     */
    public function __invoke(string $short_name, Attachment $attachment): StreamedResponse
    {
        $this->authorizeScopedAttachment($short_name, $attachment);

        return $this->downloadAttachment($attachment);
    }
}
