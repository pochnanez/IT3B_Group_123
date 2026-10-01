<?php

namespace App\Controllers;

use App\Models\AdminModel;

class AdminRegistration extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index()
    {
        return view('admin_registration');
    }

    public function save()
    {
        // Read POST fields only; reject arrays and preserve passwords exactly.
        $data = [];
        foreach (['firstname', 'middlename', 'lastname', 'birthdate', 'gender', 'email',
            'contact', 'address', 'username', 'password', 'confirmPassword'] as $field) {
            $value = $this->request->getPost($field);
            $data[$field] = is_string($value) ? $value : '';
            if (!in_array($field, ['password', 'confirmPassword'], true)) {
                $data[$field] = trim($data[$field]);
            }
        }

        $nameRules = ['max_length[50]', "regex_match[/^[\\p{L}\\p{M} '\x{2019}-]+$/u]"];
        $rules = [
            'firstname' => ['label' => 'First name', 'rules' => ['required', 'min_length[2]', ...$nameRules]],
            'middlename' => ['label' => 'Middle name', 'rules' => ['permit_empty', ...$nameRules]],
            'lastname' => ['label' => 'Last name', 'rules' => ['required', 'min_length[2]', ...$nameRules]],
            'birthdate' => ['label' => 'Birthdate', 'rules' => [
                'required', 'regex_match[/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/]', 'valid_date[Y-m-d]',
            ]],
            'gender' => ['label' => 'Gender', 'rules' => 'required|in_list[Male,Female,Prefer not to say]'],
            'email' => ['label' => 'Email address', 'rules' => 'required|valid_email|max_length[254]'],
            'contact' => ['label' => 'Contact number', 'rules' => 'required|regex_match[/^09[0-9]{9}$/]',
                'errors' => ['regex_match' => 'Enter an 11-digit mobile number starting with 09.']],
            'address' => ['label' => 'Address', 'rules' => 'required|max_length[255]'],
            'username' => ['label' => 'Username', 'rules' => 'required|min_length[4]|max_length[50]'],
            'password' => ['label' => 'Password', 'rules' => 'required|min_length[8]|max_length[72]'],
            'confirmPassword' => ['label' => 'Confirm password', 'rules' => 'required|matches[password]',
                'errors' => ['matches' => 'Passwords must match exactly.']],
        ];

        $valid = $this->validateData($data, $rules);
        $errors = $valid ? [] : $this->validator->getErrors();
        if (!isset($errors['birthdate']) && $data['birthdate'] > date('Y-m-d')) {
            $errors['birthdate'] = 'Birthdate cannot be in the future.';
        }
        // Bcrypt accepts at most 72 bytes and cannot hash null characters.
        if (strlen($data['password']) > 72 || str_contains($data['password'], "\0")) {
            $errors['password'] = 'Use at most 72 bytes and no null characters in your password.';
        }
        if ($errors) {
            return $this->failure($data, $errors);
        }

        $saveError = 'Unable to create the admin account. The email or username may already be in use. Please try again or contact the system administrator.';
        try {
            $model = new AdminModel();
            foreach (['email', 'username'] as $field) {
                if ($model->where($field, $data[$field])->first() !== null) {
                    $errors[$field] = ucfirst($field) . ' is already used by an administrator.';
                }
            }
            if ($errors) {
                return $this->failure($data, $errors);
            }
            $saved = $model->insert([
                'first_name' => $data['firstname'],
                'middle_name' => $data['middlename'] === '' ? null : $data['middlename'],
                'last_name' => $data['lastname'],
                'birthdate' => $data['birthdate'],
                'gender' => $data['gender'],
                'email' => $data['email'],
                'contact_number' => $data['contact'],
                'address' => $data['address'],
                'username' => $data['username'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);
            if ($saved === false) {
                return $this->failure($data, [], $saveError);
            }
        } catch (\Throwable $exception) {
            // Never expose database exceptions, which can contain account data.
            log_message('error', 'Admin registration could not be saved ({type}).', ['type' => get_class($exception)]);
            return $this->failure($data, [], $saveError);
        }

        return redirect()->to(site_url('admin/register'))
            ->with('success', 'Admin account created successfully.');
    }

    private function failure(array $data, array $errors, ?string $message = null)
    {
        // The view uses these flash keys. Passwords are never stored in the session.
        unset($data['password'], $data['confirmPassword']);
        return redirect()->to(site_url('admin/register'))
            ->with('admin_old', $data)->with('admin_errors', $errors)->with('admin_error', $message);
    }
}
