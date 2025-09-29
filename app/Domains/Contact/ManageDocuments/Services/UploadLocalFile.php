<?php

namespace App\Domains\Contact\ManageDocuments\Services;

use App\Models\File;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadLocalFile extends BaseService
{
    private array $data;

    private File $file;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'author_id' => 'required|uuid|exists:users,id',
            'uploaded_file' => 'required|file',
            'type' => 'required|string',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Upload a file locally.
     */
    public function execute(array $data): File
    {
        $this->data = $data;
        $this->validate();
        $this->uploadFile();
        $this->save();

        return $this->file;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function uploadFile(): void
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $this->data['uploaded_file'];

        // Generate unique filename
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;

        // Store file in public storage
        $path = $uploadedFile->storeAs('uploads', $filename, 'public');

        // Update data with file information
        $this->data['uuid'] = Str::uuid();
        $this->data['name'] = $uploadedFile->getClientOriginalName();
        $this->data['original_url'] = Storage::url($path);
        $this->data['cdn_url'] = Storage::url($path);
        $this->data['mime_type'] = $uploadedFile->getMimeType();
        $this->data['size'] = $uploadedFile->getSize();
        $this->data['local_path'] = $path;
    }

    private function save(): void
    {
        $this->file = File::create([
            'vault_id' => $this->data['vault_id'],
            'uuid' => $this->data['uuid'],
            'name' => $this->data['name'],
            'original_url' => $this->data['original_url'],
            'cdn_url' => $this->data['cdn_url'],
            'mime_type' => $this->data['mime_type'],
            'size' => $this->data['size'],
            'type' => $this->data['type'],
        ]);
    }
}
