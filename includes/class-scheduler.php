<?php
/**
 * Agendamento WP Cron para sincronização.
 *
 * @package CDW\Veiculos
 */

declare(strict_types=1);

namespace CDW\Veiculos;

final class Scheduler {

    public const HOOK = 'cdw_veiculos_cron_sync';
    private const OPTION_FREQUENCY = 'cdw_veiculos_cron_frequency';

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function init(): void {
        add_action(self::HOOK, [$this, 'run_sync']);
        add_filter('cron_schedules', [$this, 'add_schedules']);
    }

    /**
     * Frequências configuráveis: 15min, 30min, 1h, 6h, diário.
     */
    public function add_schedules(array $schedules): array {
        $schedules['cdw_15min'] = [
            'interval' => 15 * 60,
            'display'  => __('A cada 15 minutos', 'cdw-veiculos'),
        ];
        $schedules['cdw_30min'] = [
            'interval' => 30 * 60,
            'display'  => __('A cada 30 minutos', 'cdw-veiculos'),
        ];
        $schedules['cdw_1h'] = [
            'interval' => 60 * 60,
            'display'  => __('A cada hora', 'cdw-veiculos'),
        ];
        $schedules['cdw_6h'] = [
            'interval' => 6 * 60 * 60,
            'display'  => __('A cada 6 horas', 'cdw-veiculos'),
        ];
        $schedules['cdw_daily'] = [
            'interval' => 24 * 60 * 60,
            'display'  => __('Diariamente', 'cdw-veiculos'),
        ];
        return $schedules;
    }

    public function get_frequency(): string {
        $f = get_option(self::OPTION_FREQUENCY, 'cdw_1h');
        $allowed = ['cdw_15min', 'cdw_30min', 'cdw_1h', 'cdw_6h', 'cdw_daily'];
        return in_array($f, $allowed, true) ? $f : 'cdw_1h';
    }

    public function set_frequency(string $frequency): void {
        $allowed = ['cdw_15min', 'cdw_30min', 'cdw_1h', 'cdw_6h', 'cdw_daily'];
        if (in_array($frequency, $allowed, true)) {
            update_option(self::OPTION_FREQUENCY, $frequency);
        }
    }

    public function schedule(): void {
        $this->unschedule();
        $frequency = $this->get_frequency();
        wp_schedule_event(time(), $frequency, self::HOOK);
    }

    public function unschedule(): void {
        $timestamp = wp_next_scheduled(self::HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK);
        }
        wp_clear_scheduled_hook(self::HOOK);
    }

    public function run_sync(): void {
        Sync::get_instance()->run();
    }

    public static function option_frequency(): string {
        return self::OPTION_FREQUENCY;
    }
}
