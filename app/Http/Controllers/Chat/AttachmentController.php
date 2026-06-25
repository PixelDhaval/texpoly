<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Request $request, Message $message): StreamedResponse
    {
        Gate::authorize('download', $message);

        if (! $message->attachment_path || ! Storage::disk('local')->exists($message->attachment_path)) {
            abort(404, 'Attachment not found.');
        }

        return Storage::disk('local')->download(
            $message->attachment_path,
            $message->attachment_name
        );
    }
}
