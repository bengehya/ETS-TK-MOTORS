<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRequestEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_request_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'note',
    ];

    /**
     * @return BelongsTo<CustomerRequest, $this>
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(CustomerRequest::class, 'customer_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
