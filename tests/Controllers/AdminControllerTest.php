<?php
use PHPUnit\Framework\TestCase;

require_once YRR_PLUGIN_PATH . 'controllers/class-admin-controller.php';

class AdminControllerTest extends TestCase
{
    public function test_enqueue_admin_assets_ignores_non_plugin_pages()
    {
        $controller = new YRR_Admin_Controller();
        $GLOBALS['enqueued_styles'] = [];
        $GLOBALS['enqueued_scripts'] = [];
        $controller->enqueue_admin_assets('dashboard');
        $this->assertEmpty($GLOBALS['enqueued_styles']);
        $this->assertEmpty($GLOBALS['enqueued_scripts']);
    }
}
