<?php
namespace OCA\HttpUploader\AppInfo;


use OCP\AppFramework\App;


class Application extends App {
public const APP_ID = 'http_uploader';


public function __construct(array $params = []) {
parent::__construct(self::APP_ID, $params);
}
}