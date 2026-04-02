<?php
declare(strict_types=1);

/**
 * Zoro Routes
 * ----------
 * NOTE:
 * - This project uses an instance-based Router.
 * - public/index.php creates $router and then includes this file.
 * - So we MUST use: $router->get(...) / $router->post(...)
 */

// Home
$router->get('/', 'DashboardController@index');

// Auth
$router->get('/login', 'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// Onboarding
$router->get('/onboarding', 'OnboardingController@index');
$router->post('/onboarding/save', 'OnboardingController@save');

// Dashboard
$router->get('/dashboard', 'DashboardController@index');

// Settings + Mode
$router->get('/settings', 'SettingsController@index');
$router->post('/settings/save', 'SettingsController@save');
$router->post('/toggle-mode', 'SettingsController@toggleMode');

// Transactions
$router->get('/transactions', 'TransactionsController@index');
$router->get('/transactions/create', 'TransactionsController@createForm');
$router->post('/transactions/create', 'TransactionsController@create');
$router->get('/transactions/edit', 'TransactionsController@editForm');    // ?id=123
$router->post('/transactions/edit', 'TransactionsController@update');     // id in POST
$router->post('/transactions/delete', 'TransactionsController@delete');   // id in POST
$router->post('/transactions/bulk', 'TransactionsController@bulk');       // bulk actions

// (Other modules can stay for later; no harm keeping placeholders if controllers exist)
$router->get('/notifications', 'NotificationsController@index');
$router->post('/notifications/mark-read', 'NotificationsController@markRead');

$router->get('/budgets', 'BudgetsController@index');
$router->post('/budgets/save', 'BudgetsController@save');

$router->get('/subscriptions', 'SubscriptionsController@index');
$router->post('/subscriptions/save', 'SubscriptionsController@save');
$router->post('/subscriptions/toggle', 'SubscriptionsController@toggleStatus');

$router->get('/goals', 'GoalsController@index');
$router->post('/goals/save', 'GoalsController@save');
$router->post('/goals/update-progress', 'GoalsController@updateProgress');

$router->get('/rules', 'RulesController@index');
$router->post('/rules/save', 'RulesController@save');
$router->post('/rules/toggle', 'RulesController@toggle');

$router->get('/reports', 'ReportsController@index');
$router->get('/reports/export-csv', 'ReportsController@exportCsv');
$router->get('/reports/export-pdf', 'ReportsController@exportPdf');

$router->get('/support', 'SupportController@index');
$router->post('/support/create', 'SupportController@create');

$router->get('/security/sessions', 'SecurityController@sessions');
$router->post('/security/logout-all', 'SecurityController@logoutAll');

$router->get('/admin', 'AdminController@index');
$router->get('/admin/users', 'AdminController@users');
$router->post('/admin/user/toggle', 'AdminController@toggleUser');
$router->get('/admin/logs', 'AdminController@logs');
$router->post('/admin/notify', 'AdminController@broadcastNotification');

$router->get('/manifest.json', 'PwaController@manifest');
$router->get('/service-worker.js', 'PwaController@serviceWorker');
