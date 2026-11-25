<?php

namespace App\Nova\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesByBookSection
{
    /**
     * Restrict the resource query to records matching its book section.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeResourceQuery($query)
    {
        $section = static::resourceBookSection();

        if (static::scopesThroughBookRelation()) {
            return $query->whereHas('book', function (Builder $bookQuery) use ($section) {
                $bookQuery->where($bookQuery->qualifyColumn('section'), $section);
            });
        }

        return $query->where($query->qualifyColumn('section'), $section);
    }

    /**
     * Determine whether the underlying model belongs to this resource's section.
     */
    protected function isWithinResourceScope(): bool
    {
        $model = $this->model();

        if (! $model || ! $model->exists) {
            return true;
        }

        $section = static::resourceBookSection();

        if (static::scopesThroughBookRelation()) {
            $book = $model->relationLoaded('book')
                ? $model->getRelation('book')
                : $model->book()->withoutGlobalScopes()->first();

            return $book !== null && $book->section === $section;
        }

        return $model->section === $section;
    }

    /**
     * Determine whether the resource scopes via the parent book relation.
     */
    protected static function scopesThroughBookRelation(): bool
    {
        return false;
    }

    /**
     * Get the book section owned by this resource.
     */
    abstract protected static function resourceBookSection(): string;
}
