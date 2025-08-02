<?php
use PHPUnit\Framework\TestCase;

require_once YRR_PLUGIN_PATH . 'models/class-reservation-model.php';

class ReservationModelTest extends TestCase
{
    public function test_update_status_with_invalid_status_returns_wp_error()
    {
        $result = YRR_Reservation_Model::update_status(1, 'bogus');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('invalid_status', $result->get_error_code());
    }
}
