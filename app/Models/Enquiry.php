<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $fillable = [
        'firm_name',
        'contact_name',
        'email',
        'phone',
        'firm_size',
        'clio_region',
        'notes',
        'status',
    ];

    public static array $firmSizeLabels = [
        'solo'       => 'Solo practitioner',
        'small'      => 'Small firm (2–10 lawyers)',
        'mid'        => 'Mid-size firm (11–50 lawyers)',
        'large'      => 'Large firm (51–200 lawyers)',
        'enterprise' => 'Enterprise (200+ lawyers)',
    ];

    public static array $clioRegionLabels = [
        'us' => 'Clio US (app.clio.com)',
        'ca' => 'Clio Canada (ca.app.clio.com)',
        'eu' => 'Clio EU (eu.app.clio.com)',
        'au' => 'Clio Australia (au.app.clio.com)',
        'uk' => 'Clio UK (uk.app.clio.com)',
    ];

    public function firmSizeLabel(): string
    {
        return static::$firmSizeLabels[$this->firm_size] ?? $this->firm_size;
    }

    public function clioRegionLabel(): string
    {
        return static::$clioRegionLabels[$this->clio_region] ?? $this->clio_region;
    }
}
