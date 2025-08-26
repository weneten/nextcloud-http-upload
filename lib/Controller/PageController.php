<?php
namespace OCA\HttpUploader\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCA\HttpUploader\AppInfo\Application;

class PageController extends Controller {
    public function __construct($appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        return new TemplateResponse(Application::APP_ID, 'main');
    }
}