<?php
namespace App\Controllers;

use App\Libraries\Accounts;

class Registration extends BaseController
{
    public function index() { return view('registration', ['accountType' => 'customer']); }
    public function employee() { return view('registration', ['accountType' => 'employee']); }
    public function admin() { return view('registration', ['accountType' => 'admin']); }
    public function save() { return $this->create('customer', '/register'); }
    public function saveEmployee() { return $this->create('employee', '/employees/create'); }
    public function saveAdmin() { return $this->create('admin', '/admin/register'); }

    private function create(string $type, string $path)
    {
        if ($type === 'customer' && ! service('throttler')->check('signup-ip-' . hash('sha256', $this->request->getIPAddress()), 10, MINUTE)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', '60')->setBody('Too many registration attempts. Please try again in a minute.');
        }
        $input = [];
        foreach (array_keys(Accounts::rules(true)) as $field) {
            $value = $this->request->getPost($field);
            $input[$field] = is_string($value) ? (in_array($field, ['password', 'confirmPassword'], true) ? $value : trim($value)) : '';
        }
        $safe = array_diff_key($input, array_flip(['password', 'confirmPassword']));
        if ($type === 'customer' && $input['department'] !== '' && $input['department'] !== 'N/A') {
            return redirect()->to($path)->with('errors', ['Employee accounts must be created by authorized staff.'])->with('formInput', $safe);
        }
        if (! $this->validateData($input, Accounts::rules($type === 'employee'))) {
            return redirect()->to($path)->with('errors', $this->validator->getErrors())->with('formInput', $safe);
        }
        try {
            (new Accounts())->create($type, $input);
        } catch (\RuntimeException $e) {
            $message = get_class($e) === \RuntimeException::class ? $e->getMessage() : 'Account could not be created. Please try again later.';
            return redirect()->to($path)->with('errors', [$message])->with('formInput', $safe);
        } catch (\Throwable $e) {
            return redirect()->to($path)->with('errors', ['Account could not be created. Please try again later.'])->with('formInput', $safe);
        }
        return redirect()->to($type === 'customer' ? '/login' : $path)->with('success', 'Account created successfully.' . ($type === 'customer' ? ' You can now sign in.' : ''));
    }
}
