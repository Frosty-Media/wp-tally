<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Route;

use WP_Error;
use WP_Http;
use function apply_filters;
use function filter_var;
use function header;
use function headers_sent;
use function status_header;
use function TheFrosty\WpUtilities\getIpAddress;
use function time;
use function update_option;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

/**
 * Trait Limiter
 * @package FrostyMedia\WpTally\Route
 */
trait Limiter
{

    /**
     * Create a Rate Limiter.
     * If more than 100 requests are made within 60 seconds, a limit will be applied.
     * @param int $limit Number of requests
     * @param int $period Time in seconds
     * @param string|null $ip
     * @return WP_Error|int
     */
    public function rateLimiter(int $limit = 100, int $period = 60, ?string $ip = null): WP_Error|int
    {
        $limit = (int)apply_filters('frosty_media_wp_tally_rate_limit_limit', $limit, static::class);
        $period = (int)apply_filters('frosty_media_wp_tally_rate_limit_period', $period, static::class);
        $data = get_option('_tally_rate_limit', []);
        $error = new WP_Error();

        // Get the IP address of the client, handling proxy headers if present.
        $ip ??= getIpAddress();

        // Ensure the IP address is a valid IPv4 or IPv6 address.
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            $error->add('invalid_ip', sprintf('Error: Invalid IP address: %s', $ip));
            return $error;
        }

        $sendHeader = static function (int $count, array $headers = []) use ($limit): void {
            $defaults = [
                'X-Rate-Limit-Limit' => $limit,
                'X-Rate-Limit-Rules' => 'Ip',
                'X-Rate-Limit-Remaining' => $count > $limit ? 0 : absint($limit - $count),
            ];
            foreach (wp_parse_args($headers, $defaults) as $header => $value) {
                if (!headers_sent()) {
                    header("$header: $value");
                }
            }
        };

        // Get the current time and reset the count if the period has elapsed.
        $current_time = time();
        if (isset($data[$ip]) && $current_time - $data[$ip]['last_access_time'] >= $period) {
            $data[$ip]['count'] = 0;
        }

        $sendHeader($data[$ip]['count'] ?? 0);

        // Check if the limit has been exceeded.
        if (isset($data[$ip]) && $data[$ip]['count'] >= $limit) {
            // Return an error message or redirect to an error page.
            status_header(WP_Http::TOO_MANY_REQUESTS);
            $error->add('too_many_requests', 'Error: Rate limit exceeded');
        }

        // Increment the count and save the data to the file.
        if (!isset($data[$ip])) {
            $data[$ip] = ['count' => 0, 'last_access_time' => 0];
        }
        $data[$ip]['count']++;
        $data[$ip]['last_access_time'] = $current_time;
        update_option('_tally_rate_limit', $data);

        if ($error->has_errors()) {
            $sendHeader($data[$ip]['count'], ['Retry-After' => $period]);
            return $error;
        }

        // Return the remaining time until the limit resets (in seconds).
        return $period - ($current_time - $data[$ip]['last_access_time']);
    }
}
