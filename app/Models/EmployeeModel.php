<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'Employee_Id';

    protected $allowedFields = [
        'First_Name',
        'Middle_Name',
        'Last_Name',
        'Birthdate',
        'Gender',
        'Email',
        'Contact_Number',
        'Address',
        'Department',
        'Username',
        'Password'
    ];
}
