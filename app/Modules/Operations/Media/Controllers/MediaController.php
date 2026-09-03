<?php

namespace App\Modules\Operations\Media\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Operations\Media\Actions\AuthorizeMediaAccessAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private AuthorizeMediaAccessAction $access) {}

    public function show(Request $request, Media $media): StreamedResponse
    {
        abort_unless($this->access->canDownload($request->user(), $media), 403);

        $conversion = $request->query('conversion') === 'thumb' && $media->hasGeneratedConversion('thumb') ? 'thumb' : '';
        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot($conversion);

        abort_unless($disk->exists($path), 404);

        return $disk->response(
            $path,
            $media->file_name,
            ['Content-Type' => $media->mime_type],
            $request->boolean('download') ? 'attachment' : 'inline',
        );
    }

    public function destroy(Request $request, Media $media): RedirectResponse
    {
        abort_unless($this->access->canDelete($request->user(), $media), 403);

        $name = $media->name;
        $media->delete();

        return back()->with('success', ['key' => 'flash.fileDeleted', 'params' => ['name' => $name]]);
    }
}
