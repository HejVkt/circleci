<?php namespace Src;

use App\Exceptions\VisibleException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Pagination\LengthAwarePaginator;

class RestSaver
{
    use RestSaver\LogsActivity;

    public $setCreatorUserId = true;

    protected $currentUserId = null;

    public $scope = null;

    /** @var string[] */
    protected $allowedFields = [];

    protected $hasManyFields = [];
    protected $hasOneFields = [];
    protected $linkedFields = [];

    public $withRelations = null;
    protected $hasCreatorUserId = true;

    protected $createAs = null;

    /** @var string[] */
    protected $allowedActions = ['list', 'find', 'create', 'update', 'delete'];

    protected $hideFields = [];

    protected $afterCreateFunction = null;
    protected $beforeCreateFunction = null;
    protected $afterUpdateFunction = null;
    protected $queryFunc;
    protected $transformForListFunc;
    protected $transformForFindFunc;
    protected $currentAction;
    protected $validatorFunc = null;
    protected $metadata = [];

    /**
     * RestSaver constructor.
     * @throws \RuntimeException
     */
    public function __construct()
    {
        $this->scope = function () {
            throw new \RuntimeException('You need to define $rest->scope = function() { }');
        };
    }

    public function fields($fields)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $this->allowedFields = array_filter($fields); // remove nulls
    }

    public function enableFields($fields)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $this->allowedFields = array_unique(array_filter(array_merge($this->allowedFields, $fields))); // remove nulls
    }

    public function hasMany($field, $fields)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $this->hasManyFields[$field] = array_filter($fields);
    }

    public function hasOne($field, $fields)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $this->hasOneFields[$field] = array_filter($fields);
    }


    public function list($request = [])
    {
        if (!in_array('list', $this->allowedActions)) {
            throw new \RuntimeException('Not allowed');
        }

        $this->currentAction = 'list';

        $scope = $this->callScope();
        $request['per_page'] = array_get($request, 'per_page', 10);
        if ($request['per_page'] > 100 && !array_has($request, 'selector')) {
            $request['per_page'] = 100;
        }

        if (isset($request['query']) && $this->queryFunc !== null) {
            $scope = call_user_func($this->queryFunc, $request['query'], $scope);
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $scope->paginate($request['per_page'], ['*'], 'page', array_get($request, 'page'));
//        $metadata = array_except($paginator->toArray(), 'data'); //works so slowly
        $metadata = [
            'current_page' => $paginator->currentPage(),
            'first_page_url' => $paginator->url(1),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
            'per_page' => $paginator->perPage(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];

        $metadata['queryable'] = $this->isQueryable();

        $items = $paginator->getCollection()->map(function ($item) {
            return $this->transformForListFunc ? call_user_func($this->transformForListFunc, $item) : $item;
        })->toArray();

        $items = array_map(function ($i) {
            return $this->hideFieldsInItem($i);
        }, $items);

        return [$items, $metadata];
    }

    public function isListing()
    {
        return $this->currentAction === 'list';
    }

    public function create($all)
    {
        if (!in_array('create', $this->allowedActions)) {
            throw new \RuntimeException('Not allowed');
        }

        return \DB::transaction(function () use ($all) {
            if ($this->beforeCreateFunction) {
                $all = call_user_func($this->beforeCreateFunction, $all);
            }

            $data = array_only($all, $this->allowedFields);
            if ($this->hasCreatorUserId) {
                if (!$this->currentUserId) {
                    throw new \RuntimeException('Please define currentUserId');
                }

                $data['creator_user_id'] = $this->currentUserId;
            }

            if ($this->validatorFunc) {
                call_user_func($this->validatorFunc, 'create', null);
            }

            if ($this->createAs) {
                $entity = call_user_func($this->createAs, $data);
            } else {
                $entity = $this->newScope()->forceCreate($data);
            }

            $this->logCreated($entity, $data);

            $this->updateHasMany($entity, $all);
            $this->updateHasOne($entity, $all);
            $this->updateLinked($entity, $all);

            $entity->fresh()->save(); // after any updates to hasmany/one/linked

            if ($this->afterCreateFunction) {
                call_user_func($this->afterCreateFunction, $entity);
                $entity = $entity->fresh();
            }

            return $entity;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newScope()
    {
        return call_user_func($this->scope);
    }

    /**
     * @param $this
     * @return string
     */
    public function getTable()
    {
        return $this->newScope()->getModel()->getTable();
    }

    public function with(array $array)
    {
        $this->withRelations = $array;
    }

    public function addWith(array $array)
    {
        if (!is_array($this->withRelations)) {
            $this->withRelations = [];
        }
        $this->withRelations = array_merge($this->withRelations, $array);
    }

    public function hasLinked($param, $parentIdField, $scopeCallable)
    {
        $this->linkedFields[$param] = [$parentIdField, $scopeCallable];
    }

    public function disableCurrentUserId()
    {
        $this->hasCreatorUserId = false;
    }

    public function createAs($callable)
    {
        $this->createAs = $callable;
    }

    public function allowOnly($action)
    {
        $this->allowedActions = $action;
    }

    public function afterCreate($callable)
    {
        $this->afterCreateFunction = $callable;
    }

    public function beforeCreate($callable)
    {
        $this->beforeCreateFunction = $callable;
    }

    public function afterUpdate($callable)
    {
        $this->afterUpdateFunction = $callable;
    }

    /**
     * @return \string[]
     */
    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    public function isQueryable()
    {
        return $this->queryFunc !== null;
    }

    /**
     * @param mixed $queryFunc
     * @return RestSaver
     */
    public function setQueryFunc($queryFunc)
    {
        $this->queryFunc = $queryFunc;

        return $this;
    }

    public function setQueryableOn($column)
    {
        if (!preg_match('#^[a-z0-9_]+$#', $column)) {
            throw new \RuntimeException('Security violation SQLi protection');
        }

        $this->setQueryFunc(function ($query, Builder $scope) use ($column) {
            $split = preg_split('/\W*([\s]+\W*|$)/u', $query, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($split as $word) {
                $word = mb_strtolower($word, 'UTF-8');
                $word = str_replace('ё', 'е', $word);
                $word = str_replace('Ё', 'е', $word);

                $scope = $scope->whereRaw("LOWER(REPLACE(REPLACE(\"$column\", 'ё', 'е'), 'Ё', 'е')) LIKE ?",
                    ["%$word%"]);
            }

            return $scope;
        });
    }

    public function quoteIdent($field)
    {
        return '`' . str_replace('`', '``', $field) . '`';
    }

    /**
     * @param mixed $transformForListFunc
     * @return RestSaver
     */
    public function setTransformForListFunc($transformForListFunc)
    {
        $this->transformForListFunc = $transformForListFunc;

        return $this;
    }

    public function setTransformForFindFunc($transformForFindFunc)
    {
        $this->transformForFindFunc = $transformForFindFunc;

        return $this;
    }

    public function addField($string)
    {
        $this->allowedFields [] = $string;
    }

    public function hideField($string)
    {
        $this->hideFields [] = $string;
    }

    /**
     * @param callable $callable ->validate(function($action, $entity) { ... }), where action is create or update,
     *                           throw VisibleException if needed
     */
    public function validate($callable)
    {
        $this->validatorFunc = $callable;
    }

    public function getMetaData()
    {
        return $this->metadata;
    }

    protected function updateHasMany($entity, array $all)
    {
        foreach ($this->hasManyFields as $hasManyField => $nestedFields) {
            if (isset($all[$hasManyField])) {
                foreach ($all[$hasManyField] as $unfilteredAll) {
                    $this->innerUpdateHasOneOrMany($entity, $hasManyField, $unfilteredAll, $nestedFields);
                }
            }
        }
    }

    protected function innerUpdateHasOneOrMany($entity, $hasManyField, $unfilteredAll, $nestedFields): void
    {
        $nestedData = array_only($unfilteredAll, $nestedFields);
        /** @var HasMany|HasOne $rel */
        $rel = $entity->{$hasManyField}();
        if (is_int(array_get($unfilteredAll, 'id'))) {
            /** @var Model $subEntity */
            if ($rel instanceof BelongsTo) {
                $subEntity = $rel->getRelated()->where('id', '=', $unfilteredAll['id'])->first();
            } else {
                $subEntity = $rel->where('id', '=', $unfilteredAll['id'])->first();
            }
            $shouldDelete = isset($unfilteredAll['__delete__']) && $unfilteredAll['__delete__'] === true;
            if ($subEntity) {
                $old = $subEntity->getAttributes();
                if ($shouldDelete) {
                    $subEntity->delete();
                    $this->logDeleted($entity, $old, $subEntity);
                } else {
                    $subEntity->forceFill($nestedData);
                    $subEntity->save();
                    if ($rel instanceof BelongsTo) {
                        $entity->update([$rel->getForeignKey() => $subEntity->id]);
                    }
                    $subEntity->save(); // make sure "static::saved" gets called with relationship to parent entity
                    $this->logUpdated($entity, $nestedData, $old, $subEntity);
                }
            }
        } else {
            $subEntity = $rel->getRelated()->newInstance();
            $subEntity->forceFill($nestedData);

            if ($rel instanceof BelongsTo) {
                $subEntity->save();
                $entity->update([$rel->getForeignKey() => $subEntity->id]);
            } else {
                $rel->save($subEntity);
            }
            $subEntity->save(); // make sure "static::saved" gets called with relationship to parent entity
            $this->logCreated($entity, $nestedData, $subEntity);
        }
    }

    /**
     * @return mixed
     */
    public function callScope(): Builder
    {
        return $this->newScope();
    }

    private function updateHasOne($entity, $all)
    {
        foreach ($this->hasOneFields as $hasOneField => $nestedFields) {
            if (isset($all[$hasOneField])) {
                $unfilteredAll = $all[$hasOneField];
                $this->innerUpdateHasOneOrMany($entity, $hasOneField, $unfilteredAll, $nestedFields);
            }
        }
    }


    /**
     * @param $id
     * @return Model
     */
    public function find($id)
    {
        if (!in_array('find', $this->allowedActions)) {
            throw new \RuntimeException('Not allowed');
        }
        $scope = $this->callScope();

        if (isset($this->withRelations)) {
            $scope = $scope->with($this->withRelations);
        }

        $item = $scope->where('id', '=', $id)->first();

        if ($item !== null) {
            $item = $this->hideFieldsInItem($item);
        }

        return $item;
    }

    public function update($id, $all)
    {
        if (!in_array('update', $this->allowedActions)) {
            throw new \RuntimeException('Not allowed');
        }

        return \DB::transaction(function () use ($id, $all) {
            $data = array_only($all, $this->allowedFields);

            $entity = $this->find($id);

            if ($entity !== null) {
                $old = $entity->getAttributes();

                $entity->forceFill($data);

                if ($this->validatorFunc) {
                    call_user_func($this->validatorFunc, 'update', $entity);
                }

                $entity->save();

                $this->logUpdated($entity, $data, $old);

                $this->updateHasMany($entity, $all);
                $this->updateHasOne($entity, $all);
                $this->updateLinked($entity, $all);

                $newEntity = $entity->fresh();
                $newEntity->save(); // after any updates to hasmany/one/linked

                if ($this->afterUpdateFunction) {
                    call_user_func($this->afterUpdateFunction, $newEntity);
                }
            }

            return $this->find($id);
        });
    }

    public function delete($id)
    {
        $scope = $this->callScope();
        $item = $scope->where('id', '=', $id)->first();

        if ($item !== null) {
            if (method_exists($item, 'allowedToDelete')) {
                $allowedToDelete = $item->allowedToDelete($this->currentUserId);
            } else {
                $allowedToDelete = $item->status === 'draft' && $item->creator_user_id == $this->currentUserId;
            }
            if ($allowedToDelete) {
                $item->delete();

                return ['deleted' => 'ok'];
            }
        }

        throw new VisibleException('Not allowed');
    }

    public function setCurrentUserId($id)
    {
        $this->currentUserId = $id;
    }

    private function updateLinked($entity, $all)
    {
        foreach ($this->linkedFields as $field => list($parentIdField, $scopeFun)) {
            if (isset($all[$field])) {
                $newIds = collect($all[$field])->map(function ($i) {
                    return (int)$i;
                })->toArray();

                $scope = call_user_func($scopeFun, $entity);
                $oldIds = $scope->where($parentIdField, $entity->id)->pluck('id')->toArray();

                $scope = call_user_func($scopeFun, $entity);
                $scope->where($parentIdField, $entity->id)->update([$parentIdField => null]);

                $scope = call_user_func($scopeFun, $entity);
                $scope->whereIn('id', $newIds)->update([$parentIdField => $entity->id]);

                $this->logUpdated($entity, [$field => $newIds], [$field => $oldIds]);
            }
        }

    }

    public function entityFromRequest(\Illuminate\Http\Request $request)
    {
        return $request->route('id') ? $this->find($request->route('id')) : null;
    }

    private function hideFieldsInItem($item)
    {
        foreach ($this->hideFields as $hideField) {
            if (false !== strpos($hideField, '[].')) {
                [$prefix, $suffix] = explode('[].', $hideField);
                foreach (array_get($item, $prefix) as $k => &$subItem) {
                    if ($subItem instanceof Model) {
                        if (false === strpos($suffix, '.')) {
                            $subItem->addHidden([$suffix]);
                        } else {
                            [$prefix2, $suffix2] = explode('.', $suffix);
                            object_get($subItem, $prefix2)->addHidden([$suffix2]);
                        }
                    } else {
                        array_forget($item, "{$prefix}.{$k}.{$suffix}");
                    }
                };
            } else {
                array_forget($item, $hideField);
            }
        }

        return $item;
    }

    public function setMetaData($key, $value)
    {
        $this->metadata[$key] = $value;
    }
}