<?php

namespace App\Models\Radius;

use Illuminate\Database\Eloquent\Model;

class Radacct extends Model
{
    protected $connection = 'radius';
    protected $table = 'radacct';
    protected $primaryKey = 'radacctid';
    public $timestamps = false;

    protected $casts = [
        'acctstarttime'  => 'datetime',
        'acctstoptime'   => 'datetime',
        'acctupdatetime' => 'datetime',
    ];

    protected $fillable = [
        'acctsessionid', 'acctuniqueid', 'username', 'groupname', 'realm',
        'nasipaddress', 'nasportid', 'nasporttype', 'acctstarttime',
        'acctupdatetime', 'acctstoptime', 'acctinterval', 'acctsessiontime',
        'acctauthentic', 'connectinfo_start', 'connectinfo_stop',
        'acctinputoctets', 'acctoutputoctets', 'calledstationid',
        'callingstationid', 'acctterminatecause', 'servicetype',
        'framedprotocol', 'framedipaddress',
    ];

    public function scopeActive($query)
    {
        return $query->whereNull('acctstoptime');
    }
}
