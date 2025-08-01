<?php
/**
 * Coupons Model for Yenolx Restaurant Reservation System
 *
 * This class handles all data operations for coupons and promotions.
 *
 * @package YRR/Models
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Coupons_Model {

    /**
     * Get a single coupon by its ID.
     *
     * @param int $id The ID of the coupon.
     * @return object|null The coupon object, or null if not found.
     */
    public static function get_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . YRR_COUPONS_TABLE . " WHERE id = %d", $id));
    }

    /**
     * Get a single coupon by its code.
     *
     * @param string $code The coupon code.
     * @return object|null The coupon object, or null if not found.
     */
    public static function get_by_code($code) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . YRR_COUPONS_TABLE . " WHERE code = %s", strtoupper($code)));
    }

    /**
     * Get all coupons.
     *
     * @return array An array of coupon objects.
     */
    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . YRR_COUPONS_TABLE . " ORDER BY created_at DESC");
    }

    /**
     * Create a new coupon.
     *
     * @param array $data The data for the new coupon.
     * @return int|WP_Error The new coupon ID on success, or a WP_Error object on failure.
     */
    public static function create($data) {
        global $wpdb;
        $defaults = [
            'code' => '',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'is_active' => 1,
            'usage_count' => 0,
            'created_at' => current_time('mysql'),
        ];
        $data = wp_parse_args($data, $defaults);

        if (empty($data['code'])) {
            return new WP_Error('missing_code', __('Coupon code is required.', 'yrr'));
        }

        // Check for duplicates
        if (self::get_by_code($data['code'])) {
            return new WP_Error('duplicate_code', __('This coupon code already exists.', 'yrr'));
        }

        $result = $wpdb->insert(YRR_COUPONS_TABLE, $data);
        return $result ? $wpdb->insert_id : new WP_Error('db_error', 'Failed to create coupon.');
    }

    /**
     * Update an existing coupon.
     *
     * @param int   $id   The ID of the coupon to update.
     * @param array $data The new data for the coupon.
     * @return bool True on success, false on failure.
     */
    public static function update($id, $data) {
        global $wpdb;
        return $wpdb->update(YRR_COUPONS_TABLE, $data, array('id' => $id));
    }

    /**
     * Delete a coupon.
     *
     * @param int $id The ID of the coupon to delete.
     * @return bool True on success, false on failure.
     */
    public static function delete($id) {
        global $wpdb;
        return $wpdb->delete(YRR_COUPONS_TABLE, array('id' => $id));
    }

    /**
     * Validate a coupon for use.
     *
     * @param string $code The coupon code to validate.
     * @return object|WP_Error The coupon object on success, or a WP_Error object on failure.
     */
    public static function validate_coupon($code) {
        $coupon = self::get_by_code($code);

        if (!$coupon) {
            return new WP_Error('invalid_code', __('Invalid coupon code.', 'yrr'));
        }
        if (!$coupon->is_active) {
            return new WP_Error('inactive_coupon', __('This coupon is not active.', 'yrr'));
        }
        if ($coupon->valid_to && strtotime($coupon->valid_to) < current_time('timestamp')) {
            return new WP_Error('expired
