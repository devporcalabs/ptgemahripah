<?php

use App\Http\Controllers\Admin\AbsensiMakanController as AdminAbsensiMakanController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceCorrectionController;
use App\Http\Controllers\Admin\BuktiPotongA1Controller;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DeviceController as AdminDeviceController;
use App\Http\Controllers\Admin\DepartemenController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\JabatanController;
use App\Http\Controllers\Admin\KasbonController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SalaryController as AdminSalaryController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TaxController as AdminTaxController;
use App\Http\Controllers\Admin\UpdateController as AdminUpdateController;
use App\Http\Controllers\Admin\WhatsappController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Karyawan\AbsensiMakanController as EmployeeAbsensiMakanController;
use App\Http\Controllers\Karyawan\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Karyawan\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Karyawan\KasbonController as EmployeeKasbonController;
use App\Http\Controllers\Karyawan\LeaveController as EmployeeLeaveController;
use App\Http\Controllers\Karyawan\PayrollController as EmployeePayrollController;
use App\Http\Controllers\Karyawan\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Karyawan\ScheduleController as EmployeeScheduleController;
use App\Http\Controllers\Karyawan\TaxController as EmployeeTaxController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'index'])->name('install.requirements')->middleware('install.guest');
Route::post('/install', [InstallController::class, 'install'])->name('install.run')->middleware('install.guest');
Route::post('/install/test-database', [InstallController::class, 'testDatabase'])->name('install.test-database')->middleware('install.guest');
Route::get('/install/database', fn () => redirect()->route('install.requirements'))->middleware('install.guest');
Route::get('/install/website', fn () => redirect()->route('install.requirements'))->middleware('install.guest');
Route::post('/install/database', [InstallController::class, 'testDatabase'])->name('install.database.store')->middleware('install.guest');
Route::post('/install/website', [InstallController::class, 'install'])->name('install.website.store')->middleware('install.guest');

