<?php


namespace Src\RestSaver;


use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    public function enableActivityLog()
    {
        array_push($this->allowedActions, 'activity_log');
    }

    public function enableActivityLogDefault(User $user)
    {
        if ($user->hasPermission('activity_log')) {
            $this->enableActivityLog();
        }
    }

    public function activityLog($id)
    {
        if (!in_array('activity_log', $this->allowedActions)) {
            throw new NotAllowedException('Not allowed');
        }

        $entity = $this->find($id);
        if (!$entity) {
            return null;
        }

        $result = ActivityLog::orderBy('id', 'desc')->with('user')->where([
            'model' => $this->getTable(),
            'entity_id' => $id,
        ])->get()->toArray();

        foreach ($result as &$item) {
            if (isset($item['user'])) {
                $item['user'] = array_only($item['user'], ['name', 'email']);
            } else {
                $item['user'] = ['name' => '?', 'email' => '?'];
            }
        }

        return $result;
    }

    private function logCreated($entity, array $data, $subEntity = null): void
    {
        /** @var Model $subEntity */
        ActivityLog::forceCreate([
            'model' => $this->getTable(),
            'submodel' => $subEntity ? $subEntity->getTable() : null,
            'entity_id' => $entity->id,
            'user_id' => $this->currentUserId,
            'operation' => 'created',
            'custom_text' => '',
            'old_json' => null,
            'new_json' => $data,
        ]);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    private function logUpdated(Model $entity, array $data, array $old, $subEntity = null)
    {
        $old = array_only($old, array_keys($data));

        // remove keys that didn't actually change
        $allKeys = array_unique(array_merge(array_keys($data), array_keys($old)));
        foreach ($allKeys as $key) {
            if (json_encode(array_get($old, $key)) === json_encode(array_get($data, $key))) {
                if (isset($old[$key]) || $old[$key] === null) {
                    unset($old[$key]);
                }
                if (isset($data[$key]) || $data[$key] === null) {
                    unset($data[$key]);
                }
            }
        }

        if (json_encode($old) === json_encode($data)) {
            return; // nothing changed..
        }

        if (is_string($subEntity)) {
            $subentityName = $subEntity;
        } else {
            $subentityName = $subEntity ? $subEntity->getTable() : null;
        }

        /** @var Model $subEntity */
        ActivityLog::forceCreate([
            'model' => $this->getTable(),
            'submodel' => $subentityName,
            'entity_id' => $entity->id,
            'user_id' => $this->currentUserId,
            'operation' => 'updated',
            'custom_text' => '',
            'old_json' => $old,
            'new_json' => $data,
        ]);
    }

    private function logDeleted(Model $entity, array $old, $subEntity = null)
    {

        /** @var Model $subEntity */
        ActivityLog::forceCreate([
            'model' => $this->getTable(),
            'submodel' => $subEntity ? $subEntity->getTable() : null,
            'entity_id' => $entity->id,
            'user_id' => $this->currentUserId,
            'operation' => 'deleted',
            'custom_text' => '',
            'old_json' => $old,
        ]);
    }

}