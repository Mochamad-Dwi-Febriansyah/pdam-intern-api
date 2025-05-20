<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    /**
     * Upload file ke storage dengan nama unik
     */
    public static function uploadFile(?UploadedFile $file, string $folder, string $disk = 'public'): ?string
    {
        if(!$file) {
            return null;
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $filename, $disk);
    }

    /**
     * Hapus file dari storage 
     */
    public static function deleteFile(?string $filePath, string $disk = 'public'): void
    {
        if($filePath && Storage::disk($disk)->exists($filePath)){
            Storage::disk($disk)->delete($filePath);
        }
    }

    public static function savePdfToStorage($pdf, string $folder = 'documents', string $disk = 'public'): string
    {
        $pdfFileName = Str::uuid() . '.pdf'; // Generate a unique file name
        $path = storage_path("app/{$disk}/{$folder}/" . $pdfFileName);

        // Ensure the folder exists
        if (!file_exists(storage_path("app/{$disk}/{$folder}"))) {
            mkdir(storage_path("app/{$disk}/{$folder}"), 0777, true);
        }

        // Save the file to the folder
        file_put_contents($path, $pdf->output());

        return $path; // Return the full path where the PDF was saved
    }
}


?>