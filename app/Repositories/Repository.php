<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Repository
{
    /**
     * The Model name.
     *
     * @var \Illuminate\Database\Eloquent\Model;
     */
    protected $model;

    protected $model_name = "";

    public function __construct()
    {
        $this->model = new $this->model_name;
    }

    /**
     * Paginate the given query.
     *
     * @param The number of models to return for pagination $n integer
     *
     * @return mixed
     */
    public function getPaginate($n)
    {
        return $this->model->paginate($n);
    }

    /**
     * Create a new model and return the instance.
     *
     * @param array $inputs
     *
     * @return Model instance
     */
    public function store(array $inputs)
    {
        return $this->model->create($inputs);
    }

    /**
     * FindOrFail Model and return the instance.
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById($id)
    {
        // return Cache::rememberForever("{$this->model_name}_{$id}", function () use ($id) {
            return $this->model->findOrFail($id);
        // });
    }

    /**
     * Update the model in the database.
     *
     * @param $id
     * @param array $inputs
     *
     */
    public function update($id, array $inputs)
    {
        $this->getById($id)->update($inputs);
        return $this->getById($id);
    }

    /**
     * Delete the model from the database.
     *
     * @param int $id
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $this->getById($id)->delete();
    }

    /**
     * Delete multiple models by their IDs.
     *
     * @param array $ids
     * @return int Number of models deleted
     */
    public function destroyMany(array $ids)
    {
        // Use Eloquent's query builder for efficient deletion
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Get all models with optional conditions.
     *
     * @param array $conditions
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getAll(array $conditions = [], $columns = ['*'])
    {
        // Create a unique cache key based on conditions and columns
        // $cacheKey = $this->generateCacheKey($conditions, $columns);

        // return Cache::rememberForever($cacheKey, function () use ($conditions, $columns) {
            $query = $this->model->query();

            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }

            return $query->get($columns);
        // });
    }

    /**
     * Generate a cache key based on conditions and columns.
     *
     * @param array $conditions
     * @param array $columns
     *
     * @return string
     */
    protected function generateCacheKey(array $conditions, array $columns)
    {
        // Serialize the conditions and columns to create a unique key
        return $this->model_name . '_' . md5(json_encode($conditions) . json_encode($columns));
    }

    /**
     * Upsert a model in the database.
     *
     * @param array $conditions
     * @param array $data
     * @return Model instance
     */
    public function upsert(array $conditions, array $data)
    {
        // Check if the record exists
        $record = $this->model->where($conditions)->first();

        if ($record) {
            // Update the existing record
            $record->update($data);
        } else {
            // Create a new record
            return $this->model->create(array_merge($conditions, $data));
        }

        return $record;
    }

    /**
     * Get the first model matching the conditions.
     *
     * @param array $conditions
     * @param array $columns
     * @return Model|null
     */
    public function first(array $conditions = [], array $columns = ['*']): ?Model
    {
        return $this->model->where($conditions)->first($columns);
    }

    /**
     * Find a record by conditions or create it if it doesn't exist.
     *
     * @param array $conditions The conditions to check for an existing record.
     * @param array $data Additional data to insert if creating a new record.
     * @return Model
     */
    public function firstOrCreate(array $conditions, array $data = []): Model
    {
        return $this->model->firstOrCreate($conditions, $data);
    }
}