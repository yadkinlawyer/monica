<?php

namespace App\Domains\Contact\ManageAvatar\Web\Controllers;

use App\Domains\Contact\ManageAvatar\Services\DestroyAvatar;
use App\Domains\Contact\ManageAvatar\Services\UpdatePhotoAsAvatar;
use App\Domains\Contact\ManageDocuments\Services\UploadLocalFile;
use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleLocalAvatarController extends Controller
{
    public function update(Request $request, string $vaultId, string $contactId)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max for avatars
        ]);

        // first we upload the file
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'uploaded_file' => $request->file('file'),
            'type' => File::TYPE_AVATAR,
        ];

        $file = (new UploadLocalFile)->execute($data);

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'file_id' => $file->id,
        ];

        (new UpdatePhotoAsAvatar)->execute($data);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
            'success' => true,
            'message' => 'Avatar updated successfully',
            'url' => $file->cdn_url,
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ];

        (new DestroyAvatar)->execute($data);

        return response()->json([
            'data' => route('contact.show', [
                'vault' => $vaultId,
                'contact' => $contactId,
            ]),
        ], 200);
    }
}
