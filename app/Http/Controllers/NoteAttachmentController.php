<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesScopedAttachments;
use App\Models\Attachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves note attachments. Notes are projectless, so there is no short name to
 * scope by — access is gated purely by the attachment policy, which cascades to
 * the note (owner, or public + shared-project member).
 */
class NoteAttachmentController extends Controller
{
    use ServesScopedAttachments;

    public function view(Attachment $attachment): StreamedResponse
    {
        $this->authorizeScopedAttachment(null, $attachment);

        return $this->streamAttachment($attachment);
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorizeScopedAttachment(null, $attachment);

        return $this->downloadAttachment($attachment);
    }

    public function thumbnail(Attachment $attachment): StreamedResponse
    {
        $this->authorizeScopedAttachment(null, $attachment);

        return $this->streamThumbnail($attachment);
    }
}
