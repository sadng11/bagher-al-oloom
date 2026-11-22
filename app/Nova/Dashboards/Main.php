<?php

namespace App\Nova\Dashboards;

use Acme\Analytics\Analytics;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            new Analytics
        ];
    }

    public function uriKey()
    {
        return 'main';
    }

    public function name()
    {
        return __('Main');
    }
}
