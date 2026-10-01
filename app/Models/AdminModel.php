<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table = 'admins';
    protected $primaryKey = 'admin_id';

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