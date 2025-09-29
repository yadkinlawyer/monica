<?php

namespace App\Domains\Contact\ManagePhotos\Web\Controllers;

use App\Domains\Contact\ManageDocuments\Services\DestroyFile;
use App\Domains\Contact\ManageDocuments\Services\UploadLocalFile;
use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ModulePhotosViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleLocalPhotoController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'uploaded_file' => $request->file('file'),
            'type' => File::TYPE_PHOTO,
        ];

        $file = (new UploadLocalFile)->execute($data);

        $contact = Contact::where('vault_id', $vaultId)->findOrFail($contactId);
        $contact->files()->save($file);

        return response()->json([
            'data' => ModulePhotosViewHelper::dto($file, $contact),
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'url' => $file->cdn_url,
            'path' => $file->original_url,
        ], 201);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $fileId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'file_id' => $fileId,
        ];

        (new DestroyFile)->execute($data);

        return response()->json([
            'data' => route('contact.photo.index', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }
}
