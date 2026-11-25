<?php

namespace App\Nova;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource as NovaResource;

abstract class Resource extends NovaResource
{
    /**
     * Apply resource-level query scoping for single and collection lookups.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeResourceQuery($query)
    {
        return $query;
    }

    /**
     * Determine whether the current resource model is within this resource's scope.
     */
    protected function isWithinResourceScope(): bool
    {
        return true;
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return static::scopeResourceQuery($query);
    }

    /**
     * Build a Scout search query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Scout\Builder  $query
     * @return \Laravel\Scout\Builder
     */
    public static function scoutQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "detail" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function detailQuery(NovaRequest $request, $query)
    {
        return static::scopeResourceQuery(parent::detailQuery($request, $query));
    }

    /**
     * Build an "edit" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function editQuery(NovaRequest $request, $query)
    {
        return static::scopeResourceQuery(parent::editQuery($request, $query));
    }

    /**
     * Build a "replicate" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function replicateQuery(NovaRequest $request, $query)
    {
        return static::scopeResourceQuery(parent::replicateQuery($request, $query));
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableQuery(NovaRequest $request, $query)
    {
        return static::scopeResourceQuery(parent::relatableQuery($request, $query));
    }

    /**
     * Determine if the current user can replicate the given resource or throw an exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeToReplicate(Request $request)
    {
        if (! $this->isWithinResourceScope()) {
            throw new AuthorizationException();
        }

        parent::authorizeToReplicate($request);
    }

    /**
     * Determine if the current user can replicate the given resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function authorizedToReplicate(Request $request)
    {
        if (! $this->isWithinResourceScope()) {
            return false;
        }

        return parent::authorizedToReplicate($request);
    }

    /**
     * Determine if the current user has a given ability.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $ability
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function authorizeTo(Request $request, $ability)
    {
        if (! $this->isWithinResourceScope()) {
            throw new AuthorizationException();
        }

        parent::authorizeTo($request, $ability);
    }

    /**
     * Determine if the current user can perform the given ability on the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $ability
     * @return bool
     */
    public function authorizedTo(Request $request, $ability)
    {
        if (! $this->isWithinResourceScope()) {
            return false;
        }

        return parent::authorizedTo($request, $ability);
    }
}
