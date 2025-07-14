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
        Storage::disk('public')->putFileAs($disk, $file, $fileName);

        return $fileName;
    }

    public function deleteDocs($filename, $disk)
    {
        $path = storage_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.$disk.DIRECTORY_SEPARATOR.$filename;

        return File::delete($path);
    }
}
