<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GymController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MemberProgressController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TrainerAssignmentController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/', [GymController::class, 'home'])->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('role:owner,receptionist')->group(function () {
        Route::get('/frontdesk', [GymController::class, 'checkinPage'])->name('frontdesk');
        Route::get('/frontdesk/checkin', [GymController::class, 'checkinPage'])->name('checkin');
        Route::post('/frontdesk/checkin/{member}', [GymController::class, 'checkin'])->middleware('throttle:60,1')->name('checkin.store');
        Route::get('/members', [GymController::class, 'members'])->name('members');
        Route::get('/members/new', [GymController::class, 'newMember'])->name('members.new');
        Route::post('/members', [GymController::class, 'storeMember'])->name('members.store');
        Route::get('/members/{member}', [GymController::class, 'member'])->name('members.show');
        Route::put('/members/{member}', [GymController::class, 'updateMember'])->name('members.update');
        Route::post('/members/{member}/invoices', [GymController::class, 'invoice'])->name('invoices.store');
        Route::get('/payments', [GymController::class, 'payments'])->name('payments');
        Route::post('/payments/{invoice}', [GymController::class, 'pay'])->name('payments.store');
        Route::get('/shifts', [GymController::class, 'shifts'])->name('shifts');
        Route::post('/shifts', [GymController::class, 'openShift'])->name('shifts.open');
        Route::post('/shifts/{shift}/close', [GymController::class, 'closeShift'])->name('shifts.close');
    });
    Route::middleware('role:owner')->prefix('admin')->group(function () {
        Route::get('/staff', [UserController::class, 'index'])->name('staff');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/new', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::get('/users/{user}', [UserController::class, 'show'])->withTrashed()->name('users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::get('/trainers', [UserController::class, 'index'])->name('trainers');
        Route::post('/rooms', [TrainingController::class, 'room'])->name('rooms.store');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::post('/staff/{user}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
        Route::post('/members/{member}/status', [StaffController::class, 'status'])->name('members.status');
        Route::get('/dashboard', [GymController::class, 'dashboard'])->name('dashboard');
        Route::get('/membership-plans', [GymController::class, 'plans'])->name('plans');
        Route::get('/membership-plans/create', [GymController::class, 'createPlan'])->name('plans.create');
        Route::post('/membership-plans', [GymController::class, 'storePlan'])->name('plans.store');
        Route::put('/membership-plans/{plan}', [GymController::class, 'updatePlan'])->name('plans.update');
        Route::delete('/membership-plans/{plan}', [GymController::class, 'destroyPlan'])->name('plans.destroy');
        Route::get('/reports', [GymController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [GymController::class, 'export'])->name('reports.export');
        Route::get('/audit', [GymController::class, 'audit'])->name('audit');
        Route::get('/staff-attendance', [StaffAttendanceController::class, 'index'])->name('staff.attendance.index');
        Route::post('/staff-attendance', [StaffAttendanceController::class, 'manualStore'])->name('staff.attendance.manual');
        Route::delete('/staff-attendance/{attendance}', [StaffAttendanceController::class, 'destroy'])->name('staff.attendance.destroy');
    });
    Route::middleware(['role:owner,receptionist', 'can:schedule.manage'])->prefix('admin')->group(function () {
        Route::get('/classes/new', [TrainingController::class, 'create'])->name('classes.create');
        Route::post('/classes', [TrainingController::class, 'store'])->name('classes.store');
        Route::get('/classes/{session}/edit', [TrainingController::class, 'edit'])->name('classes.edit');
        Route::put('/classes/{session}', [TrainingController::class, 'update'])->name('classes.update');
    });
    Route::middleware(['role:owner,receptionist', 'can:assignments.manage'])->group(function () {
        Route::post('/members/{member}/trainer-assignments', [TrainerAssignmentController::class, 'store'])->name('trainer-assignments.store');
        Route::delete('/trainer-assignments/{assignment}', [TrainerAssignmentController::class, 'destroy'])->name('trainer-assignments.destroy');
    });
    Route::post('/admin/classes/{session}/close', [TrainingController::class, 'close'])->middleware('role:owner,receptionist,personal_trainer')->name('classes.close');
    Route::middleware('role:owner,personal_trainer')->prefix('admin')->group(function () {
        Route::post('/members/{member}/progress', [MemberProgressController::class, 'store'])->name('members.progress.store');
        Route::delete('/progress/{record}', [MemberProgressController::class, 'destroy'])->name('members.progress.destroy');
    });
    Route::get('/trainer/members', [MemberProgressController::class, 'index'])->middleware('role:personal_trainer')->name('coaching.members');
    Route::get('/trainer/members/{member}', [MemberProgressController::class, 'member'])->middleware('role:personal_trainer')->name('coaching.member');
    Route::post('/classes/{session}/join', [TrainingController::class, 'join'])->middleware(['role:member', 'throttle:20,1'])->name('classes.join');
    Route::get('/account', [UserController::class, 'account'])->name('account');
    Route::get('/classes', [TrainingController::class, 'index'])->middleware('role:owner,receptionist,personal_trainer,member')->name('classes');
    Route::get('/classes/{session}', [TrainingController::class, 'show'])->middleware('role:owner,receptionist,personal_trainer,member')->name('classes.show');
    Route::post('/classes/{session}/participants', [TrainingController::class, 'enroll'])->middleware(['role:owner,receptionist', 'can:booking.manage'])->name('classes.enroll');
    Route::put('/class-participants/{enrollment}', [TrainingController::class, 'attendance'])->middleware('role:owner,receptionist,personal_trainer')->name('classes.attendance');
    Route::get('/invoices/{invoice}', [GymController::class, 'receipt'])->name('invoices.show');
    Route::get('/members/{member}/progress/{record}', [MemberProgressController::class, 'show'])->name('members.progress.show');

    // Absensi Staf & PT
    Route::post('/staff/clock-in', [StaffAttendanceController::class, 'clockIn'])->name('staff.clock-in');
    Route::post('/staff/clock-out', [StaffAttendanceController::class, 'clockOut'])->name('staff.clock-out');
    Route::get('/staff/attendance', [StaffAttendanceController::class, 'myAttendance'])->name('staff.attendance.me');
    Route::middleware('role:member')->group(function () {
        Route::get('/member/check-in', [MemberPortalController::class, 'card'])->name('portal.card');
        Route::get('/member', [MemberPortalController::class, 'home'])->name('portal');
        Route::get('/member/membership', [MemberPortalController::class, 'membership'])->name('portal.membership');
        Route::get('/member/visits', [MemberPortalController::class, 'visits'])->name('portal.visits');
        Route::get('/member/payments', [MemberPortalController::class, 'payments'])->name('portal.payments');
        Route::get('/member/progress', [MemberPortalController::class, 'progress'])->name('portal.progress');
        Route::get('/member/profile', [MemberPortalController::class, 'account'])->name('portal.account');
        Route::get('/member/invoices/{invoice}', [MemberPortalController::class, 'receipt'])->name('portal.invoice');
        Route::post('/member/renewals', [MemberPortalController::class, 'renewal'])->middleware('throttle:10,1')->name('portal.renewal');
        Route::put('/member/password', [MemberPortalController::class, 'password'])->middleware('throttle:6,1')->name('portal.password');
        Route::put('/member/profile', [MemberPortalController::class, 'update'])->name('portal.profile');
    });
});
