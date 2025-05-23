<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Stats;

use Symfony\Component\HttpFoundation\Request;
use WP_List_Table;
use function __;
use function array_slice;
use function strcmp;
use function strtolower;
use function usort;

/**
 * Class Table
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * @package FrostyMedia\WpTally\Stats
 */
class Table extends WP_List_Table
{

    /**
     * Table Constructor.
     */
    public function __construct()
    {
        parent::__construct([
            'singular' => 'Tally',
            'plural' => 'Tally',
            'ajax' => false,
        ]);
    }

    /**
     * Prepare the items for the table to process
     */
    public function prepare_items(): void
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $data = $this->getFormattedData();
        usort($data, [$this, 'sortData']);

        $perPage = 25;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $perPage,
        ]);

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;
    }

    /**
     * Get our columns.
     * @return array
     */
    public function get_columns(): array
    {
        return [
            'username' => __('Username', 'wp-tally'),
            'total_count' => __('Total Count', 'wp-tally'),
            'api' => __('API Views', 'wp-tally'),
            'shortcode' => __('Shortcode Views', 'wp-tally'),
        ];
    }

    /**
     * Define the sortable columns
     * @return array
     */
    public function get_sortable_columns(): array
    {
        return ['username' => ['username', false]];
    }

    /**
     * Define what data to show on each column of the table
     * @param array $item Data
     * @param string $column_name - Current column name
     * @return mixed
     */
    public function column_default($item, $column_name): mixed
    {
        return match ($column_name) {
            'username', 'total_count', 'api', 'shortcode' => $item[$column_name],
            default => '',
        };
    }

    /**
     * Format our data for table view.
     * @return array
     */
    private function getFormattedData(): array
    {
        $data = [];
        $users = Lookup::getOption()[Lookup::USERS];
        foreach ($users as $user => $stats) {
            $data[$user] = [
                'username' => $user,
                'total_count' => $stats[Lookup::TOTAL_COUNT] ?? 0,
                'api' => 0,
                'shortcode' => 0,
            ];
            foreach ($stats[Lookup::USERS_VIEW] as $view => $views) {
                foreach ($views as $count) {
                    $data[$user][$view] += $count;
                }
            }
        }

        return $data;
    }

    /**
     * Sort our data.
     * @param array $a
     * @param array $b
     * @return int
     */
    private function sortData(array $a, array $b): int
    {
        $request = Request::createFromGlobals();
        $orderby = $request->query->get('orderby', 'username');
        $order = strtolower($request->query->get('order', 'asc'));

        $result = strcmp($a[$orderby], $b[$orderby]);

        return $order === 'asc' ? $result : -$result;
    }
}
