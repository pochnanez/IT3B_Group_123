<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'customer_id';

    protected $allowedFields = [
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'gender',
        'email',
        'contact_number',
        'address',
        'username',
        'password'
    ];
}