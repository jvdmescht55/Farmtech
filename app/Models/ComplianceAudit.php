<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceAudit extends Model
{
    protected $fillable = [
        'product_id', 'supplier_name', 'supplier_years', 'is_verified_supplier', 'has_trade_assurance',
        'frequency_checked', 'icasa_status', 'plug_type_checked', 'battery_transport_cert',
        'risk_score', 'audit_verdict', 'rejection_reasons', 'raw_ai_analysis',
    ];

    protected function casts(): array
    {
        return [
            'is_verified_supplier' => 'boolean',
            'has_trade_assurance' => 'boolean',
            'plug_type_checked' => 'boolean',
            'risk_score' => 'integer',
            'rejection_reasons' => 'array',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Badge color for the admin review sidebar: green/yellow/red. */
    public function frequencyBadge(): string
    {
        if (! $this->frequency_checked) {
            return 'gray';
        }

        return str_contains($this->frequency_checked, '134.2') ? 'green' : 'red';
    }

    public function icasaBadge(): string
    {
        return match ($this->icasa_status) {
            'pre_approved', 'exempt' => 'green',
            'requires_permit' => 'yellow',
            'flagged' => 'red',
            default => 'gray',
        };
    }

    public function plugBadge(): string
    {
        return $this->plug_type_checked ? 'green' : 'yellow';
    }

    public function supplierTrustBadge(): string
    {
        if ($this->is_verified_supplier && $this->supplier_years >= 3) {
            return 'green';
        }

        if ($this->is_verified_supplier || $this->supplier_years >= 1) {
            return 'yellow';
        }

        return 'red';
    }
}
