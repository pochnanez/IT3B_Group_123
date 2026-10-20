<?php
namespace App\Controllers;

use App\Libraries\Accounts;

class Auth extends BaseController
{
    public function login()
    {
        return view('login');
    }

    public function attempt()
    {
        $identifier = $this->request->getPost('identifier');
        $password = $this->request->getPost('password');
        if (! is_string($identifier) || ! is_string($password) || strlen($identifier) > 100 || strlen($password) > 1024 || trim($identifier) === '' || $password === '') {
            return redirect()->to('/login')->with('error', 'Enter your email or username and password.');
        }
        $identifier = trim($identifier);
        $throttler = service('throttler');
        if (! $throttler->check('login-ip-' . hash('sha256', $this->request->getIPAddress()), 30, MINUTE)
            || ! $throttler->check('login-id-' . hash('sha256', strtolower($identifier)), 5, MINUTE)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', '60')->setBody('Too many sign-in attempts. Please try again in a minute.');
        }
        try {
            $account = (new Accounts())->authenticate($identifier, $password);
        } catch (\Throwable $e) {
            return redirect()->to('/login')->with('error', 'Sign-in is temporarily unavailable. Please try again later.');
        }
        if (! $account) {
            return redirect()->to('/login')->with('error', 'Unable to sign in with those credentials.')->with('identifier', $identifier);
        }
        session()->regenerate(true);
        session()->set(['account' => $account, 'signed_in_at' => time(), 'last_activity' => time()]);
        $destination=session()->get('after_login')==='/bookings/new' && $account['role']==='customer'?'/bookings/new':'/account';
        session()->remove('after_login');
        return redirect()->to($destination);
    }

    public function account()
    {
        if ((new \App\Libraries\Management())->ready()) { return (new Workspace())->index(); }
        return view('account', ['account' => session()->get('account')]);
    }

    public function logout()
    {
        session()->remove(array_keys($_SESSION));
        session()->destroy();
        return redirect()->to('/login');
    }
}