Route::get('/', function () {
    if (auth()->check() && auth()->user()?->hasAdminPanelAccess()) {
        return redirect()->route('admin.dashboard');
    }

    if (auth()->check() && auth()->user()?->hasCanteenAccess() && ! auth()->user()?->hasAdminPanelAccess()) {
        return redirect()->route('admin.absensi-makan.scanner');
    }

    if (auth()->check() && auth()->user()?->hasEmployeePanelAccess()) {
        return redirect()->route('karyawan.dashboard');
    }

    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('portal')->name('karyawan.')->middleware(['auth', 'role:karyawan'])->group(function (): void {
    Route::get('/', [EmployeeDashboardController::class, 'index'])->name('dashboard');
    Route::get('/jadwal-kerja', [EmployeeScheduleController::class, 'index'])->name('jadwal');
    Route::get('/absensi', [EmployeeAttendanceController::class, 'index'])->name('absensi');
    Route::get('/absensi-makan', [EmployeeAbsensiMakanController::class, 'index'])->name('absensi-makan');
    Route::post('/absensi-makan/claim', [EmployeeAbsensiMakanController::class, 'claim'])->name('absensi-makan.claim');
    Route::get('/payroll', [EmployeePayrollController::class, 'index'])->name('payroll');
    Route::get('/payroll/{gajiKaryawan}/slip', [EmployeePayrollController::class, 'slip'])->name('payroll.slip');
    Route::get('/pajak', [EmployeeTaxController::class, 'index'])->name('pajak');
    Route::get('/izin', [EmployeeLeaveController::class, 'index'])->name('izin');
    Route::post('/izin', [EmployeeLeaveController::class, 'store'])->name('izin.store');
    Route::delete('/izin/{izin}', [EmployeeLeaveController::class, 'destroy'])->name('izin.destroy');
    Route::get('/kasbon', [EmployeeKasbonController::class, 'index'])->name('kasbon');
    Route::get('/profil', [EmployeeProfileController::class, 'index'])->name('profil');
    Route::post('/profil/password', [EmployeeProfileController::class, 'updatePassword'])->name('profil.password');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin|admin|petugas-kantin'])->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::middleware('permission:karyawan.manage')->group(function (): void {
        Route::get('/karyawan', [EmployeeController::class, 'index'])->name('karyawan');
        Route::get('/karyawan-resign', [EmployeeController::class, 'resignIndex'])->name('karyawan.resign');
        Route::get('/jabatan', [JabatanController::class, 'index'])->name('jabatan');
        Route::post('/jabatan', [JabatanController::class, 'store'])->name('jabatan.store');
        Route::put('/jabatan/{jabatan}', [JabatanController::class, 'update'])->name('jabatan.update');
        Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->name('jabatan.destroy');
        Route::get('/departemen', [DepartemenController::class, 'index'])->name('departemen');
        Route::post('/departemen', [DepartemenController::class, 'store'])->name('departemen.store');
        Route::put('/departemen/{departemen}', [DepartemenController::class, 'update'])->name('departemen.update');
        Route::delete('/departemen/{departemen}', [DepartemenController::class, 'destroy'])->name('departemen.destroy');
        Route::get('/karyawan/template', [EmployeeController::class, 'downloadTemplate'])->name('karyawan.template');
        Route::post('/karyawan/import', [EmployeeController::class, 'importCsv'])->name('karyawan.import');
        Route::post('/karyawan/rfid-pairing/start', [EmployeeController::class, 'startRfidPairing'])->name('karyawan.rfid-pairing.start');
        Route::get('/karyawan/rfid-pairing/poll', [EmployeeController::class, 'pollRfidPairing'])->name('karyawan.rfid-pairing.poll');
        Route::get('/karyawan/{karyawan}', [EmployeeController::class, 'show'])->name('karyawan.show');
        Route::post('/karyawan', [EmployeeController::class, 'store'])->name('karyawan.store');
        Route::put('/karyawan/{karyawan}', [EmployeeController::class, 'update'])->name('karyawan.update');
        Route::post('/karyawan/{karyawan}/resign', [EmployeeController::class, 'resign'])->name('karyawan.resign.store');
        Route::post('/karyawan/{karyawan}/clearance', [EmployeeController::class, 'updateClearance'])->name('karyawan.clearance.update');
        Route::post('/karyawan/{karyawan}/reactivate', [EmployeeController::class, 'reactivate'])->name('karyawan.reactivate');
        Route::post('/karyawan/{karyawan}/pair-rfid', [EmployeeController::class, 'pairRfid'])->name('karyawan.pair-rfid');
        Route::delete('/karyawan/{karyawan}', [EmployeeController::class, 'destroy'])->name('karyawan.destroy');
        Route::post('/karyawan/{karyawan}/reset-password', [EmployeeController::class, 'resetPassword'])->name('karyawan.reset-password');
        Route::post('/karyawan/{karyawan}/shift/reset', [EmployeeController::class, 'resetShiftCycle'])->name('karyawan.shift.reset');
    });

    Route::middleware('permission:absensi.view')->group(function (): void {
        Route::get('/absensi', [AdminAttendanceController::class, 'index'])->name('absensi');
        Route::get('/absensi/export', [AdminAttendanceController::class, 'export'])->name('absensi.export');
        Route::post('/absensi/{absensi}/koreksi', [AttendanceCorrectionController::class, 'store'])->name('absensi.corrections.store');
        Route::post('/absensi/koreksi/{attendanceCorrection}/approve', [AttendanceCorrectionController::class, 'approve'])->name('absensi.corrections.approve');
        Route::post('/absensi/koreksi/{attendanceCorrection}/reject', [AttendanceCorrectionController::class, 'reject'])->name('absensi.corrections.reject');
    });

    Route::middleware('permission:absensi.view|absensi_makan.view|absensi_makan.manage')->group(function (): void {
        Route::get('/absensi-makan', [AdminAbsensiMakanController::class, 'index'])->name('absensi-makan');
        Route::get('/absensi-makan/scanner', [AdminAbsensiMakanController::class, 'scanner'])->name('absensi-makan.scanner');
        Route::post('/absensi-makan/verify', [AdminAbsensiMakanController::class, 'verifyScan'])->name('absensi-makan.verify');
        Route::post('/absensi-makan', [AdminAbsensiMakanController::class, 'store'])->name('absensi-makan.store');
        Route::delete('/absensi-makan/{absensiMakan}', [AdminAbsensiMakanController::class, 'destroy'])->name('absensi-makan.destroy');
        Route::get('/absensi-makan/export', [AdminAbsensiMakanController::class, 'export'])->name('absensi-makan.export');
    });

    Route::middleware('permission:gaji.view')->group(function (): void {
        Route::get('/payroll', [AdminSalaryController::class, 'index'])->name('riwayat-gaji');
        Route::get('/payroll/export', [AdminSalaryController::class, 'exportExcel'])->name('riwayat-gaji.export');
        Route::get('/pajak-pph21', [AdminTaxController::class, 'index'])->name('pajak-pph21');
        Route::get('/pajak-pph21/export', [AdminTaxController::class, 'export'])->name('pajak-pph21.export');
        Route::get('/bukti-potong-a1', [BuktiPotongA1Controller::class, 'index'])->name('bukti-potong-a1');
        Route::get('/bukti-potong-a1/export', [BuktiPotongA1Controller::class, 'export'])->name('bukti-potong-a1.export');
        Route::get('/payroll/periode/slips', [AdminSalaryController::class, 'downloadPeriodSlips'])->name('riwayat-gaji.period.slips');
        Route::post('/payroll/periode/refresh', [AdminSalaryController::class, 'refreshPeriod'])->name('riwayat-gaji.period.refresh');
        Route::post('/payroll/periode/submit', [AdminSalaryController::class, 'submitPeriod'])->name('riwayat-gaji.period.submit');
        Route::post('/payroll/periode/approve-1', [AdminSalaryController::class, 'approvePeriodStageOne'])->name('riwayat-gaji.period.approve-1');
        Route::post('/payroll/periode/approve-2', [AdminSalaryController::class, 'approvePeriodStageTwo'])->name('riwayat-gaji.period.approve-2');
        Route::post('/payroll/periode/finalize', [AdminSalaryController::class, 'finalizePeriod'])->name('riwayat-gaji.period.finalize');
        Route::post('/payroll/periode/reopen', [AdminSalaryController::class, 'reopenPeriod'])->name('riwayat-gaji.period.reopen');
        Route::get('/payroll/{gajiKaryawan}/adjustments', [AdminSalaryController::class, 'adjustments'])->name('riwayat-gaji.adjustments');
        Route::post('/payroll/{gajiKaryawan}/adjustments', [AdminSalaryController::class, 'storeAdjustment'])->name('riwayat-gaji.adjustments.store');
        Route::put('/payroll/adjustments/{gajiTambahan}', [AdminSalaryController::class, 'updateAdjustment'])->name('riwayat-gaji.adjustments.update');
        Route::delete('/payroll/adjustments/{gajiTambahan}', [AdminSalaryController::class, 'destroyAdjustment'])->name('riwayat-gaji.adjustments.destroy');
        Route::post('/payroll/{gajiKaryawan}/kasbon', [AdminSalaryController::class, 'updateKasbon'])->name('riwayat-gaji.kasbon');
        Route::post('/payroll/{gajiKaryawan}/finalize', [AdminSalaryController::class, 'finalize'])->name('riwayat-gaji.finalize');
        Route::post('/payroll/{gajiKaryawan}/unfinalize', [AdminSalaryController::class, 'unfinalize'])->name('riwayat-gaji.unfinalize');
        Route::get('/payroll/{gajiKaryawan}/slip', [AdminSalaryController::class, 'slip'])->name('riwayat-gaji.slip');
    });

    Route::middleware('permission:kasbon.manage')->group(function (): void {
        Route::get('/kasbon', [KasbonController::class, 'index'])->name('kasbon');
        Route::get('/kasbon/{karyawan}/history', [KasbonController::class, 'history'])->name('kasbon.history');
        Route::post('/kasbon', [KasbonController::class, 'store'])->name('kasbon.store');
    });

    Route::middleware('permission:izin.manage')->group(function (): void {
        Route::get('/izin', [AdminPermissionController::class, 'index'])->name('izin');
        Route::get('/izin/preview-kuota', [AdminPermissionController::class, 'previewQuota'])->name('izin.preview-kuota');
        Route::post('/izin', [AdminPermissionController::class, 'store'])->name('izin.store');
        Route::post('/izin/{izin}/approve', [AdminPermissionController::class, 'approve'])->name('izin.approve');
        Route::post('/izin/{izin}/reject', [AdminPermissionController::class, 'reject'])->name('izin.reject');
        Route::post('/izin/{izin}/note', [AdminPermissionController::class, 'updateNote'])->name('izin.note');
        Route::delete('/izin/{izin}', [AdminPermissionController::class, 'destroy'])->name('izin.destroy');
    });

    Route::middleware('permission:laporan.view')->group(function (): void {
        Route::get('/laporan', [AdminReportController::class, 'index'])->name('laporan');
        Route::get('/laporan/export', [AdminReportController::class, 'exportExcel'])->name('laporan.export');
    });

    Route::middleware('permission:shift.manage')->group(function (): void {
        Route::get('/jam-shift', [ShiftController::class, 'index'])->name('jam-shift');
        Route::get('/jam-shift/{shift}', [ShiftController::class, 'show'])->name('jam-shift.show');
        Route::post('/jam-shift', [ShiftController::class, 'store'])->name('jam-shift.store');
        Route::put('/jam-shift/{shift}', [ShiftController::class, 'update'])->name('jam-shift.update');
        Route::delete('/jam-shift/{shift}', [ShiftController::class, 'destroy'])->name('jam-shift.destroy');
        Route::get('/kalender', [HariLiburController::class, 'index'])->name('kalender');
        Route::post('/kalender', [HariLiburController::class, 'store'])->name('kalender.store');
        Route::post('/kalender/sync', [HariLiburController::class, 'sync'])->name('kalender.sync');
        Route::put('/kalender/{hariLibur}', [HariLiburController::class, 'update'])->name('kalender.update');
        Route::delete('/kalender/{hariLibur}', [HariLiburController::class, 'destroy'])->name('kalender.destroy');
    });

    Route::middleware('permission:lokasi.manage')->group(function (): void {
        Route::get('/radius-gps', [LocationController::class, 'index'])->name('radius-gps');
        Route::post('/radius-gps', [LocationController::class, 'store'])->name('radius-gps.store');
        Route::put('/radius-gps/{lokasiGps}', [LocationController::class, 'update'])->name('radius-gps.update');
        Route::delete('/radius-gps/{lokasiGps}', [LocationController::class, 'destroy'])->name('radius-gps.destroy');
        Route::post('/radius-gps/{lokasiGps}/status', [LocationController::class, 'updateStatus'])->name('radius-gps.status');
        Route::post('/radius-gps/{lokasiGps}/default', [LocationController::class, 'makeDefault'])->name('radius-gps.default');
    });

    Route::middleware('permission:whatsapp.manage')->group(function (): void {
        Route::get('/whatsapp-gateway', [WhatsappController::class, 'index'])->name('whatsapp-gateway');
        Route::post('/whatsapp-gateway/settings', [WhatsappController::class, 'updateSettings'])->name('whatsapp-gateway.settings');
        Route::post('/whatsapp-gateway/test', [WhatsappController::class, 'sendTest'])->name('whatsapp-gateway.test');
        Route::post('/whatsapp-gateway/broadcast', [WhatsappController::class, 'broadcast'])->name('whatsapp-gateway.broadcast');
    });

    Route::middleware('permission:profil.manage')->group(function (): void {
        Route::get('/profil', [AdminProfileController::class, 'index'])->name('profil');
        Route::post('/profil', [AdminProfileController::class, 'updateProfile'])->name('profil.update');
        Route::delete('/profil/logo', [AdminProfileController::class, 'destroyLogo'])->name('profil.logo.destroy');
        Route::delete('/profil/favicon', [AdminProfileController::class, 'destroyFavicon'])->name('profil.favicon.destroy');
        Route::post('/profil/password', [AdminProfileController::class, 'updatePassword'])->name('profil.password');
    });

    Route::middleware('permission:sistem.manage')->group(function (): void {
        Route::get('/sistem', [SystemController::class, 'index'])->name('sistem');
        Route::get('/sistem/payroll', [SystemController::class, 'payroll'])->name('payroll-settings');
        Route::get('/sistem/update', [AdminUpdateController::class, 'index'])->name('update-aplikasi');
        Route::get('/sistem/update/progress', [AdminUpdateController::class, 'progress'])->name('update-aplikasi.progress');
        Route::post('/sistem/update/check', [AdminUpdateController::class, 'check'])->name('update-aplikasi.check');
        Route::post('/sistem/update/install', [AdminUpdateController::class, 'install'])->name('update-aplikasi.install');
        Route::get('/sistem/jenis-cuti', [LeaveTypeController::class, 'index'])->name('jenis-izin');
        Route::post('/sistem/jenis-cuti', [LeaveTypeController::class, 'store'])->name('jenis-izin.store');
        Route::post('/sistem/jenis-cuti/policy', [LeaveTypeController::class, 'updatePolicy'])->name('jenis-izin.policy');
        Route::put('/sistem/jenis-cuti/{jenisIzin}', [LeaveTypeController::class, 'update'])->name('jenis-izin.update');
        Route::delete('/sistem/jenis-cuti/{jenisIzin}', [LeaveTypeController::class, 'destroy'])->name('jenis-izin.destroy');
        Route::post('/sistem/zona-waktu', [SystemController::class, 'updateTimezone'])->name('sistem.timezone');
        Route::post('/sistem/payroll', [SystemController::class, 'updatePayrollSettings'])->name('payroll-settings.update');
        Route::get('/sistem/backup', [SystemController::class, 'downloadBackup'])->name('sistem.backup');
        Route::post('/sistem/reset-data', [SystemController::class, 'resetData'])->name('sistem.reset-data');
        Route::post('/sistem/reset-all', [SystemController::class, 'resetAll'])->name('sistem.reset-all');
    });

    Route::middleware('permission:device.manage')->group(function (): void {
        Route::get('/mesin-absensi', [AdminDeviceController::class, 'index'])->name('mesin-absensi');
        Route::get('/mesin-absensi/{device}/log', [AdminDeviceController::class, 'logs'])->name('mesin-absensi.logs');
        Route::post('/mesin-absensi', [AdminDeviceController::class, 'store'])->name('mesin-absensi.store');
        Route::put('/mesin-absensi/{device}', [AdminDeviceController::class, 'update'])->name('mesin-absensi.update');
        Route::post('/mesin-absensi/{device}/activate', [AdminDeviceController::class, 'activate'])->name('mesin-absensi.activate');
        Route::post('/mesin-absensi/{device}/deactivate', [AdminDeviceController::class, 'deactivate'])->name('mesin-absensi.deactivate');
        Route::post('/mesin-absensi/{device}/revoke', [AdminDeviceController::class, 'revoke'])->name('mesin-absensi.revoke');
        Route::post('/mesin-absensi/{device}/reset', [AdminDeviceController::class, 'reset'])->name('mesin-absensi.reset');
        Route::delete('/mesin-absensi/{device}', [AdminDeviceController::class, 'destroy'])->name('mesin-absensi.destroy');
    });
});
