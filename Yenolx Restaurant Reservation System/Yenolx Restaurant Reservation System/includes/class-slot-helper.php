<?php
/**
 * Slot Helper - generates and filters available booking time slots
 */

if (!defined('ABSPATH')) {
    exit;
}

class YRR_Slot_Helper {

    /**
     * Get available slots for a date, party size, and location
     * Returns array of time strings like ['17:00', '18:00', ...]
     */
    public static function get_available_slots($date, $party_size, $location_id = 1) {
        // Get operating hours for the date's weekday
        $weekday = date('l', strtotime($date));

        $hours = YRR_Hours_Model::get_hours_for_day($weekday, $location_id);

        if (!$hours || $hours->is_closed) {
            return []; // closed that day
        }

        $slot_duration = (int) YRR_Settings_Model::get_setting('slot_duration', 60);  // minutes
        $buffer_hours = (int) YRR_Settings_Model::get_setting('booking_buffer', 2); // hours buffer before close
        $max_advance_days = (int) YRR_Settings_Model::get_setting('advance_booking_days', 30);

        // Check advance booking limit
        $today = strtotime(current_time('Y-m-d'));
        $target_date = strtotime($date);

        if (($target_date - $today) / 86400 > $max_advance_days) {
            return [];
        }

        // Calculate usable time range excluding buffer
        $open_time = strtotime($hours->open_time);
        $close_time = strtotime($hours->close_time) - ($buffer_hours * 3600);
        $now = current_time('timestamp');

        // Handle overnight close (past midnight)
        if ($close_time <= $open_time) {
            $close_time += 86400; // add 1 day
        }

        // Start slot from max(open_time, now + buffer_time if date == today)
        if ($date === date('Y-m-d', $now)) {
            $min_time = max($open_time, $now + (60 * 60 * $buffer_hours));
        } else {
            $min_time = $open_time;
        }

        // Break times
        $break_start = $hours->break_start ? strtotime($hours->break_start) : null;
        $break_end = $hours->break_end ? strtotime($hours->break_end) : null;
        if ($break_end && $break_end <= $break_start) {
            $break_end += 86400; // overnight break
        }

        $slots = [];

        for ($slot = $min_time; $slot + ($slot_duration * 60) <= $close_time; $slot += $slot_duration * 60) {
            $slot_end = $slot + $slot_duration * 60;

            // Check if slot is in break
            if ($break_start && $break_end) {
                if (($slot >= $break_start && $slot < $break_end) ||
                    ($slot_end > $break_start && $slot_end <= $break_end)) {
                    continue; // skip break slots
                }
            }

            $slot_time_str = date('H:i', $slot);

            // Check availability for this slot
            if (self::is_slot_available($date, $slot_time_str, $party_size, $location_id)) {
                $slots[] = $slot_time_str;
            }
        }

        return $slots;
    }

    /**
     * Check if slot is available (unused or unbooked)
     */
    public static function is_slot_available($date, $time, $party_size, $location_id = 1) {
        // Find tables available at this date and time for party size
        $available_tables = YRR_Tables_Model::get_available_tables_for_slot($date, $time, $party_size, $location_id);

        return !empty($available_tables);
    }
}
