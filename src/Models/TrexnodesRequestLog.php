<?php

namespace Iphadey\Trexnodes\Models;

use Illuminate\Database\Eloquent\Model;

class TrexnodesRequestLog extends Model
{
    protected $table = 'trexnodes_request_logs';

    protected $guarded = [];

    protected $casts = [
        'request'  => 'array',
        'response' => 'array',
        'result'   => 'array',
    ];
}
