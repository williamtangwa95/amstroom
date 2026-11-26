<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number',
        'type',
        'sale_id',
        'shop_id',
        'created_by',
        'customer_name',
        'customer_id',
        'customer_po_box',
        'deliver_to',
        'issue_date',
        'delivery_date',
        'delivery_time',
        'validity_date',
        'terms_of_payment',
        'notes',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'issue_date'    => 'date',
        'delivery_date' => 'date',
        'validity_date' => 'date',
        'total_amount'  => 'decimal:2',
    ];

    public const TYPES = [
        'invoice'  => 'Invoice',
        'proforma' => 'Proforma Invoice',
        'delivery' => 'Delivery Note',
    ];

    public const PREFIXES = [
        'invoice'  => 'INV',
        'proforma' => 'PF',
        'delivery' => 'DN',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class)->withTrashed();
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public static function generateNumber(string $type): string
    {
        $prefix = self::PREFIXES[$type] ?? 'DOC';
        $lastNumber = self::where('type', $type)
            ->orderByDesc('id')
            ->value('document_number');

        $nextSeq = 1;
        if ($lastNumber && preg_match('/(\d+)$/', $lastNumber, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        $candidate = $prefix . '-' . str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
        while (self::where('document_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = $prefix . '-' . str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
