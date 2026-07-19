<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BibleCourse;
use App\Models\User;
use App\Models\BibleCertificate;
use Illuminate\Support\Str;

class CertificatePreviewController extends Controller
{
    public function preview($courseId)
    {
        $course = BibleCourse::findOrFail($courseId);
        
        // Демо-данные
        $demoUser = new User();
        $demoUser->name = 'Иван';
        $demoUser->last_name = 'Иванов';
        $demoUser->middle_name = 'Иванович';
        
        $demoCertificate = new BibleCertificate();
        $demoCertificate->certificate_uuid = (string) Str::uuid();
        $demoCertificate->issued_at = now();
        
        return view('admin.certificate-preview', [
            'course' => $course,
            'user' => $demoUser,
            'certificate' => $demoCertificate,
        ]);
    }
}