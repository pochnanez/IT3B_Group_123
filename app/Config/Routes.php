<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('/register', 'Registration::index');
$routes->post('/register/save', 'Registration::save');
