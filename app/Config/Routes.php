<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('/register', 'Registration::index');
$routes->post('/register/save', 'Registration::save');

$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->post('logout', 'Auth::logout', ['filter' => 'accountAuth']);
$routes->get('account', 'Auth::account', ['filter' => 'accountAuth']);
$routes->get('employees/create', 'Registration::employee', ['filter' => 'accountAuth:admin,hr']);
$routes->post('employees/create', 'Registration::saveEmployee', ['filter' => 'accountAuth:admin,hr']);
$routes->get('admin/register', 'Registration::admin', ['filter' => 'accountAuth:admin']);
$routes->post('admin/register', 'Registration::saveAdmin', ['filter' => 'accountAuth:admin']);

$routes->get('dashboard', 'Workspace::index', ['filter'=>'accountAuth']);
$routes->get('bookings/new', 'Workspace::newBooking', ['filter'=>'accountAuth:customer']);
$routes->post('bookings', 'Workspace::requestBooking', ['filter'=>'accountAuth:customer']);
$routes->get('bookings', 'Workspace::bookings', ['filter'=>'accountAuth:customer,admin,dispatcher,technician']);
$routes->get('bookings/(:num)', 'Workspace::booking/$1', ['filter'=>'accountAuth:customer,admin,dispatcher,technician']);
$routes->post('bookings/(:num)/change', 'Workspace::changeBooking/$1', ['filter'=>'accountAuth:customer,admin,dispatcher']);
$routes->post('bookings/(:num)/assign', 'Workspace::assign/$1', ['filter'=>'accountAuth:admin,dispatcher']);
$routes->post('bookings/(:num)/payment', 'Workspace::recordPayment/$1', ['filter'=>'accountAuth:admin']);
$routes->post('assignments/(:num)/attendance', 'Workspace::attendance/$1', ['filter'=>'accountAuth:technician']);
$routes->post('assignments/(:num)/report', 'Workspace::report/$1', ['filter'=>'accountAuth:technician']);
$routes->post('assignments/(:num)/issue', 'Workspace::issue/$1', ['filter'=>'accountAuth:technician']);
$routes->get('notifications', 'Workspace::notifications', ['filter'=>'accountAuth']);
$routes->post('notifications/read', 'Workspace::readNotifications', ['filter'=>'accountAuth']);
$routes->get('profile', 'Workspace::profile', ['filter'=>'accountAuth']);
$routes->post('profile', 'Workspace::saveProfile', ['filter'=>'accountAuth']);
$routes->post('profile/password', 'Workspace::password', ['filter'=>'accountAuth']);
$routes->get('support', 'Workspace::support', ['filter'=>'accountAuth:customer,admin']);
$routes->post('support', 'Workspace::createTicket', ['filter'=>'accountAuth:customer']);
$routes->post('support/(:num)/respond', 'Workspace::respond/$1', ['filter'=>'accountAuth:admin']);
$routes->get('payments', 'Workspace::payments', ['filter'=>'accountAuth:customer,admin']);
$routes->get('receipts/(:num)', 'Workspace::receipt/$1', ['filter'=>'accountAuth:customer,admin']);
$routes->get('users', 'Workspace::users', ['filter'=>'accountAuth:admin,hr']);
$routes->get('users/(:num)', 'Workspace::editUser/$1', ['filter'=>'accountAuth:admin,hr']);
$routes->post('users/(:num)', 'Workspace::saveUser/$1', ['filter'=>'accountAuth:admin,hr']);
$routes->get('announcements', 'Workspace::announcements', ['filter'=>'accountAuth:admin,hr,dispatcher,technician,employee']);
$routes->post('announcements', 'Workspace::publish', ['filter'=>'accountAuth:admin,hr']);
$routes->get('manage/(:segment)', 'Workspace::catalog/$1', ['filter'=>'accountAuth:admin']);
$routes->post('manage/(:segment)/(:num)', 'Workspace::saveCatalog/$1/$2', ['filter'=>'accountAuth:admin']);
$routes->get('settings', 'Workspace::settings', ['filter'=>'accountAuth:admin']);
$routes->post('settings', 'Workspace::saveSettings', ['filter'=>'accountAuth:admin']);
$routes->get('reports', 'Workspace::reports', ['filter'=>'accountAuth:admin']);
$routes->get('records', 'Workspace::records', ['filter'=>'accountAuth:admin']);
$routes->post('issues/(:num)/resolve', 'Workspace::closeIssue/$1', ['filter'=>'accountAuth:admin']);
