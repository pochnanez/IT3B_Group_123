<?php
namespace App\Filters;

use App\Libraries\Accounts;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AccountAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $identity = $session->get('account');
        $policy = config('AccountAccess');
        if (! is_array($identity) || time() - (int) $session->get('last_activity') > $policy->idleSeconds
            || time() - (int) $session->get('signed_in_at') > $policy->absoluteSeconds) {
            $session->remove(['account', 'last_activity', 'signed_in_at']);
            if($request->getMethod()==='GET' && trim($request->getUri()->getPath(),'/')==='bookings/new') { $session->set('after_login','/bookings/new'); }
            $session->regenerate(true);
            return redirect()->to('/login')->with('error', 'Please sign in to continue.');
        }
        try {
            $account = (new Accounts())->find((string) ($identity['type'] ?? ''), (int) ($identity['id'] ?? 0));
        } catch (\Throwable $e) {
            return service('response')->setStatusCode(503)->setBody('Account service is temporarily unavailable.');
        }
        if (! $account || ($account['user_id']>0 && (int)($identity['session_version']??0)!==$account['session_version'])) {
            $session->remove(['account', 'last_activity', 'signed_in_at']);
            $session->regenerate(true);
            return redirect()->to('/login')->with('error', 'Please sign in again.');
        }
        $session->set(['account' => $account, 'last_activity' => time()]);
        if ($arguments && ! in_array($account['role'], $arguments, true)) {
            return service('response')->setStatusCode(403)->setBody('You do not have permission to access this page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'no-store, private');
    }
}
