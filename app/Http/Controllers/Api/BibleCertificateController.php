<?php
// app/Http/Controllers/Api/BibleCertificateController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BibleCertificate;
use App\Models\BibleCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BibleCertificateController extends Controller
{
    /**
     * Получить список сертификатов пользователя
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->isEnrolledInSchool()) {
            return response()->json([
                'success' => false,
                'message' => 'Доступ только для учеников школы'
            ], 403);
        }

        $certificates = BibleCertificate::where('user_id', $user->id)
            ->with('course')
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'course_title' => $cert->course->title,
                    'issued_at' => $cert->issued_at,
                    'pdf_url' => $cert->pdf_url,
                    'uuid' => $cert->certificate_uuid,
                ];
            });

        return response()->json([
            'success' => true,
            'certificates' => $certificates
        ]);
    }

    /**
     * Получить сертификат по UUID (для верификации)
     */
    public function verify($uuid)
    {
        $certificate = BibleCertificate::with(['user', 'course'])
            ->where('certificate_uuid', $uuid)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'certificate' => [
                'uuid' => $certificate->certificate_uuid,
                'user_name' => $certificate->user->full_name,
                'course_title' => $certificate->course->title,
                'issued_at' => $certificate->issued_at->format('d.m.Y')
            ]
        ]);
    }

    /**
 * Скачать PDF сертификата
 */
public function download($uuid)
{
    $certificate = BibleCertificate::with(['user', 'course'])
        ->where('certificate_uuid', $uuid)
        ->firstOrFail();

    $user = Auth::user();

    // Проверка прав: владелец сертификата или админ
    if (!$user || ($user->id !== $certificate->user_id && !$user->isAnyAdmin())) {
        return response()->json([
            'success' => false,
            'message' => 'Нет доступа к этому сертификату'
        ], 403);
    }

    $html = view('pdf.certificate', [
        'certificate' => $certificate,
        'user' => $certificate->user,
        'course' => $certificate->course
    ])->render();
    
    $pdf = Pdf::loadHTML($html);
    $pdf->setPaper('a4', 'landscape');
    $pdf->getOptions()->set('isRemoteEnabled', true);
    $pdf->getOptions()->set('isHtml5ParserEnabled', true);
    $pdf->getOptions()->set('defaultFont', 'DejaVu Sans');

    $filename = "certificate-{$certificate->course->slug}-{$certificate->user->id}.pdf";
    
    return $pdf->download($filename);
}
    /**
 * Сгенерировать сертификат после завершения курса
 */
public function generate($userId, $courseId)
{
    $user = \App\Models\User::findOrFail($userId);
    $course = BibleCourse::findOrFail($courseId);
    
    // Проверяем, не существует ли уже сертификат
    $existing = BibleCertificate::where('user_id', $userId)
        ->where('course_id', $courseId)
        ->first();
        
    if ($existing) {
        return $existing;
    }
    
    // Создаём сертификат
    $certificate = BibleCertificate::create([
        'user_id' => $userId,
        'course_id' => $courseId,
        'certificate_uuid' => (string) Str::uuid(),
        'issued_at' => now(),
    ]);
    
    // Генерируем HTML
    $html = view('pdf.certificate', [
        'certificate' => $certificate,
        'user' => $user,
        'course' => $course
    ])->render();
    
    // Настройки DomPDF
    $pdf = Pdf::loadHTML($html);
    $pdf->setPaper('a4', 'landscape');
    $pdf->getOptions()->set('isRemoteEnabled', true);
    $pdf->getOptions()->set('isHtml5ParserEnabled', true);
    $pdf->getOptions()->set('defaultFont', 'DejaVu Sans');
    
    $pdfContent = $pdf->output();
    $filename = "certificates/{$certificate->certificate_uuid}.pdf";
    
    // Сохраняем на S3
    \Storage::disk('s3')->put($filename, $pdfContent, 'public');
    
    $certificate->pdf_url = \Storage::disk('s3')->url($filename);
    $certificate->save();
    
    return $certificate;
}
}