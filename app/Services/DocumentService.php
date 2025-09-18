<?php

namespace App\Services;

use File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Models\Attachment;
use Illuminate\Support\Str;
// DB transactions are handled by callers (controllers); avoid nested transactions here
use Exception;

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

    /**
     * Save attachment(s) for a model
     * 
     * @param array|UploadedFile $files
     * @param string $modelType (task, comment, note, workspace, project)
     * @param int $modelId
     * @param int|null $userId
     * @return array Array of created Attachment models
     * @throws Exception
     */
    public function saveAttachments($files, $modelType, $modelId, $userId = null)
    {
        // map short names to model class and folder name
        $map = [
            'task' => ['class' => \App\Models\Task::class, 'folder' => 'tasks'],
            'comment' => ['class' => \App\Models\Comment::class, 'folder' => 'comments'],
            'note' => ['class' => \App\Models\Note::class, 'folder' => 'notes'],
            'workspace' => ['class' => \App\Models\Workspace::class, 'folder' => 'workspaces'],
            'project' => ['class' => \App\Models\Project::class, 'folder' => 'projects'],
        ];

        $modelType = strtolower($modelType);
        if (!isset($map[$modelType])) {
            throw new Exception('Tipe model tidak didukung.');
        }

        $modelClass = $map[$modelType]['class'];
        $folder = $map[$modelType]['folder'];

        // ensure the target model exists
        $target = $modelClass::find($modelId);
        if (!$target) {
            throw new Exception('Model tujuan tidak ditemukan.');
        }

        // normalize files to array
        if (!is_array($files)) {
            $files = [$files];
        }

        $created = [];
        $savedFiles = [];

        try {
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $mime = $file->getClientMimeType();
                $size = $file->getSize();

                // save file using existing saveDocs method
                $title = pathinfo($originalName, PATHINFO_FILENAME);
                $disk = "attachments/{$folder}/{$modelId}";
                $fileName = $this->saveDocs($file, $title, $disk, $modelId);
                $path = "{$disk}/{$fileName}";

                // track saved file so we can cleanup on failure
                $savedFiles[] = ['fileName' => $fileName, 'disk' => $disk];

                $attachment = Attachment::create([
                    'uuid' => (string) Str::uuid(),
                    'model_type' => $modelClass,
                    'model_id' => $modelId,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'file_path' => $path,
                    'original_filename' => $originalName,
                    'mime_type' => $mime,
                    'file_size' => $size,
                ]);

                $created[] = $attachment;
            }

            return $created;
        } catch (Exception $e) {
            // cleanup saved files on error (delete any files already written)
            foreach ($savedFiles as $sf) {
                try {
                    $this->deleteDocs($sf['fileName'], $sf['disk']);
                } catch (Exception $_) {
                    // ignore deletion errors
                }
            }

            // rethrow so caller can decide how to handle DB rollback
            throw $e;
        }
    }

    /**
     * Delete attachment and its file
     * 
     * @param Attachment $attachment
     * @param int|null $userId
     * @return bool
     */
    public function deleteAttachment(Attachment $attachment, $userId = null)
    {
        try {
            // extract disk and filename from file_path
            $pathParts = explode('/', $attachment->file_path);
            $fileName = array_pop($pathParts);
            $disk = implode('/', $pathParts);

            // delete physical file
            $this->deleteDocs($fileName, $disk);

            // soft delete the attachment record
            if ($userId) {
                $attachment->deleted_by = $userId;
                $attachment->save();
            }
            $attachment->delete();

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
