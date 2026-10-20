<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'Customer_id';

    protected $allowedFields = [
        'First_Name',
        'Middle_Name',
        'Last_Name',
        'Birthdate',
        'Gender',
        'Email',
        'Contact_Number',
        'Address',
        'Username',
        'Password'
    ];
}
