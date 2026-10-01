<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

//User Reg
$routes->get('/register', 'Registration::index');
$routes->post('/register/save', 'Registration::save');

//Admin Reg
$routes->get('/admin/register', 'AdminRegistration::index', ['filter' => 'csrf']);
$routes->post('/admin/register/save', 'AdminRegistration::save', ['filter' => 'csrf']);
