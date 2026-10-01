<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\EmployeeModel;

class Registration extends BaseController
{
    public function index()
    {
        return view('registration');
    }

    public function save()
    {
        $rules = [
            'firstname' => 'required|min_length[2]|max_length[50]',
            'lastname' => 'required|min_length[2]|max_length[50]',
            'birthdate' => 'required',
            'gender' => 'required',
            'email' => 'required|valid_email',
            'contact' => 'required|regex_match[/^09[0-9]{9}$/]',
            'address' => 'required',
            'department' => 'required',
            'username' => 'required|min_length[4]|max_length[50]',
            'password' => 'required|min_length[8]',
            'confirmPassword' => 'required|matches[password]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $department = $this->request->getPost('department');

        $data = [
            'first_name' => $this->request->getPost('firstname'),
            'middle_name' => $this->request->getPost('middlename'),
            'last_name' => $this->request->getPost('lastname'),
            'birthdate' => $this->request->getPost('birthdate'),
            'gender' => $this->request->getPost('gender'),
            'email' => $this->request->getPost('email'),
            'contact_number' => $this->request->getPost('contact'),
            'address' => $this->request->getPost('address'),
            'username' => $this->request->getPost('username'),


            'password' => password_hash(
                $this->request->getPost('password'),
                PASSWORD_DEFAULT
            )
        ];

        if ($department === 'N/A') {

            $customerModel = new CustomerModel();

            $customerModel->insert($data);

        } else {

            $data['department'] = $department;

            $employeeModel = new EmployeeModel();

            $employeeModel->insert($data);
        }

        return redirect()->to('/register')
            ->with('success', 'Registration successful!');
    }
}