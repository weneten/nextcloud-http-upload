<?php
use OCP\Util;
use OCP\IL10N;
use OCA\HttpUploader\AppInfo\Application;

// Initialize the localization service
$l = \OC::$server->getL10N(Application::APP_ID);

Util::addStyle(Application::APP_ID, 'largefileupload');
Util::addScript(Application::APP_ID, 'largefileupload');

$navigationEntry = [
    'id' => Application::APP_ID,
    'order' => 50,
    'href' => \OC::$server->getURLGenerator()->linkToRoute(Application::APP_ID . '.page.index'),
    'icon' => \OC::$server->getURLGenerator()->imagePath(Application::APP_ID, 'app.svg'),
    'name' => $l->t('Large File Upload'),
];

Util::addNavigationEntry($navigationEntry);