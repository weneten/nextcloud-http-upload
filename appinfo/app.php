<?php
use OCP\Util;
use OCP\IL10N;

// Initialize the localization service
$l = \OC::$server->getL10N('largefileupload');

Util::addStyle('largefileupload', 'largefileupload');
Util::addScript('largefileupload', 'largefileupload');

$navigationEntry = [
    'id' => 'largefileupload',
    'order' => 50,
    'href' => \OC::$server->getURLGenerator()->linkToRoute('largefileupload.page.index'),
    'icon' => \OC::$server->getURLGenerator()->imagePath('largefileupload', 'app.svg'),
    'name' => $l->t('Large File Upload'),
];

Util::addNavigationEntry($navigationEntry);