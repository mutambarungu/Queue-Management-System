<?php

use App\Http\Controllers\Admin\AdminServiceRequestController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\QueueCalendarSettingsController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicQueueController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffRequestController;
use App\Http\Controllers\Student\ServiceRequestController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Models\Faq;
use App\Models\Office;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {

    $faqs = Faq::where('is_active', true)->latest()->get();
    $offices = Office::all();
    return view('welcome', compact('faqs', 'offices'));
});

// Route::get('/', function () {
//     return view('hello');
// });

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/api/service-types/{office}', function ($officeId) {
    return \App\Models\ServiceType::where('office_id', $officeId)->get();
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::middleware(['auth'])->group(function () {
        Route::get(
            '/appointments/{appointment}',
            [AppointmentController::class, 'show']
        )->name('appointments.show');
    });

    Route::get(
        '/student/complete-profile',
        [StudentDashboardController::class, 'edit']
    )->name('student.profile.complete');

    Route::put(
        '/student/complete-profile/{id}',
        [StudentDashboardController::class, 'update']
    )->name('student.profile.update');
    // Student routes
    Route::middleware('role:student')->group(function () {
        Route::get('/student/dashboard', [StudentDashboardController::class, 'dashboard']);

        Route::prefix('student')->name('student.')->group(function () {
            Route::get('requests', [ServiceRequestController::class, 'index'])->name('requests.index');
            Route::get('requests/create', [ServiceRequestController::class, 'create'])->name('requests.create');
            Route::post('requests', [ServiceRequestController::class, 'store'])->name('requests.store');
            Route::get('requests/{request}', [ServiceRequestController::class, 'show'])->name('requests.show');
            Route::post('requests/{request}/reply', [ServiceRequestController::class, 'reply'])->name('requests.reply');
            Route::get('appointments', [AppointmentController::class, 'studentIndex'])->name('appointments.index');
            Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
            Route::get('appointments/{appointment}', [AppointmentController::class, 'showStudent'])->name('appointments.show');

            // FAQ routes
            Route::get('faq', [FaqController::class, 'faq'])->name('faq.index');
        });
    });

    // Staff routes
    Route::middleware('role:staff')->group(function () {
        Route::get('/staff/dashboard', [StaffDashboardController::class, 'dashboard']);
        Route::prefix('staff')->name('staff.')->group(function () {
            // Define staff-specific routes here
            Route::get('requests', [StaffRequestController::class, 'index'])->name('requests.index');
            Route::get('requests/{request}', [StaffRequestController::class, 'show'])
                ->name('requests.show');

            Route::post('requests/{request}/reply', [StaffRequestController::class, 'reply'])
                ->name('requests.reply');

            Route::post('requests/{request}/status', [StaffRequestController::class, 'updateStatus'])
                ->name('requests.status');

            Route::put('requests/{request}', [StaffRequestController::class, 'update'])->name('requests.update');
            Route::delete('requests/{request}', [StaffRequestController::class, 'destroy'])->name('requests.destroy');
            Route::post('requests/{request}/reply', [StaffRequestController::class, 'reply'])->name('requests.reply');

            Route::get('appointments', [AppointmentController::class, 'staffIndex'])->name('appointments.index');
            Route::get('appointments/{appointment}', [AppointmentController::class, 'showStaff'])->name('appointments.show');
            Route::post('appointments/{appointment}/update', [AppointmentController::class, 'update'])->name('appointments.update');
            Route::post('requests/{serviceRequest}/appointment', [AppointmentController::class, 'store'])->name('appointments.store');
            Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
            Route::put('staff/appointments/{appointment}/reschedule', [AppointmentController::class, 'rescheduleStaff'])->name('appointments.reschedule');

            Route::resource('service-types', \App\Http\Controllers\Staff\ServiceTypeController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });

        Route::get('/staff/requests/archived', [StaffRequestController::class, 'archived'])
            ->name('staff.requests.archived');

        Route::prefix('staff')->name('staff.')->group(function () {
            Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
            Route::patch('faqs/{faq}/toggle', [FaqController::class, 'toggle'])->name('faqs.toggle');
            Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
            Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
            Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
        });
    });

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'dashboard']);

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
            Route::patch('users/{user}/verify', [UserController::class, 'verify'])
                ->name('users.verify');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('offices', [OfficeController::class, 'index'])->name('offices.index');
            Route::post('offices', [OfficeController::class, 'store'])->name('offices.store');
            Route::put('offices/{office}', [OfficeController::class, 'update'])->name('offices.update');
            Route::delete('offices/{office}', [OfficeController::class, 'destroy'])->name('offices.destroy');
            Route::get('/offices/qrcodes', [OfficeController::class, 'qrCodes'])->name('offices.qrcodes');
            // Optional: Download single QR code as PNG
            Route::get('/office/qrcodes/{office}/download', [OfficeController::class, 'download'])->name('offices.qrcodes.download');
            Route::get('/offices/qrcodes/download-all', [OfficeController::class, 'downloadAllPdf'])->name('offices.qrcodes.downloadAllPdf');
            Route::get('/offices/qrcodes/download-all-zip', [OfficeController::class, 'downloadAllSvgZip'])
                ->name('offices.qrcodes.downloadAllSvgZip');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
            Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('students', [StudentController::class, 'index'])->name('students.index');
            Route::post('students', [StudentController::class, 'store'])->name('students.store');
            Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
            Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('requests', [AdminServiceRequestController::class, 'index'])->name('requests.index');
            Route::get('requests/create', [AdminServiceRequestController::class, 'create'])->name('requests.create');
            Route::post('requests', [AdminServiceRequestController::class, 'store'])->name('requests.store');
            Route::get('requests/{request}', [AdminServiceRequestController::class, 'show'])->name('requests.show');
            Route::put('requests/{request}', [AdminServiceRequestController::class, 'update'])->name('requests.update');
            Route::delete('requests/{request}', [AdminServiceRequestController::class, 'destroy'])->name('requests.destroy');
            Route::post('requests/{request}/reply', [AdminServiceRequestController::class, 'reply'])->name('requests.reply');

            Route::post('requests/{serviceRequest}/appointment', [AppointmentController::class, 'store'])->name('appointments.store');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
            Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
            Route::patch('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

            Route::put('appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
            Route::patch('faqs/{faq}/toggle', [FaqController::class, 'toggle'])->name('faqs.toggle');
            Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
            Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
            Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('queue-calendar', [QueueCalendarSettingsController::class, 'index'])->name('queue-calendar.index');
            Route::put('queue-calendar', [QueueCalendarSettingsController::class, 'update'])->name('queue-calendar.update');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('service-types', \App\Http\Controllers\Admin\ServiceTypeController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });

        Route::prefix('admin')->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
            Route::get('/reports/download/pdf', [ReportController::class, 'downloadPdf'])->name('admin.reports.pdf');
            Route::get('/reports/download/excel', [ReportController::class, 'downloadExcel'])->name('admin.reports.excel');
            Route::get('/reports/download/csv', [ReportController::class, 'downloadCsv'])->name('admin.reports.csv');
        });

        Route::post('/admin/requests/{id}/archive', [AdminServiceRequestController::class, 'archive'])->name('admin.requests.archive');
        Route::get('/requests/archived', [AdminServiceRequestController::class, 'archived'])->name('admin.requests.archived');
        Route::post('/admin/requests/{request}/restore', [AdminServiceRequestController::class, 'restore'])->name('admin.requests.restore');
    });
});

Route::get('/queue/{office}', [PublicQueueController::class, 'show'])
    ->name('queue.public.display');

Route::get('/student/requests/{request}/queue-status', [ServiceRequestController::class, 'status'])
    ->name('student.requests.queueStatus');

Route::get('/seed-admin', function () {
    Artisan::call('db:seed', [
        '--class' => 'AdminSeeder',
        '--force' => true // allow seeding in production if needed
    ]);

    return 'Admin user seeded successfully!';
});
require __DIR__ . '/auth.php';
