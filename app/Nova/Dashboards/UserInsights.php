<?php

namespace App\Nova\Dashboards;

use Acme\Analytics\Analytics;
use Laravel\Nova\Dashboard;

class UserInsights extends Dashboard
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

    /**
     * Get the URI key for the dashboard.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'user-insights';
    }

    public function name()
    {
        return __('User Insights');
    }

}
