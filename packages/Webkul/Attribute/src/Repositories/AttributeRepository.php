<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class AttributeRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeOptionRepository $attributeOptionRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return 'Webkul\Attribute\Contracts\Attribute';
    }

    /**
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function create(array $data)
    {
        $options = isset($data['options']) ? $data['options'] : [];

        $attribute = $this->model->create($data);

        if (in_array($attribute->type, ['select', 'multiselect', 'checkbox']) && count($options)) {
            $sortOrder = 1;

            foreach ($options as $optionInputs) {
                $this->attributeOptionRepository->create(array_merge([
                    'attribute_id' => $attribute->id,
                    'sort_order'   => $sortOrder++,
                ], $optionInputs));
            }
        }

        return $attribute;
    }

    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $attribute = $this->find($id);

        $attribute->update($data);

        $previousOptionIds = $attribute->options()->pluck('id');

        if (in_array($attribute->type, ['select', 'multiselect', 'checkbox']) && isset($data['options'])) {
            $sortOrder = 1;

            foreach ($data['options'] as $optionId => $optionInputs) {
                if (Str::contains($optionId, 'option_')) {
                    $this->attributeOptionRepository->create(array_merge([
                        'attribute_id' => $attribute->id,
                        'sort_order'   => $sortOrder++,
                    ], $optionInputs));
                } else {
                    if (is_numeric($index = $previousOptionIds->search($optionId))) {
                        $previousOptionIds->forget($index);
                    }

                    $this->attributeOptionRepository->update(array_merge([
                        'sort_order' => $sortOrder++,
                    ], $optionInputs), $optionId);
                }
            }
        }

        foreach ($previousOptionIds as $optionId) {
            $this->attributeOptionRepository->delete($optionId);
        }

        return $attribute;
    }

    /**
     * @param  string  $code
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function getAttributeByCode($code)
    {
        static $attributes = [];

        if (array_key_exists($code, $attributes)) {
            return $attributes[$code];
        }

        return $attributes[$code] = $this->findOneByField('code', $code);
    }

    /**
     * @param  int  $lookup
     * @param  string  $query
     * @param  array  $columns
     * @return array
     */
    public function getLookUpOptions($lookup, $query = '', $columns = [])
    {
        $lookup = config('attribute_lookups.'.$lookup);

        if (! count($columns)) {
            $columns = [($lookup['value_column'] ?? 'id').' as id', ($lookup['label_column'] ?? 'name').' as name'];
        }

        if (Str::contains($lookup['repository'], 'UserRepository')) {
            $userRepository = app($lookup['repository'])->where('status', 1);

            $currentUser = auth()->guard('user')->user();

            if ($currentUser->view_permission === 'group') {
                return $userRepository->leftJoin('user_groups', 'users.id', '=', 'user_groups.user_id')
                    ->where('users.name', 'like', '%'.urldecode($query).'%')
                    ->get();
            } elseif ($currentUser->view_permission === 'individual') {
                return $userRepository->where('users.id', $currentUser->id);
            }

            return $userRepository->where('users.name', 'like', '%'.urldecode($query).'%')->get();
        }

        return app($lookup['repository'])->findWhere([
            [$lookup['label_column'] ?? 'name', 'like', '%'.urldecode($query).'%'],
        ], $columns);
    }

    /**
     * @param  string  $lookup
     * @param  int|array  $entityId
     * @param  array  $columns
     * @return mixed
     */
    public function getLookUpEntity($lookup, $entityId = null, $columns = [])
    {
        if (! $entityId) {
            return;
        }

        $lookup = config('attribute_lookups.'.$lookup);

        if (! count($columns)) {
            $columns = [($lookup['value_column'] ?? 'id').' as id', ($lookup['label_column'] ?? 'name').' as name'];
        }

        if (is_array($entityId)) {
            return app($lookup['repository'])->findWhereIn(
                'id',
                $entityId,
                $columns
            );
        } else {
            return app($lookup['repository'])->find($entityId, $columns);
        }
    }
}
