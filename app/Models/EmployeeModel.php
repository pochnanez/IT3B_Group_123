<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'employee_id';

    protected $allowedFields = [
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'gender',
        'email',
        'contact_number',
        'address',
        'department',
        'username',
        'password'
    ];
}