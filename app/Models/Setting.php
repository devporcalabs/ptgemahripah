<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const OVERTIME_MODE_FLAT_HOURLY = 'flat_hourly';
    public const OVERTIME_MODE_UU_CIPTA_KERJA = 'uu_cipta_kerja';
    public const LEAVE_QUOTA_POLICY_ANNUAL_RESET = 'annual_reset';
    public const LEAVE_QUOTA_POLICY_CARRY_LIMITED = 'carry_limited';
    public const LEAVE_QUOTA_POLICY_CARRY_FULL = 'carry_full';

    protected $table = 'settings';

    protected $guarded = [];

    protected $casts = [
        'gaji_per_hari' => 'float',
        'payroll_divisor_bulanan' => 'integer',
        'bpjs_auto_enabled' => 'boolean',
        'bpjs_kesehatan_company_percent' => 'float',
        'bpjs_kesehatan_employee_percent' => 'float',
        'bpjs_kesehatan_salary_cap' => 'float',
        'bpjs_jht_company_percent' => 'float',
        'bpjs_jht_employee_percent' => 'float',
        'bpjs_jp_company_percent' => 'float',
        'bpjs_jp_employee_percent' => 'float',
        'bpjs_jp_salary_cap' => 'float',
        'bpjs_jkk_company_percent' => 'float',
        'bpjs_jkm_company_percent' => 'float',
        'pph21_auto_enabled' => 'boolean',
        'pph21_job_expense_percent' => 'float',
        'pph21_job_expense_monthly_cap' => 'float',
        'thr_auto_enabled' => 'boolean',
        'thr_payment_month' => 'integer',
        'thr_payment_date' => 'date',
        'thr_include_tunjangan_jabatan' => 'boolean',
        'thr_include_tunjangan_makan' => 'boolean',
        'thr_include_tunjangan_transport' => 'boolean',
        'leave_carryover_max_days' => 'integer',
        'leave_quota_policy_effective_year' => 'integer',
    ];

    public const CREATED_AT = null;
    public const UPDATED_AT = null;
}
