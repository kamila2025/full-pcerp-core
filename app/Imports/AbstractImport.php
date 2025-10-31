<?php

namespace App\Imports;

abstract class AbstractImport
{
    protected array $resolvedFields = [];

    /**
     * Define the field mappings in child classes
     *
     * @return array
     */
    abstract protected function getFieldDefinitions(): array;

    protected function resolveMappedFields(array $rowKeys): void
    {
        foreach ($this->getFieldDefinitions() as $fieldKey => $aliases) {
            foreach ($aliases as $alias) {
                $index = array_search($alias, $rowKeys);
                if ($index !== false) {
                    $this->resolvedFields[$fieldKey] = $rowKeys[$index];
                    break;
                }
            }
        }
    }

    protected function getFieldValue($row, string $key, $default = null)
    {
        $field = $this->resolvedFields[$key] ?? null;

        return ($field && isset($row[$field]) && $row[$field] !== '') ? $row[$field] : $default;
    }
}
