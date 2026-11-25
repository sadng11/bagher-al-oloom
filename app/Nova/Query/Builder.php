<?php

namespace App\Nova\Query;

use Laravel\Nova\Query\Builder as NovaQueryBuilder;

class Builder extends NovaQueryBuilder
{
    /**
     * Build a "whereKey" query for the given resource.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $key
     * @return $this
     */
    public function whereKey($query, $key)
    {
        parent::whereKey($query, $key);

        $resourceClass = $this->resourceClass;

        if (method_exists($resourceClass, 'scopeResourceQuery')) {
            $this->tap(function ($query) use ($resourceClass) {
                $resourceClass::scopeResourceQuery($query);
            });
        }

        return $this;
    }
}
