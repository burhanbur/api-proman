<?php

namespace App\Services;

use File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentService
{
    public function saveDocs(UploadedFile $file, $title, $disk, $id = null)
    {
        $fileName = \Str::slug($title).'-'.date('YmdHis').$id.'.'.$file->getClientOriginalExtension();

        // ensure directory exists in storage/app/public/{disk}
        $publicPath = storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$disk);
        if (!File::exists($publicPath)) {
            // create directory recursively with 0755 permissions
            File::makeDirectory($publicPath, 0755, true);
            // ensure permissions are set (suppress possible warnings)
            @chmod($publicPath, 0755);
        }

        // store the file and ensure it is publicly visible on the 'public' disk
        Storage::disk('public')->putFileAs($disk, $file, $fileName, ['visibility' => 'public']);

        return $fileName;
    }

    public function deleteDocs($filename, $disk)
    {
        $path = storage_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$disk.DIRECTORY_SEPARATOR.$filename;

        return File::delete($path);
    }
}
