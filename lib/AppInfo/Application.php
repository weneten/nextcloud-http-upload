<?php
namespace OCA\HttpUploader\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootstrapContext;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IL10N; // For localization

class Application extends App implements IBootstrap {
    public const APP_ID = 'http_uploader';

    private INavigationManager $navigationManager;
    private IURLGenerator $urlGenerator;
    private IL10N $l10n;

    public function __construct(array $params = [], INavigationManager $navigationManager, IURLGenerator $urlGenerator, IL10N $l10n) {
        parent::__construct(self::APP_ID, $params);
        $this->navigationManager = $navigationManager;
        $this->urlGenerator = $urlGenerator;
        $this->l10n = $l10n;
    }

    public function boot(IBootstrapContext $context): void {
        $this->navigationManager->add(function () {
            $l = $this->l10n->get(self::APP_ID); // Get localization for the app

            return [
                'id' => self::APP_ID,
                'order' => 50,
                'href' => $this->urlGenerator->linkToRoute(self::APP_ID . '.page.index'),
                'icon' => $this->urlGenerator->imagePath(self::APP_ID, 'app.svg'),
                'name' => $l->t('Large File Upload'),
            ];
        });
    }
}