<?php

namespace AstraTech\DataForge\Base;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseModel;

class Table extends ClassStatic
{
    public static function update(array $input, string $tableName, $filterKeys = null, array $appendFields = [])
    {
        // Reuse the process logic for updating
        return self::save($input, $tableName, $filterKeys, $appendFields, true);
    }

    public static function save(array $input, string $tableName, $filterKeys = null, array $appendFields = [], $updateOnly = false)
    {
        if (!$input) {
            self::setError('Empty input to save table - '.$tableName);
            return false;
        }

        $tableName = trim(ltrim(trim($tableName),"jos_"));
        if(!$tableName) {
            self::setError('Empty tableName to save!');
            return false;
        }

        // Clean input fields.
        $columns    = Schema::getColumnListing($tableName);
        $input      = array_intersect_key($input, array_flip($columns));

        $record = self::fetchExistingRecord($tableName, $filterKeys, $input);
        if (!$record && $updateOnly) {
            self::setError('Record not found!');
            return false;
        }

        // Handle field append logic (merge fields)
        if ($record) {
            foreach ($appendFields as $appendField) {
                if (isset($input[$appendField]) && in_array($appendField, $columns)) {
                    $input[$appendField] = $record->$appendField . ' ' . $input[$appendField];
                }
            }
        }

        // Handle auto timestamps
        if (in_array('created', $columns) && empty($input['created']) && !$record)
            $input['created'] = \Factory()->Date();

        if (in_array('updated', $columns))
            $input['updated'] = \Factory()->Date();

        // Perform update or insert
        $dataBeforeSave = [];
        if (!$record)
            $record = new BaseModel($tableName);
        else
            $dataBeforeSave = $record->toArray();

        // Assign input values.
        foreach ($input as $key => $value)
            $record->{$key} = $value;

        $record->setPrimaryKey(self::getPrimaryKey($tableName));
        if (!$record->save())
            return false;

        $dataAfterSave = $record->toArray();
        self::logAudit($tableName, $dataBeforeSave, $dataAfterSave);

        return $dataAfterSave;
    }

    // Fetch existing record based on filter keys
    private static function fetchExistingRecord($tableName, $filterKeys, $input)
    {
        $filterConditions = self::parseFilterKeys($filterKeys, $input);
        if (!$filterConditions)
            return false;

        // Build query for filtering
        foreach ($filterConditions as $condition)
        {
            $baseModel = new BaseModel($tableName);
            if ($record = $baseModel->Where($condition)->first()) {}
                return $record;
        }

        return false;
    }

    // Helper function to parse filter keys (like id|email&name)
    private static function parseFilterKeys($filterKeys, $input)
    {
        $conditions = [];
        if (!$filterKeys)
            return $conditions;

        $keyGroups = explode('|', $filterKeys);
        foreach ($keyGroups as $group) {
            $andConditions = [];
            $keys = explode('&', $group);
            foreach ($keys as $key) {
                if (!isset($input[$key])) {
                    $andConditions = [];
                    break;
                }

                $andConditions[$key] = $input[$key];
            }

            if (!empty($andConditions)) {
                $conditions[] = $andConditions;
            }
        }

        return $conditions;
    }

    private static function getPrimaryKey($tableName)
    {
        $primaryKey = DB::select("SHOW INDEX FROM `jos_".$tableName."` WHERE Key_name = 'PRIMARY'");
        if (!$primaryKey)
            return '';

        return $primaryKey[0]->Column_name;
    }

    // Batch save method to handle multiple records
    public static function saveBatch(array $inputs, string $tableName, $filterKeys = null, array $appendFields = [])
    {
        DB::transaction(function () use ($inputs, $tableName, $filterKeys, $appendFields) {
            foreach ($inputs as $input) {
                self::save($input, $tableName, $filterKeys, $appendFields);
            }
        });
    }

    // Batch update method to handle multiple records
    public static function updateBatch(array $inputs, string $tableName, $filterKeys = null, array $appendFields = [])
    {
        DB::transaction(function () use ($inputs, $tableName, $filterKeys, $appendFields) {
            foreach ($inputs as $input) {
                self::save($input, $tableName, $filterKeys, $appendFields, true);
            }
        });
    }

    private static function logAudit($tableName, $dataBeforeSave, $dataAfterSave)
    {
        $diff = array_diff_assoc($dataAfterSave, $dataBeforeSave);

        if (isset($diff['created']))
            unset($diff['created']);

        if (isset($diff['updated']))
            unset($diff['updated']);

        if (!$diff && !$dataBeforeSave)
            return true;

        $changes = array();
        foreach ($diff as $key => $value)
        {
            if ($dataBeforeSave) {
                if ($dataAfterSave[$key] == $dataBeforeSave[$key]) {
                    unset($diff[$key]);
                    continue;
                }

                $changes[$key] = [$dataBeforeSave[$key], $dataAfterSave[$key]];
            } else
                $changes[$key] = ['', $dataAfterSave[$key]];
        }

        $changes = json_encode($changes);
        if (!$diff)
            return true;

        $historyTable = self::getHistoryTable($tableName);
        if (!$historyTable || empty($historyTable->primary_key) || $historyTable->enabled == 0)
            return true;

        $historyRecord = new BaseModel('history_log');
        $historyRecord->table_id = $historyTable->id;
		$historyRecord->record_id = $dataAfterSave[$historyTable->primary_key];
		$historyRecord->changed_by = user()->id;
        $historyRecord->changed = \Factory()->Date();
		$historyRecord->is_first = empty($dataBeforeSave) ? 1 : 0;

        if ($historyTable->new_table == 1) {
            $historyChangesRecord = new BaseModel('history_log_changes');
            $historyChangesRecord->changes = $changes;
            $historyChangesRecord->save();

            $historyRecord->changes_id = $historyChangesRecord->id;
        } else
    		$historyRecord->changes = $changes;

        return $historyRecord->save();
    }

    private static function getHistoryTable($tableName)
	{
        $historyTable = new BaseModel('history_log_tables');
        if ($record = $historyTable->Where('name', 'jos_'.$tableName)->first())
            return $record;

        $historyTable->name = 'jos_'.$tableName;
        $historyTable->enabled = 1;
        $historyTable->primary_key = self::getPrimaryKey($tableName);
        $historyTable->save();

		return $historyTable;
	}
}

