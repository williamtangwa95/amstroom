<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            self::logAction('CREATED', $model);
        });

        static::updated(function (Model $model) {
            self::logAction('UPDATED', $model);
        });

        static::deleted(function (Model $model) {
            self::logAction('DELETED', $model);
        });
    }

    protected static function logAction($action, Model $model)
    {
        if (!auth()->check()) {
            return;
        }

        $modelName = class_basename($model);
        $subjectName = self::getModelSubjectName($model);

        $description = "{$action} {$modelName}: {$subjectName}";

        if ($action === 'CREATED') {
            $description = "Created new {$modelName}: {$subjectName}";
        } elseif ($action === 'DELETED') {
            $description = "Deleted {$modelName}: {$subjectName}";
        } elseif ($action === 'UPDATED') {
            $description = "Updated {$modelName}: {$subjectName}";
        }

        $changes = null;
        if ($action === 'UPDATED') {
            $changes = [];
            foreach ($model->getDirty() as $key => $newValue) {
                // Ignore timestamp/password hash fields
                if (in_array($key, ['updated_at', 'created_at', 'deleted_at', 'password'])) {
                    continue;
                }
                $oldValue = $model->getOriginal($key);
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }

            // If no actual changes other than timestamps, don't log
            if (empty($changes)) {
                return;
            }
        }

        ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'description'  => $description,
            'subject_type' => get_class($model),
            'subject_id'   => $model->id,
            'changes'      => $changes,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }

    protected static function getModelSubjectName(Model $model)
    {
        // Try common attributes to represent the model name
        if (isset($model->item_name)) {
            return $model->item_name;
        }
        if (isset($model->category_name)) {
            return $model->category_name;
        }
        if (isset($model->shop_name)) {
            return $model->shop_name;
        }
        if (isset($model->name)) {
            return $model->name;
        }
        if (isset($model->title)) {
            return $model->title;
        }
        if (isset($model->key)) {
            return $model->key;
        }
        if (isset($model->reference_no)) {
            return $model->reference_no;
        }
        if (isset($model->invoice_no)) {
            return $model->invoice_no;
        }
        return '#' . $model->id;
    }
}
